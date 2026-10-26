import { useEffect, useRef, useState } from "react";
import { ChevronLeft, ChevronRight, LayoutGrid } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { fetchCategories, type ShopCategory } from "@/lib/shopify";
import { cn } from "@/lib/utils";

type CategoryRailProps = {
  activeSlug?: string | null;
  onSelect?: (category: ShopCategory | null) => void;
  className?: string;
};

function CategoryChip({
  active,
  label,
  imageUrl,
  fallback,
}: {
  active?: boolean;
  label: string;
  imageUrl?: string | null;
  fallback?: React.ReactNode;
}) {
  return (
    <span className="flex w-[5.75rem] flex-col items-center gap-2.5 text-center sm:w-[6.5rem] md:w-[7.25rem]">
      <span
        className={cn(
          "relative aspect-square w-full overflow-hidden rounded-full border transition duration-300",
          "bg-[#161616] border-white/10",
          "group-hover:-translate-y-0.5 group-hover:border-gold/50 group-hover:shadow-[0_14px_30px_-14px_rgba(212,175,55,0.55)]",
          active && "border-gold shadow-[0_14px_30px_-14px_rgba(212,175,55,0.5)] ring-2 ring-gold/35",
        )}
      >
        {imageUrl ? (
          <img
            src={imageUrl}
            alt=""
            className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            loading="lazy"
          />
        ) : (
          <span className="grid h-full w-full place-items-center bg-gradient-to-b from-[#1f1f1f] to-[#121212] text-gold">
            {fallback}
          </span>
        )}
        <span
          className={cn(
            "pointer-events-none absolute inset-0 rounded-full ring-1 ring-inset",
            active ? "ring-gold/50" : "ring-white/5",
          )}
        />
      </span>
      <span
        className={cn(
          "max-w-full truncate font-display text-xs uppercase leading-none tracking-wide transition sm:text-sm md:text-base",
          active ? "text-gold" : "text-white group-hover:text-gold",
        )}
      >
        {label}
      </span>
    </span>
  );
}

export function CategoryRail({ activeSlug = null, onSelect, className }: CategoryRailProps) {
  const scrollerRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);
  const { data: categories = [], isLoading } = useQuery({
    queryKey: ["categories"],
    queryFn: fetchCategories,
  });

  const updateScrollState = () => {
    const el = scrollerRef.current;
    if (!el) return;
    setCanScrollLeft(el.scrollLeft > 4);
    setCanScrollRight(el.scrollLeft + el.clientWidth < el.scrollWidth - 4);
  };

  useEffect(() => {
    updateScrollState();
    const el = scrollerRef.current;
    if (!el) return;
    el.addEventListener("scroll", updateScrollState, { passive: true });
    window.addEventListener("resize", updateScrollState);
    return () => {
      el.removeEventListener("scroll", updateScrollState);
      window.removeEventListener("resize", updateScrollState);
    };
  }, [categories.length, onSelect]);

  const scrollBy = (dir: -1 | 1) => {
    scrollerRef.current?.scrollBy({ left: dir * 240, behavior: "smooth" });
  };

  if (isLoading) {
    return (
      <div className={cn("mx-auto w-full max-w-7xl px-4 py-6 md:px-6", className)}>
        <div className="flex gap-4 overflow-hidden">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="flex w-[6.5rem] flex-shrink-0 flex-col items-center gap-2.5">
              <div className="aspect-square w-full animate-pulse rounded-full border border-white/5 bg-[#161616]" />
              <div className="h-3 w-14 animate-pulse rounded bg-white/10" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (!categories.length) return null;

  const itemClass = "group flex-shrink-0 snap-start";

  return (
    <section className={cn("relative mx-auto w-full max-w-7xl px-4 py-6 md:px-6", className)} data-aos="fade-up">
      <div className="mb-5 flex items-end justify-between gap-4">
        <div>
          <p className="text-[10px] uppercase tracking-[0.3em] text-gold">Explorar</p>
          <h2 className="font-display text-3xl uppercase text-white md:text-4xl">Categorías</h2>
        </div>
        <div className="hidden gap-2 sm:flex">
          <button
            type="button"
            aria-label="Categorías anteriores"
            disabled={!canScrollLeft}
            onClick={() => scrollBy(-1)}
            className="grid h-9 w-9 place-items-center rounded-full border border-gold/30 bg-[#161616] text-gold transition hover:bg-gold hover:text-primary-foreground disabled:cursor-default disabled:opacity-30"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          <button
            type="button"
            aria-label="Más categorías"
            disabled={!canScrollRight}
            onClick={() => scrollBy(1)}
            className="grid h-9 w-9 place-items-center rounded-full border border-gold/30 bg-[#161616] text-gold transition hover:bg-gold hover:text-primary-foreground disabled:cursor-default disabled:opacity-30"
          >
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      </div>

      <div className="relative">
        {canScrollLeft && (
          <div className="pointer-events-none absolute inset-y-0 left-0 z-[1] w-8 bg-gradient-to-r from-background to-transparent sm:w-12" />
        )}
        {canScrollRight && (
          <div className="pointer-events-none absolute inset-y-0 right-0 z-[1] w-8 bg-gradient-to-l from-background to-transparent sm:w-12" />
        )}

        <div
          ref={scrollerRef}
          className="flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth px-1 pb-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:gap-4 md:gap-5 [&::-webkit-scrollbar]:hidden"
        >
          {onSelect && (
            <button type="button" onClick={() => onSelect(null)} className={itemClass}>
              <CategoryChip
                active={activeSlug == null}
                label="Todas"
                fallback={<LayoutGrid className="h-6 w-6 stroke-[1.75]" />}
              />
            </button>
          )}

          {categories.map((cat) => {
            const active = activeSlug === cat.slug;
            const initial = (cat.name || "?").trim().charAt(0).toUpperCase();
            const chip = (
              <CategoryChip
                active={active}
                label={cat.name}
                imageUrl={cat.iconUrl || null}
                fallback={
                  <span className="font-display text-xl text-gold/80 sm:text-2xl">{initial}</span>
                }
              />
            );

            if (onSelect) {
              return (
                <button key={cat.id} type="button" onClick={() => onSelect(cat)} className={itemClass}>
                  {chip}
                </button>
              );
            }

            return (
              <a
                key={cat.id}
                href={`/categories?cat=${encodeURIComponent(cat.slug)}`}
                className={itemClass}
              >
                {chip}
              </a>
            );
          })}
        </div>
      </div>
    </section>
  );
}
