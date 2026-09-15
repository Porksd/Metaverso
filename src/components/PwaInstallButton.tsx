"use client";

import { AppWindow } from "lucide-react";
import { useEffect, useState } from "react";
import { usePathname } from "next/navigation";

type InstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
};

type InstallAction = "install" | "open";

export default function PwaInstallButton() {
  const [installPrompt, setInstallPrompt] = useState<InstallPromptEvent | null>(null);
  const [installAction, setInstallAction] = useState<InstallAction>("install");
  const [isInstalled, setIsInstalled] = useState(false);
  const pathname = usePathname();

  useEffect(() => {
    const isStandalone =
      window.matchMedia("(display-mode: standalone)").matches ||
      (navigator as Navigator & { standalone?: boolean }).standalone === true;

    setIsInstalled(isStandalone);
    if (isStandalone) return;

    let nativePromptAvailable = false;
    const userAgent = navigator.userAgent.toLowerCase();
    const isIos = /iphone|ipad|ipod/.test(userAgent);

    const handleBeforeInstallPrompt = (event: Event) => {
      event.preventDefault();
      nativePromptAvailable = true;
      setInstallPrompt(event as InstallPromptEvent);
      setInstallAction("install");
    };

    const handleAppInstalled = () => {
      window.localStorage.setItem("metaverso-pwa-installed", "true");
      setInstallPrompt(null);
      setInstallAction("open");
    };

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.addEventListener("appinstalled", handleAppInstalled);

    const fallbackTimer = window.setTimeout(() => {
      if (!nativePromptAvailable && !isIos) {
        const wasInstalled = window.localStorage.getItem("metaverso-pwa-installed") === "true";
        setInstallAction(wasInstalled ? "open" : "install");
      }
    }, 1200);

    return () => {
      window.clearTimeout(fallbackTimer);
      window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
      window.removeEventListener("appinstalled", handleAppInstalled);
    };
  }, []);

  if (isInstalled) return null;

  const isStudentDashboard = pathname === "/admin/empresa/alumnos/cursos";
  const buttonText = installAction === "open" ? "Abrir en la app" : "Instalar app";

  const handleInstall = async () => {
    if (installAction === "open") {
      window.open("/admin", "_blank", "noopener,noreferrer");
      return;
    }

    if (!installPrompt) {
      return;
    }

    await installPrompt.prompt();
    const choice = await installPrompt.userChoice;
    if (choice.outcome === "accepted") {
      window.localStorage.setItem("metaverso-pwa-installed", "true");
      setInstallPrompt(null);
      setInstallAction("open");
    }
  };

  return (
    <button
      type="button"
      onClick={handleInstall}
      className={
        isStudentDashboard
          ? "fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-full bg-[#31D22D] px-4 py-3 text-sm font-semibold text-black shadow-lg shadow-black/30 transition hover:bg-[#55e852]"
          : "fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-full border border-cyan-300/35 bg-[#07151b] px-4 py-3 text-sm font-semibold text-cyan-100 shadow-lg shadow-black/35 transition hover:border-cyan-200 hover:bg-[#0b222b]"
      }
      aria-label={`${buttonText} Metaverso Otec`}
    >
      <AppWindow className="h-4 w-4" />
      {buttonText}
    </button>
  );
}