import React, { useRef, useState, useEffect, forwardRef, useImperativeHandle } from 'react';
import { Play, Pause, Volume2, VolumeX, Maximize, AlertCircle } from 'lucide-react';

interface VideoPlayerProps {
    src: string;
    onEnded?: () => void;
}

export interface VideoPlayerRef {
    pause: () => void;
    play: () => void;
    stop: () => void;
}

const VideoPlayer = forwardRef<VideoPlayerRef, VideoPlayerProps>(({ src, onEnded }, ref) => {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [playing, setPlaying] = useState(false);
    const [muted, setMuted] = useState(false);
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState(false);
    const [isYouTube, setIsYouTube] = useState(false);
    const [youtubeId, setYoutubeId] = useState('');
    const [completed, setCompleted] = useState(false);
    const completedRef = useRef(false);
    
    // Expose control methods via ref
    useImperativeHandle(ref, () => ({
        pause: () => {
            if (videoRef.current) {
                videoRef.current.pause();
                setPlaying(false);
            }
        },
        play: () => {
            if (videoRef.current) {
                videoRef.current.play();
                setPlaying(true);
            }
        },
        stop: () => {
            if (videoRef.current) {
                videoRef.current.pause();
                videoRef.current.currentTime = 0;
                setPlaying(false);
            }
        }
    }));

    useEffect(() => {
        // Check if URL is YouTube
        const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
        const match = src?.match(youtubeRegex);
        
        if (match && match[1]) {
            setIsYouTube(true);
            setYoutubeId(match[1]);
        }
    }, [src]);

    const togglePlay = () => {
        if (videoRef.current) {
            if (playing) videoRef.current.pause();
            else videoRef.current.play();
            setPlaying(!playing);
        }
    };

    const handleTimeUpdate = () => {
        if (videoRef.current) {
            const p = (videoRef.current.currentTime / videoRef.current.duration) * 100;
            setProgress(p);
            
            // Auto-completar al 90% del video (backup por si onEnded falla)
            if (p >= 90 && !completedRef.current && onEnded) {
                console.log('[VideoPlayer] Video alcanzó 90%, marcando como completado');
                completedRef.current = true;
                setCompleted(true);
                onEnded();
            }
        }
    };

    const handleError = () => {
        setError(true);
        console.error('Error loading video:', src);
    };

    // YouTube embed
    if (isYouTube) {
        return (
            <div className="relative bg-black aspect-video group">
                <iframe
                    src={`https://www.youtube.com/embed/${youtubeId}?rel=0&modestbranding=1&autoplay=0`}
                    className="w-full h-full"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowFullScreen
                    onLoad={() => {
                        console.log('[VideoPlayer] YouTube cargado, iniciando timer de completación de 15s');
                        if (onEnded) {
                            setTimeout(() => {
                                if (!completedRef.current) {
                                    console.log('[VideoPlayer] YouTube completado por tiempo');
                                    completedRef.current = true;
                                    setCompleted(true);
                                    onEnded();
                                }
                            }, 15000); // 15 segundos para YouTube como fallback
                        }
                    }}
                />
                
                {completed && (
                    <div className="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center gap-2 z-20">
                        <span>✓</span> Video Completado
                    </div>
                )}
            </div>
        );
    }

    // Error state
    if (error) {
        return (
            <div className="relative bg-black aspect-video flex items-center justify-center">
                <div className="text-center p-8">
                    <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
                    <p className="text-white/60 text-sm mb-2">No se pudo cargar el video</p>
                    <p className="text-white/40 text-xs font-mono break-all">{src}</p>
                </div>
            </div>
        );
    }

    // Standard video player
    return (
        <div className="relative group bg-black aspect-video flex items-center justify-center">
            <video
                ref={videoRef}
                className="w-full h-full object-contain"
                onEnded={() => {
                    console.log('[VideoPlayer] Video terminado (evento onEnded)');
                    if (!completedRef.current && onEnded) {
                        completedRef.current = true;
                        setCompleted(true);
                        onEnded();
                    }
                }}
                onTimeUpdate={handleTimeUpdate}
                onClick={togglePlay}
                onError={handleError}
                crossOrigin="anonymous"
            >
                <source src={src} type="video/mp4" />
                <source src={src} type="video/webm" />
                <source src={src} type="video/ogg" />
                Tu navegador no soporta el elemento de video.
            </video>

            {/* Custom Controls Overlay */}
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4">
                <div className="flex items-center gap-4">
                    <button onClick={togglePlay} className="text-white hover:text-brand transition-colors">
                        {playing ? <Pause className="w-6 h-6" /> : <Play className="w-6 h-6" />}
                    </button>

                    <div className="flex-1 h-1 bg-white/20 rounded-full overflow-hidden cursor-pointer" onClick={(e) => {
                        if (!videoRef.current) return;
                        const rect = e.currentTarget.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        const width = rect.width;
                        const percent = x / width;
                        videoRef.current.currentTime = percent * videoRef.current.duration;
                    }}>
                        <div className="h-full bg-brand" style={{ width: `${progress}%` }} />
                    </div>

                    <button onClick={() => {
                        if (videoRef.current) {
                            videoRef.current.muted = !muted;
                            setMuted(!muted);
                        }
                    }} className="text-white hover:text-brand transition-colors">
                        {muted ? <VolumeX className="w-5 h-5" /> : <Volume2 className="w-5 h-5" />}
                    </button>

                    <button onClick={() => videoRef.current?.requestFullscreen()} className="text-white hover:text-brand transition-colors">
                        <Maximize className="w-5 h-5" />
                    </button>
                </div>
            </div>

            {!playing && (
                <button onClick={togglePlay} className="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div className="w-16 h-16 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center border border-white/20">
                        <Play className="w-6 h-6 text-white ml-1" />
                    </div>
                </button>
            )}
            
            {/* Indicador de video completado */}
            {completed && (
                <div className="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center gap-2">
                    <span>✓</span> Video Completado
                </div>
            )}
        </div>
    );
});

VideoPlayer.displayName = 'VideoPlayer';

export default VideoPlayer;
