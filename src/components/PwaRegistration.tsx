"use client";

import { useEffect } from "react";

export default function PwaRegistration() {
  useEffect(() => {
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/sw.js").catch(() => {
        // PWA installation remains available when service worker registration is unavailable.
      });
    }
  }, []);

  return null;
}