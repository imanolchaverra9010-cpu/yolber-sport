import { create } from "zustand";
import { persist, createJSONStorage } from "zustand/middleware";
import {
  type ShopifyProduct,
  createShopifyCart,
  addLineToShopifyCart,
  updateShopifyCartLine,
  removeLineFromShopifyCart,
  formatPrice,
} from "@/lib/shopify";

export interface CartItem {
  lineId: string | null;
  product: ShopifyProduct;
  variantId: string;
  variantTitle: string;
  price: { amount: string; currencyCode: string };
  quantity: number;
  selectedOptions: Array<{ name: string; value: string }>;
}

export interface CheckoutCustomer {
  name: string;
  phone: string;
  city: string;
  address: string;
  notes?: string;
}

interface CartStore {
  items: CartItem[];
  cartId: string | null;
  checkoutUrl: string | null;
  isLoading: boolean;
  isSyncing: boolean;
  addItem: (item: Omit<CartItem, "lineId">) => Promise<void>;
  updateQuantity: (variantId: string, quantity: number) => Promise<void>;
  removeItem: (variantId: string) => Promise<void>;
  clearCart: () => void;
  syncCart: () => Promise<void>;
  getCheckoutUrl: () => string | null;
  getWhatsAppCheckoutUrl: (phoneNumber: string, customer: CheckoutCustomer) => string;
}

function localLineId() {
  return `local-line-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

export const useCartStore = create<CartStore>()(
  persist(
    (set, get) => ({
      items: [],
      cartId: null,
      checkoutUrl: null,
      isLoading: false,
      isSyncing: false,

      addItem: async (item) => {
        const { items, cartId } = get();
        const existing = items.find((i) => i.variantId === item.variantId);

        // Local-first: UI updates immediately (WhatsApp checkout does not need server cart)
        if (existing) {
          set({
            items: items.map((i) =>
              i.variantId === item.variantId
                ? { ...i, quantity: i.quantity + item.quantity }
                : i,
            ),
            isLoading: true,
          });
        } else {
          set({
            items: [...items, { ...item, lineId: localLineId() }],
            isLoading: true,
          });
        }

        try {
          if (!cartId) {
            const r = await createShopifyCart(item.variantId, item.quantity);
            if (r) {
              set({
                cartId: r.cartId,
                checkoutUrl: r.checkoutUrl,
                items: get().items.map((i) =>
                  i.variantId === item.variantId ? { ...i, lineId: r.lineId || i.lineId } : i,
                ),
              });
            }
          } else if (existing?.lineId && !existing.lineId.startsWith("local-")) {
            await updateShopifyCartLine(cartId, existing.lineId, existing.quantity + item.quantity);
          } else {
            const r = await addLineToShopifyCart(cartId, item.variantId, item.quantity);
            if (r.success && r.lineId) {
              set({
                items: get().items.map((i) =>
                  i.variantId === item.variantId ? { ...i, lineId: r.lineId ?? i.lineId } : i,
                ),
              });
            }
          }
        } catch (e) {
          console.warn("Backend cart sync failed (local cart kept):", e);
        } finally {
          set({ isLoading: false });
        }
      },

      updateQuantity: async (variantId, quantity) => {
        if (quantity <= 0) return get().removeItem(variantId);
        const { items, cartId } = get();
        const item = items.find((i) => i.variantId === variantId);
        if (!item) return;

        set({
          items: items.map((i) => (i.variantId === variantId ? { ...i, quantity } : i)),
          isLoading: true,
        });

        try {
          if (cartId && item.lineId && !item.lineId.startsWith("local-")) {
            await updateShopifyCartLine(cartId, item.lineId, quantity);
          }
        } catch (e) {
          console.warn("Backend qty sync failed:", e);
        } finally {
          set({ isLoading: false });
        }
      },

      removeItem: async (variantId) => {
        const { items, cartId } = get();
        const item = items.find((i) => i.variantId === variantId);
        if (!item) return;

        const next = items.filter((i) => i.variantId !== variantId);
        set({
          items: next,
          ...(next.length === 0 ? { cartId: null, checkoutUrl: null } : {}),
          isLoading: true,
        });

        try {
          if (cartId && item.lineId && !item.lineId.startsWith("local-")) {
            await removeLineFromShopifyCart(cartId, item.lineId);
          }
        } catch (e) {
          console.warn("Backend remove sync failed:", e);
        } finally {
          set({ isLoading: false });
        }
      },

      clearCart: () => set({ items: [], cartId: null, checkoutUrl: null }),
      getCheckoutUrl: () => get().checkoutUrl,

      getWhatsAppCheckoutUrl: (phoneNumber: string, customer: CheckoutCustomer) => {
        const { items } = get();
        if (items.length === 0) return "#";

        const currency = items[0].price.currencyCode;
        let message = "*Nuevo pedido — Yolber Sport*\n\n";
        message += "*Datos del cliente*\n";
        message += `Nombre: ${customer.name.trim()}\n`;
        message += `Teléfono: ${customer.phone.trim()}\n`;
        message += `Ciudad: ${customer.city.trim()}\n`;
        message += `Dirección: ${customer.address.trim()}\n`;
        if (customer.notes?.trim()) {
          message += `Notas: ${customer.notes.trim()}\n`;
        }
        message += "\n*Productos*\n";

        let total = 0;
        items.forEach((item, index) => {
          const price = parseFloat(item.price.amount);
          const itemTotal = price * item.quantity;
          total += itemTotal;

          const options =
            item.selectedOptions.length > 0
              ? item.selectedOptions.map((o) => `${o.name}: ${o.value}`).join(" · ")
              : item.variantTitle;

          message += `${index + 1}. *${item.product.node.title}*\n`;
          message += `   ${options}\n`;
          message += `   Cantidad: ${item.quantity}\n`;
          message += `   Precio unitario: ${formatPrice(item.price.amount, item.price.currencyCode)}\n`;
          message += `   Subtotal: ${formatPrice(itemTotal.toString(), item.price.currencyCode)}\n\n`;
        });

        message += `*Total: ${formatPrice(total.toString(), currency)}*\n\n`;
        message += "Quedo atento para confirmar disponibilidad y envío.";

        return `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
      },

      // Never wipe the local cart: checkout is WhatsApp and sessions often don't match.
      syncCart: async () => {
        set({ isSyncing: false });
      },
    }),
    {
      name: "yolber-cart-v2",
      storage: createJSONStorage(() => localStorage),
      partialize: (s) => ({ items: s.items, cartId: s.cartId, checkoutUrl: s.checkoutUrl }),
    },
  ),
);
