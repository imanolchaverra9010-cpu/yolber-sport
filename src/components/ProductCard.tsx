import { Link } from "@tanstack/react-router";
import { Loader2, Plus } from "lucide-react";
import { toast } from "sonner";
import { type ShopifyProduct, formatPrice } from "@/lib/shopify";
import { useCartStore } from "@/stores/cartStore";
import { cn } from "@/lib/utils";

export function ProductCard({
  product,
  aosDelay = 0,
}: {
  product: ShopifyProduct;
  aosDelay?: number;
}) {
  const addItem = useCartStore((s) => s.addItem);
  const isLoading = useCartStore((s) => s.isLoading);
  const p = product.node;
  const variant = p.variants.edges[0]?.node;
  const img = p.images.edges[0]?.node;
  const available = Boolean(variant?.availableForSale);
  const price = p.priceRange.minVariantPrice;

  const add = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!variant || !available) return;
    await addItem({
      product,
      variantId: variant.id,
      variantTitle: variant.title,
      price: variant.price,
      quantity: 1,
      selectedOptions: variant.selectedOptions,
    });
    toast.success("Agregado al carrito", { description: p.title, position: "top-center" });
  };

  return (
    <Link
      to="/product/$handle"
      params={{ handle: p.handle }}
      data-aos="fade-up"
      data-aos-delay={aosDelay}
      className={cn(
        "group relative flex flex-col outline-none transition duration-300",
        // Mobile: clean circular catalog tile
        "items-center text-center",
        // Desktop: full product card
        "md:overflow-hidden md:rounded-2xl md:border md:border-white/10 md:bg-[#161616] md:text-left",
        "md:hover:-translate-y-1.5 md:hover:border-gold/50 md:hover:shadow-[0_18px_40px_-20px_rgba(212,175,55,0.45)]",
        "focus-visible:ring-2 focus-visible:ring-gold focus-visible:ring-offset-2 focus-visible:ring-offset-background",
      )}
    >
      <div
        className={cn(
          "relative overflow-hidden bg-[#1e1e1e]",
          // Mobile circle
          "aspect-square w-[78%] rounded-full border border-white/10",
          "shadow-[0_12px_28px_-16px_rgba(0,0,0,0.8)]",
          "transition duration-300 group-hover:border-gold/45",
          // Desktop rectangle
          "md:w-full md:aspect-[4/5] md:rounded-none md:border-0 md:shadow-none",
        )}
      >
        {img ? (
          <img
            src={img.url}
            alt={img.altText ?? p.title}
            loading="lazy"
            className="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.07]"
          />
        ) : (
          <div className="grid h-full w-full place-items-center text-[10px] uppercase tracking-widest text-muted-foreground">
            Sin imagen
          </div>
        )}

        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-80 md:from-[#161616] md:via-[#161616]/20 md:opacity-90" />

        {!available && (
          <span className="absolute left-1/2 top-3 z-10 -translate-x-1/2 rounded-full bg-black/70 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[0.16em] text-white backdrop-blur-sm md:left-3 md:translate-x-0 md:px-3 md:text-[10px]">
            Agotado
          </span>
        )}

        {available && (
          <span className="absolute left-3 top-3 hidden rounded-full border border-white/10 bg-black/45 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-gold backdrop-blur-sm opacity-0 transition group-hover:opacity-100 md:inline-flex">
            Nuevo
          </span>
        )}

        <button
          type="button"
          onClick={add}
          disabled={isLoading || !available}
          aria-label={`Agregar ${p.title} al carrito`}
          className={cn(
            "absolute z-10 inline-flex items-center justify-center rounded-full bg-gold text-primary-foreground shadow-gold",
            "transition duration-300 hover:brightness-110",
            "disabled:pointer-events-none disabled:opacity-40",
            // Mobile: floating + on the circle
            "bottom-1 right-[8%] h-9 w-9",
            // Desktop: pill with label on hover
            "md:bottom-3 md:right-3 md:h-auto md:w-auto md:gap-2 md:px-3.5 md:py-2.5 md:text-xs md:font-bold md:uppercase md:tracking-wider",
            "md:translate-y-2 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100",
          )}
        >
          {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
          <span className="hidden md:inline">Agregar</span>
        </button>
      </div>

      <div
        className={cn(
          "relative flex w-full flex-col",
          "mt-3 gap-1 px-1",
          "md:mt-0 md:flex-1 md:gap-2 md:px-4 md:pb-5 md:pt-4",
        )}
      >
        <h3
          className={cn(
            "font-display uppercase tracking-wide text-white transition group-hover:text-gold",
            "line-clamp-2 text-[0.95rem] leading-[1.05]",
            "md:text-[1.65rem] md:leading-[0.95]",
          )}
        >
          {p.title}
        </h3>

        <p className="hidden line-clamp-2 text-sm leading-relaxed text-muted-foreground md:block">{p.description}</p>

        <div
          className={cn(
            "flex items-center justify-center gap-2",
            "md:mt-auto md:items-end md:justify-between md:border-t md:border-white/5 md:pt-4",
          )}
        >
          <div>
            <p className="hidden text-[10px] uppercase tracking-[0.22em] text-muted-foreground md:block">Desde</p>
            <p className="font-display text-lg leading-none text-gold-gradient md:text-3xl">
              {formatPrice(price.amount, price.currencyCode)}
            </p>
          </div>
          <span className="hidden rounded-full border border-gold/30 px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-gold transition group-hover:bg-gold group-hover:text-primary-foreground md:inline-flex">
            Ver
          </span>
        </div>
      </div>
    </Link>
  );
}
