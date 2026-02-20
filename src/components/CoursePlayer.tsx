"use client";

import { useState, useEffect, useRef } from "react";
import { supabase } from "@/lib/supabase";
import { motion, AnimatePresence } from "framer-motion";
import { 
    ChevronRight, CheckCircle2, Lock, Volume2,
    ChevronLeft, Library, FileText as FileIcon, Download
} from "lucide-react";
import GeniallyEmbed from "./GeniallyEmbed";
import VideoPlayer, { VideoPlayerRef } from "./VideoPlayer";
import SignatureCanvas from "./SignatureCanvas";
import QuizEngine from "./QuizEngine";
import ScormPlayer from "./ScormPlayer";
import SurveyEngine from "./SurveyEngine";

// Types
type ModuleItem = {
    id: string;
    type: 'video' | 'audio' | 'image' | 'pdf' | 'genially' | 'scorm' | 'quiz' | 'signature' | 'text' | 'header' | 'survey';
    content: any;
    order_index: number;
};

type CoursePlayerProps = {
    courseId: string;
    studentId: string;
    onComplete?: () => void;
    mode?: 'student' | 'preview';
    className?: string;
    language?: string; // Nuevo prop para el idioma
};

const translations: any = {
    es: {
        loading: "Cargando curso...",
        no_content: "Este curso no tiene contenido asignado.",
        module_not_found: "Módulo no encontrado.",
        slide: "Diapositiva",
        of: "de",
        material: "Material",
        open_pdf: "Abrir PDF",
        interactive_activity: "Actividad Interactiva",
        start: "Iniciar",
        restart: "Reiniciar",
        signature_title: "Firma Digital del Alumno",
        signature_desc: "Por favor, firma en el recuadro para validar tu participación.",
        signature_success: "Firma registrada correctamente",
        course_approved: "¡Curso Aprobado!",
        course_approved_desc: "Has superado satisfactoriamente todas las evaluaciones y contenidos del curso.",
        diploma_desc: "Ya puedes descargar tu certificado de participación.",
        download_cert: "Descargar Certificado",
        current_module: "Módulo Actual",
        progress: "Progreso",
        previous: "Anterior",
        next: "Siguiente",
        finish: "Finalizar",
        survey_title: "Encuesta de Satisfacción",
        survey_desc: "Tu opinión es muy importante para nosotros.",
        survey_submit: "Enviar Encuesta",
        survey_thanks: "¡Muchas gracias por tu feedback!",
        survey_prerequisite: "Debes completar la encuesta obligatoria para descargar tu certificado.",
        survey_pending_after_exam: "Evaluación aprobada. Para habilitar la descarga del certificado, envía la encuesta obligatoria."
    },
    ht: {
        loading: "Chaje kou...",
        no_content: "Kou sa a pa gen kontni asiyen.",
        module_not_found: "Modil pa jwenn.",
        slide: "Diapozitif",
        of: "nan",
        material: "Materyèl",
        open_pdf: "Louvri PDF",
        interactive_activity: "Aktivite Enteraktif",
        start: "Kòmanse",
        restart: "Rekòmanse",
        signature_title: "Siyati Dijital Elèv la",
        signature_desc: "Tanpri, siyen nan bwat la pou valide patisipasyon ou.",
        signature_success: "Siyati anrejistre kòrèkteman",
        course_approved: "Kou Apwouve!",
        course_approved_desc: "Ou te pase avèk siksè tout evalyasyon ak kontni kou a.",
        diploma_desc: "Ou ka telechaje sètifika patisipasyon ou kounye a.",
        download_cert: "Telechaje Sètifika",
        current_module: "Modil aktyèl",
        progress: "Pwogrè",
        previous: "Anvan",
        next: "Next",
        finish: "Fini",
        survey_title: "Sondaj Satisfaksyon",
        survey_desc: "Opinyon ou trè enpòtan pou nou.",
        survey_submit: "Voye Sondaj",
        survey_thanks: "Mèsi anpil pou feedback ou!",
        survey_prerequisite: "Ou dwe manyen sondaj obligatwa a pou telechaje sètifika ou.",
        survey_pending_after_exam: "Egzamen an apwouve. Pou aktive telechajman sètifika a, voye sondaj obligatwa a."
    }
};

