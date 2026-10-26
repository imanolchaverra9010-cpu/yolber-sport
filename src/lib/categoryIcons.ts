import {
  Footprints,
  Gem,
  LayoutGrid,
  type LucideIcon,
  PersonStanding,
  Shirt,
  Tag,
  Watch,
  Wind,
  Zap,
} from "lucide-react";

const BY_KEY: Record<string, LucideIcon> = {
  shirt: Shirt,
  hoodie: Shirt,
  hoodies: Shirt,
  footprints: Footprints,
  sneaker: Footprints,
  sneakers: Footprints,
  shoes: Footprints,
  zapatos: Footprints,
  personstanding: PersonStanding,
  joggers: PersonStanding,
  pants: PersonStanding,
  pantalones: PersonStanding,
  jeans: PersonStanding,
  mochos: PersonStanding,
  mocho: PersonStanding,
  shorts: PersonStanding,
  wind: Wind,
  tops: Wind,
  top: Wind,
  camisetas: Shirt,
  camiseta: Shirt,
  watch: Watch,
  gem: Gem,
  accesorios: Watch,
  accessories: Watch,
  zap: Zap,
  tag: Tag,
  layoutgrid: LayoutGrid,
  all: LayoutGrid,
};

export const CATEGORY_ICON_OPTIONS = [
  { value: "shirt", label: "Hoodie / Camiseta" },
  { value: "footprints", label: "Sneakers / Calzado" },
  { value: "personStanding", label: "Joggers / Pants" },
  { value: "wind", label: "Tops / Ligero" },
  { value: "watch", label: "Accesorios" },
  { value: "gem", label: "Premium" },
  { value: "zap", label: "Destacado" },
  { value: "tag", label: "General" },
] as const;

function normalizeKey(value: string) {
  return value.trim().toLowerCase().replace(/[^a-z0-9]/g, "");
}

/** Resolve Lucide icon from admin icon key, slug, or legacy image URL. */
export function getCategoryIcon(iconOrSlug: string, slug?: string): LucideIcon {
  const raw = (iconOrSlug || "").trim();
  const isUrl = /^https?:\/\//i.test(raw) || raw.includes("/") || raw.includes(".");

  if (!isUrl && raw) {
    const byKey = BY_KEY[normalizeKey(raw)];
    if (byKey) return byKey;
  }

  if (slug) {
    const bySlug = BY_KEY[normalizeKey(slug)];
    if (bySlug) return bySlug;
  }

  // Try extracting keyword from legacy unsplash/filename
  const hay = `${raw} ${slug ?? ""}`.toLowerCase();
  if (hay.includes("hoodie") || hay.includes("shirt")) return Shirt;
  if (hay.includes("sneaker") || hay.includes("shoe") || hay.includes("zapato")) return Footprints;
  if (hay.includes("jogger") || hay.includes("pant")) return PersonStanding;
  if (hay.includes("top")) return Wind;
  if (hay.includes("accesor") || hay.includes("watch")) return Watch;

  return Tag;
}
