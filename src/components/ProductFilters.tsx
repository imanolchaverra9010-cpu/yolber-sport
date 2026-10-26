import { useMemo, useState } from "react";
import { Filter, RotateCcw, X } from "lucide-react";
import type { ShopifyProduct } from "@/lib/shopify";
import { formatPrice } from "@/lib/shopify";
import {
  SORT_OPTIONS,
  applyProductFilters,
  collectAvailableSizes,
  defaultFilterState,
  getPriceBounds,
  hasActiveFilters,
  type ProductFilterState,
  type ProductSort,
} from "@/lib/productFilters";
import { cn } from "@/lib/utils";
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet";

type ProductFiltersProps = {
  products: ShopifyProduct[];
  value: ProductFilterState;
  onChange: (next: ProductFilterState) => void;
  resultCount?: number;
  className?: string;
};

function SizeChips({
  sizes,
  selected,
  onToggle,
}: {
  sizes: string[];
  selected: string[];
  onToggle: (size: string) => void;
}) {
  if (!sizes.length) {
    return <p className="text-xs text-muted-foreground">Sin tallas en el catálogo</p>;
  }

  return (
    <div className="flex flex-wrap gap-1.5">
      {sizes.map((size) => {
        const active = selected.some((s) => s.toLowerCase() === size.toLowerCase());
        return (
          <button
            key={size}
            type="button"
            onClick={() => onToggle(size)}
            className={cn(
              "min-w-9 rounded-md border px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide transition",
              active
                ? "border-gold bg-gold/15 text-gold"
                : "border-gold/25 text-muted-foreground hover:border-gold/50 hover:text-gold",
            )}
          >
            {size}
          </button>
        );
      })}
    </div>
  );
}

