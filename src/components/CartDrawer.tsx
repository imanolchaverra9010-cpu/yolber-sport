import { useEffect, useState } from "react";
import { Link } from "@tanstack/react-router";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { ShoppingBag, Minus, Plus, Trash2, MessageCircle, Loader2, ArrowLeft } from "lucide-react";
import { useCartStore, type CheckoutCustomer } from "@/stores/cartStore";
import { formatPrice } from "@/lib/shopify";

const emptyCustomer: CheckoutCustomer = {
  name: "",
  phone: "",
  city: "",
  address: "",
  notes: "",
};

export function CartDrawer() {
  const [open, setOpen] = useState(false);
  const [step, setStep] = useState<"cart" | "checkout">("cart");
  const [customer, setCustomer] = useState<CheckoutCustomer>(emptyCustomer);
  const [errors, setErrors] = useState<Partial<Record<keyof CheckoutCustomer, string>>>({});
  const { items, isLoading, isSyncing, updateQuantity, removeItem, getWhatsAppCheckoutUrl } = useCartStore();
  const totalItems = items.reduce((s, i) => s + i.quantity, 0);
  const total = items.reduce((s, i) => s + parseFloat(i.price.amount) * i.quantity, 0);
  const currency = items[0]?.price.currencyCode || "COP";

  const whatsappPhone = import.meta.env.VITE_WHATSAPP_PHONE || "573207885337";

  useEffect(() => {
    if (!open) {
      setStep("cart");
      setErrors({});
    }
  }, [open]);

  const updateField = (field: keyof CheckoutCustomer, value: string) => {
    setCustomer((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const validate = () => {
    const next: Partial<Record<keyof CheckoutCustomer, string>> = {};
    if (!customer.name.trim()) next.name = "Ingresa tu nombre";
    if (!customer.phone.trim()) next.phone = "Ingresa tu teléfono";
    if (!customer.city.trim()) next.city = "Ingresa tu ciudad";
    if (!customer.address.trim()) next.address = "Ingresa tu dirección";
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const checkout = () => {
    if (!validate()) return;
    const url = getWhatsAppCheckoutUrl(whatsappPhone, customer);
    if (url && url !== "#") {
      window.open(url, "_blank");
      setOpen(false);
      setStep("cart");
    }
  };

  const fieldClass =
    "w-full rounded border border-gold/20 bg-gray-800 px-3 py-2.5 text-sm text-white placeholder:text-muted-foreground focus:border-gold focus:outline-none";

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <button aria-label="Carrito" className="relative grid h-11 w-11 place-items-center border border-gold/40 text-gold transition hover:bg-gold hover:text-primary-foreground">
          <ShoppingBag className="h-5 w-5" />
          {totalItems > 0 && (
            <span className="absolute -right-2 -top-2 grid h-5 w-5 place-items-center rounded-full bg-gold text-[10px] font-bold text-primary-foreground">
              {totalItems}
            </span>
          )}
        </button>
      </SheetTrigger>
      <SheetContent className="flex h-full w-full flex-col border-gold/20 bg-background sm:max-w-lg">
        <SheetHeader>
          <SheetTitle className="font-display text-3xl uppercase tracking-wide text-gold-gradient">
            {step === "cart" ? "Tu carrito" : "Datos de envío"}
          </SheetTitle>
          <SheetDescription>
            {step === "cart"
              ? totalItems === 0
                ? "Tu carrito está vacío"
                : `${totalItems} artículo${totalItems !== 1 ? "s" : ""}`
              : "Completa tus datos para enviar el pedido por WhatsApp"}
          </SheetDescription>
        </SheetHeader>
        <div className="flex min-h-0 flex-1 flex-col pt-6">
          {items.length === 0 ? (
            <div className="flex flex-1 flex-col items-center justify-center text-muted-foreground">
              <ShoppingBag className="mb-4 h-12 w-12 text-gold/50" />
              <p className="text-lg">Aún no has agregado nada</p>
              <Link to="/categories" className="mt-4 text-sm uppercase tracking-wider text-gold hover:text-gold/80">
                Ver productos →
              </Link>
            </div>
          ) : step === "cart" ? (
            <>
              <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-2">
                {items.map((item) => (
                  <div key={item.variantId} className="flex gap-4 border-b border-gold/20 pb-4">
                    <div className="h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg border border-gold/10 bg-card">
                      {item.product.node.images.edges[0] && (
                        <img src={item.product.node.images.edges[0].node.url} alt={item.product.node.title} className="h-full w-full object-cover" />
                      )}
                    </div>
                    <div className="min-w-0 flex-1">
                      <h4 className="truncate font-semibold uppercase text-white">{item.product.node.title}</h4>
                      <p className="mt-1 text-sm text-muted-foreground">
                        {item.selectedOptions.map((o) => `${o.name}: ${o.value}`).join(" · ") || item.variantTitle}
                      </p>
                      <p className="mt-2 font-bold text-gold">{formatPrice(item.price.amount, item.price.currencyCode)}</p>
                    </div>
                    <div className="flex flex-col items-end gap-2">
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-muted-foreground hover:text-red-500" onClick={() => removeItem(item.variantId)}>
                        <Trash2 className="h-4 w-4" />
                      </Button>
                      <div className="flex items-center gap-2 rounded-lg border border-gold/20">
                        <Button variant="ghost" size="icon" className="h-8 w-8 text-gold hover:bg-gold/10" onClick={() => updateQuantity(item.variantId, item.quantity - 1)}>
                          <Minus className="h-4 w-4" />
                        </Button>
                        <span className="w-8 text-center text-sm font-semibold text-white">{item.quantity}</span>
                        <Button variant="ghost" size="icon" className="h-8 w-8 text-gold hover:bg-gold/10" onClick={() => updateQuantity(item.variantId, item.quantity + 1)}>
                          <Plus className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              <div className="-mx-6 space-y-4 rounded-t-lg border-t border-gold/20 bg-gray-800/50 px-6 pb-6 pt-4">
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                  <span>Subtotal</span>
                  <span>{formatPrice(String(total), currency)}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="font-semibold uppercase tracking-widest text-white">Total</span>
                  <span className="font-display text-3xl text-gold">{formatPrice(String(total), currency)}</span>
                </div>
                <button
                  onClick={() => setStep("checkout")}
                  disabled={isLoading || isSyncing}
                  className="btn-gold w-full py-4 text-lg"
                >
                  {isLoading || isSyncing ? <Loader2 className="h-5 w-5 animate-spin" /> : "Continuar al pedido"}
                </button>
                <p className="text-center text-xs text-muted-foreground">
                  En el siguiente paso pedimos tus datos de envío y abrimos WhatsApp.
                </p>
              </div>
            </>
          ) : (
            <>
              <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-2">
                <button
                  type="button"
                  onClick={() => setStep("cart")}
                  className="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-muted-foreground hover:text-gold"
                >
                  <ArrowLeft className="h-4 w-4" /> Volver al carrito
                </button>

                <div className="rounded-lg border border-gold/20 bg-card/50 p-3 text-sm">
                  <div className="mb-2 flex justify-between text-muted-foreground">
                    <span>{totalItems} artículo{totalItems !== 1 ? "s" : ""}</span>
                    <span className="font-semibold text-gold">{formatPrice(String(total), currency)}</span>
                  </div>
                  <ul className="space-y-1 text-xs text-muted-foreground">
                    {items.map((item) => (
                      <li key={item.variantId}>
                        {item.quantity}× {item.product.node.title}
                        {" · "}
                        {item.selectedOptions.map((o) => o.value).join(" / ") || item.variantTitle}
                      </li>
                    ))}
                  </ul>
                </div>

                <div className="space-y-3">
                  <div>
                    <label className="mb-1.5 block text-xs uppercase tracking-wider text-muted-foreground">Nombre completo</label>
                    <input
                      type="text"
                      value={customer.name}
                      onChange={(e) => updateField("name", e.target.value)}
                      className={fieldClass}
                      placeholder="Ej. Ana Gómez"
                      autoComplete="name"
                    />
                    {errors.name && <p className="mt-1 text-xs text-red-400">{errors.name}</p>}
                  </div>
                  <div>
                    <label className="mb-1.5 block text-xs uppercase tracking-wider text-muted-foreground">Teléfono / WhatsApp</label>
                    <input
                      type="tel"
                      value={customer.phone}
                      onChange={(e) => updateField("phone", e.target.value)}
                      className={fieldClass}
                      placeholder="Ej. 300 123 4567"
                      autoComplete="tel"
                    />
                    {errors.phone && <p className="mt-1 text-xs text-red-400">{errors.phone}</p>}
                  </div>
                  <div>
                    <label className="mb-1.5 block text-xs uppercase tracking-wider text-muted-foreground">Ciudad</label>
                    <input
                      type="text"
                      value={customer.city}
                      onChange={(e) => updateField("city", e.target.value)}
                      className={fieldClass}
                      placeholder="Ej. Bogotá"
                      autoComplete="address-level2"
                    />
                    {errors.city && <p className="mt-1 text-xs text-red-400">{errors.city}</p>}
                  </div>
                  <div>
                    <label className="mb-1.5 block text-xs uppercase tracking-wider text-muted-foreground">Dirección de envío</label>
                    <input
                      type="text"
                      value={customer.address}
                      onChange={(e) => updateField("address", e.target.value)}
                      className={fieldClass}
                      placeholder="Calle, número, barrio"
                      autoComplete="street-address"
                    />
                    {errors.address && <p className="mt-1 text-xs text-red-400">{errors.address}</p>}
                  </div>
                  <div>
                    <label className="mb-1.5 block text-xs uppercase tracking-wider text-muted-foreground">Notas (opcional)</label>
                    <textarea
                      value={customer.notes}
                      onChange={(e) => updateField("notes", e.target.value)}
                      className={`${fieldClass} resize-none`}
                      rows={2}
                      placeholder="Referencias, horario, etc."
                    />
                  </div>
                </div>
              </div>
              <div className="-mx-6 space-y-4 rounded-t-lg border-t border-gold/20 bg-gray-800/50 px-6 pb-6 pt-4">
                <div className="flex items-center justify-between">
                  <span className="font-semibold uppercase tracking-widest text-white">Total</span>
                  <span className="font-display text-3xl text-gold">{formatPrice(String(total), currency)}</span>
                </div>
                <button onClick={checkout} disabled={isLoading || isSyncing} className="btn-gold flex w-full items-center justify-center py-4 text-lg">
                  {isLoading || isSyncing ? (
                    <Loader2 className="h-5 w-5 animate-spin" />
                  ) : (
                    <>
                      <MessageCircle className="mr-2 h-5 w-5" /> Enviar pedido por WhatsApp
                    </>
                  )}
                </button>
                <p className="text-center text-xs text-muted-foreground">
                  Se abrirá WhatsApp con el resumen del pedido, tallas y tus datos de envío.
                </p>
              </div>
            </>
          )}
        </div>
      </SheetContent>
    </Sheet>
  );
}
