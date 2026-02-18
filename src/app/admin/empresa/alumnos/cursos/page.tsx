"use client";

import { useEffect, useState, useRef, useCallback } from "react";
import Image from "next/image";
import {
    LogOut, BookOpen, Clock, Award, CheckCircle2,
    X, Lock, Download, ChevronRight, Percent, Award as AwardIcon, Users,
    Globe
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import ScormPlayer from "@/components/ScormPlayer";
import QuizEngine from "@/components/QuizEngine";
import CertificateCanvas from "@/components/CertificateCanvas";
import CoursePlayer from "@/components/CoursePlayer";
import { jsPDF } from "jspdf";
import { supabase } from "@/lib/supabase";

const translations: any = {
    es: {
        welcome: "Bienvenido",
        assigned_courses: "Cursos Asignados",
        completed: "Completados",
        global_avg: "Promedio Global",
        my_training: "Mis Capacitaciones",
        view_courses: "Vista de Cursos",
        loading_courses: "Cargando cursos asignados...",
        no_courses: "No tienes cursos asignados actualmente.",
        in_progress: "En Progreso",
        completed_course: "Curso Realizado",
        available: "Disponible",
        start: "Comenzar",
        certificate: "Certificado",
        access_desc: "Accede a tus cursos asignados y mantén tu certificación al día.",
        collab: "Colaborador",
        logout: "Cerrar Sesión",
    },
    ht: {
        welcome: "Byenvini",
        assigned_courses: "Kou Asiyen",
        completed: "Konplè",
        global_avg: "Mwayèn Global",
        my_training: "Fòmasyon m yo",
        view_courses: "Gade kou yo",
        loading_courses: "Chaje kou asiyen...",
        no_courses: "Ou pa gen okenn kou asiyen kounye a.",
        in_progress: "Nan Pwogrè",
        completed_course: "Kou Konplete",
        available: "Disponib",
        start: "Kòmanse",
        certificate: "Sètifika",
        access_desc: "Aksede kou ou asiyen yo epi kenbe sètifikasyon ou a jou.",
        collab: "Kolaboratè",
        logout: "Dekonekte",
    }
};

export default function CoursesPage() {
    const [user, setUser] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [enrollments, setEnrollments] = useState<any[]>([]);
    const [activeCourse, setActiveCourse] = useState<any>(null);
    const [courseContent, setCourseContent] = useState<any>(null);
    const [certData, setCertData] = useState<any>(null);
    const [companyInfo, setCompanyInfo] = useState<any>(null);
    const [isGeneratingCert, setIsGeneratingCert] = useState(false);
    const certGenerationLock = useRef(false); // Lock robusto para evitar doble descarga

    const t = translations[user?.language || 'es'];

    useEffect(() => {
        const storedUser = localStorage.getItem("user");
        if (storedUser) {
            const parsedUser = JSON.parse(storedUser);
            setUser(parsedUser);
            fetchEnrollments(parsedUser.id, parsedUser.client_id);
        } else {
            window.location.href = "/admin/empresa/alumnos/login";
        }
    }, []);

    const fetchEnrollments = async (studentId: string, clientId: string) => {
        setLoading(true);

        // 0. Recargar datos del estudiante para asegurar el idioma más reciente
        const { data: studentData } = await supabase
            .from('students')
            .select('*')
            .eq('id', studentId)
            .single();
        
        if (studentData) {
            setUser(studentData);
            localStorage.setItem("user", JSON.stringify(studentData));
        }

        // 1. Obtener Info Empresa (Firmas)
        const { data: comp } = await supabase.from('companies').select('*').eq('id', clientId).single();
        if (comp) setCompanyInfo(comp);

        // 2. Obtener Inscripciones
        const { data: enrData } = await supabase
            .from('enrollments')
            .select('*, courses(*, course_modules(*))')
            .eq('student_id', studentId);

        if (enrData) {
            // Para cada enrollment, obtener su progreso
            const processedPromises = enrData.map(async (e) => {
                const { data: progress } = await supabase
                    .from('course_progress')
                    .select('*')
                    .eq('enrollment_id', e.id);

                console.log('Progress for enrollment', e.id, ':', progress);

                const scormProgress = progress?.find((p: any) => p.module_type === 'scorm');
                const scormCompleted = !!scormProgress?.completed_at;
                const quizCompleted = e.status === 'completed';

                // Calcular progreso parcial
                let partialProgress = e.progress || 0; // Prioritize DB progress
                const courseConfig = e.courses;

                // Solo si no hay progreso guardado en DB (Legacy o SCORM antiguo), calcular dinámicamente
                if (!partialProgress && partialProgress !== 0) { // Check if undefined/null
                    if (courseConfig?.scorm_weight && courseConfig?.quiz_weight) {
                        if (scormCompleted) partialProgress += parseFloat(courseConfig.scorm_weight);
                        if (quizCompleted) partialProgress += parseFloat(courseConfig.quiz_weight);
                    } else if (courseConfig?.config?.scorm_url && courseConfig?.config?.questions) {
                        if (scormCompleted) partialProgress = 50;
                        if (quizCompleted) partialProgress = 100;
                    } else {
                        if (scormCompleted || quizCompleted) partialProgress = 100;
                    }
                }

                console.log('Calculated progress:', { scormCompleted, quizCompleted, partialProgress, dbProgress: e.progress });

                return {
                    ...e,
                    course: e.courses,
                    config: e.courses?.config,
                    scorm_completed: scormCompleted,
                    partial_progress: Math.round(partialProgress)
                };
            });

            const processed = await Promise.all(processedPromises);
            console.log('Processed enrollments:', processed);
            setEnrollments(processed);
        }

        setLoading(false);
    };

    // Fetch course content when activeCourse changes
    useEffect(() => {
        const fetchContent = async () => {
            if (activeCourse?.course?.id) {
                const { data } = await supabase
                    .from('course_content')
                    .select('key, value')
                    .eq('course_id', activeCourse.course.id);

                if (data && data.length > 0) {
                    // Transform array to object map
                    const contentMap = data.reduce((acc: any, curr: any) => {
                        acc[curr.key] = curr.value;
                        return acc;
                    }, {});
                    setCourseContent(contentMap);
                } else {
                    setCourseContent(null);
                }
            }
        };

        if (activeCourse) {
            fetchContent();
        } else {
            setCourseContent(null);
        }
    }, [activeCourse]);

    const handleLogout = () => {
        const user = localStorage.getItem("user");
        let redirectUrl = "/admin/empresa/alumnos/login";
        
        if (user) {
            try {
                const parsedUser = JSON.parse(user);
                // Si tenemos la info de la empresa y el slug, redirigimos al portal
                if (companyInfo && companyInfo.slug) {
                    redirectUrl = `/portal/${companyInfo.slug}`;
                } else if (parsedUser.companies && parsedUser.companies.slug) {
                    redirectUrl = `/portal/${parsedUser.companies.slug}`;
                }
            } catch (e) {
                console.error("Error parsing user for logout redirect", e);
            }
        }

        localStorage.removeItem("user");
        window.location.href = redirectUrl;
    };

    const handleLanguageChange = async (newLang: string) => {
        if (!user) return;
        
        const { error } = await supabase
            .from('students')
            .update({ language: newLang })
            .eq('id', user.id);

        if (!error) {
            const updatedUser = { ...user, language: newLang };
            setUser(updatedUser);
            localStorage.setItem("user", JSON.stringify(updatedUser));
            // Actualizar enrollments para refrescar cualquier título traducido
            fetchEnrollments(user.id, user.client_id);
        }
    };

    const handleDownloadCertificate = async (enrollment: any) => {
        if (certGenerationLock.current) return; // Lock manual inmediato
        setIsGeneratingCert(true);
        certGenerationLock.current = true;
        
        // Asegurar que companyInfo existe antes de procesar
        if (!companyInfo) {
            console.error("No company info available for signatures");
            setIsGeneratingCert(false);
            certGenerationLock.current = false;
            return;
        }

        const sigs = [
            { url: companyInfo.signature_url_1, name: companyInfo.signature_name_1, role: companyInfo.signature_role_1 },
            { url: companyInfo.signature_url_2, name: companyInfo.signature_name_2, role: companyInfo.signature_role_2 },
            { url: companyInfo.signature_url_3, name: companyInfo.signature_name_3, role: companyInfo.signature_role_3 }
        ].filter(s => s.url || s.name);

        // CRÍTICO: Obtener la firma digital del estudiante y otros datos de perfil
        const { data: studentData, error: studentError } = await supabase
            .from('students')
            .select('digital_signature_url, age, gender, company_name, job_position, role_id')
            .eq('id', user.id)
            .single();

        if (studentError) {
            console.error("Error fetching student signature:", studentError);
        }

        // Obtener nombre del cargo
        let jobName = studentData?.job_position;
        
        // 1. Intentar por role_id (Cargo específico de empresa)
        if (studentData?.role_id) {
            const { data: roleInfo } = await supabase
                .from('company_roles')
                .select('name, name_ht')
                .eq('id', studentData.role_id)
                .single();
            if (roleInfo) {
                jobName = user.language === 'ht' ? roleInfo.name_ht || roleInfo.name : roleInfo.name;
            }
        } 
        // 2. Si no hay role_id o falló, intentar por job_position (global code)
        else if (studentData?.job_position) {
            const { data: jobInfo } = await supabase
                .from('job_positions')
                .select('name_es, name_ht')
                .eq('code', studentData.job_position)
                .single();
            
            if (jobInfo) {
                jobName = user.language === 'ht' ? jobInfo.name_ht || jobInfo.name_es : jobInfo.name_es;
            }
        }

        const studentSignature = studentData?.digital_signature_url;
        
        if (!studentSignature) {
            console.warn("⚠️ Student has no digital signature saved. Certificate will be generated without it.");
        } else {
            console.log("✅ Student signature found, will be included in certificate");
        }

        setCertData({
            studentName: `${user.first_name} ${user.last_name}`,
            rut: user.rut,
            courseName: enrollment.course.name.toUpperCase(),
            date: new Date(enrollment.completed_at || Date.now()).toLocaleDateString(),
            signatures: sigs,
            studentSignature: studentSignature,
            companyLogo: companyInfo.logo_url,
            companyName: studentData?.company_name || companyInfo.name,
            jobPosition: jobName,
            age: studentData?.age,
            gender: studentData?.gender
        });
    };

    // Stats
    const completedCount = enrollments.filter(e => e.status === 'completed').length;
    const avgScore = completedCount > 0
        ? Math.round(enrollments.filter(e => e.status === 'completed').reduce((acc, curr) => acc + (curr.best_score || 0), 0) / completedCount)
        : 0;

    if (!user) return null;

    return (
        <div className="min-h-screen text-white flex flex-col relative overflow-hidden bg-[#060606] font-sans">

            {/* Background Premium */}
            <div className="fixed inset-0 pointer-events-none z-0">
                <div className="absolute top-0 right-0 w-[800px] h-[800px] rounded-full blur-[160px] opacity-10" style={{ background: 'radial-gradient(circle, #00f2ff 0%, transparent 70%)', transform: 'translate(40%, -40%)' }} />
                <div className="absolute bottom-0 left-0 w-[600px] h-[600px] rounded-full blur-[140px] opacity-5" style={{ background: 'radial-gradient(circle, #31D22D 0%, transparent 70%)', transform: 'translate(-30%, 30%)' }} />
            </div>

            <AnimatePresence>
                {activeCourse && (
                    <div className="fixed inset-0 z-[100] bg-[#060606] flex flex-col overflow-hidden">
                        {/* Header Superior Consolidado */}
                        <div className="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-black/40 backdrop-blur-xl z-[110]">
                            <div className="flex items-center gap-4">
                                <div className="w-10 h-10 rounded-xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                    <div className="w-2 h-2 rounded-full bg-brand animate-pulse shadow-[0_0_10px_#31D22D]" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-white uppercase tracking-wider">{activeCourse.course.name}</h3>
                                    <p className="text-[10px] text-white/40 uppercase tracking-widest font-black">Plataforma de Capacitación</p>
                                </div>
                            </div>
                            <button 
                                onClick={() => setActiveCourse(null)} 
                                className="p-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-white hover:text-brand transition-all border border-white/10 flex items-center gap-2 group"
                            >
                                <span className="text-xs font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">Cerrar</span>
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <div className="flex-1 flex flex-col overflow-hidden">
                            {!activeCourse.course.config && !activeCourse.course.id ? (
                                <div className="text-center space-y-4 py-20 flex-1 flex flex-col items-center justify-center">
                                    <Lock className="w-16 h-16 text-white/20 mx-auto" />
                                    <h3 className="text-xl font-bold">Contenido no disponible</h3>
                                </div>
                            ) : (
                                /* Dynamic Course Player Handling */
                                /* Si tiene módulos dinámicos, usamos el nuevo CoursePlayer. Si no, revisamos legado */
                                ((activeCourse.course.course_modules && activeCourse.course.course_modules.length > 0) || (!activeCourse.course.config?.scorm_url && !activeCourse.course.config?.questions)) ? (
                                    <div className="flex-1 overflow-hidden">
                                        <CoursePlayer
                                            courseId={activeCourse.course.id}
                                            studentId={user.id}
                                            onComplete={() => {
                                                fetchEnrollments(user.id, user.client_id);
                                            }}
                                            className="h-full"
                                            language={user.language || 'es'}
                                        />
                                    </div>
                                ) :
                                    /* Legacy SCORM/Quiz Handling */
                                    activeCourse.course.config.scorm_url && activeCourse.course.config.questions ? (
                                        // Curso con SCORM + Quiz: Mostrar según progreso
                                        (() => {
                                            // Verificar si ya completó SCORM
                                            const scormCompleted = activeCourse.scorm_completed || false;

                                            if (!scormCompleted) {
                                                // Mostrar SCORM primero
                                                return (
                                                    <ScormPlayer
                                                        courseUrl={activeCourse.course.config.scorm_url}
                                                        courseTitle={activeCourse.course.name}
                                                        user={user}
                                                        enrollment={activeCourse}
                                                        courseConfig={activeCourse.course}
                                                        language={user.language || 'es'}
                                                        onClose={() => {
                                                            setActiveCourse(null);
                                                            fetchEnrollments(user.id, user.client_id);
                                                        }}
                                                        onComplete={(scormScore) => {
                                                            console.log("SCORM completed with score:", scormScore);
                                                            // Marcar SCORM como completado y refrescar
                                                            setTimeout(() => {
                                                                fetchEnrollments(user.id, user.client_id);
                                                            }, 1500);
                                                        }}
                                                    />
                                                );
                                            } else {
                                                // SCORM ya completado, mostrar Quiz
                                                return (
                                                    <QuizEngine
                                                        config={{ ...activeCourse.course.config, id: activeCourse.course.id, title: activeCourse.course.name }}
                                                        user={user}
                                                        currentEnrollment={activeCourse}
                                                        language={user.language || 'es'}
                                                        onFinish={(s: number) => {
                                                            fetchEnrollments(user.id, user.client_id);
                                                        }}
                                                    />
                                                );
                                            }
                                        })()
                                    ) : activeCourse.course.config.scorm_url ? (
                                        // Solo SCORM
                                        <ScormPlayer
                                            courseUrl={activeCourse.course.config.scorm_url}
                                            courseTitle={activeCourse.course.name}
                                            user={user}
                                            enrollment={activeCourse}
                                            courseConfig={activeCourse.course}
                                            language={user.language || 'es'}
                                            onClose={() => {
                                                setActiveCourse(null);
                                                fetchEnrollments(user.id, user.client_id);
                                            }}
                                            onComplete={(scormScore) => {
                                                console.log("SCORM completed with score:", scormScore);
                                            }}
                                        />
                                    ) : activeCourse.course.config.questions ? (
                                        <QuizEngine
                                            config={{ ...activeCourse.course.config, id: activeCourse.course.id, title: activeCourse.course.name }}
                                            user={user}
                                            currentEnrollment={activeCourse}
                                            language={user.language || 'es'}
                                            onFinish={async (finalScore: number) => {
                                                // Refrescar enrollments
                                                await fetchEnrollments(user.id, user.client_id);

                                                // Si aprobó (>60%), descargar certificado automáticamente
                                                if (finalScore >= 60) {
                                                    setTimeout(() => {
                                                        // Buscar el enrollment actualizado
                                                        const updatedEnrollment = enrollments.find(e => e.course.id === activeCourse.course.id);
                                                        if (updatedEnrollment) {
                                                            handleDownloadCertificate(updatedEnrollment);
                                                        }
                                                    }, 1500);
                                                }
                                            }}
                                        />
                                    ) : (
                                        <div className="text-center text-white/40">Error: Formato de curso desconocido.</div>
                                    )
                            )}
                        </div>
                    </div>
                )}
            </AnimatePresence>

            <header className="sticky top-0 z-50 w-full bg-[#0a0a0a]/80 backdrop-blur-2xl border-b border-white/10 px-6 py-4 flex items-center justify-between shadow-2xl">
                <div className="flex items-center gap-4">
                    <img src="/logo-metaverso.png" alt="Logo" className="h-8 w-auto hover:opacity-80 transition-opacity" />
                    <div className="h-6 w-px bg-white/10 mx-2 hidden md:block" />
                    <div className="hidden md:flex flex-col">
                        <span className="text-[10px] text-white/40 uppercase tracking-widest font-black">{t?.collab}</span>
                        <span className="text-sm font-bold text-brand">{companyInfo?.name || "Empresa"}</span>
                    </div>
                </div>

                <div className="flex items-center gap-4">
                    {/* Selector de Idioma */}
                    <div className="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 backdrop-blur-md">
                        <Globe className="w-4 h-4 text-white/40" />
                        <select 
                            value={user.language || 'es'} 
                            onChange={(e) => handleLanguageChange(e.target.value)}
                            className="bg-transparent border-none text-xs font-bold uppercase tracking-widest outline-none cursor-pointer text-white/70 hover:text-white transition-colors"
                        >
                            <option value="es" className="bg-[#111]">ES</option>
                            <option value="ht" className="bg-[#111]">HT</option>
                        </select>
                    </div>

                    <div className="flex flex-col items-end mr-2">
                        <span className="text-sm font-black tracking-tight">{user.first_name} {user.last_name}</span>
                        <span className="text-xs text-white/40 font-mono">{user.rut}</span>
                    </div>
                    <button onClick={handleLogout} title={t?.logout} className="p-2.5 rounded-xl bg-white/5 hover:bg-red-500/10 hover:text-red-400 transition-all border border-white/10 group">
                        <LogOut className="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
                    </button>
                </div>
            </header>

            <main className="flex-1 max-w-7xl mx-auto w-full p-4 md:p-8 space-y-10 relative z-10">
                <section className="relative overflow-hidden rounded-[32px] p-8 md:p-14 glass border-brand/20 shadow-[0_0_80px_rgba(49,210,45,0.08)]">
                    <div className="absolute top-0 left-0 w-64 h-64 bg-brand/10 blur-[100px] -ml-32 -mt-32 pointer-events-none" />
                    <div className="absolute bottom-0 right-0 w-64 h-64 bg-blue-500/10 blur-[100px] -mr-32 -mb-32 pointer-events-none" />

                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="space-y-6 relative z-10">
                        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand/10 border border-brand/20 text-brand text-xs font-black uppercase tracking-widest">
                            <div className="w-1.5 h-1.5 rounded-full bg-brand animate-pulse" />
                            {companyInfo?.name} Training Hub
                        </div>
                        <h2 className="text-4xl md:text-6xl font-black tracking-tighter leading-none">
                            {t?.welcome},<br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-brand to-blue-400">{user.first_name}</span>
                        </h2>
                        <p className="text-white/50 text-lg max-w-2xl font-medium leading-relaxed">
                            {t?.access_desc}
                        </p>
                    </motion.div>
                </section>

                <section className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {[
                        { label: t?.assigned_courses, value: enrollments.length, icon: BookOpen },
                        { label: t?.completed, value: completedCount, icon: CheckCircle2 },
                        { label: t?.global_avg, value: `${avgScore}%`, icon: Percent },
                    ].map((stat, i) => (
                        <motion.div key={i} initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} transition={{ delay: i * 0.1 }}
                            className="glass p-8 rounded-3xl flex items-center justify-between group transition-all duration-500 relative overflow-hidden bg-white/[0.02]">
                            <div className="absolute top-0 right-0 w-32 h-32 bg-brand/5 blur-3xl group-hover:bg-brand/10 transition-all" />
                            <div className="space-y-1 relative z-10">
                                <span className="text-white/40 text-[10px] font-black uppercase tracking-[0.2em]">{stat.label}</span>
                                <p className="text-4xl font-black tracking-tighter">{stat.value}</p>
                            </div>
                            <stat.icon className="w-12 h-12 text-brand opacity-60 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500 relative z-10" />
                        </motion.div>
                    ))}
                </section>

                <section className="space-y-8">
                    <div className="flex items-center justify-between">
                        <h3 className="text-2xl font-black tracking-tight flex items-center gap-3">
                            <div className="w-2.5 h-8 bg-brand rounded-full shadow-[0_0_15px_#31D22D]" />
                            {t?.my_training}
                        </h3>
                        <span className="text-xs text-white/30 font-bold uppercase tracking-widest">{t?.view_courses}</span>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 pb-20">
                        {loading && <div className="text-white/40 animate-pulse col-span-full">{t?.loading_courses}</div>}

                        {!loading && enrollments.length === 0 && (
                            <div className="col-span-full py-12 text-center border border-white/10 rounded-3xl bg-white/[0.02]">
                                <BookOpen className="w-12 h-12 text-white/20 mx-auto mb-4" />
                                <p className="text-white/40 font-medium">{t?.no_courses}</p>
                            </div>
                        )}

                        {enrollments.map((enrollment, i) => {
                            const isCompleted = enrollment.status === 'completed';
                            const course = enrollment.course;

                            return (
                                <motion.div
                                    key={enrollment.id}
                                    initial={{ opacity: 0, y: 30 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: i * 0.15 }}
                                    className={`glass group rounded-[2.5rem] overflow-hidden flex flex-col transition-all duration-500 hover:shadow-[0_20px_50px_rgba(0,0,0,0.5)] border-white/5 active:scale-[0.98] ${isCompleted ? 'border-brand/40 bg-brand/[0.02]' : 'hover:border-white/20'}`}
                                >
                                    <div className="h-44 bg-white/[0.03] relative flex flex-col items-center justify-center border-b border-white/5 group-hover:bg-white/[0.05] transition-all">
                                        {isCompleted ? (
                                            <div className="flex flex-col items-center gap-2 relative z-10">
                                                <div className="w-14 h-14 bg-brand/20 rounded-full flex items-center justify-center border border-brand/50 shadow-[0_0_30px_rgba(49,210,45,0.3)]">
                                                    <CheckCircle2 className="w-8 h-8 text-brand" />
                                                </div>
                                                <span className="text-brand font-black text-xl tracking-tighter">NOTA: {enrollment.best_score}%</span>
                                            </div>
                                        ) : enrollment.partial_progress > 0 ? (
                                            <div className="flex flex-col items-center gap-2 relative z-10">
                                                <div className="relative w-20 h-20">
                                                    <svg className="w-20 h-20 transform -rotate-90">
                                                        <circle cx="40" cy="40" r="32" stroke="currentColor" strokeWidth="6" fill="none" className="text-white/10" />
                                                        <circle cx="40" cy="40" r="32" stroke="currentColor" strokeWidth="6" fill="none" className="text-brand" strokeDasharray={`${2 * Math.PI * 32}`} strokeDashoffset={`${2 * Math.PI * 32 * (1 - enrollment.partial_progress / 100)}`} strokeLinecap="round" />
                                                    </svg>
                                                    <div className="absolute inset-0 flex items-center justify-center">
                                                        <span className="text-brand font-black text-lg">{enrollment.partial_progress}%</span>
                                                    </div>
                                                </div>
                                                <span className="text-brand/80 font-bold text-xs">{t?.in_progress}</span>
                                            </div>
                                        ) : (
                                            <BookOpen className="w-16 h-16 text-white/10 group-hover:text-brand/30 transition-all duration-700 group-hover:scale-110" />
                                        )}

                                        <div className={`mt-3 text-[9px] px-3 py-1 rounded-full font-black uppercase tracking-[0.15em] border z-10 backdrop-blur-md ${isCompleted ? 'bg-brand/20 text-brand border-brand/40 shadow-[0_5px_15px_rgba(49,210,45,0.2)]' : enrollment.partial_progress > 0 ? 'bg-yellow-500/20 text-yellow-400 border-yellow-500/40' : 'bg-white/5 text-white/40 border-white/10'}`}>
                                            {isCompleted ? t?.completed_course : enrollment.partial_progress > 0 ? `${enrollment.partial_progress}% ${t?.completed}` : t?.available}
                                        </div>
                                    </div>

                                    <div className="p-8 space-y-6 relative z-10">
                                        <div className="space-y-2">
                                            <h4 className="text-xl font-black leading-tight group-hover:text-brand transition-colors line-clamp-2 uppercase tracking-tighter">{course.name}</h4>
                                            <div className="flex items-center gap-4 text-[10px] font-black text-white/30 uppercase tracking-widest">
                                                <span className="flex items-center gap-1.5"><Clock className="w-3 h-3" /> 45'</span>
                                                <span>•</span>
                                                <span>{companyInfo?.name}</span>
                                            </div>
                                        </div>

                                        <div className="flex gap-3">
                                            {!isCompleted ? (
                                                <button
                                                    onClick={() => setActiveCourse(enrollment)}
                                                    className="flex-1 py-4 font-black uppercase tracking-widest text-xs rounded-2xl transition-all shadow-xl flex items-center justify-center gap-2 bg-brand text-black hover:bg-white hover:scale-[1.03] shadow-brand/20"
                                                >
                                                    {t?.start} <ChevronRight className="w-4 h-4" />
                                                </button>
                                            ) : (
                                                <button
                                                    onClick={() => handleDownloadCertificate(enrollment)}
                                                    className="flex-1 py-4 bg-brand text-black border border-brand/30 rounded-2xl hover:bg-white transition-all flex items-center justify-center gap-2 font-black uppercase tracking-widest text-xs"
                                                >
                                                    <Award className="w-5 h-5" /> {t?.certificate}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </motion.div>
                            );
                        })}
                    </div>
                </section>
            </main>

            {/* Motor PDF (Oculto) */}
            {certData && (
                <CertificateCanvas
                    {...certData}
                    onReady={(blob) => {
                        // PREVENIR DOBLE DESCARGA: Solo ejecutar una vez si el lock está activo
                        if (!certGenerationLock.current) return;
                        
                        // Bloquear inmediatamente futuras llamadas antes de cualquier proceso async
                        certGenerationLock.current = false;
                        
                        const reader = new FileReader();
                        reader.readAsDataURL(blob);
                        reader.onloadend = () => {
                            const base64data = reader.result as string;
                            const pdf = new jsPDF("p", "px", [1414, 2000]); // Portrait: 1414x2000
                            pdf.addImage(base64data, "PNG", 0, 0, 1414, 2000);
                            pdf.save(`Certificado_${certData.rut}.pdf`);
                            
                            // Limpiar datos y estado de carga
                            setCertData(null);
                            setIsGeneratingCert(false);
                            console.log("✅ Certificado generado y descargado exitosamente.");
                        };
                    }}
                />
            )}
        </div>
    );
}
