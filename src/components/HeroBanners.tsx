import { useEffect, useMemo, useState } from "react";
import { Link } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { fetchProducts, formatPrice, type ShopifyProduct } from "@/lib/shopify";
import { cn } from "@/lib/utils";

type HeroSlide = {
  id: string;
  eyebrow: string;
  title: string;
  cta: string;
  handle: string;
  image: string;
  imageAlt: string;
  priceBefore?: string;
  priceNow?: string;
  badge?: string;
  tone: "dark" | "teal" | "lime";
};

/** Mismos 4 campos en los 3 sliders: mocho, pantalones, camisetas, tenis. */
const HERO_CATEGORIES = [
  { slug: "mochos", aliases: ["mochos", "mocho"], label: "Mochos" },
  { slug: "pantalones", aliases: ["pantalones"], label: "Pantalones" },
  { slug: "camisetas", aliases: ["camisetas"], label: "Camisetas" },
  { slug: "sneakers", aliases: ["sneakers", "tenis"], label: "Tenis" },
] as const;

type HeroPick = {
  product: ShopifyProduct;
  imageIndex: number;
  categoryLabel: string;
};

function shuffle<T>(items: T[]): T[] {
  const arr = [...items];
  for (let i = arr.length - 1; i > 0; i -= 1) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

function productImageUrl(product: ShopifyProduct, imageIndex = 0): string {
  const edges = product.node.images.edges;
  if (!edges.length) return "";
  return edges[imageIndex % edges.length]?.node.url || edges[0]?.node.url || "";
}

function matchesCategory(product: ShopifyProduct, aliases: readonly string[]): boolean {
  const slug = (product.node.categorySlug || "").toLowerCase();
  return aliases.includes(slug);
}

function claimUniqueImage(
  product: ShopifyProduct,
  usedImages: Set<string>,
): number | null {
  const edges = product.node.images.edges;
  for (let g = 0; g < Math.max(edges.length, 1); g += 1) {
    const url = productImageUrl(product, g);
    if (url && !usedImages.has(url)) return g;
  }
  return null;
}

/**
 * 3 sliders × 1 prenda por categoría (4).
 * Ningún producto ni URL de imagen se repite entre los 12 slots.
 */
function buildHeroSlots(products: ShopifyProduct[]): [HeroPick[], HeroPick[], HeroPick[]] {
  const slots: [HeroPick[], HeroPick[], HeroPick[]] = [[], [], []];
  const usedIds = new Set<string>();
  const usedImages = new Set<string>();

  for (const cat of HERO_CATEGORIES) {
    const pool = shuffle(products.filter((p) => matchesCategory(p, cat.aliases)));

    for (let slot = 0; slot < 3; slot += 1) {
      let chosen: HeroPick | null = null;

      for (const product of pool) {
        if (usedIds.has(product.node.id)) continue;
        const imageIndex = claimUniqueImage(product, usedImages);
        if (imageIndex === null) continue;
        const url = productImageUrl(product, imageIndex);
        usedIds.add(product.node.id);
        if (url) usedImages.add(url);
        chosen = { product, imageIndex, categoryLabel: cat.label };
        break;
      }

      if (chosen) slots[slot].push(chosen);
    }
  }

  return slots;
}

function productToSlide(
  pick: HeroPick,
  tone: HeroSlide["tone"],
): HeroSlide {
  const { product, imageIndex, categoryLabel } = pick;
  const p = product.node;
  const edges = p.images.edges;
  const img = edges[imageIndex % Math.max(edges.length, 1)]?.node ?? edges[0]?.node;
  const amount = parseFloat(p.priceRange.minVariantPrice.amount || "0");
  const currency = p.priceRange.minVariantPrice.currencyCode || "COP";
  const now = formatPrice(String(amount), currency);
  const before =
    amount > 0
      ? formatPrice(String(Math.round(amount * 1.25)), currency)
      : undefined;

  return {
    id: `${p.id}-img${imageIndex}`,
    eyebrow: categoryLabel,
    title: p.title,
    cta: "Compra ahora",
    handle: p.handle,
    image: img?.url || "/hero.jpg",
    imageAlt: img?.altText || p.title,
    priceBefore: before,
    priceNow: now,
    badge: "Tiempo limitado",
    tone,
  };
}

function useAutoSlider(length: number, intervalMs: number) {
  const [index, setIndex] = useState(0);

  useEffect(() => {
    if (length <= 1) return;
    const id = window.setInterval(() => {
      setIndex((i) => (i + 1) % length);
    }, intervalMs);
    return () => window.clearInterval(id);
  }, [length, intervalMs]);

  useEffect(() => {
    setIndex(0);
  }, [length]);

  return {
    index,
    setIndex,
    prev: () => setIndex((i) => (i - 1 + length) % Math.max(length, 1)),
    next: () => setIndex((i) => (i + 1) % Math.max(length, 1)),
  };
}

function toneClasses(tone: HeroSlide["tone"]) {
  if (tone === "teal") {
    return {
      panel: "bg-[#1a9e9a]",
      fade: "from-[#1a9e9a] via-[#1a9e9a]/55",
      eyebrow: "text-white/85",
    };
  }
  if (tone === "lime") {
    return {
      panel: "bg-[#7cb518]",
      fade: "from-[#7cb518] via-[#7cb518]/50",
      eyebrow: "text-white/90",
    };
  }
  return {
    panel: "bg-[#2a2d35]",
    fade: "from-[#2a2d35] via-[#2a2d35]/50",
    eyebrow: "text-white/80",
  };
}

function SlideContent({
  slide,
  size,
}: {
  slide: HeroSlide;
  size: "main" | "side";
}) {
  const tones = toneClasses(slide.tone);
  const isMain = size === "main";

  return (
    <Link
      to="/product/$handle"
      params={{ handle: slide.handle }}
      aria-label={`${slide.title} — ${slide.cta}`}
      className={cn(
        "relative flex h-full w-full cursor-pointer overflow-hidden outline-none transition hover:brightness-[1.03] focus-visible:ring-2 focus-visible:ring-[#f5c518] focus-visible:ring-inset",
        tones.panel,
      )}
    >
      <div
        className={cn(
          "relative z-10 flex h-full flex-col justify-center",
          isMain ? "max-w-[52%] px-8 py-10 md:px-12 lg:px-14" : "max-w-[58%] px-5 py-5 md:px-6",
        )}
      >
        <p
          className={cn(
            "font-semibold uppercase tracking-[0.12em]",
            tones.eyebrow,
            isMain ? "text-sm md:text-base" : "text-[10px] md:text-xs",
          )}
        >
          {slide.eyebrow}
        </p>
        <h2
          className={cn(
            "mt-2 font-display uppercase leading-[0.95] text-white line-clamp-3",
            isMain ? "text-3xl md:text-5xl lg:text-6xl" : "text-xl md:text-2xl lg:text-3xl",
          )}
        >
          {slide.title}
        </h2>
        <span
          className={cn(
            "mt-5 inline-flex w-fit items-center justify-center rounded-full bg-[#f5c518] px-5 py-2.5 font-bold uppercase tracking-wide text-[#1a1a1a]",
            isMain ? "text-sm md:text-base" : "px-3.5 py-1.5 text-[11px] md:text-xs",
          )}
        >
          {slide.cta}
        </span>
      </div>

      <div className="absolute inset-y-0 right-0 w-[55%] md:w-[50%]">
        <img
          src={slide.image}
          alt=""
          className="h-full w-full object-cover object-center"
        />
        <div className={cn("pointer-events-none absolute inset-0 bg-gradient-to-r to-transparent", tones.fade)} />
      </div>

      {(slide.priceBefore || slide.priceNow) && (
        <div
          className={cn(
            "pointer-events-none absolute z-10 text-right text-white",
            isMain ? "bottom-8 right-8 md:bottom-10 md:right-10" : "bottom-4 right-4",
          )}
        >
          {slide.priceBefore && (
            <p className={cn("text-white/70 line-through", isMain ? "text-sm" : "text-[10px]")}>
              Antes: {slide.priceBefore}
            </p>
          )}
          {slide.priceNow && (
            <p className={cn("font-bold", isMain ? "text-2xl md:text-3xl" : "text-base md:text-lg")}>
              Ahora: {slide.priceNow}
            </p>
          )}
          {slide.badge && (
            <p className={cn("mt-1 text-white/75", isMain ? "text-xs" : "text-[10px]")}>{slide.badge}</p>
          )}
        </div>
      )}
    </Link>
  );
}

function PromoSlider({
  slides,
  size,
  intervalMs,
  showArrows = false,
  className,
  aos,
  aosDelay,
}: {
  slides: HeroSlide[];
  size: "main" | "side";
  intervalMs: number;
  showArrows?: boolean;
  className?: string;
  aos?: string;
  aosDelay?: number;
}) {
  const { index, setIndex, prev, next } = useAutoSlider(slides.length, intervalMs);

  if (!slides.length) {
    return (
      <div
        className={cn("animate-pulse rounded-2xl bg-[#161616]", className)}
        data-aos={aos}
        data-aos-delay={aosDelay}
      />
    );
  }

  return (
    <div
      className={cn("group relative overflow-hidden rounded-2xl", className)}
      data-aos={aos}
      data-aos-delay={aosDelay}
    >
      <div
        className="flex h-full transition-transform duration-500 ease-out"
        style={{ transform: `translateX(-${index * 100}%)` }}
      >
        {slides.map((slide) => (
          <div key={slide.id} className="h-full min-w-full">
            <SlideContent slide={slide} size={size} />
          </div>
        ))}
      </div>

      {showArrows && slides.length > 1 && (
        <>
          <button
            type="button"
            aria-label="Anterior"
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              prev();
            }}
            className="absolute left-3 top-1/2 z-20 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/35 text-white backdrop-blur-sm transition hover:bg-black/50 md:left-4"
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          <button
            type="button"
            aria-label="Siguiente"
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              next();
            }}
            className="absolute right-3 top-1/2 z-20 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/35 text-white backdrop-blur-sm transition hover:bg-black/50 md:right-4"
          >
            <ChevronRight className="h-5 w-5" />
          </button>
        </>
      )}

      {slides.length > 1 && (
        <div className="absolute bottom-3 left-1/2 z-20 flex -translate-x-1/2 gap-1.5">
          {slides.map((slide, i) => (
            <button
              key={slide.id}
              type="button"
              aria-label={`Ir al slide ${i + 1}`}
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setIndex(i);
              }}
              className={cn(
                "h-2 rounded-full transition-all",
                i === index ? "w-5 bg-white" : "w-2 bg-white/45 hover:bg-white/70",
              )}
            />
          ))}
        </div>
      )}
    </div>
  );
}

