"use client";

import { useRef, useState } from "react";
import { Play, Pause, Volume2, VolumeX, Maximize } from "lucide-react";

interface VideoPlayerProps {
    src: string;
    onCreateLog?: () => void;
    onComplete?: () => void;
}

export default function VideoPlayer({ src, onComplete }: VideoPlayerProps) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [playing, setPlaying] = useState(false);
    const [progress, setProgress] = useState(0);
    const [muted, setMuted] = useState(false);

    const togglePlay = () => {
        if (videoRef.current) {
            if (playing) videoRef.current.pause();
            else videoRef.current.play();
            setPlaying(!playing);
        }
    };

    const handleTimeUpdate = () => {
        if (videoRef.current) {
            const current = videoRef.current.currentTime;
            const duration = videoRef.current.duration;
            setProgress((current / duration) * 100);
        }
    };

    return (
        <div className="relative w-full max-w-4xl mx-auto aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl group">
            <video
                ref={videoRef}
                src={src}
                className="w-full h-full object-contain"
                onTimeUpdate={handleTimeUpdate}
                onEnded={() => {
                    setPlaying(false);
                    onComplete?.();
                }}
                onClick={togglePlay}
            />

            {/* Custom Controls Overlay */}
            <div className="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                <div className="flex items-center gap-4">
                    <button onClick={togglePlay} className="text-white hover:text-brand transition-colors">
                        {playing ? <Pause className="w-6 h-6" /> : <Play className="w-6 h-6" />}
                    </button>

                    {/* Progress Bar */}
                    <div className="flex-1 h-1 bg-white/20 rounded-full overflow-hidden cursor-pointer" onClick={(e) => {
                        const rect = e.currentTarget.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        if (videoRef.current) {
                            const newTime = (x / rect.width) * videoRef.current.duration;
                            videoRef.current.currentTime = newTime;
                        }
                    }}>
                        <div className="h-full bg-brand" style={{ width: `${progress}%` }} />
                    </div>

                    <button onClick={() => {
                        if (videoRef.current) {
                            videoRef.current.muted = !muted;
                            setMuted(!muted);
                        }
                    }} className="text-white hover:text-brand">
                        {muted ? <VolumeX className="w-5 h-5" /> : <Volume2 className="w-5 h-5" />}
                    </button>

                    <button onClick={() => videoRef.current?.requestFullscreen()} className="text-white hover:text-brand">
                        <Maximize className="w-5 h-5" />
                    </button>
                </div>
            </div>

            {/* Big Play Button Overlay */}
            {!playing && (
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div className="w-20 h-20 bg-black/50 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/10 shadow-lg">
                        <Play className="w-8 h-8 text-white ml-1" />
                    </div>
                </div>
            )}
        </div>
    );
}
