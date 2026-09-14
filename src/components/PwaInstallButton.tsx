"use client";

import { AppWindow, X } from "lucide-react";
import { useEffect, useState } from "react";
import { usePathname } from "next/navigation";

type InstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
};

type DeviceType = "desktop" | "android" | "iphone";

export default function PwaInstallButton() {
  const [installPrompt, setInstallPrompt] = useState<InstallPromptEvent | null>(null);
  const [showHelp, setShowHelp] = useState(false);
  const [isExpanded, setIsExpanded] = useState(false);
  const [deviceType, setDeviceType] = useState<DeviceType>("desktop");
  const pathname = usePathname();
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

  const isStudentDashboard = pathname === "/admin/empresa/alumnos/cursos";

  const handleInstall = async () => {
    if (!installPrompt) {
      const userAgent = navigator.userAgent.toLowerCase();
      setDeviceType(userAgent.includes("iphone") || userAgent.includes("ipad") ? "iphone" : userAgent.includes("android") ? "android" : "desktop");
      setShowHelp(true);
      return;
    }

    await installPrompt.prompt();
    const choice = await installPrompt.userChoice;
    if (choice.outcome === "accepted") {
      setInstallPrompt(null);
      setIsExpanded(false);
    }
  };

  return (
    <>
      {isStudentDashboard ? (
        <button
          type="button"
          onClick={handleInstall}
          className="fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-full bg-[#31D22D] px-4 py-3 text-sm font-semibold text-black shadow-lg shadow-black/30 transition hover:bg-[#55e852]"
          aria-label="Instalar aplicación Metaverso Otec"
        >
          <AppWindow className="h-4 w-4" />
          Instalar aplicación
        </button>
      ) : (
        <div className="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-2">
          {isExpanded && (
            <div className="w-[min(18rem,calc(100vw-2.5rem))] rounded-2xl border border-cyan-400/25 bg-[#07151b]/95 p-3 text-white shadow-2xl shadow-black/40 backdrop-blur-md">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="text-sm font-semibold">Instalar Metaverso Otec</p>
                  <p className="mt-1 text-xs text-white/55">Acceso rápido desde tu escritorio o celular.</p>
                </div>
                <button
                  type="button"
                  onClick={() => { setIsExpanded(false); setShowHelp(false); }}
                  className="rounded-full p-1 text-white/55 transition hover:bg-white/10 hover:text-white"
                  aria-label="Cerrar opciones de instalación"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>
              <button
                type="button"
                onClick={handleInstall}
                className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-cyan-300/30 bg-cyan-400/15 px-3 py-2.5 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/25"
              >
                <AppWindow className="h-4 w-4" />
                Instalar aplicación
              </button>
            </div>
          )}
          {!showHelp && (
            <button
              type="button"
              onClick={() => setIsExpanded((expanded) => !expanded)}
              className="flex h-11 w-11 items-center justify-center rounded-full border border-cyan-300/35 bg-[#07151b] text-cyan-200 shadow-lg shadow-black/35 transition hover:border-cyan-200 hover:bg-[#0b222b]"
              aria-label="Mostrar opciones para instalar la aplicación"
              title="Instalar aplicación"
            >
              <AppWindow className="h-5 w-5" />
            </button>
          )}
        </div>
      )}

      {showHelp && (
        <div
          role="dialog"
          aria-label="Instrucciones para instalar Metaverso Otec"
          className="fixed bottom-20 right-5 z-50 w-[min(23rem,calc(100vw-2.5rem))] rounded-xl border border-white/15 bg-[#151515] p-5 text-sm text-white shadow-2xl"
        >
          <button
            type="button"
            onClick={() => setShowHelp(false)}
            className="float-right text-white/60 hover:text-white"
            aria-label="Cerrar instrucciones de instalación"
          >
            <X className="h-4 w-4" />
          </button>
          <p className="pr-5 font-semibold">Instala Metaverso Otec</p>
          {deviceType === "iphone" ? (
            <ol className="mt-3 list-decimal space-y-2 pl-5 text-white/75">
              <li>Toca el botón Compartir de Safari.</li>
              <li>Selecciona “Añadir a pantalla de inicio”.</li>
              <li>Toca “Añadir” para terminar.</li>
            </ol>
          ) : deviceType === "android" ? (
            <ol className="mt-3 list-decimal space-y-2 pl-5 text-white/75">
              <li>Abre el menú de tres puntos de Chrome.</li>
              <li>Toca “Instalar aplicación” o “Añadir a pantalla de inicio”.</li>
              <li>Confirma tocando “Instalar” o “Añadir”.</li>
            </ol>
          ) : (
            <ol className="mt-3 list-decimal space-y-2 pl-5 text-white/75">
              <li>Abre el menú de tres puntos de Chrome o Edge.</li>
              <li>Selecciona “Instalar aplicación”.</li>
              <li>Confirma la instalación.</li>
            </ol>
          )}
        </div>
      )}
    </>
  );
}