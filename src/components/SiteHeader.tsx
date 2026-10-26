import { Menu, X } from "lucide-react";
import { CartDrawer } from "./CartDrawer";
import { ProductSearch } from "./ProductSearch";
import { BrandLogo } from "./BrandLogo";
import { useState } from "react";

export function SiteHeader() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <header className="sticky top-0 z-40 border-b border-white/10 bg-background/90 backdrop-blur">
      <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-6">
        <BrandLogo size="sm" />

        <div className="flex items-center gap-4">
          <ProductSearch variant="desktop" />
          <CartDrawer />
          <button
            type="button"
            className="text-white md:hidden"
            aria-label={mobileMenuOpen ? "Cerrar búsqueda" : "Abrir búsqueda"}
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          >
            {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </button>
        </div>
      </div>

      {mobileMenuOpen && (
        <div className="border-t border-white/10 bg-background py-4 md:hidden">
          <div className="px-6">
            <ProductSearch variant="mobile" onNavigate={() => setMobileMenuOpen(false)} />
          </div>
        </div>
      )}
    </header>
  );
}
