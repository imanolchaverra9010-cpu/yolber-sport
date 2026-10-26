import { Link } from "@tanstack/react-router";
import { cn } from "@/lib/utils";

type BrandLogoProps = {
  className?: string;
  /** Compact mark for the sticky header */
  size?: "sm" | "md" | "lg";
  withLink?: boolean;
};

const sizes = {
  sm: "h-12 w-12",
  md: "h-16 w-16",
  lg: "h-24 w-24",
};

export function BrandLogo({ className, size = "md", withLink = true }: BrandLogoProps) {
  const mark = (
    <span
      className={cn(
        "relative inline-flex overflow-hidden rounded-full border border-white/20 bg-black shadow-[0_0_0_1px_rgba(255,255,255,0.06)]",
        sizes[size],
        className,
      )}
      aria-label="Yolber Sport"
    >
      <img
        src="/logo.jpg"
        alt="Yolber Sport"
        className="h-[220%] w-[220%] max-w-none -translate-x-[27%] -translate-y-[8%] object-cover object-top"
      />
    </span>
  );

  if (!withLink) return mark;

  return (
    <Link to="/" className="inline-flex items-center gap-3 transition hover:opacity-90">
      {mark}
      <span className="hidden font-display text-2xl uppercase tracking-wider text-white sm:inline md:text-3xl">
        Yolber <span className="text-white/80">Sport</span>
      </span>
    </Link>
  );
}
