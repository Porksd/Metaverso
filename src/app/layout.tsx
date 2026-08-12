import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Metaverso Otec - LMS Corporativo",
  description: "Plataforma premium de capacitación empresarial",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="es" className="dark">
      <body className="antialiased text-white">
        {children}
      </body>
    </html>
  );
}
