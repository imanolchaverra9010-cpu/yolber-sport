import { z } from "zod";
import type { ShopifyProduct } from "@/lib/shopify";

export const SORT_OPTIONS = [
  { value: "featured", label: "Destacados" },
  { value: "price-asc", label: "Precio: menor a mayor" },
  { value: "price-desc", label: "Precio: mayor a menor" },
  { value: "name", label: "Nombre A-Z" },
] as const;

export type ProductSort = (typeof SORT_OPTIONS)[number]["value"];

export type ProductFilterState = {
  sizes: string[];
  minPrice?: number;
  maxPrice?: number;
  sort: ProductSort;
  inStockOnly: boolean;
};

export const productFilterSearchSchema = {
  talla: z.string().optional().catch(undefined),
  min: z.coerce.number().finite().nonnegative().optional().catch(undefined),
  max: z.coerce.number().finite().nonnegative().optional().catch(undefined),
  sort: z.enum(["featured", "price-asc", "price-desc", "name"]).optional().catch(undefined),
  stock: z.literal("1").optional().catch(undefined),
};

export const defaultFilterState: ProductFilterState = {
  sizes: [],
  sort: "featured",
  inStockOnly: false,
};

const SIZE_ORDER = ["XXS", "XS", "S", "M", "L", "XL", "XXL", "XXXL", "ÚNICA", "UNICA", "U"];

function isSizeOption(name: string) {
  const n = name.toLowerCase().trim();
  return n === "talla" || n === "size" || n === "talle" || n.includes("talla");
}

export function productPrice(product: ShopifyProduct): number {
  return parseFloat(product.node.priceRange.minVariantPrice.amount || "0") || 0;
}

export function productSizes(product: ShopifyProduct): string[] {
  const fromOptions = product.node.options
    .filter((o) => isSizeOption(o.name))
    .flatMap((o) => o.values);

  if (fromOptions.length) {
    return [...new Set(fromOptions.map((v) => v.trim()).filter(Boolean))];
  }

  const fromVariants = product.node.variants.edges.flatMap((e) =>
    e.node.selectedOptions.filter((o) => isSizeOption(o.name)).map((o) => o.value.trim()),
  );
  return [...new Set(fromVariants.filter(Boolean))];
}

export function productHasSize(product: ShopifyProduct, size: string): boolean {
  const target = size.toLowerCase();
  return productSizes(product).some((s) => s.toLowerCase() === target);
}

export function productInStock(product: ShopifyProduct): boolean {
  return product.node.variants.edges.some((e) => e.node.availableForSale);
}

function compareSizes(a: string, b: string): number {
  const ai = SIZE_ORDER.indexOf(a.toUpperCase());
  const bi = SIZE_ORDER.indexOf(b.toUpperCase());
  if (ai !== -1 || bi !== -1) {
    if (ai === -1) return 1;
    if (bi === -1) return -1;
    return ai - bi;
  }
  const an = parseFloat(a);
  const bn = parseFloat(b);
  if (!Number.isNaN(an) && !Number.isNaN(bn)) return an - bn;
  return a.localeCompare(b, "es", { numeric: true });
}

export function collectAvailableSizes(products: ShopifyProduct[]): string[] {
  const set = new Set<string>();
  for (const p of products) {
    for (const s of productSizes(p)) set.add(s);
  }
  return [...set].sort(compareSizes);
}

export function getPriceBounds(products: ShopifyProduct[]): { min: number; max: number } {
  if (!products.length) return { min: 0, max: 0 };
  let min = Infinity;
  let max = 0;
  for (const p of products) {
    const price = productPrice(p);
    if (price < min) min = price;
    if (price > max) max = price;
  }
  if (!Number.isFinite(min)) min = 0;
  return { min, max };
}

export function parseFilterState(input: {
  talla?: string;
  min?: number;
  max?: number;
  sort?: ProductSort;
  stock?: "1";
}): ProductFilterState {
  const sizes = (input.talla || "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean);

  return {
    sizes,
    minPrice: input.min,
    maxPrice: input.max,
    sort: input.sort || "featured",
    inStockOnly: input.stock === "1",
  };
}

/** Serialize filters for URL search (omit defaults). */
export function filtersToSearchParams(state: ProductFilterState): Record<string, string | number | undefined> {
  return {
    ...(state.sizes.length ? { talla: state.sizes.join(",") } : {}),
    ...(state.minPrice != null && state.minPrice > 0 ? { min: Math.round(state.minPrice) } : {}),
    ...(state.maxPrice != null && state.maxPrice > 0 ? { max: Math.round(state.maxPrice) } : {}),
    ...(state.sort && state.sort !== "featured" ? { sort: state.sort } : {}),
    ...(state.inStockOnly ? { stock: "1" } : {}),
  };
}

export function hasActiveFilters(state: ProductFilterState): boolean {
  return (
    state.sizes.length > 0 ||
    (state.minPrice != null && state.minPrice > 0) ||
    (state.maxPrice != null && state.maxPrice > 0) ||
    state.sort !== "featured" ||
    state.inStockOnly
  );
}

export function applyProductFilters(
  products: ShopifyProduct[],
  state: ProductFilterState,
): ShopifyProduct[] {
  let list = products.filter((p) => {
    if (state.sizes.length && !state.sizes.some((s) => productHasSize(p, s))) return false;

    const price = productPrice(p);
    if (state.minPrice != null && state.minPrice > 0 && price < state.minPrice) return false;
    if (state.maxPrice != null && state.maxPrice > 0 && price > state.maxPrice) return false;
    if (state.inStockOnly && !productInStock(p)) return false;
    return true;
  });

  if (state.sort === "price-asc") {
    list = [...list].sort((a, b) => productPrice(a) - productPrice(b));
  } else if (state.sort === "price-desc") {
    list = [...list].sort((a, b) => productPrice(b) - productPrice(a));
  } else if (state.sort === "name") {
    list = [...list].sort((a, b) => a.node.title.localeCompare(b.node.title, "es"));
  }

  return list;
}
