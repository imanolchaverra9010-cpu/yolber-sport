import { createFileRoute, Link } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { useEffect, useMemo, useState } from "react";
import {
  CheckCircle2,
  Heart,
  Loader2,
  MapPin,
  MessageCircle,
  ShoppingCart,
  Sparkles,
  Truck,
  Zap,
} from "lucide-react";
import { toast } from "sonner";
import { fetchProduct, fetchProducts, formatPrice } from "@/lib/shopify";
import { useCartStore } from "@/stores/cartStore";
import { ProductCard } from "@/components/ProductCard";
import { cn } from "@/lib/utils";
import { useWhatsAppPhone } from "@/lib/whatsapp";

export const Route = createFileRoute("/product/$handle")({
  head: ({ params }) => ({
    meta: [
      { title: `${params.handle} — Yolber Sport` },
      { name: "description", content: "Detalle de producto de ropa y calzado deportivo Yolber Sport." },
      { property: "og:title", content: `${params.handle} — Yolber Sport` },
    ],
  }),
  component: ProductPage,
});

function ProductPage() {
  const { handle } = Route.useParams();
  const { data: product, isLoading } = useQuery({
    queryKey: ["product", handle],
    queryFn: () => fetchProduct(handle),
  });
  const { data: allProducts = [] } = useQuery({
    queryKey: ["products", "related"],
    queryFn: () => fetchProducts(24),
  });

  const [variantIdx, setVariantIdx] = useState(0);
  const [imgIdx, setImgIdx] = useState(0);
  const [wishlisted, setWishlisted] = useState(false);
  const addItem = useCartStore((s) => s.addItem);
  const adding = useCartStore((s) => s.isLoading);
  const { phone: whatsappPhone, href: whatsappHref } = useWhatsAppPhone();

  useEffect(() => {
    setVariantIdx(0);
    setImgIdx(0);
    setWishlisted(false);
  }, [handle]);

  const related = useMemo(() => {
    if (!product) return { suggestions: [], similar: [] };
    const others = allProducts.filter((p) => p.node.handle !== product.node.handle);
    const titleWords = product.node.title.toLowerCase().split(/\s+/).filter((w) => w.length > 3);
    const similar = others
      .filter((p) => {
        const hay = `${p.node.title} ${p.node.description}`.toLowerCase();
        return titleWords.some((w) => hay.includes(w));
      })
      .slice(0, 4);
    const similarIds = new Set(similar.map((p) => p.node.id));
    const suggestions = others.filter((p) => !similarIds.has(p.node.id)).slice(0, 4);
    // fallback if filters are too strict with few products
    if (similar.length === 0) {
      return {
        suggestions: others.slice(0, 2),
        similar: others.slice(2, 6),
      };
    }
    return { suggestions, similar };
  }, [allProducts, product]);

  if (isLoading) {
    return (
      <div className="flex justify-center py-40">
        <Loader2 className="h-8 w-8 animate-spin text-gold" />
      </div>
    );
  }

  if (!product) {
    return <div className="py-40 text-center text-muted-foreground">Producto no encontrado</div>;
  }

  const p = product.node;
  const variants = p.variants.edges.map((e) => e.node);
  const v = variants[variantIdx] ?? variants[0];
  const imgs = p.images.edges.map((e) => e.node);
  const optionName = p.options[0]?.name || "Opción";
  const availableCount = variants.filter((x) => x.availableForSale).length;

  const add = async () => {
    if (!v) return;
    await addItem({
      product,
      variantId: v.id,
      variantTitle: v.title,
      price: v.price,
      quantity: 1,
      selectedOptions: v.selectedOptions,
    });
    toast.success("Agregado al carrito", { description: p.title, position: "top-center" });
  };

  const askWhatsApp = () => {
    const msg = encodeURIComponent(
      `Hola, me interesa el producto *${p.title}*${v ? ` (${v.title})` : ""}. ¿Sigue disponible?`,
    );
    window.open(`https://wa.me/${whatsappPhone}?text=${msg}`, "_blank");
  };

  return (
    <main className="relative pb-24">
      <div className="mx-auto max-w-7xl px-4 py-6 md:px-6 md:py-10">
        <nav className="mb-6 flex flex-wrap items-center gap-2 text-xs text-muted-foreground md:mb-8" data-aos="fade-down">
          <Link to="/" className="hover:text-gold">
            Inicio
          </Link>
          <span>/</span>
          <Link to="/categories" className="hover:text-gold">
            Tienda
          </Link>
          <span>/</span>
          <span className="text-gold">{optionName}</span>
          <span>/</span>
          <span className="max-w-[12rem] truncate text-foreground md:max-w-xs">{p.title}</span>
        </nav>

        <div className="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:gap-12">
          {/* Gallery */}
          <div className="flex gap-3 md:gap-4" data-aos="fade-right">
            {imgs.length > 0 && (
              <div className="hidden w-20 flex-shrink-0 flex-col gap-3 sm:flex">
                {imgs.map((im, i) => (
                  <button
                    key={`${im.url}-${i}`}
                    type="button"
                    onClick={() => setImgIdx(i)}
                    className={cn(
                      "aspect-square overflow-hidden rounded-xl border bg-card transition",
                      i === imgIdx ? "border-gold" : "border-border hover:border-gold/50",
                    )}
                  >
                    <img src={im.url} alt="" className="h-full w-full object-cover" />
                  </button>
                ))}
              </div>
            )}

            <div className="relative min-w-0 flex-1 overflow-hidden rounded-2xl border border-border bg-card">
              {imgs[imgIdx] ? (
                <img
                  src={imgs[imgIdx].url}
                  alt={imgs[imgIdx].altText ?? p.title}
                  className="aspect-square w-full object-cover md:aspect-[5/5]"
                />
              ) : (
                <div className="grid aspect-square place-items-center text-muted-foreground">Sin imagen</div>
              )}

              <button
                type="button"
                aria-label={wishlisted ? "Quitar de favoritos" : "Agregar a favoritos"}
                onClick={() => {
                  setWishlisted((w) => !w);
                  toast.success(wishlisted ? "Quitado de favoritos" : "Guardado en favoritos", {
                    position: "top-center",
                  });
                }}
                className="absolute right-3 top-3 grid h-11 w-11 place-items-center rounded-full border border-border bg-background/90 text-foreground backdrop-blur transition hover:border-gold hover:text-gold"
              >
                <Heart className={cn("h-5 w-5", wishlisted && "fill-gold text-gold")} />
              </button>

              {imgs.length > 1 && (
                <div className="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-2 sm:hidden">
                  {imgs.map((im, i) => (
                    <button
                      key={`dot-${im.url}-${i}`}
                      type="button"
                      aria-label={`Imagen ${i + 1}`}
                      onClick={() => setImgIdx(i)}
                      className={cn(
                        "h-2 w-2 rounded-full transition",
                        i === imgIdx ? "bg-gold" : "bg-white/50",
                      )}
                    />
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Info */}
          <div className="flex flex-col" data-aos="fade-left" data-aos-delay="100">
            <p className="text-sm text-muted-foreground">
              Marca: <span className="font-semibold text-foreground">Yolber Sport</span>
            </p>

            <div className="mt-3 flex flex-wrap gap-2">
              <span className="inline-flex items-center gap-1.5 rounded-full bg-gold/15 px-3 py-1 text-xs font-semibold text-gold">
                <Sparkles className="h-3.5 w-3.5" />
                {availableCount} {optionName.toLowerCase()}
                {availableCount === 1 ? " disponible" : "s disponibles"}
              </span>
              {v?.availableForSale && (
                <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-400">
                  En stock
                </span>
              )}
            </div>

            <h1 className="mt-4 font-display text-4xl uppercase leading-[0.95] text-white md:text-5xl lg:text-6xl">
              {p.title}
              {v && variants.length > 1 ? (
                <span className="text-gold">, {v.selectedOptions.map((o) => o.value).join(" / ") || v.title}</span>
              ) : null}
            </h1>

            <div className="mt-6 grid gap-3 sm:grid-cols-2">
              <div>
                <p className="mb-2 text-xs uppercase tracking-[0.2em] text-muted-foreground">Condición del producto</p>
                <div className="flex items-center gap-3 rounded-lg border border-border bg-card px-3 py-3">
                  <CheckCircle2 className="h-5 w-5 flex-shrink-0 text-emerald-400" />
                  <span className="text-sm font-medium text-foreground">100% Nuevo</span>
                </div>
              </div>
              <div>
                <p className="mb-2 text-xs uppercase tracking-[0.2em] text-muted-foreground">Origen del producto</p>
                <div className="flex items-center gap-3 rounded-lg border border-border bg-card px-3 py-3">
                  <MapPin className="h-5 w-5 flex-shrink-0 text-gold" />
                  <span className="text-sm font-medium text-foreground">Producto de Colombia</span>
                </div>
              </div>
            </div>

            {variants.length > 0 && (
              <div className="mt-6">
                <p className="mb-3 text-sm text-muted-foreground">
                  {optionName}:{" "}
                  <span className="font-semibold text-foreground">
                    {v?.selectedOptions.map((o) => o.value).join(" / ") || v?.title}
                    {v?.availableForSale ? ` (${availableCount} disponibles)` : " (agotado)"}
                  </span>
                </p>
                <div className="flex flex-wrap gap-2">
                  {variants.map((vv, i) => (
                    <button
                      key={vv.id}
                      type="button"
                      disabled={!vv.availableForSale}
                      onClick={() => setVariantIdx(i)}
                      className={cn(
                        "min-w-12 border px-4 py-2.5 text-sm uppercase transition disabled:opacity-30",
                        i === variantIdx
                          ? "border-gold bg-gold text-primary-foreground"
                          : "border-border hover:border-gold",
                      )}
                    >
                      {vv.title}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {v && (
              <p className="mt-6 font-display text-5xl text-gold-gradient md:text-6xl">
                {formatPrice(v.price.amount, v.price.currencyCode)}
              </p>
            )}

            <div className="mt-4 flex flex-wrap gap-2">
              <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1.5 text-xs font-semibold text-emerald-400">
                <Zap className="h-3.5 w-3.5" /> Entrega rápida
              </span>
              <span className="inline-flex items-center gap-1.5 rounded-full bg-gold/15 px-3 py-1.5 text-xs font-semibold text-gold">
                <Truck className="h-3.5 w-3.5" /> Envío a todo el país
              </span>
            </div>

            <p className="mt-6 text-sm leading-relaxed text-muted-foreground">{p.description}</p>

            <button
              type="button"
              onClick={add}
              disabled={adding || !v?.availableForSale}
              className="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#f5c518] px-6 py-4 text-base font-bold uppercase tracking-wide text-[#1a1a1a] transition hover:brightness-110 disabled:opacity-50"
            >
              {adding ? (
                <Loader2 className="h-5 w-5 animate-spin" />
              ) : (
                <>
                  <ShoppingCart className="h-5 w-5" />
                  {v?.availableForSale ? "Agregar al carrito" : "Agotado"}
                </>
              )}
            </button>

            <button
              type="button"
              onClick={askWhatsApp}
              className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full border border-[#25D366]/40 px-6 py-3 text-sm font-semibold text-[#25D366] transition hover:bg-[#25D366]/10"
            >
              <MessageCircle className="h-4 w-4" /> Consultar por WhatsApp
            </button>
          </div>
        </div>

        {/* Suggestions */}
        {related.suggestions.length > 0 && (
          <section className="mt-16 md:mt-24">
            <div className="mb-8 flex items-end justify-between gap-4" data-aos="fade-up">
              <div>
                <p className="text-xs uppercase tracking-[0.3em] text-gold">Para ti</p>
                <h2 className="mt-2 font-display text-4xl uppercase md:text-5xl">
                  Sugerencias <span className="text-gold-gradient">recomendadas</span>
                </h2>
              </div>
              <Link to="/categories" className="hidden text-xs uppercase tracking-[0.25em] text-muted-foreground hover:text-gold md:inline">
                Ver más →
              </Link>
            </div>
            <div className="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
              {related.suggestions.map((item, i) => (
                <ProductCard key={item.node.id} product={item} aosDelay={Math.min(i * 80, 320)} />
              ))}
            </div>
          </section>
        )}

        {/* Similar */}
        {related.similar.length > 0 && (
          <section className="mt-16 md:mt-24">
            <div className="mb-8 flex items-end justify-between gap-4" data-aos="fade-up">
              <div>
                <p className="text-xs uppercase tracking-[0.3em] text-gold">Misma vibra</p>
                <h2 className="mt-2 font-display text-4xl uppercase md:text-5xl">
                  Productos <span className="text-gold-gradient">similares</span>
                </h2>
              </div>
              <Link to="/" className="hidden text-xs uppercase tracking-[0.25em] text-muted-foreground hover:text-gold md:inline">
                Ver colección →
              </Link>
            </div>
            <div className="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
              {related.similar.map((item, i) => (
                <ProductCard key={item.node.id} product={item} aosDelay={Math.min(i * 80, 320)} />
              ))}
            </div>
          </section>
        )}
      </div>

      <a
        href={whatsappHref}
        target="_blank"
        rel="noopener noreferrer"
        aria-label="WhatsApp"
        className="fixed bottom-6 right-6 z-40 grid h-14 w-14 place-items-center rounded-full bg-[#25D366] text-white shadow-lg transition hover:scale-105 hover:brightness-110"
      >
        <MessageCircle className="h-7 w-7" />
      </a>
    </main>
  );
}
