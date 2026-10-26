import { Link } from "@tanstack/react-router";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";

export const PRODUCTS_PER_PAGE = 12;

export function getTotalPages(totalItems: number, pageSize = PRODUCTS_PER_PAGE) {
  return Math.max(1, Math.ceil(totalItems / pageSize));
}

export function paginateItems<T>(items: T[], page: number, pageSize = PRODUCTS_PER_PAGE): T[] {
  const totalPages = getTotalPages(items.length, pageSize);
  const safePage = Math.min(Math.max(1, page), totalPages);
  const start = (safePage - 1) * pageSize;
  return items.slice(start, start + pageSize);
}

function pageNumbers(current: number, total: number): Array<number | "ellipsis"> {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const pages = new Set<number>([1, total, current, current - 1, current + 1]);
  if (current <= 3) {
    pages.add(2);
    pages.add(3);
    pages.add(4);
  }
  if (current >= total - 2) {
    pages.add(total - 1);
    pages.add(total - 2);
    pages.add(total - 3);
  }

  const sorted = [...pages].filter((p) => p >= 1 && p <= total).sort((a, b) => a - b);
  const out: Array<number | "ellipsis"> = [];
  let prev = 0;
  for (const p of sorted) {
    if (prev && p - prev > 1) out.push("ellipsis");
    out.push(p);
    prev = p;
  }
  return out;
}

type ProductPaginationProps = {
  page: number;
  totalItems: number;
  pageSize?: number;
  buildSearch: (page: number) => Record<string, unknown>;
  to: "/" | "/categories";
  className?: string;
};

export function ProductPagination({
  page,
  totalItems,
  pageSize = PRODUCTS_PER_PAGE,
  buildSearch,
  to,
  className,
}: ProductPaginationProps) {
  const totalPages = getTotalPages(totalItems, pageSize);
  if (totalItems <= pageSize) return null;

  const safePage = Math.min(Math.max(1, page), totalPages);
  const numbers = pageNumbers(safePage, totalPages);

  const linkClass = (active?: boolean) =>
    cn(
      "inline-flex h-9 min-w-9 items-center justify-center gap-1 rounded-md border px-2.5 text-sm transition",
      active
        ? "border-gold bg-gold/15 font-semibold text-gold"
        : "border-gold/25 text-muted-foreground hover:border-gold/50 hover:text-gold",
    );

  const disabledClass =
    "inline-flex h-9 min-w-9 items-center justify-center gap-1 rounded-md border border-gold/15 px-2.5 text-sm text-muted-foreground opacity-40";

  return (
    <nav
      aria-label="Paginación de productos"
      className={cn("mt-12 flex flex-col items-center gap-4", className)}
    >
      <p className="text-xs uppercase tracking-[0.2em] text-muted-foreground">
        Página {safePage} de {totalPages}
        <span className="ml-2 normal-case tracking-normal">({totalItems} productos)</span>
      </p>
      <ul className="flex flex-wrap items-center justify-center gap-1.5">
        <li>
          {safePage <= 1 ? (
            <span className={disabledClass} aria-disabled="true">
              <ChevronLeft className="h-4 w-4" />
              <span className="hidden sm:inline">Anterior</span>
            </span>
          ) : (
            <Link to={to} search={buildSearch(safePage - 1)} aria-label="Página anterior" className={linkClass()}>
              <ChevronLeft className="h-4 w-4" />
              <span className="hidden sm:inline">Anterior</span>
            </Link>
          )}
        </li>
        {numbers.map((item, i) =>
          item === "ellipsis" ? (
            <li key={`e-${i}`} className="px-1 text-muted-foreground" aria-hidden>
              …
            </li>
          ) : (
            <li key={item}>
              <Link
                to={to}
                search={buildSearch(item)}
                aria-label={`Ir a la página ${item}`}
                aria-current={item === safePage ? "page" : undefined}
                className={linkClass(item === safePage)}
              >
                {item}
              </Link>
            </li>
          ),
        )}
        <li>
          {safePage >= totalPages ? (
            <span className={disabledClass} aria-disabled="true">
              <span className="hidden sm:inline">Siguiente</span>
              <ChevronRight className="h-4 w-4" />
            </span>
          ) : (
            <Link to={to} search={buildSearch(safePage + 1)} aria-label="Página siguiente" className={linkClass()}>
              <span className="hidden sm:inline">Siguiente</span>
              <ChevronRight className="h-4 w-4" />
            </Link>
          )}
        </li>
      </ul>
    </nav>
  );
}
