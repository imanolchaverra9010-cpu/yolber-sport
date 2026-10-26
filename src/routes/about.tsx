import { createFileRoute } from "@tanstack/react-router";
import { Trophy, Users, Heart, Zap } from "lucide-react";

export const Route = createFileRoute("/about")({
  head: () => ({
    meta: [
      { title: "Nosotros — Yolber Sport" },
      { name: "description", content: "Conoce la historia de Yolber Sport, nuestra pasión por el deporte y el estilo urbano." },
      { property: "og:title", content: "Nosotros — Yolber Sport" },
      { property: "og:description", content: "Conoce la historia de Yolber Sport." },
    ],
  }),
  component: About,
});

function About() {
  return (
    <main>
      <section className="relative isolate flex min-h-[60vh] items-center overflow-hidden bg-gradient-to-b from-gray-900 to-gray-800">
        <div className="mx-auto w-full max-w-7xl px-6 text-center" data-aos="zoom-in">
          <h1 className="font-display text-6xl uppercase leading-[0.9] text-gold-gradient md:text-8xl">Sobre Nosotros</h1>
          <p className="mx-auto mt-6 max-w-2xl text-xl text-muted-foreground" data-aos="fade-up" data-aos-delay="120">
            Pasión por el deporte, estilo urbano, compromiso con la calidad
          </p>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-6 py-24">
        <div className="grid gap-12 md:grid-cols-2">
          <div data-aos="fade-right">
            <h2 className="font-display text-4xl uppercase text-gold-gradient mb-6">Nuestra Historia</h2>
            <p className="text-lg leading-relaxed text-muted-foreground mb-4">
              Yolber Sport nació de la pasión por fusionar el rendimiento deportivo con el estilo urbano. 
              Desde nuestros inicios, nos hemos comprometido a crear ropa y calzado que no solo sea funcional, 
              sino que también exprese la personalidad de quienes la usan.
            </p>
            <p className="text-lg leading-relaxed text-muted-foreground">
              Cada pieza está diseñada pensando en quienes viven la intensidad del deporte y la libertad 
              de la calle. Creemos que el atuendo deportivo debe ser una extensión de tu actitud y tu energía.
            </p>
          </div>
          <div className="grid grid-cols-2 gap-6">
            <div className="border border-gold/20 bg-card p-6 rounded-lg" data-aos="fade-up" data-aos-delay="50">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gold/20">
                <Trophy className="h-6 w-6 text-gold" />
              </div>
              <h3 className="font-display text-xl uppercase text-gold mb-2">Calidad Premium</h3>
              <p className="text-muted-foreground text-sm">
                Materiales de alta selección para máximo rendimiento y durabilidad.
              </p>
            </div>
            <div className="border border-gold/20 bg-card p-6 rounded-lg" data-aos="fade-up" data-aos-delay="120">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gold/20">
                <Users className="h-6 w-6 text-gold" />
              </div>
              <h3 className="font-display text-xl uppercase text-gold mb-2">Comunidad</h3>
              <p className="text-muted-foreground text-sm">
                Más que una marca, somos una comunidad de deportistas y soñadores.
              </p>
            </div>
            <div className="border border-gold/20 bg-card p-6 rounded-lg" data-aos="fade-up" data-aos-delay="190">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gold/20">
                <Heart className="h-6 w-6 text-gold" />
              </div>
              <h3 className="font-display text-xl uppercase text-gold mb-2">Pasión</h3>
              <p className="text-muted-foreground text-sm">
                Diseñamos con amor por el deporte y dedicación al detalle.
              </p>
            </div>
            <div className="border border-gold/20 bg-card p-6 rounded-lg" data-aos="fade-up" data-aos-delay="260">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gold/20">
                <Zap className="h-6 w-6 text-gold" />
              </div>
              <h3 className="font-display text-xl uppercase text-gold mb-2">Innovación</h3>
              <p className="text-muted-foreground text-sm">
                Constantemente evolucionamos para ofrecerte lo mejor en cada temporada.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="border-t border-gold/20 bg-gray-800/50 py-24">
        <div className="mx-auto max-w-7xl px-6">
          <h2 className="font-display text-4xl uppercase text-center text-gold-gradient mb-12" data-aos="fade-up">Nuestros Valores</h2>
          <div className="grid gap-8 md:grid-cols-3">
            <div className="text-center" data-aos="fade-up" data-aos-delay="80">
              <h3 className="font-display text-2xl uppercase text-white mb-4">Excelencia</h3>
              <p className="text-muted-foreground">
                No conformarnos con lo suficiente es lo que nos impulsa a superar expectativas en cada producto.
              </p>
            </div>
            <div className="text-center" data-aos="fade-up" data-aos-delay="160">
              <h3 className="font-display text-2xl uppercase text-white mb-4">Autenticidad</h3>
              <p className="text-muted-foreground">
                Celebramos la individualidad. Cada diseño refleja nuestra identidad única y genuina.
              </p>
            </div>
            <div className="text-center" data-aos="fade-up" data-aos-delay="240">
              <h3 className="font-display text-2xl uppercase text-white mb-4">Compromiso</h3>
              <p className="text-muted-foreground">
                Con nuestros clientes, con el deporte, con crear experiencias que transformen.
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