export function HeroBanners() {
  const { data: products = [], isLoading } = useQuery({
    queryKey: ["products", "hero"],
    queryFn: () => fetchProducts(120),
  });

  const { mainSlides, topSlides, bottomSlides } = useMemo(() => {
    const [main, top, bottom] = buildHeroSlots(products);
    return {
      mainSlides: main.map((pick) => productToSlide(pick, "dark")),
      topSlides: top.map((pick) => productToSlide(pick, "teal")),
      bottomSlides: bottom.map((pick) => productToSlide(pick, "lime")),
    };
  }, [products]);

  return (
    <section className="mx-auto w-full max-w-7xl px-4 py-6 md:px-6 md:py-8">
      <div className="grid gap-4 lg:grid-cols-3 lg:gap-5">
        <PromoSlider
          slides={isLoading ? [] : mainSlides}
          size="main"
          intervalMs={5500}
          showArrows
          className="h-[340px] sm:h-[400px] lg:col-span-2 lg:h-[460px]"
          aos="fade-right"
        />
        <div className="grid gap-4 lg:h-[460px]" data-aos="fade-left" data-aos-delay="120">
          <PromoSlider
            slides={isLoading ? [] : topSlides}
            size="side"
            intervalMs={6500}
            className="h-[200px] lg:h-full"
          />
          <PromoSlider
            slides={isLoading ? [] : bottomSlides}
            size="side"
            intervalMs={7000}
            className="h-[200px] lg:h-full"
          />
        </div>
      </div>
    </section>
  );
}
