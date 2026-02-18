"use client";

import { useEffect, useRef, useState } from "react";
import { X, CheckCircle2, ArrowRight } from "lucide-react";
import { initScormAPI } from "@/lib/scorm-driver";
import { supabase } from "@/lib/supabase";
import { motion, AnimatePresence } from "framer-motion";

interface ScormPlayerProps {
    courseUrl: string;
    courseTitle: string;
    user: any;
    enrollment?: any;
    courseId?: string; // Add courseId prop
    courseConfig?: any;
    onClose: () => void;
    onComplete?: (scormScore: number) => void;
    language?: string; // New prop for language
}

const translations: any = {
    es: {
        vr_mode: "Modo Aprendizaje VR",
        exit: "Salir (tu progreso se guardará automáticamente)",
        scorm_finished: "¡SCORM Finalizado!",
        max_score: "con el puntaje máximo",
        hits: (score: number) => `con ${score}% de aciertos`,
        raw_score: "Puntaje Bruto",
        weight: "Ponderación (20%)",
        finish_btn: "Finalizar y volver al curso"
    },
    ht: {
        vr_mode: "Mod Aprantisaj VR",
        exit: "Soti (pwogrè ou ap sove otomatikman)",
        scorm_finished: "SCORM Fini!",
        max_score: "ak nòt maksimòm nan",
        hits: (score: number) => `avèk ${score}% siksè`,
        raw_score: "Nòt brit",
        weight: "Pwa (20%)",
        finish_btn: "Fini epi tounen nan kou a"
    }
};

export default function ScormPlayer({ courseUrl, courseTitle, user, enrollment, courseId, courseConfig, onClose, onComplete, language = "es" }: ScormPlayerProps) {
    const t = translations[language] || translations.es;
    const iframeRef = useRef<HTMLIFrameElement>(null);
    const [showCompletionModal, setShowCompletionModal] = useState(false);
    const [scormScore, setScormScore] = useState(0);
    const [scormStatus, setScormStatus] = useState("");
    const hasCompletedRef = useRef(false);
    const enrollmentIdRef = useRef<string | null>(null);

    useEffect(() => {
        if (!user || !enrollment) return;
        
        const enrollmentId = enrollment.id;
        
        // Prevent double initialization for the same enrollment
        if (enrollmentIdRef.current === enrollmentId) return;
        enrollmentIdRef.current = enrollmentId;
        
        console.log("ScormPlayer: Initializing ONCE with enrollment ID:", enrollmentId);

        const api = initScormAPI(supabase, user, enrollmentId, courseId);
        
        const handleProgressSave = () => {
            const status = api.LMSGetValue("cmi.core.lesson_status");
            const score = parseFloat(api.LMSGetValue("cmi.core.score.raw") || "0");

            console.log("ScormPlayer: Progress saved - Status:", status, "Score:", score);
            setScormStatus(status);
            setScormScore(score);

            if ((status === "completed" || status === "passed") && !hasCompletedRef.current) {
                hasCompletedRef.current = true;
                console.log("ScormPlayer: COMPLETED! Calling onComplete with score:", score);
                // Mostrar modal de completitud
                setTimeout(() => {
                    setShowCompletionModal(true);
                    if (onComplete) {
                        console.log("ScormPlayer: Executing onComplete callback with score:", score);
                        onComplete(score);
                    }
                }, 600);
            }
        };

        // Inject callbacks into the API
        api.onSave = handleProgressSave;
        api.onFinish = handleProgressSave;

        document.body.style.overflow = "hidden";

        return () => {
            document.body.style.overflow = "unset";
        };
    }, [user, enrollment?.id, courseId]); // Dependency on ID instead of object

    const handleExit = () => {
        setShowCompletionModal(false);
        onClose();
    };

    return (
        <div className="fixed inset-0 z-[100] bg-black/95 flex flex-col">
            {/* Top Bar Player */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-[#0a0a0a]">
                <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-xl bg-brand/10 flex items-center justify-center">
                        <div className="w-2 h-2 rounded-full bg-brand animate-pulse" />
                    </div>
                    <div>
                        <h3 className="text-sm font-bold text-white">{courseTitle}</h3>
                        <p className="text-[10px] text-white/40 uppercase tracking-widest font-bold">{t.vr_mode}</p>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={onClose}
                        className="p-2.5 hover:bg-white/5 rounded-xl text-white/60 hover:text-white transition-colors border border-white/10"
                        title={t.exit}
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>
            </div>

            {/* Course Content */}
            <div className="flex-1 relative bg-white">
                <iframe
                    ref={iframeRef}
                    src={courseUrl}
                    className="absolute inset-0 w-full h-full border-none"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                />
            </div>

            {/* Completion Modal */}
            <AnimatePresence>
                {showCompletionModal && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-[110] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
                    >
                        <motion.div
                            initial={{ scale: 0.9, y: 20 }}
                            animate={{ scale: 1, y: 0 }}
                            exit={{ scale: 0.9, y: 20 }}
                            className="glass max-w-md w-full p-8 rounded-3xl border-brand/30 space-y-6"
                        >
                            <div className="flex flex-col items-center text-center space-y-4">
                                <div className="w-16 h-16 rounded-full bg-brand/20 flex items-center justify-center border-2 border-brand/50">
                                    <CheckCircle2 className="w-10 h-10 text-brand" />
                                </div>
                                <div>
                                    <h3 className="text-2xl font-black mb-2">{t.scorm_finished}</h3>
                                    <p className="text-white/60 text-sm">
                                        Has realizado la actividad {scormScore === 100 ? t.max_score : t.hits(scormScore)}.
                                    </p>
                                </div>
                                <div className="grid grid-cols-2 gap-4 w-full">
                                    <div className="bg-white/5 border border-white/10 rounded-2xl p-4">
                                        <p className="text-xs text-white/40 uppercase tracking-widest font-black mb-1">{t.raw_score}</p>
                                        <p className="text-3xl font-black text-brand">{scormScore}%</p>
                                    </div>
                                    <div className="bg-brand/10 border border-brand/30 rounded-2xl p-4">
                                        <p className="text-xs text-brand/70 uppercase tracking-widest font-black mb-1">{t.weight}</p>
                                        <p className="text-3xl font-black text-brand">{Math.round(scormScore * 0.2)}%</p>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <button
                                    onClick={handleExit}
                                    className="w-full py-4 bg-brand text-black font-black uppercase text-sm rounded-xl hover:bg-white transition-all flex items-center justify-center gap-2"
                                >
                                    {t.finish_btn} <ArrowRight className="w-5 h-5" />
                                </button>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}
