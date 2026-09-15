import type { Metadata } from "next";
import PwaRegistration from "@/components/PwaRegistration";
import PwaInstallButton from "@/components/PwaInstallButton";
import "./globals.css";

export const metadata: Metadata = {
  title: "Metaverso Otec - LMS Corporativo",
  description: "Plataforma premium de capacitación empresarial",
  manifest: "/manifest.webmanifest",
  applicationName: "Metaverso Otec",
  appleWebApp: {
    capable: true,
    title: "Metaverso Otec",
    statusBarStyle: "black-translucent",
  },
  icons: {
    icon: [
      { url: "/icons/icon-192.png", sizes: "192x192", type: "image/png" },
      { url: "/icons/icon-512.png", sizes: "512x512", type: "image/png" },
    ],
    apple: [{ url: "/icons/apple-touch-icon.png", sizes: "180x180", type: "image/png" }],
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="es" className="dark">
      <body className="antialiased text-white">
        <PwaRegistration />
        <PwaInstallButton />
        {children}
      </body>
    </html>
  );
}
