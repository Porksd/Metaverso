"use client";

import { useEffect, useRef } from "react";
import Image from "next/image";

interface AudioTextImageProps {
    text: string;
    audioSrc: string;
    imageSrc: string;
    onComplete?: () => void;
}

export default function AudioTextImage({ text, audioSrc, imageSrc, onComplete }: AudioTextImageProps) {
    const audioRef = useRef<HTMLAudioElement>(null);

    useEffect(() => {
        // Auto-play audio when mounted
        if (audioRef.current) {
            audioRef.current.play().catch(e => console.log("Autoplay prevented:", e));
        }
    }, [audioSrc]);

    return (
        <div className="flex flex-col md:flex-row gap-8 items-center max-w-6xl mx-auto h-[70vh]">
            {/* Texto y Audio */}
            <div className="flex-1 space-y-8">
                <div className="glass p-8 rounded-3xl border-white/10 relative overflow-hidden">
                    <div className="absolute top-0 left-0 w-2 h-full bg-brand" />
                    <p className="text-xl md:text-2xl font-medium leading-relaxed text-white/90">
                        {text}
                    </p>
                </div>

                <div className="glass p-4 rounded-2xl border-white/10 flex items-center gap-4">
                    <span className="text-xs font-bold uppercase text-white/40 tracking-wider">Audio Guía</span>
                    <audio
                        ref={audioRef}
                        src={audioSrc}
                        controls
                        className="w-full h-10 accent-brand"
                        onEnded={onComplete}
                    />
                </div>
            </div>

            {/* Imagen Ilustrativa */}
            <div className="flex-1 h-full relative rounded-3xl overflow-hidden border border-white/10 shadow-2xl">
                <img
                    src={imageSrc}
                    alt="Ilustración"
                    className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
            </div>
        </div>
    );
}
