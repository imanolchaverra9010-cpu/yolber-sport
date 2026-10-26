import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { useEffect, useMemo } from "react";
import { Loader2, X } from "lucide-react";
import { fetchProducts } from "@/lib/shopify";
import { ProductCard } from "@/components/ProductCard";
import { HeroBanners } from "@/components/HeroBanners";
import { CategoryRail } from "@/components/CategoryRail";
import { ProductFilters } from "@/components/ProductFilters";
import {
  ProductPagination,
  PRODUCTS_PER_PAGE,
  paginateItems,
} from "@/components/ProductPagination";
import {
  applyProductFilters,
  filtersToSearchParams,
  hasActiveFilters,
  parseFilterState,
  productFilterSearchSchema,
  type ProductFilterState,
} from "@/lib/productFilters";
import { z } from "zod";
import { refreshAos } from "@/hooks/useAos";

const searchSchema = z.object({
  search: z.string().optional().catch(undefined),
  page: z.coerce.number().int().min(1).optional().catch(1),
  ...productFilterSearchSchema,
});

export const Route = createFileRoute("/")({
  validateSearch: searchSchema,
  head: () => ({
    meta: [
      { title: "Yolber Sport — Luce siempre tu mejor estilo" },
      { name: "description", content: "Ropa deportiva y sneakers premium. Luce siempre tu mejor estilo." },
      { property: "og:title", content: "Yolber Sport — Luce siempre tu mejor estilo" },
      { property: "og:description", content: "Ropa deportiva y sneakers premium. Luce siempre tu mejor estilo." },
    ],
  }),
  component: Index,
});

function Index() {
  const navigate = useNavigate({ from: "/" });
  const search = Route.useSearch();
  const { search: searchQuery = "", page = 1 } = search;
  const filterState = useMemo(
    () =>
      parseFilterState({
        talla: search.talla,
        min: search.min,
        max: search.max,
        sort: search.sort,
        stock: search.stock,
      }),
    [search.talla, search.min, search.max, search.sort, search.stock],
  );

  const { data: products, isLoading } = useQuery({
    queryKey: ["products"],
    queryFn: () => fetchProducts(100),
  });

  const searchedProducts = useMemo(() => {
    if (!products) return [];
    if (!searchQuery) return products;
    const q = searchQuery.toLowerCase();
    return products.filter(
      (p) =>
        p.node.title.toLowerCase().includes(q) ||
        p.node.description.toLowerCase().includes(q) ||
        p.node.handle.toLowerCase().includes(q),
    );
  }, [products, searchQuery]);

  const filteredProducts = useMemo(
    () => applyProductFilters(searchedProducts, filterState),
    [searchedProducts, filterState],
  );

  const totalItems = filteredProducts.length;
  const pageItems = paginateItems(filteredProducts, page, PRODUCTS_PER_PAGE);

  useEffect(() => {
    const id = window.setTimeout(() => refreshAos(), 160);
    return () => window.clearTimeout(id);
  }, [pageItems, isLoading, page, filterState]);

  const baseSearch = () => ({
    ...(searchQuery ? { search: searchQuery } : {}),
    ...filtersToSearchParams(filterState),
  });

  const buildSearch = (nextPage: number) => ({
    ...baseSearch(),
    ...(nextPage > 1 ? { page: nextPage } : {}),
  });

  const setFilters = (next: ProductFilterState) => {
    navigate({
      search: {
        ...(searchQuery ? { search: searchQuery } : {}),
        ...filtersToSearchParams(next),
      },
      replace: true,
    });
  };

  return (
    <main>
      {!searchQuery && (
        <>
          <div data-aos="fade-up">
            <HeroBanners />
          </div>
          <div data-aos="fade-up" data-aos-delay="100">
            <CategoryRail />
          </div>
        </>
      )}

      <section id="coleccion" className="mx-auto max-w-7xl px-6 py-16 md:py-24">
        <div className="mb-12 flex items-end justify-between gap-6" data-aos="fade-up">
          <h2 className="font-display text-5xl uppercase md:text-7xl">
            {searchQuery ? (
              <>
                Resultados <span className="text-gold-gradient">de búsqueda</span>
              </>
            ) : (
              <>
                Nueva <span className="text-gold-gradient">colección</span>
              </>
            )}
          </h2>
          <span className="hidden text-xs uppercase tracking-[0.3em] text-muted-foreground md:block">
            Sueña · Viste · Brilla
          </span>
        </div>

        {searchQuery && (
          <div className="mb-8 flex flex-wrap items-center gap-3 text-muted-foreground">
            <span>
              Buscando: <span className="text-gold">{searchQuery}</span>
              {totalItems > 0 && <span className="ml-2">({totalItems} productos)</span>}
            </span>
            <Link
              to="/"
              search={filtersToSearchParams(filterState)}
              className="inline-flex items-center gap-1 rounded border border-gold/30 px-2 py-1 text-xs uppercase tracking-wider text-gold hover:bg-gold/10"
            >
              <X className="h-3 w-3" /> Limpiar búsqueda
            </Link>
          </div>
        )}

        {!isLoading && (products?.length ?? 0) > 0 && (
          <ProductFilters
            products={searchedProducts}
            value={filterState}
            onChange={setFilters}
            resultCount={totalItems}
          />
        )}

        {isLoading ? (
          <div className="flex justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-gold" />
          </div>
        ) : !totalItems ? (
          <div className="border border-dashed border-gold/30 py-20 text-center text-muted-foreground">
            {searchQuery || hasActiveFilters(filterState)
              ? "No hay productos con estos filtros"
              : "No hay productos disponibles"}
          </div>
        ) : (
          <>
            <div className="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
              {pageItems.map((p, i) => (
                <ProductCard key={p.node.id} product={p} aosDelay={Math.min(i * 80, 400)} />
              ))}
            </div>
            <ProductPagination
              page={page}
              totalItems={totalItems}
              buildSearch={buildSearch}
              to="/"
            />
          </>
        )}
      </section>

      <footer className="border-t border-gold/20 py-10 text-center text-xs uppercase tracking-[0.3em] text-muted-foreground">
        © {new Date().getFullYear()} Yolber Sport · Luce siempre tu mejor estilo
      </footer>
    </main>
  );
}
