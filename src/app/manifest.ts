import type { MetadataRoute } from "next";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "Metaverso Otec - LMS Corporativo",
    short_name: "Metaverso Otec",
    description: "Plataforma de capacitación empresarial",
    start_url: "/admin",
    display: "standalone",
    background_color: "#0a0a0a",
    theme_color: "#0a0a0a",
    lang: "es",
    icons: [
      {
        src: "/logo-metaverso.png",
        sizes: "any",
        type: "image/png",
        purpose: "any",
      },
    ],
  };
}