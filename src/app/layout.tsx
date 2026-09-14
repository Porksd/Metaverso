import type { Metadata } from "next";
import PwaRegistration from "@/components/PwaRegistration";
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
    icon: "/logo-metaverso.png",
    apple: "/logo-metaverso.png",
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
        {children}
      </body>
    </html>
  );
}
