import { Building2 } from "lucide-react";

type CompanyLogoProps = {
    src?: string | null;
    lightSrc?: string | null;
    darkSrc?: string | null;
    alt: string;
    surface?: "light" | "dark";
    frameClassName?: string;
    imageClassName?: string;
    iconClassName?: string;
};

function resolveLogoSource({
    src,
    lightSrc,
    darkSrc,
    surface
}: Pick<CompanyLogoProps, "src" | "lightSrc" | "darkSrc" | "surface">) {
    if (surface === "dark") {
        return lightSrc || src || darkSrc || null;
    }

    return darkSrc || src || lightSrc || null;
}

export default function CompanyLogo({
    src,
    lightSrc,
    darkSrc,
    alt,
    surface = "light",
    frameClassName = "w-24 h-24 rounded-3xl p-4",
    imageClassName = "max-w-full max-h-full object-contain",
    iconClassName = "w-10 h-10 text-slate-700"
}: CompanyLogoProps) {
    const resolvedSrc = resolveLogoSource({ src, lightSrc, darkSrc, surface });
    const isDarkSurface = surface === "dark";

    return (
        <div
            className={`relative isolate overflow-hidden backdrop-blur-xl ${
                isDarkSurface
                    ? "border border-white/12 bg-slate-950/72 shadow-[0_20px_60px_rgba(2,6,23,0.45)]"
                    : "border border-white/20 bg-slate-100/90 shadow-[0_18px_50px_rgba(2,6,23,0.28)]"
            } ${frameClassName}`}
        >
            <div
                className={`absolute inset-0 ${
                    isDarkSurface
                        ? "bg-[linear-gradient(145deg,rgba(15,23,42,0.96),rgba(2,6,23,0.9))]"
                        : "bg-[linear-gradient(145deg,rgba(255,255,255,0.98),rgba(226,232,240,0.9))]"
                }`}
            />
            <div
                className={`absolute inset-0 ${
                    isDarkSurface
                        ? "bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.14),transparent_58%)] opacity-80"
                        : "bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.95),transparent_58%)] opacity-90"
                }`}
            />
            <div className={`absolute inset-[1px] rounded-[inherit] ${isDarkSurface ? "border border-white/8" : "border border-slate-900/6"}`} />
            <div className="relative z-10 flex h-full w-full items-center justify-center">
                {resolvedSrc ? (
                    <img
                        src={resolvedSrc}
                        alt={alt}
                        className={`${imageClassName} drop-shadow-[0_1px_1px_rgba(15,23,42,0.45)] drop-shadow-[0_8px_18px_rgba(15,23,42,0.18)]`}
                    />
                ) : (
                    <Building2 className={iconClassName} />
                )}
            </div>
        </div>
    );
}