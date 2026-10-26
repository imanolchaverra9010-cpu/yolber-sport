import { toast } from "sonner";

// API Configuration
export const API_BASE_URL = import.meta.env.VITE_API_URL || "http://localhost/backend";

// Set VITE_USE_MOCK=true in .env to force mock catalog offline
const USE_MOCK_DATA = String(import.meta.env.VITE_USE_MOCK ?? "false") === "true";

export interface ShopifyProduct {
  node: {
    id: string;
    title: string;
    description: string;
    handle: string;
    categoryId?: number | null;
    categorySlug?: string | null;
    priceRange: { minVariantPrice: { amount: string; currencyCode: string } };
    images: { edges: Array<{ node: { url: string; altText: string | null } }> };
    variants: {
      edges: Array<{
        node: {
          id: string;
          title: string;
          price: { amount: string; currencyCode: string };
          availableForSale: boolean;
          selectedOptions: Array<{ name: string; value: string }>;
        };
      }>;
    };
    options: Array<{ name: string; values: string[] }>;
  };
}

class ApiError extends Error {
  status: number;
  constructor(status: number, message: string) {
    super(message);
    this.status = status;
  }
}

function apiCredentialsMode(): RequestCredentials {
  // Cross-origin (Vercel → Hostinger) cannot use credentials with ACAO "*".
  // Cart is local-first; cookies across sites are not required for the catalog.
  try {
    const apiOrigin = new URL(API_BASE_URL, typeof window !== "undefined" ? window.location.href : "http://localhost").origin;
    if (typeof window !== "undefined" && apiOrigin !== window.location.origin) {
      return "omit";
    }
  } catch {
    /* ignore */
  }
  return "include";
}

async function apiRequest(endpoint: string, options: RequestInit = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  const response = await fetch(url, {
    ...options,
    credentials: options.credentials ?? apiCredentialsMode(),
    headers: {
      "Content-Type": "application/json",
      ...options.headers,
    },
  });

  if (!response.ok) {
    throw new ApiError(response.status, `HTTP error! status: ${response.status}`);
  }

  const text = await response.text();
  try {
    return JSON.parse(text);
  } catch {
    throw new Error("Invalid JSON response");
  }
}

export async function fetchProducts(first = 24): Promise<ShopifyProduct[]> {
  if (USE_MOCK_DATA) {
    return getMockProducts();
  }

  const data = await apiRequest(`/api/products.php?first=${first}`);
  const edges = (data?.data?.products?.edges ?? []) as ShopifyProduct[];
  return edges.map(withResolvedImages);
}

export async function fetchProduct(handle: string): Promise<ShopifyProduct | null> {
  if (USE_MOCK_DATA) {
    const products = getMockProducts();
    return products.find((p) => p.node.handle === handle) || null;
  }

  try {
    const data = await apiRequest(`/api/products.php?handle=${encodeURIComponent(handle)}`);
    const p = data?.data?.product;
    return p ? withResolvedImages({ node: p }) : null;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) return null;
    throw error;
  }
}

export function formatPrice(amount: string, currency: string) {
  return new Intl.NumberFormat("es-CO", { style: "currency", currency, maximumFractionDigits: 0 }).format(parseFloat(amount));
}

// ---- Categories ----
export interface ShopCategory {
  id: number;
  name: string;
  slug: string;
  /** Fallback key when no image is set. */
  icon: string;
  /** Circular image for the category rail (admin upload / URL). */
  iconUrl: string;
  sortOrder: number;
  isActive: boolean;
}

