"use client";

import { Download, X } from "lucide-react";
import { useEffect, useState } from "react";

type InstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
};

export default function PwaInstallButton() {
  const [installPrompt, setInstallPrompt] = useState<InstallPromptEvent | null>(null);
  const [showHelp, setShowHelp] = useState(false);
  const [isInstalled] = useState(() =>
    typeof window !== "undefined" && window.matchMedia("(display-mode: standalone)").matches
  );

  useEffect(() => {
    const handleBeforeInstallPrompt = (event: Event) => {
      event.preventDefault();
      setInstallPrompt(event as InstallPromptEvent);
    };

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    return () => window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
  }, []);

  if (isInstalled) return null;

  const handleInstall = async () => {
    if (!installPrompt) {
      setShowHelp(true);
      return;
    }

    await installPrompt.prompt();
    const choice = await installPrompt.userChoice;
    if (choice.outcome === "accepted") setInstallPrompt(null);
  };

  return (
    <>
      <button
        type="button"
        onClick={handleInstall}
        className="fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-full bg-[#31D22D] px-4 py-3 text-sm font-semibold text-black shadow-lg shadow-black/30 transition hover:bg-[#55e852]"
        aria-label="Instalar aplicación Metaverso Otec"
      >
        <Download className="h-4 w-4" />
        Instalar app
      </button>

      {showHelp && (
        <div className="fixed bottom-20 right-5 z-50 w-[min(22rem,calc(100vw-2.5rem))] rounded-xl border border-white/15 bg-[#151515] p-4 text-sm text-white shadow-2xl">
          <button
            type="button"
            onClick={() => setShowHelp(false)}
            className="float-right text-white/60 hover:text-white"
            aria-label="Cerrar instrucciones de instalación"
          >
            <X className="h-4 w-4" />
          </button>
          <p className="pr-5 font-semibold">Instala Metaverso Otec</p>
          <p className="mt-2 text-white/70">
            En Chrome o Edge abre el menú ⋮ y selecciona “Instalar aplicación”. En iPhone usa Compartir y luego “Añadir a pantalla de inicio”.
          </p>
        </div>
      )}
    </>
  );
}