export default function CoursePlayer({ courseId, studentId, onComplete, mode = 'student', className = '', language = 'es' }: CoursePlayerProps) {
    const t = translations[language] || translations.es;
    const [modules, setModules] = useState<any[]>([]);
    const [activeModuleIndex, setActiveModuleIndex] = useState(0);
    const [loading, setLoading] = useState(true);
    const [itemsCompleted, setItemsCompleted] = useState<Set<string>>(new Set());
    const [moduleCompleted, setModuleCompleted] = useState(false);
    const [approved, setApproved] = useState(false);
    const [quizScore, setQuizScore] = useState<number | null>(null);
    const [scormScore, setScormScore] = useState<number>(0);
    const [scormModalItem, setScormModalItem] = useState<ModuleItem | null>(null);
    const [enrollment, setEnrollment] = useState<any>(null);
    const [extrasOpen, setExtrasOpen] = useState(false);
    const [surveyDone, setSurveyDone] = useState(false);
    const [evaluationPassed, setEvaluationPassed] = useState(false);

    const audioRefs = useRef<Map<string, HTMLAudioElement>>(new Map());
    const videoRefs = useRef<Map<string, VideoPlayerRef>>(new Map());

    const handleEvaluationItemScore = (itemId: string, score: number, type: string, passed?: boolean) => {
        console.log(`[CoursePlayer] handleEvaluationItemScore called - Type: ${type}, Score: ${score}, ItemId: ${itemId}, Passed: ${passed}`);
        
        // Solo actualizar puntajes si el módulo actual es de tipo 'evaluation'
        const currentModule = modules[activeModuleIndex];
        const isEvalModule = currentModule?.type === 'evaluation';

        if (isEvalModule) {
            if (type === 'quiz') {
                console.log('[CoursePlayer] Setting Quiz Score:', score);
                setQuizScore(score);
                if (passed) {
                    setEvaluationPassed(true);
                }
            }
            if (type === 'scorm') {
                console.log('[CoursePlayer] Setting SCORM Score:', score);
                setScormScore(score);
            }
        } else {
            console.log('[CoursePlayer] Quiz completado en módulo de contenido (no evaluativo).');
        }
        
        // Solo marcar como completado si aprobó (para quiz de avance)
        if (passed !== false) {
            handleItemCompletion(itemId);
        } else {
            console.log('[CoursePlayer] Item no aprobado. Repetir para avanzar.');
        }
    };

    // NUEVO: Lógica de aprobación ponderada
    useEffect(() => {
        if (quizScore === null) return;

        // Intentar obtener pesos del módulo actual o del curso
        const currentModule = modules[activeModuleIndex];
        const quizWeight = (currentModule?.settings?.quiz_percentage ?? enrollment?.courses?.config?.weight_quiz ?? 80) / 100;
        const scormWeight = (currentModule?.settings?.scorm_percentage ?? enrollment?.courses?.config?.weight_scorm ?? 20) / 100;
        const minPass = currentModule?.settings?.min_score ?? enrollment?.courses?.config?.passing_score ?? 90;

        const qScore = quizScore || 0;
        const sScore = scormScore || 0;

        const total = (qScore * quizWeight) + (sScore * scormWeight);
        const roundedTotal = Math.round(total);
        
        console.log('[CoursePlayer] Score Calculation:', {
            quizScore: qScore,
            scormScore: sScore,
            quizWeight,
            scormWeight,
            total: roundedTotal,
            minPass,
            passed: roundedTotal >= minPass
        });
        
        // Determinar si realmente puede completar el curso
        // El diploma se genera si el puntaje es suficiente (passed) y estamos en el último módulo.
        // La presencia de la firma ya no bloquea la generación del diploma en la DB si el alumno aprobó.
        const isLastModule = activeModuleIndex === modules.length - 1;
        
        // Verificación CRÍTICA de encuestas pendientes antes de permitir la APROBACIÓN VISUAL
        const hasPendingSurvey = modules.some((mod: any) => 
            mod.items?.some((item: any) => 
                item.type === 'survey' && 
                item.content?.is_mandatory && 
                !itemsCompleted.has(item.id)
            )
        );

        const passedEvaluation = roundedTotal >= minPass && isLastModule;
        const canComplete = passedEvaluation && !hasPendingSurvey;
        
        console.log('[CoursePlayer] Can Complete Check:', { roundedTotal, minPass, isLastModule, hasPendingSurvey });

        if (canComplete) {
            console.log('[CoursePlayer] COURSE COMPLETED! Total score:', roundedTotal);
            setEvaluationPassed(true);
            setApproved(true);
            updateEnrollmentStatus('completed', roundedTotal);
        } else {
            // Solo mostramos aprobado si pasó el mínimo, pero bloqueamos el status 'completed' si falta encuesta
            // Si falta encuesta, setApproved DEBE ser false para no mostrar el botón de descarga
            const visualApproval = passedEvaluation && !hasPendingSurvey;
            setApproved(visualApproval); 

            if (passedEvaluation) {
                setEvaluationPassed(true);
                updateEnrollmentStatus('completed', roundedTotal);
                return;
            }
            
            if (total > 0) updateEnrollmentStatus('in_progress', roundedTotal);
        }
    }, [quizScore, scormScore, activeModuleIndex, modules, enrollment, itemsCompleted]);

    useEffect(() => {
        loadCourseStructure();
    }, [courseId]);

    useEffect(() => {
        // Reset completion status when changing module
        // setItemsCompleted(new Set()); // NO LIMPIAR itemsCompleted para mantener el estado entre módulos
        setModuleCompleted(false);
        stopAllMedia();
        setExtrasOpen(false);
    }, [activeModuleIndex]);

    const stopAllMedia = () => {
        audioRefs.current.forEach((audio) => {
            if (audio) {
                audio.pause();
                audio.currentTime = 0;
            }
        });
        videoRefs.current.forEach((videoControl) => {
            if (videoControl && videoControl.stop) {
                videoControl.stop();
            }
        });
    };

    const loadCourseStructure = async () => {
        setLoading(true);
        const { data: mods } = await supabase
            .from('course_modules')
            .select('*, module_items(*)')
            .eq('course_id', courseId)
            .order('order_index');

        if (mods) {
            const formatted = mods.map((m: any) => ({
                ...m,
                items: (m.module_items || []).sort((a: any, b: any) => (a.order_index ?? 0) - (b.order_index ?? 0))
            })).sort((a: any, b: any) => (a.order_index ?? 0) - (b.order_index ?? 0));
            
            setModules(formatted);
        }
        setLoading(false);
    };

    const fetchEnrollment = async () => {
        if (mode === 'preview' || studentId === "preview-admin" || !studentId || !courseId) return;
        try {
            const { data, error } = await supabase
                .from('enrollments')
                .select('*, courses(*), students(*)')
                .eq('student_id', studentId)
                .eq('course_id', courseId)
                .single();

            if (data) {
                setEnrollment(data);
                
                // SOLO cargar puntajes si el curso NO está reiniciado (status != 'not_started')
                // Si está en not_started, resetear todo a 0 y comenzar desde el principio
                if (data.status === 'not_started') {
                    console.log("[CoursePlayer] Course in 'not_started' status, resetting to module 0");
                    setActiveModuleIndex(0); // Forzar inicio desde módulo 0
                    setQuizScore(0);
                    setScormScore(0);
                    setApproved(false);
                    setEvaluationPassed(false);
                    setItemsCompleted(new Set()); // Limpiar items completados
                    setModuleCompleted(false);
                } else {
                    // Cargar el módulo guardado
                    if (data.current_module_index !== undefined && data.current_module_index !== null) {
                        setActiveModuleIndex(data.current_module_index);
                    }
                    
                    // Cargar puntajes detallados si existen
                    if (data.quiz_score !== undefined && data.quiz_score !== null) setQuizScore(data.quiz_score);
                    if (data.scorm_score !== undefined && data.scorm_score !== null) setScormScore(data.scorm_score);

                    // Si hay scores temporales (porque faltaba la encuesta), restaurarlos
                    if (data.last_exam_score !== undefined && data.last_exam_score !== null) {
                        setQuizScore(data.last_exam_score);
                    }
                    if (data.survey_completed) {
                        setSurveyDone(true);
                    }

                    if (data.last_exam_passed || data.status === 'completed') {
                        setEvaluationPassed(true);
                    }

                    // Si no hay scores detallados todavía, pero hay un best_score (migración vieja), lo usamos de base para el quiz
                    if (!data.quiz_score && data.best_score && !data.scorm_score) {
                        setQuizScore(data.best_score);
                    }

                    if (data.status === 'completed') {
                        setApproved(true);
                    }
                }
            }
        } catch (e) {
            console.log("Error loading enrollment", e);
        }
    };

    async function updateEnrollmentStatus(status: string, totalScore: number) {
        if (mode === 'preview' || studentId === "preview-admin" || !enrollment?.id) return;
        if (enrollment.status === 'completed' && status !== 'completed') return; // Don't downgrade status

        try {
            // Verificamos si hay encuestas obligatorias pendientes en CUALQUIER módulo
            // No solo en el actual, para evitar saltos entre módulos
            const hasPendingSurvey = modules.some((mod: any) => 
                mod.items?.some((item: any) => 
                    item.type === 'survey' && 
                    item.content?.is_mandatory && 
                    !itemsCompleted.has(item.id)
                )
            );

            // Si intentamos completar pero falto la encuesta, guardamos como in_progress + scores temporales
            const finalStatus = (status === 'completed' && hasPendingSurvey) ? 'in_progress' : status;

            const updatePayload: any = { 
                status: finalStatus, 
                best_score: totalScore,
                quiz_score: quizScore,
                scorm_score: scormScore,
                completed_at: finalStatus === 'completed' ? new Date().toISOString() : enrollment.completed_at
            };
            
            // Asegurar que el progress siempre se actualice al final
            if (status === 'completed') {
                updatePayload.progress = 100;
            }

            // Guardar scores temporales si está aprobado pero bloqueado por encuesta
            if (status === 'completed' && hasPendingSurvey) {
                updatePayload.last_exam_score = quizScore;
                updatePayload.last_exam_passed = true;
                updatePayload.survey_completed = false;
                setEvaluationPassed(true);
            } else if (finalStatus === 'completed') {
                // Si ya completó todo, limpiar scores temporales
                updatePayload.last_exam_score = null;
                updatePayload.last_exam_passed = null;
                updatePayload.survey_completed = true;
                setEvaluationPassed(true);
            }

            await supabase
                .from('enrollments')
                .update(updatePayload)
                .eq('id', enrollment.id);
            console.log("CoursePlayer: Enrollment status updated with scores:", { status: finalStatus, totalScore, quizScore, scormScore, hasPendingSurvey });
        } catch (err) {
            console.error("Error updating enrollment status:", err);
        }
    }

    useEffect(() => {
        fetchEnrollment();
    }, [studentId, courseId, mode]);

    useEffect(() => {
        if (!modules || !enrollment) return;

        // Auto-completar items conocidos basándose en la base de datos
        setItemsCompleted(prev => {
            const newSet = new Set(prev);
            let changed = false;

            modules.forEach((mod: any) => {
                mod.items?.forEach((item: any) => {
                    // Si es un quiz y ya tiene puntaje de aprobación
                    if (item.type === 'quiz' && (enrollment.last_exam_passed || enrollment.quiz_score >= (mod.settings?.min_score || 60))) {
                        if (!newSet.has(item.id)) {
                            newSet.add(item.id);
                            changed = true;
                        }
                    }
                    // Si es una encuesta y ya está marcada como completada
                    if (item.type === 'survey' && enrollment.survey_completed) {
                        if (!newSet.has(item.id)) {
                            newSet.add(item.id);
                            changed = true;
                        }
                    }
                });
            });

            return changed ? newSet : prev;
        });
    }, [modules, enrollment?.quiz_score, enrollment?.survey_completed, activeModuleIndex]); // Update itemsCompleted on module change too

    const handleItemCompletion = (itemId: string) => {
        console.log(`[CoursePlayer] Item completado: ${itemId}`);
        setItemsCompleted(prev => {
            const newSet = new Set(prev);
            newSet.add(itemId);
            console.log(`[CoursePlayer] Total items completados:`, newSet.size);
            return newSet;
        });
    };

    const handleSignatureSave = async (data: string, itemId: string) => {
        handleItemCompletion(itemId);
        if (mode === 'student' && studentId && studentId !== "preview-admin") {
            try {
                console.log(`[CoursePlayer] Saving signature for student ${studentId}, data length: ${data.length} chars`);
                
                const { error } = await supabase
                    .from('students')
                    .update({ 
                        digital_signature_url: data,
                        consent_accepted_at: new Date().toISOString()
                    })
                    .eq('id', studentId);
                
                if (error) {
                    console.error("[CoursePlayer] ❌ Error saving signature:", error);
                } else {
                    console.log("[CoursePlayer] ✅ Signature and consent saved successfully for student:", studentId);
                    
                    // Verificación inmediata
                    const { data: verification } = await supabase
                        .from('students')
                        .select('digital_signature_url, consent_accepted_at')
                        .eq('id', studentId)
                        .single();
                    
                    console.log("[CoursePlayer] 🔍 Verification - Signature in DB:", verification?.digital_signature_url ? `YES (${verification.digital_signature_url.length} chars)` : 'NO');
                    console.log("[CoursePlayer] 🔍 Verification - Consent at:", verification?.consent_accepted_at || 'NO');
                }
            } catch (err) {
                console.error("[CoursePlayer] Exception saving signature:", err);
            }
        }
    };

    useEffect(() => {
        // Reset completion status when changing module
        setModuleCompleted(false);
        
        if (!modules || !modules[activeModuleIndex]) return;

        const currentModule = modules[activeModuleIndex];
        const currentItems = currentModule.items || [];

        // Si ya pasamos por aquí antes (módulo mayor que el actual en BD), permitir avanzar
        // CORRECCIÓN: Si el curso está en 'not_started', NO desbloquear nada por progreso previo
        if (enrollment && enrollment.status !== 'not_started' && typeof enrollment.current_module_index === 'number' && activeModuleIndex < enrollment.current_module_index) {
            console.log(`[CoursePlayer] Módulo ${activeModuleIndex + 1} ya fue superado previamente. Desbloqueando.`);
            setModuleCompleted(true);
            return;
        }

        // Timer automático para Genially (15 segundos)
        const geniallyItems = currentItems.filter((i: ModuleItem) => i.type === 'genially');
        const geniallyTimers: NodeJS.Timeout[] = [];
        
        geniallyItems.forEach((item: ModuleItem) => {
            if (!itemsCompleted.has(item.id)) {
                console.log(`[CoursePlayer] Iniciando timer de 15s para Genially: ${item.id}`);
                const timer = setTimeout(() => {
                    console.log(`[CoursePlayer] Timer de Genially completado: ${item.id}`);
                    handleItemCompletion(item.id);
                }, 15000); // 15 segundos
                geniallyTimers.push(timer);
            }
        });

        const pendingItems = currentItems.filter((item: ModuleItem) => {
            // Estos elementos NO bloquean el botón:
            if (['text', 'image', 'pdf', 'header'].includes(item.type)) return false;
            
            // Estos elementos SÍ bloquean hasta que se dispara handleItemCompletion:
            // - video (onEnded o 90%)
            // - audio (onEnded)
            // - scorm (onComplete)
            // - genially (15s timer o interacción)
            // - quiz (onComplete)
            // - signature (onSave)
            // - survey (onComplete)
            if (item.type === 'survey') {
                const isMandatory = item.content?.is_mandatory;
                if (!isMandatory) return false;
                return !itemsCompleted.has(item.id);
            }

            return !itemsCompleted.has(item.id);
        });
        
        const isDone = pendingItems.length === 0;
        console.log(`[CoursePlayer] Módulo ${activeModuleIndex + 1} - Pendientes (${pendingItems.length}):`, pendingItems.map((i: any) => `${i.type}:${i.id}`));
        
        setModuleCompleted(isDone);

        return () => {
            geniallyTimers.forEach(clearTimeout);
        };
    }, [itemsCompleted, activeModuleIndex, modules.length, enrollment?.current_module_index]);

    const saveProgress = async (index: number) => {
        if (mode === 'preview' || studentId === "preview-admin") return;
        if (!studentId || !courseId) return;
        try {
            const targetId = enrollment?.id;
            if (targetId) {
                // Calcular porcentaje de progreso basado en total de módulos
                const progressPercent = modules.length > 0 ? Math.round((index / modules.length) * 100) : 0;

                // Si estamos avanzando desde el módulo 0 y el status es 'not_started', actualizar a 'in_progress'
                const updatePayload: any = { 
                    current_module_index: index, 
                    progress: progressPercent 
                };
                
                if (enrollment?.status === 'not_started' && index > 0) {
                    updatePayload.status = 'in_progress';
                    console.log("[CoursePlayer] Updating status from 'not_started' to 'in_progress'");
                }
                
                await supabase
                    .from('enrollments')
                    .update(updatePayload)
                    .eq('id', targetId);
                    
                console.log("CoursePlayer: Progress saved for index:", index);
                
                // Actualizar estado local para prevenir bloqueos al navegar
                setEnrollment((prev: any) => prev ? { ...prev, ...updatePayload } : prev);
            }
        } catch (err) {
            console.error("Error saving progress:", err);
        }
    };

    const handleNext = async () => {
        if (activeModuleIndex < modules.length - 1) {
            const nextIndex = activeModuleIndex + 1;
            setActiveModuleIndex(nextIndex);
            await saveProgress(nextIndex);
        } else {
            // Último módulo - cerrar curso
            console.log('[CoursePlayer] Finalizando curso...');
            if (onComplete) {
                onComplete();
            } else {
                // Si no hay callback, navegar de regreso
                // Intentamos redirigir al portal si es posible
                const userStr = localStorage.getItem('user');
                if (userStr) {
                    try {
                        const user = JSON.parse(userStr);
                        if (user.companies?.slug) {
                            window.location.href = `/portal/${user.companies.slug}`;
                            return;
                        }
                    } catch (e) {
                        console.error("Error redirecting to portal", e);
                    }
                }
                window.location.href = '/admin/empresa/alumnos/cursos';
            }
        }
    };

    const handlePrevious = () => {
        if (activeModuleIndex > 0) {
            setActiveModuleIndex(prev => prev - 1);
        }
    };

    if (loading) return <div className="text-white text-center p-20">{t.loading}</div>;
    if (modules.length === 0) return <div className="text-white text-center p-20">{t.no_content}</div>;

    const currentModule = modules[activeModuleIndex];
    
    // Guard preventivo si el índice no es válido o módulos aún no cargados
    if (!currentModule) {
        return <div className="text-white text-center p-20">{t.module_not_found}</div>;
    }

    const isEvaluation = currentModule.type === 'evaluation';

    // Helper to detect light backgrounds (very basic check)
    const isLightBg = currentModule.settings?.bg_color && 
        ['#ffffff', '#fff', '#f4f4f4', '#f8f8f8', '#eeeeee', 'white'].includes(currentModule.settings.bg_color.toLowerCase());

    return (
        <div className={`flex flex-col w-full text-white relative bg-[#060606] ${className}`}>
            {/* Content area con scroll visible solo aquí */}
            <div className="flex-1 overflow-y-auto scroll-smooth custom-scrollbar">
                {/* Visual Stage / Diapositiva */}
                <div 
                    className={`max-w-5xl mx-auto min-h-full shadow-2xl transition-all duration-500 border-x border-white/5 pb-32 ${isLightBg ? 'text-slate-900' : 'text-white'}`}
                    style={{ backgroundColor: currentModule.settings?.bg_color || '#0a0a0a' }}
                >
                    <div className="max-w-4xl mx-auto px-4 md:px-8 py-10 w-full">

                        {/* Header */}
                        <header className={`mb-8 pb-4 border-b ${isLightBg ? 'border-black/10' : 'border-white/10'}`}>
                            <div className="flex justify-between items-center gap-4">
                                <div>
                                    <span className="text-brand text-xs font-bold">{t.slide} {activeModuleIndex + 1} {t.of} {modules.length}</span>
                                    <h1 className={`text-2xl md:text-3xl font-black mt-1 ${isLightBg ? 'text-slate-900' : 'text-white'}`}>{currentModule.title}</h1>
                                </div>
                                
                                {/* Biblioteca */}
                                {currentModule.settings?.extras?.length > 0 && (
                                    <div className="relative">
                                        <button
                                            onClick={() => setExtrasOpen((prev) => !prev)}
                                            className={`px-4 py-2 rounded-xl border transition-all flex items-center gap-2 text-sm font-bold ${isLightBg ? 'bg-slate-100 border-slate-200 text-slate-800 hover:bg-slate-200' : 'bg-brand/10 border-brand/20 text-white hover:bg-brand/20'}`}
                                        >
                                            <Library className="w-4 h-4" />
                                            <span>{t.material} ({currentModule.settings.extras.length})</span>
                                        </button>
                                        
                                        <div
                                            className={`absolute right-0 top-full mt-2 w-72 bg-[#111] border border-white/10 rounded-xl shadow-2xl transition-all z-50 p-3 text-white ${extrasOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'}`}
                                        >
                                            {currentModule.settings.extras.map((extra: any, idx: number) => (
                                                <a 
                                                    key={idx}
                                                    href={extra.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 transition-all"
                                                    onClick={() => setExtrasOpen(false)}
                                                >
                                                    <FileIcon className="w-5 h-5 text-brand" />
                                                    <span className="text-sm flex-1 truncate">{extra.name}</span>
                                                </a>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </header>

                        {/* CONTENIDO DEL MÓDULO */}
                        <div className="space-y-12">
                            {currentModule.items && currentModule.items.map((item: ModuleItem) => (
                                <div key={item.id}>
                                    {/* Título/Header */}
                                    {(item.type === 'header' || (item.type === 'text' && item.content?.isHeader)) && (
                                        <h2 
                                            className="text-center py-6"
                                            style={{ 
                                                fontSize: item.content?.tag === 'h1' ? '2.5rem' : item.content?.tag === 'h2' ? '2rem' : '1.5rem',
                                                fontWeight: item.content?.bold ? '900' : '700',
                                                color: item.content?.color || (isLightBg ? '#0f172a' : '#fff')
                                            }}
                                        >
                                            {item.content?.text}
                                        </h2>
                                    )}

                                    {/* Texto */}
                                    {item.type === 'text' && !item.content?.isHeader && (
                                        <div className={`prose max-w-none p-6 rounded-xl border ${isLightBg ? 'prose-slate bg-black/5 border-black/10' : 'prose-invert bg-white/5 border-white/10'}`}>
                                            <div dangerouslySetInnerHTML={{ __html: item.content?.html || item.content?.text || '' }} />
                                        </div>
                                    )}

                                    {/* Imagen */}
                                    {item.type === 'image' && (
                                        <div className="flex justify-center">
                                            <img src={item.content?.url} alt="" className="max-w-full rounded-xl shadow-xl" />
                                        </div>
                                    )}

                                    {/* Video */}
                                    {item.type === 'video' && (
                                        <div className={`rounded-xl overflow-hidden bg-black border ${isLightBg ? 'border-black/10' : 'border-white/10'}`}>
                                            <VideoPlayer
                                                ref={(el) => { if (el) videoRefs.current.set(item.id, el); }}
                                                src={item.content?.url}
                                                onEnded={() => handleItemCompletion(item.id)}
                                            />
                                        </div>
                                    )}

                                    {/* Audio */}
                                    {item.type === 'audio' && (
                                        <div className={`p-6 rounded-xl flex items-center gap-4 border ${isLightBg ? 'bg-black/5 border-black/10' : 'bg-white/5 border-white/10'}`}>
                                            <div className="p-3 bg-brand rounded-lg">
                                                <Volume2 className="w-6 h-6 text-black" />
                                            </div>
                                            <audio
                                                ref={(el) => { if (el) audioRefs.current.set(item.id, el); }}
                                                controls
                                                className="flex-1"
                                                src={item.content?.url}
                                                onEnded={() => handleItemCompletion(item.id)}
                                            />
                                        </div>
                                    )}

                                    {/* PDF */}
                                    {item.type === 'pdf' && (
                                        <div className="space-y-4">
                                            <iframe src={item.content?.url} className={`w-full h-[600px] rounded-xl border ${isLightBg ? 'border-black/10' : 'border-white/10'}`} />
                                            <a href={item.content?.url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 px-4 py-2 bg-brand/10 hover:bg-brand/20 rounded-lg text-sm font-bold transition-all">
                                                <FileIcon className="w-4 h-4" />
                                                {t.open_pdf}
                                            </a>
                                        </div>
                                    )}

                                    {/* Genially */}
                                    {item.type === 'genially' && (
                                        <div className="h-[600px] rounded-xl overflow-hidden border border-white/10">
                                            <GeniallyEmbed src={item.content?.url} onInteract={() => handleItemCompletion(item.id)} />
                                        </div>
                                    )}

                                    {/* SCORM - Ocultar Reiniciar si ya se aprobó el quiz */}
                                    {item.type === 'scorm' && !evaluationPassed && (
                                        <div className={`p-6 rounded-xl border flex items-center justify-between ${isLightBg ? 'bg-black/5 border-black/10' : 'bg-white/5 border-white/10'}`}>
                                            <div className="flex items-center gap-4">
                                                <div className="w-12 h-12 bg-brand/20 rounded-lg flex items-center justify-center">
                                                    <Lock className="w-6 h-6 text-brand" />
                                                </div>
                                                <div>
                                                    <h4 className="font-bold">{t.interactive_activity}</h4>
                                                    <p className={`text-sm ${isLightBg ? 'text-slate-500' : 'text-white/40'}`}>SCORM</p>
                                                </div>
                                            </div>
                                            <button
                                                onClick={() => setScormModalItem(item)}
                                                className="px-6 py-3 bg-brand text-black font-bold rounded-lg hover:scale-105 transition-all shadow-lg"
                                            >
                                                {itemsCompleted.has(item.id) ? t.restart : t.start}
                                            </button>
                                        </div>
                                    )}

                                    {/* Quiz */}
                                    {item.type === 'quiz' && (
                                        <div className={`${((approved || evaluationPassed) && isEvaluation) ? 'opacity-50 pointer-events-none' : ''}`}>
                                            <QuizEngine
                                                questions={item.content.questions || []}
                                                passingScore={isEvaluation ? (currentModule.settings?.min_score || 60) : 100}
                                                courseId={courseId}
                                                enrollmentId={enrollment?.id}
                                                currentEnrollment={enrollment}
                                                onComplete={(score, passed) => handleEvaluationItemScore(item.id, score, 'quiz', passed)}
                                                persistScore={isEvaluation}
                                                forceFinished={isEvaluation && evaluationPassed}
                                                language={language}
                                            />
                                        </div>
                                    )}

                                    {/* Survey */}
                                    {item.type === 'survey' && (
                                        <div className="bg-white/5 border border-white/10 rounded-3xl overflow-hidden mt-6">
                                            {evaluationPassed && !surveyDone && (
                                                <div className="mx-6 mt-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                                                    <p className="text-yellow-500 text-xs md:text-sm font-bold text-center">{t.survey_pending_after_exam}</p>
                                                </div>
                                            )}
                                            <SurveyEngine 
                                                surveyId={item.content?.survey_id} 
                                                studentId={studentId}
                                                enrollmentId={enrollment?.id}
                                                onComplete={() => {
                                                    handleItemCompletion(item.id);
                                                    setSurveyDone(true);
                                                    // Forzar guardado inmediato si ya estaba aprobado
                                                    if (approved || evaluationPassed) {
                                                        const quizWeight = (currentModule?.settings?.quiz_percentage ?? enrollment?.courses?.config?.weight_quiz ?? 80) / 100;
                                                        const scormWeight = (currentModule?.settings?.scorm_percentage ?? enrollment?.courses?.config?.weight_scorm ?? 20) / 100;
                                                        const total = (quizScore! * quizWeight) + (scormScore * scormWeight);
                                                        updateEnrollmentStatus('completed', Math.round(total));
                                                    }
                                                }}
                                                language={language as any}
                                            />
                                        </div>
                                    )}

                                    {/* Firma - Solo mostrar si se ha añadido explícitamente */}
                                    {item.type === 'signature' && (
                                        <div className={`p-8 rounded-2xl border text-center ${isLightBg ? 'bg-black/5 border-black/10' : 'bg-white/5 border-white/10'}`}>
                                            <h3 className="text-xl font-bold mb-2">{t.signature_title}</h3>
                                            <p className={`text-sm mb-6 ${isLightBg ? 'text-slate-500' : 'text-white/40'}`}>{t.signature_desc}</p>
                                            
                                            {itemsCompleted.has(item.id) ? (
                                                <div className="bg-brand/10 p-6 rounded-xl border border-brand/20 flex flex-col items-center gap-3">
                                                    <CheckCircle2 className="w-12 h-12 text-brand" />
                                                    <p className="text-brand font-bold">{t.signature_success}</p>
                                                </div>
                                            ) : (
                                                <SignatureCanvas 
                                                    onSave={(data) => handleSignatureSave(data, item.id)} 
                                                    isLight={isLightBg}
                                                />
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>

                        {/* Banner de Aprobación Final */}
                        {isEvaluation && approved && (
                            <motion.div 
                                initial={{ opacity: 0, scale: 0.9 }}
                                animate={{ opacity: 1, scale: 1 }}
                                className="mt-12 bg-brand/5 p-12 rounded-3xl border border-brand/20 text-center shadow-2xl shadow-brand/10"
                            >
                                <div className="w-20 h-20 bg-brand rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_30px_rgba(49,210,45,0.4)]">
                                    <CheckCircle2 className="w-10 h-10 text-black" />
                                </div>
                                <h3 className="text-3xl font-black text-brand mb-2">{t.course_approved}</h3>
                                <p className="text-white/60 text-lg mt-4 font-medium">{t.course_approved_desc}</p>
                                <p className="text-white/40 text-sm mt-2">{t.diploma_desc}</p>
                                
                                {moduleCompleted ? (
                                    <button
                                        onClick={() => window.location.href = '/admin/empresa/alumnos/cursos?download=' + (currentModule.title || 'Certificado')}
                                        className="mt-8 px-10 py-5 bg-brand text-black font-black rounded-xl flex items-center justify-center gap-3 mx-auto hover:scale-105 transition-all shadow-xl shadow-brand/20 text-lg"
                                    >
                                        <Download className="w-6 h-6" /> {t.download_cert}
                                    </button>
                                ) : (
                                    <div className="mt-8 p-6 bg-yellow-500/10 border border-yellow-500/30 rounded-2xl flex flex-col items-center gap-2">
                                        <div className="w-10 h-10 bg-yellow-500/20 rounded-full flex items-center justify-center">
                                            <Library className="w-5 h-5 text-yellow-500" />
                                        </div>
                                        <p className="text-yellow-500 text-sm font-bold">{t.survey_prerequisite}</p>
                                    </div>
                                )}
                            </motion.div>
                        )}
                </div>
              </div>
            </div>

            {/* Footer de Navegación Profesional */}
            <footer className="w-full bg-black/98 border-t border-white/10 z-[100] backdrop-blur-xl relative">
                {/* Slim Progress Bar on top of footer */}
                <div className="absolute top-0 left-0 w-full h-1 bg-white/5 overflow-hidden">
                    <motion.div
                        className="h-full bg-brand shadow-[0_0_15px_rgba(49,210,45,0.4)]"
                        initial={{ width: 0 }}
                        animate={{ width: `${((activeModuleIndex + 1) / modules.length) * 100}%` }}
                        transition={{ type: "spring", stiffness: 100, damping: 20 }}
                    />
                </div>

                <div className="max-w-4xl mx-auto flex items-center justify-between py-5 px-4 md:px-8">
                    {/* Info Módulo */}
                    <div className="flex items-center gap-6">
                        <div className="flex flex-col">
                            <span className="text-[10px] text-white/40 uppercase tracking-widest mb-1 font-bold">{t.current_module}</span>
                            <span className="text-sm font-bold text-white/90 truncate max-w-[200px]">
                                {activeModuleIndex + 1}. {currentModule?.title}
                            </span>
                        </div>
                        <div className="h-8 w-px bg-white/10 hidden sm:block"></div>
                        <div className="flex flex-col hidden sm:flex">
                            <span className="text-[10px] text-white/40 uppercase tracking-widest mb-1 font-bold">{t.progress}</span>
                            <span className="text-sm font-mono text-brand font-bold">
                                {activeModuleIndex + 1}/{modules.length}
                            </span>
                        </div>
                    </div>

                    {/* Botones de Navegación */}
                    <div className="flex items-center gap-4">
                        <button
                            onClick={handlePrevious}
                            disabled={activeModuleIndex === 0}
                            className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white/5 text-white/60 hover:bg-white/10 transition-colors disabled:opacity-30 disabled:cursor-not-allowed text-sm font-bold border border-white/10 group"
                        >
                            <ChevronLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                            <span>{t.previous}</span>
                        </button>

                        <button
                            onClick={() => {
                                if (activeModuleIndex === modules.length - 1) {
                                    window.location.href = '/admin/empresa/alumnos/cursos';
                                } else {
                                    handleNext();
                                }
                            }}
                            disabled={mode !== 'preview' && !moduleCompleted}
                            style={{ 
                                opacity: (mode !== 'preview' && !moduleCompleted) ? 0.3 : 1,
                                cursor: (mode !== 'preview' && !moduleCompleted) ? 'not-allowed' : 'pointer',
                                backgroundColor: (mode !== 'preview' && !moduleCompleted) ? '#333' : '#31D22D'
                            }}
                            className="flex items-center gap-2 px-6 py-2.5 rounded-lg text-black hover:bg-brand/90 transition-all active:scale-95 text-sm font-black shadow-[0_0_20px_rgba(49,210,45,0.3)] group"
                        >
                            <span>{activeModuleIndex === modules.length - 1 ? t.finish : t.next}</span>
                            <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </button>
                    </div>
                </div>
            </footer>

            {/* SCORM Modal */}
            <AnimatePresence>
                {scormModalItem && (
                    <div className="fixed inset-0 z-[200]">
                        <ScormPlayer
                            courseUrl={scormModalItem.content.url}
                            courseTitle={currentModule.title || "Actividad"}
                            user={enrollment?.students || { id: studentId }}
                            enrollment={enrollment}
                            courseId={courseId}
                            onClose={() => setScormModalItem(null)}
                            onComplete={(score) => {
                                handleEvaluationItemScore(scormModalItem.id, score, 'scorm', score >= 60);
                            }}
                        />
                    </div>
                )}
            </AnimatePresence>
        </div>
    );
}
