import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { useEffect, useMemo } from "react";
import { Loader2 } from "lucide-react";
import { fetchProducts, fetchStoreSettings, type ShopCategory } from "@/lib/shopify";
import { ProductCard } from "@/components/ProductCard";
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
  parseFilterState,
  productFilterSearchSchema,
  type ProductFilterState,
} from "@/lib/productFilters";
import { z } from "zod";
import { refreshAos } from "@/hooks/useAos";

const searchSchema = z.object({
  cat: z.string().optional(),
  page: z.coerce.number().int().min(1).optional().catch(1),
  ...productFilterSearchSchema,
});

export const Route = createFileRoute("/categories")({
  validateSearch: searchSchema,
  head: () => ({
    meta: [
      { title: "Categorías — Yolber Sport" },
      { name: "description", content: "Explora nuestras categorías de ropa y calzado deportivo." },
      { property: "og:title", content: "Categorías — Yolber Sport" },
      { property: "og:description", content: "Explora nuestras categorías." },
    ],
  }),
  component: Categories,
});

function Categories() {
  const navigate = useNavigate({ from: "/categories" });
  const search = Route.useSearch();
  const { cat: activeSlug, page = 1 } = search;

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

  const { data: storeSettings } = useQuery({
    queryKey: ["settings", "categories-banner"],
    queryFn: () =>
      fetchStoreSettings([
        "categories_banner_image",
        "categories_banner_title",
        "categories_banner_subtitle",
      ]),
  });

  const bannerImage = storeSettings?.categories_banner_image || "";
  const bannerTitle = storeSettings?.categories_banner_title || "Categorías";
  const bannerSubtitle =
    storeSettings?.categories_banner_subtitle ||
    "Elige una categoría e inicia tu próximo look";

  const categoryProducts = useMemo(() => {
    if (!products) return [];
    if (!activeSlug) return products;
    return products.filter((p) => {
      const slug = (p.node.categorySlug || "").toLowerCase();
      if (slug && slug === activeSlug.toLowerCase()) return true;
      const haystack = `${p.node.title} ${p.node.handle} ${p.node.description}`.toLowerCase();
      return (
        haystack.includes(activeSlug.toLowerCase().replace(/-/g, " ")) ||
        haystack.includes(activeSlug.toLowerCase())
      );
    });
  }, [products, activeSlug]);

  const filteredProducts = useMemo(
    () => applyProductFilters(categoryProducts, filterState),
    [categoryProducts, filterState],
  );

  const totalItems = filteredProducts.length;
  const pageItems = paginateItems(filteredProducts, page, PRODUCTS_PER_PAGE);

  useEffect(() => {
    const id = window.setTimeout(() => refreshAos(), 160);
    return () => window.clearTimeout(id);
  }, [pageItems, isLoading, page, activeSlug, filterState]);

  const selectCategory = (category: ShopCategory | null) => {
    navigate({
      search: {
        ...(category ? { cat: category.slug } : {}),
        ...filtersToSearchParams(filterState),
      },
    });
  };

  const setFilters = (next: ProductFilterState) => {
    navigate({
      search: {
        ...(activeSlug ? { cat: activeSlug } : {}),
        ...filtersToSearchParams(next),
      },
      replace: true,
    });
  };

  const buildSearch = (nextPage: number) => ({
    ...(activeSlug ? { cat: activeSlug } : {}),
    ...filtersToSearchParams(filterState),
    ...(nextPage > 1 ? { page: nextPage } : {}),
  });

  return (
    <main>
      <section className="relative isolate overflow-hidden bg-gradient-to-b from-gray-900 to-gray-800 py-16 md:py-24">
        {bannerImage ? (
          <>
            <img
              src={bannerImage}
              alt=""
              className="absolute inset-0 h-full w-full object-cover"
            />
            <div className="absolute inset-0 bg-gradient-to-b from-black/75 via-black/55 to-background" />
          </>
        ) : null}
        <div className="relative mx-auto w-full max-w-7xl px-6 text-center" data-aos="zoom-in">
          <h1 className="font-display text-6xl uppercase leading-[0.9] text-gold-gradient md:text-8xl">
            {bannerTitle}
          </h1>
          <p
            className="mx-auto mt-6 max-w-2xl text-xl text-muted-foreground"
            data-aos="fade-up"
            data-aos-delay="120"
          >
            {bannerSubtitle}
          </p>
        </div>
      </section>

      <div data-aos="fade-up">
        <CategoryRail activeSlug={activeSlug ?? null} onSelect={selectCategory} />
      </div>

      <section className="mx-auto max-w-7xl px-6 pb-16">
        <h2 className="mb-8 font-display text-4xl uppercase text-gold-gradient" data-aos="fade-up">
          {activeSlug ? `Categoría: ${activeSlug}` : "Todos los productos"}
        </h2>

        {!isLoading && (products?.length ?? 0) > 0 && (
          <ProductFilters
            products={categoryProducts}
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
            No hay productos con estos filtros
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
              to="/categories"
            />
          </>
        )}
      </section>
    </main>
  );
}
