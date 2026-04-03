"use client";

import { useEffect, useRef, useState } from "react";
import { CheckCircle } from "lucide-react";

interface GeniallyEmbedProps {
    src: string; // URL local (index.html) o URL externa
    title?: string;
    onComplete?: () => void;
    hotspotConfig?: {
        top: string;
        right: string;
        width: string;
        height: string;
    };
}

export default function GeniallyEmbed({ src, title, onComplete, hotspotConfig }: GeniallyEmbedProps) {
    const iframeRef = useRef<HTMLIFrameElement>(null);
    const [showHotspot, setShowHotspot] = useState(false);

    // Simular tiempo de lectura/interacción para mostrar el hotspot si es necesario
    useEffect(() => {
        if (hotspotConfig) {
            const timer = setTimeout(() => {
                setShowHotspot(true);
            }, 5000); // Aparece a los 5 segundos por defecto
            return () => clearTimeout(timer);
        }
    }, [hotspotConfig]);

    return (
        <div className="relative w-full max-w-6xl mx-auto aspect-video bg-black/5 rounded-2xl overflow-hidden shadow-2xl border border-white/10">
            {title && (
                <div className="absolute top-4 left-4 z-10 bg-black/50 backdrop-blur-md px-4 py-2 rounded-lg text-white font-bold text-sm">
                    {title}
                </div>
            )}

            <iframe
                ref={iframeRef}
                src={src}
                className="w-full h-full border-0"
                allowFullScreen
                loading="lazy"
            />

            {/* Hotspot invisible/visible para continuar */}
            {hotspotConfig && showHotspot && (
                <button
                    onClick={onComplete}
                    className="absolute z-50 bg-brand/20 hover:bg-brand/40 border-2 border-brand/50 rounded-lg transition-all animate-pulse flex items-center justify-center group"
                    style={{
                        top: hotspotConfig.top,
                        right: hotspotConfig.right,
                        width: hotspotConfig.width,
                        height: hotspotConfig.height,
                    }}
                    title="Haga clic aquí para continuar"
                >
                    <CheckCircle className="w-8 h-8 text-brand opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-lg" />
                </button>
            )}
        </div>
    );
}
