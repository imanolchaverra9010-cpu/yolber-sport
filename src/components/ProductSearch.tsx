import { useEffect, useMemo, useRef, useState } from "react";
import { Link, useNavigate } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { Loader2, Search, X } from "lucide-react";
import { fetchProducts, formatPrice, type ShopifyProduct } from "@/lib/shopify";
import { cn } from "@/lib/utils";

function matchesQuery(product: ShopifyProduct, query: string) {
  const q = query.trim().toLowerCase();
  if (!q) return false;
  const { title, description, handle } = product.node;
  return (
    title.toLowerCase().includes(q) ||
    description.toLowerCase().includes(q) ||
    handle.toLowerCase().includes(q)
  );
}

type ProductSearchProps = {
  variant?: "desktop" | "mobile";
  className?: string;
  onNavigate?: () => void;
};

export function ProductSearch({ variant = "desktop", className, onNavigate }: ProductSearchProps) {
  const navigate = useNavigate();
  const rootRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const [open, setOpen] = useState(variant === "mobile");
  const [query, setQuery] = useState("");
  const [focused, setFocused] = useState(false);

  const { data: products = [], isLoading } = useQuery({
    queryKey: ["products", "search"],
    queryFn: () => fetchProducts(50),
    enabled: open || focused || query.length > 0,
  });

  const results = useMemo(() => {
    if (query.trim().length < 1) return [];
    return products.filter((p) => matchesQuery(p, query)).slice(0, 6);
  }, [products, query]);

  const showDropdown = (focused || open) && query.trim().length > 0;

  useEffect(() => {
    if (!showDropdown && !open) return;
    const onPointerDown = (e: MouseEvent) => {
      if (!rootRef.current?.contains(e.target as Node)) {
        setFocused(false);
        if (variant === "desktop") setOpen(false);
      }
    };
    document.addEventListener("mousedown", onPointerDown);
    return () => document.removeEventListener("mousedown", onPointerDown);
  }, [showDropdown, open, variant]);

  useEffect(() => {
    if (open && variant === "desktop") {
      inputRef.current?.focus();
    }
  }, [open, variant]);

  const goToResults = () => {
    const q = query.trim();
    if (!q) return;
    setFocused(false);
    if (variant === "desktop") setOpen(false);
    onNavigate?.();
    navigate({ to: "/", search: { search: q } });
  };

  const selectProduct = () => {
    setQuery("");
    setFocused(false);
    if (variant === "desktop") setOpen(false);
    onNavigate?.();
  };

  if (variant === "desktop" && !open) {
    return (
      <button
        type="button"
        aria-label="Buscar"
        className={cn("text-gold transition hover:text-gold/80", className)}
        onClick={() => setOpen(true)}
      >
        <Search className="h-5 w-5" />
      </button>
    );
  }

  return (
    <div ref={rootRef} className={cn("relative", variant === "desktop" ? "hidden md:block" : "w-full", className)}>
      <form
        onSubmit={(e) => {
          e.preventDefault();
          goToResults();
        }}
        className="relative"
      >
        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gold/80" />
        <input
          ref={inputRef}
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onFocus={() => setFocused(true)}
          placeholder="Buscar productos..."
          className={cn(
            "w-full rounded border border-gold/20 bg-gray-800 py-2.5 pl-10 pr-10 text-sm text-white placeholder:text-muted-foreground focus:border-gold focus:outline-none",
            variant === "desktop" ? "w-72 lg:w-80" : "w-full",
          )}
          autoComplete="off"
          aria-autocomplete="list"
          aria-expanded={showDropdown}
        />
        {variant === "desktop" ? (
          <button
            type="button"
            aria-label="Cerrar búsqueda"
            className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-gold"
            onClick={() => {
              setOpen(false);
              setQuery("");
              setFocused(false);
            }}
          >
            <X className="h-4 w-4" />
          </button>
        ) : query ? (
          <button
            type="button"
            aria-label="Limpiar"
            className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-gold"
            onClick={() => setQuery("")}
          >
            <X className="h-4 w-4" />
          </button>
        ) : null}
      </form>

      {showDropdown && (
        <div className="absolute left-0 right-0 z-50 mt-2 overflow-hidden rounded-lg border border-gold/20 bg-card shadow-xl md:min-w-[22rem]">
          {isLoading ? (
            <div className="flex items-center justify-center gap-2 px-4 py-6 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin text-gold" /> Buscando...
            </div>
          ) : results.length === 0 ? (
            <div className="px-4 py-6 text-center text-sm text-muted-foreground">
              Sin resultados para <span className="text-gold">{query.trim()}</span>
            </div>
          ) : (
            <ul className="max-h-80 overflow-y-auto py-1">
              {results.map((product) => {
                const p = product.node;
                const img = p.images.edges[0]?.node;
                const price = p.priceRange.minVariantPrice;
                return (
                  <li key={p.id}>
                    <Link
                      to="/product/$handle"
                      params={{ handle: p.handle }}
                      onClick={selectProduct}
                      className="flex items-center gap-3 px-3 py-2.5 transition hover:bg-gold/10"
                    >
                      <div className="h-14 w-14 flex-shrink-0 overflow-hidden rounded border border-gold/15 bg-muted">
                        {img ? (
                          <img src={img.url} alt="" className="h-full w-full object-cover" />
                        ) : (
                          <div className="grid h-full w-full place-items-center text-[10px] text-muted-foreground">N/A</div>
                        )}
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold uppercase text-foreground">{p.title}</p>
                        <p className="mt-0.5 font-display text-lg text-gold">
                          {formatPrice(price.amount, price.currencyCode)}
                        </p>
                      </div>
                    </Link>
                  </li>
                );
              })}
            </ul>
          )}

          {results.length > 0 && (
            <button
              type="button"
              onClick={goToResults}
              className="w-full border-t border-gold/15 px-4 py-2.5 text-left text-xs uppercase tracking-wider text-gold hover:bg-gold/10"
            >
              Ver todos los resultados →
            </button>
          )}
        </div>
      )}
    </div>
  );
}
