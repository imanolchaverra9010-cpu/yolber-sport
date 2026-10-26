import { useEffect, useRef } from "react";
import { useRouterState } from "@tanstack/react-router";

type AosApi = {
  init: (options?: Record<string, unknown>) => void;
  refresh: () => void;
  refreshHard: () => void;
};

let aosInstance: AosApi | null = null;

/** Safe to call from route effects after product lists change. */
export function refreshAos() {
  if (typeof window === "undefined") return;
  aosInstance?.refreshHard();
}

export function useAos() {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const search = useRouterState({ select: (s) => s.location.searchStr });
  const aosRef = useRef<AosApi | null>(null);

  useEffect(() => {
    let cancelled = false;
    let timeoutId = 0;
    let idleId = 0;

    async function setup() {
      const [{ default: AOS }] = await Promise.all([
        import("aos") as Promise<{ default: AosApi }>,
        import("aos/dist/aos.css"),
      ]);
      if (cancelled) return;

      AOS.init({
        duration: 700,
        easing: "ease-out-cubic",
        once: true,
        offset: 40,
        mirror: false,
      });
      AOS.refreshHard();
      aosInstance = AOS;
      aosRef.current = AOS;

      // Mark ready only after animate classes are applied so content never stays hidden
      requestAnimationFrame(() => {
        if (!cancelled) document.documentElement.classList.add("aos-ready");
      });
    }

    // Defer past React hydration. AOS adds `aos-init` to the DOM; if that
    // runs while child routes are still hydrating, React reports a mismatch.
    timeoutId = window.setTimeout(() => {
      if (cancelled) return;
      const ric = window.requestIdleCallback?.bind(window);
      if (ric) {
        idleId = ric(() => {
          void setup();
        }, { timeout: 400 });
      } else {
        void setup();
      }
    }, 120);

    return () => {
      cancelled = true;
      window.clearTimeout(timeoutId);
      if (idleId && window.cancelIdleCallback) window.cancelIdleCallback(idleId);
    };
  }, []);

  useEffect(() => {
    const aos = aosRef.current ?? aosInstance;
    if (!aos) return;
    const id = window.setTimeout(() => aos.refreshHard(), 60);
    return () => window.clearTimeout(id);
  }, [pathname, search]);
}
