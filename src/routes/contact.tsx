import { createFileRoute } from "@tanstack/react-router";
import { Mail, Phone, MapPin, Send } from "lucide-react";

export const Route = createFileRoute("/contact")({
  head: () => ({
    meta: [
      { title: "Contacto — Yolber Sport" },
      { name: "description", content: "Contáctanos para cualquier consulta. Estamos aquí para ayudarte." },
      { property: "og:title", content: "Contacto — Yolber Sport" },
      { property: "og:description", content: "Contáctanos para cualquier consulta." },
    ],
  }),
  component: Contact,
});

function Contact() {
  const whatsappPhone = import.meta.env.VITE_WHATSAPP_PHONE || "573000000000";

  return (
    <main>
      <section className="relative isolate flex min-h-[60vh] items-center overflow-hidden bg-gradient-to-b from-gray-900 to-gray-800">
        <div className="mx-auto w-full max-w-7xl px-6 text-center" data-aos="zoom-in">
          <h1 className="font-display text-6xl uppercase leading-[0.9] text-gold-gradient md:text-8xl">Contacto</h1>
          <p className="mx-auto mt-6 max-w-2xl text-xl text-muted-foreground" data-aos="fade-up" data-aos-delay="120">
            ¿Tienes alguna pregunta? Estamos aquí para ayudarte
          </p>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-6 py-24">
        <div className="grid gap-12 md:grid-cols-2">
          <div data-aos="fade-right">
            <h2 className="font-display text-4xl uppercase text-gold-gradient mb-8">Información de Contacto</h2>
            <div className="space-y-6">
              <div className="flex items-start gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gold/20 flex-shrink-0">
                  <Phone className="h-5 w-5 text-gold" />
                </div>
                <div>
                  <h3 className="font-semibold text-white mb-1">WhatsApp</h3>
                  <p className="text-muted-foreground">+57 300 000 0000</p>
                  <a
                    href={`https://wa.me/${whatsappPhone}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-gold hover:text-gold/80 text-sm mt-1 inline-block"
                  >
                    Enviar mensaje →
                  </a>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gold/20 flex-shrink-0">
                  <Mail className="h-5 w-5 text-gold" />
                </div>
                <div>
                  <h3 className="font-semibold text-white mb-1">Email</h3>
                  <p className="text-muted-foreground">info@yobersport.com</p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gold/20 flex-shrink-0">
                  <MapPin className="h-5 w-5 text-gold" />
                </div>
                <div>
                  <h3 className="font-semibold text-white mb-1">Ubicación</h3>
                  <p className="text-muted-foreground">Colombia</p>
                  <p className="text-muted-foreground text-sm">Envíos a todo el país</p>
                </div>
              </div>
            </div>

            <div className="mt-12 border-t border-gold/20 pt-8">
              <h3 className="font-display text-2xl uppercase text-white mb-4">Horario de Atención</h3>
              <div className="space-y-2 text-muted-foreground">
                <p>Lunes a Viernes: 9:00 AM - 6:00 PM</p>
                <p>Sábados: 10:00 AM - 4:00 PM</p>
                <p>Domingos: Cerrado</p>
              </div>
            </div>
          </div>

          <div className="border border-gold/20 bg-card p-8 rounded-lg" data-aos="fade-left" data-aos-delay="100">
            <h2 className="font-display text-3xl uppercase text-gold-gradient mb-6">Envíanos un Mensaje</h2>
            <form className="space-y-6">
              <div>
                <label className="block text-sm text-muted-foreground mb-2">Nombre</label>
                <input
                  type="text"
                  className="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3 text-white focus:outline-none focus:border-gold transition"
                  placeholder="Tu nombre"
                />
              </div>
              <div>
                <label className="block text-sm text-muted-foreground mb-2">Email</label>
                <input
                  type="email"
                  className="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3 text-white focus:outline-none focus:border-gold transition"
                  placeholder="tu@email.com"
                />
              </div>
              <div>
                <label className="block text-sm text-muted-foreground mb-2">Mensaje</label>
                <textarea
                  rows={4}
                  className="w-full bg-gray-800 border border-gray-700 rounded px-4 py-3 text-white focus:outline-none focus:border-gold transition resize-none"
                  placeholder="¿En qué podemos ayudarte?"
                />
              </div>
              <button
                type="button"
                onClick={() => window.open(`https://wa.me/${whatsappPhone}`, "_blank")}
                className="w-full bg-gold text-black font-bold py-3 rounded hover:bg-gold/90 transition flex items-center justify-center gap-2"
              >
                <Send className="h-4 w-4" />
                Enviar por WhatsApp
              </button>
            </form>
          </div>
        </div>
      </section>
    </main>
  );
}
