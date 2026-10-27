import { useQuery } from "@tanstack/react-query";
import { fetchStoreSettings } from "@/lib/shopify";

/** Digits only. Prefer Hostinger settings; fall back to Vite env / default. */
export function normalizeWhatsAppPhone(raw?: string | null): string {
  const digits = String(raw || "").replace(/\D/g, "");
  if (digits.length >= 10) return digits;
  const fromEnv = String(import.meta.env.VITE_WHATSAPP_PHONE || "").replace(/\D/g, "");
  if (fromEnv.length >= 10) return fromEnv;
  return "573207885337";
}

export function formatWhatsAppDisplay(phone: string): string {
  const d = normalizeWhatsAppPhone(phone);
  if (d.startsWith("57") && d.length >= 12) {
    return `+57 ${d.slice(2, 5)} ${d.slice(5, 8)} ${d.slice(8)}`;
  }
  return d ? `+${d}` : "";
}

export function useWhatsAppPhone() {
  const { data } = useQuery({
    queryKey: ["settings", "whatsapp"],
    queryFn: () => fetchStoreSettings(["whatsapp_phone", "contact_phone"]),
    staleTime: 60_000,
  });

  const phone = normalizeWhatsAppPhone(
    data?.whatsapp_phone || data?.contact_phone || import.meta.env.VITE_WHATSAPP_PHONE,
  );

  return {
    phone,
    display: formatWhatsAppDisplay(phone),
    href: `https://wa.me/${phone}`,
  };
}
