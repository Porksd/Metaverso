"use client";

import Image from "next/image";
import { useEffect, useState } from "react";
import { usePathname } from "next/navigation";

type InstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
};

type InstallAction = "install" | "open";

const getIsStandalone = () =>
  typeof window !== "undefined" &&
  (window.matchMedia("(display-mode: standalone)").matches ||
    (navigator as Navigator & { standalone?: boolean }).standalone === true);

export default function PwaInstallButton() {
  const [installPrompt, setInstallPrompt] = useState<InstallPromptEvent | null>(null);
  const [installAction, setInstallAction] = useState<InstallAction>("install");
  const [isInstalled] = useState(getIsStandalone);
  const [canRenderButton, setCanRenderButton] = useState(false);
  const pathname = usePathname();

  useEffect(() => {
    if (getIsStandalone()) return;

    const userAgent = navigator.userAgent.toLowerCase();
    const isIos = /iphone|ipad|ipod/.test(userAgent);
    const initialStateTimer = window.setTimeout(() => {
      setCanRenderButton(!isIos);

      if (window.localStorage.getItem("metaverso-pwa-installed") === "true") {
        setInstallAction("open");
      }
    }, 0);

    const handleBeforeInstallPrompt = (event: Event) => {
      event.preventDefault();
      setInstallPrompt(event as InstallPromptEvent);
      setInstallAction("install");
      setCanRenderButton(true);
    };

    const handleAppInstalled = () => {
      window.localStorage.setItem("metaverso-pwa-installed", "true");
      setInstallPrompt(null);
      setInstallAction("open");
      setCanRenderButton(true);
    };

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.addEventListener("appinstalled", handleAppInstalled);

    return () => {
      window.clearTimeout(initialStateTimer);
      window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
      window.removeEventListener("appinstalled", handleAppInstalled);
    };
  }, []);

  if (isInstalled || !canRenderButton) return null;

  const isStudentDashboard = pathname === "/admin/empresa/alumnos/cursos";
  const buttonText = installAction === "open" ? "Abrir en la app" : "Instalar app";

  const handleInstall = async () => {
    if (installAction === "open") {
      window.open("/admin", "_blank", "noopener,noreferrer");
      return;
    }

    if (!installPrompt) {
      window.localStorage.removeItem("metaverso-pwa-installed");
      setInstallAction("install");
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
      <span className="flex h-5 w-5 items-center justify-center rounded-full bg-white p-1">
        <Image src="/icons/app_16529957.svg" alt="" width={12} height={12} aria-hidden="true" />
      </span>
      {buttonText}
    </button>
  );
}