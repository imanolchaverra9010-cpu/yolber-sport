import { Facebook, Instagram, Twitter } from "lucide-react";
import { BrandLogo } from "./BrandLogo";

export function SiteFooter() {
  const year = new Date().getFullYear();

  return (
    <footer className="border-t border-white/10 bg-black">
      <div className="mx-auto max-w-7xl px-6 py-14">
        <div className="flex flex-col items-center gap-6 text-center" data-aos="fade-up">
          <BrandLogo size="md" />
          <p className="font-[cursive] text-lg italic text-white/90">Luce siempre tu mejor estilo</p>
          <div className="flex gap-4">
            <a
              href="#"
              className="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 text-white transition hover:bg-white hover:text-black"
            >
              <Facebook className="h-5 w-5" />
            </a>
            <a
              href="#"
              className="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 text-white transition hover:bg-white hover:text-black"
            >
              <Instagram className="h-5 w-5" />
            </a>
            <a
              href="#"
              className="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 text-white transition hover:bg-white hover:text-black"
            >
              <Twitter className="h-5 w-5" />
            </a>
          </div>
        </div>

        <div className="mt-10 border-t border-white/10 pt-6 text-center">
          <p className="text-xs uppercase tracking-[0.3em] text-muted-foreground">
            © {year} Yolber Sport. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </footer>
  );
}