function FilterFields({
  products,
  value,
  onChange,
}: {
  products: ShopifyProduct[];
  value: ProductFilterState;
  onChange: (next: ProductFilterState) => void;
}) {
  const sizes = useMemo(() => collectAvailableSizes(products), [products]);
  const bounds = useMemo(() => getPriceBounds(products), [products]);
  const currency = products[0]?.node.priceRange.minVariantPrice.currencyCode || "COP";

  const presets = useMemo(() => {
    if (bounds.max <= 0) return [];
    const mid = Math.round((bounds.min + bounds.max) / 2 / 1000) * 1000;
    return [
      { label: "Todos", min: undefined, max: undefined },
      { label: `Hasta ${formatPrice(String(mid), currency)}`, min: undefined, max: mid },
      {
        label: `${formatPrice(String(mid), currency)}+`,
        min: mid,
        max: undefined,
      },
    ];
  }, [bounds, currency]);

  const patch = (partial: Partial<ProductFilterState>) => onChange({ ...value, ...partial });

  const toggleSize = (size: string) => {
    const exists = value.sizes.some((s) => s.toLowerCase() === size.toLowerCase());
    patch({
      sizes: exists
        ? value.sizes.filter((s) => s.toLowerCase() !== size.toLowerCase())
        : [...value.sizes, size],
    });
  };

  const presetActive = (min?: number, max?: number) =>
    value.minPrice === min && value.maxPrice === max;

  return (
    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
      <div className="space-y-2">
        <p className="text-[10px] font-semibold uppercase tracking-[0.22em] text-gold">Talla</p>
        <SizeChips sizes={sizes} selected={value.sizes} onToggle={toggleSize} />
      </div>

      <div className="space-y-2">
        <p className="text-[10px] font-semibold uppercase tracking-[0.22em] text-gold">Precio</p>
        <div className="flex flex-wrap gap-1.5">
          {presets.map((p) => (
            <button
              key={p.label}
              type="button"
              onClick={() => patch({ minPrice: p.min, maxPrice: p.max })}
              className={cn(
                "rounded-md border px-2.5 py-1.5 text-xs transition",
                presetActive(p.min, p.max)
                  ? "border-gold bg-gold/15 text-gold"
                  : "border-gold/25 text-muted-foreground hover:border-gold/50 hover:text-gold",
              )}
            >
              {p.label}
            </button>
          ))}
        </div>
        <div className="flex items-center gap-2">
          <label className="sr-only" htmlFor="filter-min">
            Precio mínimo
          </label>
          <input
            id="filter-min"
            type="number"
            inputMode="numeric"
            min={0}
            placeholder="Mín"
            value={value.minPrice ?? ""}
            onChange={(e) =>
              patch({
                minPrice: e.target.value === "" ? undefined : Math.max(0, Number(e.target.value)),
              })
            }
            className="h-9 w-full rounded-md border border-gold/25 bg-transparent px-2.5 text-sm text-foreground outline-none placeholder:text-muted-foreground focus:border-gold/60"
          />
          <span className="text-muted-foreground">—</span>
          <label className="sr-only" htmlFor="filter-max">
            Precio máximo
          </label>
          <input
            id="filter-max"
            type="number"
            inputMode="numeric"
            min={0}
            placeholder="Máx"
            value={value.maxPrice ?? ""}
            onChange={(e) =>
              patch({
                maxPrice: e.target.value === "" ? undefined : Math.max(0, Number(e.target.value)),
              })
            }
            className="h-9 w-full rounded-md border border-gold/25 bg-transparent px-2.5 text-sm text-foreground outline-none placeholder:text-muted-foreground focus:border-gold/60"
          />
        </div>
      </div>

      <div className="space-y-2">
        <p className="text-[10px] font-semibold uppercase tracking-[0.22em] text-gold">Ordenar</p>
        <select
          value={value.sort}
          onChange={(e) => patch({ sort: e.target.value as ProductSort })}
          className="h-9 w-full rounded-md border border-gold/25 bg-[#161616] px-2.5 text-sm text-foreground outline-none focus:border-gold/60"
        >
          {SORT_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      </div>

      <div className="space-y-2">
        <p className="text-[10px] font-semibold uppercase tracking-[0.22em] text-gold">Disponibilidad</p>
        <label className="flex h-9 cursor-pointer items-center gap-2.5 rounded-md border border-gold/25 px-3 text-sm text-muted-foreground transition hover:border-gold/45 hover:text-gold">
          <input
            type="checkbox"
            checked={value.inStockOnly}
            onChange={(e) => patch({ inStockOnly: e.target.checked })}
            className="h-4 w-4 accent-[#d4af37]"
          />
          Solo en stock
        </label>
        {bounds.max > 0 && (
          <p className="text-[11px] text-muted-foreground">
            Rango catálogo: {formatPrice(String(bounds.min), currency)} –{" "}
            {formatPrice(String(bounds.max), currency)}
          </p>
        )}
      </div>
    </div>
  );
}

export function ProductFilters({
  products,
  value,
  onChange,
  resultCount,
  className,
}: ProductFiltersProps) {
  const [open, setOpen] = useState(false);
  const active = hasActiveFilters(value);
  const matching = useMemo(
    () => applyProductFilters(products, value).length,
    [products, value],
  );
  const count = resultCount ?? matching;

  const clear = () => onChange({ ...defaultFilterState });

  return (
    <div
      className={cn(
        "mb-8 rounded-2xl border border-gold/20 bg-[#121212]/80 p-4 md:p-5",
        className,
      )}
      data-aos="fade-up"
    >
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-gold" />
          <h3 className="font-display text-lg uppercase tracking-wide text-white">Filtros</h3>
          {active && (
            <span className="rounded-full border border-gold/40 bg-gold/10 px-2 py-0.5 text-[10px] uppercase tracking-wider text-gold">
              Activos
            </span>
          )}
        </div>
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <span>
            {count} producto{count === 1 ? "" : "s"}
          </span>
          {active && (
            <button
              type="button"
              onClick={clear}
              className="inline-flex items-center gap-1 rounded-md border border-gold/30 px-2 py-1 uppercase tracking-wider text-gold transition hover:bg-gold/10"
            >
              <RotateCcw className="h-3 w-3" /> Limpiar
            </button>
          )}
          <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
              <button
                type="button"
                className="inline-flex items-center gap-1.5 rounded-md border border-gold/30 px-2.5 py-1.5 text-xs uppercase tracking-wider text-gold transition hover:bg-gold/10 md:hidden"
              >
                <Filter className="h-3.5 w-3.5" /> Ajustar
              </button>
            </SheetTrigger>
            <SheetContent side="bottom" className="max-h-[85vh] overflow-y-auto border-gold/20 bg-[#121212]">
              <SheetHeader className="mb-4 text-left">
                <SheetTitle className="font-display text-2xl uppercase text-gold">Filtros</SheetTitle>
              </SheetHeader>
              <FilterFields products={products} value={value} onChange={onChange} />
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-gold px-4 py-3 text-sm font-bold uppercase tracking-wide text-primary-foreground"
              >
                Ver {count} productos
                <X className="h-4 w-4 opacity-0" aria-hidden />
              </button>
            </SheetContent>
          </Sheet>
        </div>
      </div>

      <div className="hidden md:block">
        <FilterFields products={products} value={value} onChange={onChange} />
      </div>

      {active && value.sizes.length > 0 && (
        <div className="mt-4 flex flex-wrap gap-1.5 border-t border-gold/10 pt-4 md:hidden">
          {value.sizes.map((size) => (
            <button
              key={size}
              type="button"
              onClick={() =>
                onChange({
                  ...value,
                  sizes: value.sizes.filter((s) => s.toLowerCase() !== size.toLowerCase()),
                })
              }
              className="inline-flex items-center gap-1 rounded-full border border-gold/40 bg-gold/10 px-2.5 py-1 text-xs text-gold"
            >
              Talla {size} <X className="h-3 w-3" />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