export function resolveMediaUrl(url: string) {
  if (!url) return "";
  if (/^https?:\/\//i.test(url) || url.startsWith("data:")) return url;
  const base = API_BASE_URL.replace(/\/$/, "");
  const path = url.replace(/^\//, "");
  return `${base}/${path}`;
}

/** Normalize product image URLs coming from the API (relative uploads → absolute). */
export function withResolvedImages(product: ShopifyProduct): ShopifyProduct {
  return {
    ...product,
    node: {
      ...product.node,
      images: {
        edges: product.node.images.edges.map((edge) => ({
          ...edge,
          node: {
            ...edge.node,
            url: resolveMediaUrl(edge.node.url),
          },
        })),
      },
    },
  };
}

function getMockCategories(): ShopCategory[] {
  return [
    { id: 1, name: "Hoodies", slug: "hoodies", icon: "shirt", iconUrl: "", sortOrder: 1, isActive: true },
    { id: 2, name: "Sneakers", slug: "sneakers", icon: "footprints", iconUrl: "", sortOrder: 2, isActive: true },
    { id: 3, name: "Joggers", slug: "joggers", icon: "personStanding", iconUrl: "", sortOrder: 3, isActive: true },
    { id: 4, name: "Tops", slug: "tops", icon: "wind", iconUrl: "", sortOrder: 4, isActive: true },
    { id: 5, name: "Accesorios", slug: "accesorios", icon: "watch", iconUrl: "", sortOrder: 5, isActive: true },
  ];
}

export async function fetchCategories(): Promise<ShopCategory[]> {
  if (USE_MOCK_DATA) {
    return getMockCategories();
  }

  try {
    const data = await apiRequest("/api/categories.php");
    const list = data?.data?.categories ?? [];
    return list.map((c: ShopCategory & { icon?: string }) => ({
      ...c,
      icon: c.icon || c.slug || "tag",
      iconUrl: resolveMediaUrl(c.iconUrl || ""),
    }));
  } catch (error) {
    console.warn("Backend not available, using mock categories:", error);
    return getMockCategories();
  }
}

export type StoreSettings = {
  store_name?: string;
  store_slogan?: string;
  categories_banner_image?: string;
  categories_banner_title?: string;
  categories_banner_subtitle?: string;
  [key: string]: string | undefined;
};

export async function fetchStoreSettings(keys?: string[]): Promise<StoreSettings> {
  if (USE_MOCK_DATA) {
    return {
      categories_banner_title: "Categorías",
      categories_banner_subtitle: "Elige una categoría e inicia tu próximo look",
      categories_banner_image: "",
    };
  }

  try {
    const qs = keys?.length ? `?keys=${encodeURIComponent(keys.join(","))}` : "";
    const data = await apiRequest(`/api/settings.php${qs}`);
    const settings = (data?.data?.settings ?? {}) as StoreSettings;
    if (settings.categories_banner_image) {
      settings.categories_banner_image = resolveMediaUrl(settings.categories_banner_image);
    }
    return settings;
  } catch (error) {
    console.warn("Settings API unavailable:", error);
    return {};
  }
}

// ---- Cart ----
type UE = Array<{ field: string[] | null; message: string }>;

function formatCheckoutUrl(u: string) {
  try {
    const url = new URL(u);
    url.searchParams.set("channel", "online_store");
    return url.toString();
  } catch {
    return u;
  }
}

export async function createShopifyCart(variantId: string, quantity: number) {
  if (USE_MOCK_DATA) {
    // Return mock cart data
    return {
      cartId: "mock-cart-" + Date.now(),
      checkoutUrl: "#",
      lineId: "mock-line-" + Date.now(),
    };
  }

  try {
    const data = await apiRequest("/api/cart.php?action=create", {
      method: "POST",
      body: JSON.stringify({
        lines: [{ quantity, merchandiseId: variantId }],
      }),
    });

    const cart = data?.data?.cart;
    const lineId = cart?.lines?.edges?.[0]?.node?.id;
    if (!cart?.id || !lineId) return null;

    return {
      cartId: cart.id as string,
      checkoutUrl: formatCheckoutUrl(`http://localhost/backend/checkout`),
      lineId: lineId as string,
    };
  } catch (error) {
    console.warn("Backend not available, using mock cart:", error);
    return {
      cartId: "mock-cart-" + Date.now(),
      checkoutUrl: "#",
      lineId: "mock-line-" + Date.now(),
    };
  }
}

export async function addLineToShopifyCart(cartId: string, variantId: string, quantity: number) {
  if (USE_MOCK_DATA) {
    return { success: true, lineId: "mock-line-" + Date.now() };
  }

  try {
    const data = await apiRequest("/api/cart.php?action=add", {
      method: "POST",
      body: JSON.stringify({
        lines: [{ quantity, merchandiseId: variantId }],
      }),
    });

    const lines = data?.data?.cart?.lines?.edges || [];
    const l = lines.find((x: { node: { merchandise: { id: string } } }) => x.node.merchandise.id === variantId);
    return { success: true, lineId: l?.node?.id as string | undefined };
  } catch (error) {
    console.warn("Backend not available, using mock cart:", error);
    return { success: true, lineId: "mock-line-" + Date.now() };
  }
}

export async function updateShopifyCartLine(cartId: string, lineId: string, quantity: number) {
  if (USE_MOCK_DATA) {
    return { success: true };
  }

  try {
    const data = await apiRequest("/api/cart.php", {
      method: "PUT",
      body: JSON.stringify({
        lines: [{ id: lineId, quantity }],
      }),
    });

    return { success: true };
  } catch (error) {
    console.warn("Backend not available, using mock cart:", error);
    return { success: true };
  }
}

export async function removeLineFromShopifyCart(cartId: string, lineId: string) {
  if (USE_MOCK_DATA) {
    return { success: true };
  }

  try {
    const data = await apiRequest(`/api/cart.php?lineIds=${lineId}`, {
      method: "DELETE",
    });

    return { success: true };
  } catch (error) {
    console.warn("Backend not available, using mock cart:", error);
    return { success: true };
  }
}

// Mock data for development
function getMockProducts(): ShopifyProduct[] {
  return [
    {
      node: {
        id: "gid://shopify/Product/1",
        title: "Hoodie Deportivo Negro",
        description: "Hoodie premium con diseño deportivo exclusivo. Fabricado con algodón de alta calidad y detalles en dorado.",
        handle: "hoodie-deportivo-negro",
        priceRange: {
          minVariantPrice: { amount: "89000", currencyCode: "COP" },
        },
        images: {
          edges: [
            { node: { url: "https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800", altText: "Hoodie Deportivo Negro frontal" } },
            { node: { url: "https://images.unsplash.com/photo-1578768079052-aa76e52ff62e?w=800", altText: "Hoodie Deportivo Negro detalle" } },
          ],
        },
        variants: {
          edges: [
            {
              node: {
                id: "gid://shopify/ProductVariant/1",
                title: "S",
                price: { amount: "89000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "S" }],
              },
            },
            {
              node: {
                id: "gid://shopify/ProductVariant/2",
                title: "M",
                price: { amount: "89000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "M" }],
              },
            },
            {
              node: {
                id: "gid://shopify/ProductVariant/3",
                title: "L",
                price: { amount: "89000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "L" }],
              },
            },
          ],
        },
        options: [{ name: "Talla", values: ["S", "M", "L"] }],
      },
    },
    {
      node: {
        id: "gid://shopify/Product/2",
        title: "Sneakers Deportivas Gold",
        description: "Sneakers urbanas con detalles dorados. Comodidad máxima para el día a día.",
        handle: "sneakers-deportivas-gold",
        priceRange: {
          minVariantPrice: { amount: "150000", currencyCode: "COP" },
        },
        images: {
          edges: [
            { node: { url: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800", altText: "Sneakers Deportivas Gold frontal" } },
          ],
        },
        variants: {
          edges: [
            {
              node: {
                id: "gid://shopify/ProductVariant/4",
                title: "40",
                price: { amount: "150000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "40" }],
              },
            },
            {
              node: {
                id: "gid://shopify/ProductVariant/5",
                title: "41",
                price: { amount: "150000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "41" }],
              },
            },
            {
              node: {
                id: "gid://shopify/ProductVariant/6",
                title: "42",
                price: { amount: "150000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "42" }],
              },
            },
          ],
        },
        options: [{ name: "Talla", values: ["40", "41", "42"] }],
      },
    },
    {
      node: {
        id: "gid://shopify/Product/3",
        title: "Joggers Premium",
        description: "Joggers con corte moderno y tejido premium. Perfectos para cualquier ocasión.",
        handle: "joggers-premium",
        priceRange: {
          minVariantPrice: { amount: "75000", currencyCode: "COP" },
        },
        images: {
          edges: [
            { node: { url: "https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=800", altText: "Joggers Premium frontal" } },
          ],
        },
        variants: {
          edges: [
            {
              node: {
                id: "gid://shopify/ProductVariant/7",
                title: "M",
                price: { amount: "75000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "M" }],
              },
            },
            {
              node: {
                id: "gid://shopify/ProductVariant/8",
                title: "L",
                price: { amount: "75000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "L" }],
              },
            },
            {
              node: {
                id: "gid://shopify/ProductVariant/9",
                title: "XL",
                price: { amount: "75000", currencyCode: "COP" },
                availableForSale: true,
                selectedOptions: [{ name: "Talla", value: "XL" }],
              },
            },
          ],
        },
        options: [{ name: "Talla", values: ["M", "L", "XL"] }],
      },
    },
  ];
}
