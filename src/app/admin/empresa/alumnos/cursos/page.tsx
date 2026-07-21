"use client";

import { useEffect, useState, useRef, useCallback } from "react";
import Image from "next/image";
import {
    LogOut, BookOpen, Clock, CheckCircle2, Award,
    X, Lock, Download, ChevronRight, Percent, Award as AwardIcon, Users,
    Globe, ClipboardList
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import ScormPlayer from "@/components/ScormPlayer";
import QuizEngine from "@/components/QuizEngine";
import CertificateCanvas from "@/components/CertificateCanvas";
import CoursePlayer from "@/components/CoursePlayer";
import SignatureCanvas from "@/components/SignatureCanvas";
import { jsPDF } from "jspdf";
import { supabase } from "@/lib/supabase";
import { generateMetaversoCert } from "@/lib/generateMetaversoCert";
import { generateIrlCert } from "@/lib/generateIrlCert";
import { SACYR_COMPANY_ID, SACYR_IRL_FORMS } from "@/lib/sacyrIrlData";
import SacyrIrlFormModal from "@/components/SacyrIrlFormModal";
import { generateSacyrIrlPdf } from "@/lib/generateSacyrIrlPdf";

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
        exit_course: "Salir",
        exit_course_confirm: "Estas seguro de que deseas salir del curso?",
        access_desc: "Accede a tus cursos asignados y mantén tu certificación al día.",
        collab: "Colaborador",
        logout: "Cerrar Sesión",
        sign_now: "Firmar",
        sign_to_unlock_cert: "Firma pendiente para habilitar certificado",
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
        exit_course: "Soti",
        exit_course_confirm: "Eske ou sèten ou vle soti nan kou a?",
        access_desc: "Aksede kou ou asiyen yo epi kenbe sètifikasyon ou a jou.",
        collab: "Kolaboratè",
        logout: "Dekonekte",
        sign_now: "Siyen",
        sign_to_unlock_cert: "Siyati an reta pou aktive sètifika a",
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
    const [certFlagsMap, setCertFlagsMap] = useState<Record<string, { participacion: boolean; aprobacion: boolean; irl: boolean; irlRoleIds: string[] }>>({});
    const [diplomaConfig, setDiplomaConfig] = useState<any>(null);
    const [showSignatureModal, setShowSignatureModal] = useState(false);
    const [signatureTargetCourse, setSignatureTargetCourse] = useState<string>("");
    const [irlDocsByCourse, setIrlDocsByCourse] = useState<Record<string, Array<{ id: string; title: string; file_url: string }>>>({});
    const [irlConsentByEnrollment, setIrlConsentByEnrollment] = useState<Record<string, boolean>>({});
    const certGenerationLock = useRef(false); // Lock robusto para evitar doble descarga

    // ── Sacyr IRL ────────────────────────────────────────────────────────────
    const [sacyrIrlAssignments, setSacyrIrlAssignments] = useState<Array<{
        id: string; form_id: string; form_slug: string; status: 'pending' | 'completed';
        form_cargo_name?: string;
    }>>([]);
    const [activeSacyrIrl, setActiveSacyrIrl] = useState<string | null>(null); // assignment_id being filled

    const t = translations[user?.language || 'es'];
    const hasUserSignature = typeof user?.digital_signature_url === 'string' && user.digital_signature_url.trim().length > 0;

    const resolveParticipationFlag = (row: {
        cert_participacion_enabled?: boolean | null;
        diploma_metaverso_enabled?: boolean | null;
    }) => {
        // Keep legacy behavior for NULL, except when approval certificate is enabled.
        if (row.cert_participacion_enabled === true) return true;
        if (row.cert_participacion_enabled === false) return false;
        return row.diploma_metaverso_enabled !== true;
    };

    const MONTHS_ES_LOCAL = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    const calcExpirationDate = (completedAt?: string | null, validezAnios?: number | null): string | undefined => {
        if (!completedAt || !validezAnios) return undefined;
        const d = new Date(completedAt);
        if (isNaN(d.getTime())) return undefined;
        d.setFullYear(d.getFullYear() + validezAnios);
        return `${d.getDate()} de ${MONTHS_ES_LOCAL[d.getMonth()]} de ${d.getFullYear()}`;
    };

    const confirmExitCourse = useCallback(() => {
        if (window.confirm(t.exit_course_confirm)) {
            setActiveCourse(null);
            if (user?.id && user?.client_id) {
                fetchEnrollments(user.id, user.client_id);
            }
        }
    }, [t.exit_course_confirm, user?.id, user?.client_id]);

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

        // 1. Obtener Info Empresa (Firmas) + cert flags + diploma config
        const [{ data: comp }, { data: ccData }, { data: dipCfg }] = await Promise.all([
            supabase.from('companies').select('*').eq('id', clientId).single(),
            supabase.from('company_courses')
                .select('course_id, cert_participacion_enabled, diploma_metaverso_enabled, cert_irl_enabled, irl_role_id, irl_role_ids, start_date, validez_anios, courses(irl_certificate_enabled)')
                .eq('company_id', clientId),
            supabase.from('diploma_config')
                .select('*')
                .eq('id', '00000000-0000-0000-0000-000000000001')
                .single()
        ]);
        if (comp) setCompanyInfo(comp);
        if (dipCfg) setDiplomaConfig(dipCfg);
        // Build cert flags map
        const flagsMap: Record<string, { participacion: boolean; aprobacion: boolean; irl: boolean; irlRoleIds: string[] }> = {};
        const scheduleMap: Record<string, { validez_anios: number | null }> = {};
        (ccData || []).forEach((cc: any) => {
            const globalIrlEnabled = cc?.courses?.irl_certificate_enabled === true;
            const roleIds = Array.isArray(cc.irl_role_ids)
                ? cc.irl_role_ids.filter(Boolean)
                : (cc.irl_role_id ? [cc.irl_role_id] : []);
            const effectiveRoleIds = globalIrlEnabled ? [] : roleIds;
            flagsMap[cc.course_id] = {
                participacion: resolveParticipationFlag(cc),
                aprobacion: cc.diploma_metaverso_enabled === true,
                irl: cc.cert_irl_enabled === true || globalIrlEnabled,
                irlRoleIds: effectiveRoleIds,
            };
            scheduleMap[cc.course_id] = {
                validez_anios: cc.validez_anios ?? null,
            };
        });
        setCertFlagsMap(flagsMap);

                        // 2. Obtener Inscripciones (Revertido a query simple para evitar errores de relación profunda)
        const { data: enrData, error: enrError } = await supabase
            .from('enrollments')
            .select('*, courses(*, course_modules(*))') // course_modules solo trae IDs
            .eq('student_id', studentId);

        if (enrError) {
            console.error("Error fetching enrollments:", enrError);
        }

        if (enrData) {
            // Para cada enrollment, obtener su progreso y verificar encuestas manualmente
            const processedPromises = enrData.map(async (e) => {
                const { data: progress } = await supabase
                    .from('course_progress')
                    .select('*')
                    .eq('enrollment_id', e.id);

                console.log('Progress for enrollment', e.id, ':', progress);

                const scormProgress = progress?.find((p: any) => p.module_type === 'scorm');
                const scormCompleted = !!scormProgress?.completed_at;
                const evaluationApproved = e.status === 'completed' || e.last_exam_passed === true;

                // Verificar manualmente si hay encuesta obligatoria
                // Solo necesitamos verificar esto si el curso está completado o el examen aprobado
                let hasMandatorySurvey = false;
                if (evaluationApproved) {
                    try {
                        // Obtener los modules IDs (course_modules.id ES el module_id referenciado por module_items)
                        const moduleIds = e.courses?.course_modules?.map((cm: any) => cm.id) || [];
                        if (moduleIds.length > 0) {
                            const { data: modItemsData } = await supabase
                                .from('module_items')
                                .select('type, content')
                                .in('module_id', moduleIds)
                                .eq('type', 'survey');
                            
                            if (modItemsData) {
                                hasMandatorySurvey = modItemsData.some((item: any) => item.content?.is_mandatory);
                            }
                        }
                    } catch (err) {
                        console.error("Error checking survey status:", err);
                    }
                }

                // Calcular progreso parcial
                let partialProgress = typeof e.progress === 'number' ? e.progress : 0;
                const courseConfig = e.courses;

                // Si ya aprobó evaluación final pero falta encuesta, el curso debe verse 100% completado.
                if (evaluationApproved) {
                    partialProgress = Math.max(partialProgress, 100);
                }

                // Solo si no hay progreso guardado en DB (Legacy o SCORM antiguo), calcular dinámicamente
                if (partialProgress === 0 && !evaluationApproved) {
                    if (courseConfig?.scorm_weight && courseConfig?.quiz_weight) {
                        if (scormCompleted) partialProgress += parseFloat(courseConfig.scorm_weight);
                        if (evaluationApproved) partialProgress += parseFloat(courseConfig.quiz_weight);
                    } else if (courseConfig?.config?.scorm_url && courseConfig?.config?.questions) {
                        if (scormCompleted) partialProgress = 50;
                        if (evaluationApproved) partialProgress = 100;
                    } else {
                        if (scormCompleted || evaluationApproved) partialProgress = 100;
                    }
                }

                console.log('Calculated progress:', { scormCompleted, evaluationApproved, partialProgress, dbProgress: e.progress });

                return {
                    ...e,
                    course: {
                        ...e.courses,
                        company_course_validez_anios: scheduleMap[e.course_id]?.validez_anios ?? null,
                    },
                    config: e.courses?.config,
                    scorm_completed: scormCompleted,
                    partial_progress: Math.round(partialProgress),
                    has_mandatory_survey: hasMandatorySurvey // Propagated from manual check
                };
            });

            const processed = await Promise.all(processedPromises);
            console.log('Processed enrollments:', processed);
            setEnrollments(processed);
            const consentState: Record<string, boolean> = {};
            processed.forEach((e: any) => {
                consentState[e.id] = e.irl_confirmed === true;
            });
            setIrlConsentByEnrollment(consentState);

            const courseIds = Array.from(new Set(processed.map((e: any) => e.course_id).filter(Boolean)));
            if (courseIds.length > 0) {
                const { data: irlDocs } = await supabase
                    .from('course_irl_documents')
                    .select('id, course_id, title, file_url, sort_order, is_active')
                    .in('course_id', courseIds)
                    .eq('is_active', true)
                    .order('sort_order', { ascending: true });

                const byCourse: Record<string, Array<{ id: string; title: string; file_url: string }>> = {};
                (irlDocs || []).forEach((d: any) => {
                    if (!byCourse[d.course_id]) byCourse[d.course_id] = [];
                    byCourse[d.course_id].push({ id: d.id, title: d.title, file_url: d.file_url });
                });
                setIrlDocsByCourse(byCourse);
            } else {
                setIrlDocsByCourse({});
            }
        }

        // ── Sacyr IRL assignments ────────────────────────────────────────────
        if (clientId === SACYR_COMPANY_ID) {
            const { data: irlData } = await supabase
                .from('sacyr_irl_assignments')
                .select('id, form_id, status, sacyr_irl_forms(slug, cargo_name)')
                .eq('student_id', studentId)
                .order('assigned_at');
            setSacyrIrlAssignments(
                (irlData || []).map((a: any) => ({
                    id: a.id,
                    form_id: a.form_id,
                    form_slug: a.sacyr_irl_forms?.slug || '',
                    status: a.status,
                    form_cargo_name: a.sacyr_irl_forms?.cargo_name || '',
                }))
            );
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

        // Re-fetch student data fresh from DB to ensure we have latest values
        const { data: freshStudent, error: freshError } = await supabase
            .from('students')
            .select('*')
            .eq('id', user.id)
            .single();

        // Use fresh data if available, fall back to user state (which was loaded on page init)
        const studentSrc = freshStudent || user;

        if (freshError) {
            console.warn("⚠️ Could not refresh student data, using cached:", freshError.message);
        } else {
            // Update local user state with fresh data
            setUser(freshStudent);
            localStorage.setItem("user", JSON.stringify(freshStudent));
        }

        console.log("📋 Student source data:", { 
            role_id: studentSrc.role_id, 
            job_position: studentSrc.job_position, 
            age: studentSrc.age, 
            gender: studentSrc.gender,
            company_name: companyInfo.name,
            digital_signature_url: studentSrc.digital_signature_url ? '✓' : '✗'
        });

        // Obtener nombre del cargo
        let jobName = studentSrc.job_position || null;
        
        // 1. Intentar por role_id (Cargo específico de empresa)
        if (studentSrc.role_id) {
            const { data: roleInfo } = await supabase
                .from('company_roles')
                .select('name, name_ht')
                .eq('id', studentSrc.role_id)
                .single();
            if (roleInfo) {
                jobName = studentSrc.language === 'ht' ? roleInfo.name_ht || roleInfo.name : roleInfo.name;
            }
        } 
        // 2. Si no hay role_id o falló, intentar por job_position (global code)
        else if (studentSrc.job_position) {
            const { data: jobInfo } = await supabase
                .from('job_positions')
                .select('name_es, name_ht')
                .eq('code', studentSrc.job_position)
                .single();
            
            if (jobInfo) {
                jobName = studentSrc.language === 'ht' ? jobInfo.name_ht || jobInfo.name_es : jobInfo.name_es;
            }
        }

        const studentSignature = studentSrc.digital_signature_url;
        
        if (!studentSignature) {
            console.warn("⚠️ Student has no digital signature saved. Certificate will be generated without it.");
        } else {
            console.log("✅ Student signature found, will be included in certificate");
        }

        console.log("📋 Certificate final data:", { jobName, age: studentSrc.age, gender: studentSrc.gender, company_name: companyInfo.name });

        setCertData({
            studentName: `${studentSrc.first_name} ${studentSrc.last_name}`,
            rut: studentSrc.rut,
            courseName: enrollment.course.name.toUpperCase(),
            date: new Date(enrollment.completed_at || Date.now()).toLocaleDateString(),
            score: enrollment.best_score ?? 100,
            signatures: sigs,
            studentSignature: studentSignature,
            companyLogo: companyInfo.logo_url,
            companyName: companyInfo.name,
            jobPosition: jobName,
            age: studentSrc.age,
            gender: studentSrc.gender
        });
    };

    const handleDownloadAprobacion = async (enrollment: any) => {
        if (certGenerationLock.current) return;
        if (!diplomaConfig) {
            alert('No hay configuración de diploma.');
            return;
        }
        if (!companyInfo) {
            alert('No hay información de empresa para emitir el diploma.');
            return;
        }

        try {
            certGenerationLock.current = true;
            setIsGeneratingCert(true);

            const { data: freshStudent, error: freshError } = await supabase
                .from('students')
                .select('*')
                .eq('id', user.id)
                .single();

            if (freshError) {
                console.warn("⚠️ Could not refresh student data for diploma, using cached:", freshError.message);
            } else if (freshStudent) {
                setUser(freshStudent);
                localStorage.setItem("user", JSON.stringify(freshStudent));
            }

            const studentSrc = freshStudent || user;
            const fc = diplomaConfig.fields_config || {};

            await generateMetaversoCert({
                studentName: `${studentSrc.first_name} ${studentSrc.last_name}`,
                rut: studentSrc.rut,
                companyName: companyInfo.name,
                companyRut: companyInfo.rut || '',
                companyId: studentSrc.client_id,
                courseId: enrollment.course_id || enrollment.course?.id,
                courseName: (enrollment.course?.name || '').toUpperCase(),
                courseCode: enrollment.course?.code || '',
                hours: enrollment.course?.config?.hours,
                date: enrollment.completed_at
                    ? new Date(enrollment.completed_at).toLocaleDateString('es-CL')
                    : new Date().toLocaleDateString('es-CL'),
                expirationDate: calcExpirationDate(enrollment.completed_at, enrollment.course?.company_course_validez_anios),
                backgroundUrl: diplomaConfig.background_url,
                layoutConfig: fc.layout,
                fieldsConfig: fc,
            });
        } catch (error: any) {
            console.error("❌ Error generating approval diploma:", error);
            alert('No se pudo generar el certificado de aprobación.');
        } finally {
            setIsGeneratingCert(false);
            certGenerationLock.current = false;
        }
    };

    const handleSaveMissingSignature = async (signatureUrl: string): Promise<boolean> => {
        if (!user?.id) return false;

        const nowIso = new Date().toISOString();
        const { error } = await supabase
            .from('students')
            .update({
                digital_signature_url: signatureUrl,
                consent_accepted_at: nowIso
            })
            .eq('id', user.id);

        if (error) {
            console.error("❌ Error saving signature from courses list:", error);
            alert('No se pudo guardar tu firma. Intenta nuevamente.');
            return false;
        }

        const updatedUser = {
            ...user,
            digital_signature_url: signatureUrl,
            consent_accepted_at: nowIso
        };

        setUser(updatedUser);
        localStorage.setItem("user", JSON.stringify(updatedUser));
        setShowSignatureModal(false);
        setSignatureTargetCourse("");
        return true;
    };

    const handleToggleIrlConsent = async (enrollmentId: string, checked: boolean) => {
        setIrlConsentByEnrollment((prev) => ({ ...prev, [enrollmentId]: checked }));
        const patch: any = {
            irl_confirmed: checked,
            irl_confirmed_at: checked ? new Date().toISOString() : null,
        };
        const { error } = await supabase.from('enrollments').update(patch).eq('id', enrollmentId);
        if (error) {
            setIrlConsentByEnrollment((prev) => ({ ...prev, [enrollmentId]: !checked }));
            alert('No se pudo guardar la confirmacion IRL. Intenta nuevamente.');
        }
    };

    const handleDownloadIrlCertificate = async (enrollment: any) => {
        if (certGenerationLock.current || !user) return;

        const irlDocs = irlDocsByCourse[enrollment.course_id] || [];
        if (irlDocs.length === 0) {
            alert('Este curso no tiene documentos IRL cargados. El certificado IRL no esta disponible.');
            return;
        }

        certGenerationLock.current = true;
        setIsGeneratingCert(true);
        try {
            let jobName = user.job_position || 'Sin Cargo';
            if (user.role_id) {
                const { data: roleInfo } = await supabase
                    .from('company_roles')
                    .select('name')
                    .eq('id', user.role_id)
                    .single();
                if (roleInfo?.name) jobName = roleInfo.name;
            }

            await generateIrlCert({
                studentName: `${user.first_name} ${user.last_name}`.trim(),
                rut: user.rut,
                age: user.age,
                jobName,
                date: new Date().toLocaleDateString('es-CL'),
                companyName: companyInfo?.name || null,
                studentSignatureUrl: user.digital_signature_url || null,
                ...((() => {
                    const cfg = companyInfo?.cert_signature_config as { irl?: number[] } | null;
                    const idx = (cfg?.irl ?? [0])[0] ?? 0;
                    const urlKey = `signature_url_${idx + 1}` as const;
                    const nameKey = `signature_name_${idx + 1}` as const;
                    const roleKey = `signature_role_${idx + 1}` as const;
                    return {
                        relatorSignatureUrl: (companyInfo as any)?.[urlKey] || null,
                        relatorName:         (companyInfo as any)?.[nameKey] || null,
                        relatorRole:         (companyInfo as any)?.[roleKey] || null,
                    };
                })()),
            });
        } finally {
            setIsGeneratingCert(false);
            certGenerationLock.current = false;
        }
    };

    // Stats
    const completedCount = enrollments.filter(e => e.status === 'completed').length;
    const avgScore = completedCount > 0
        ? Math.round(enrollments.filter(e => e.status === 'completed').reduce((acc, curr) => acc + (curr.best_score || 0), 0) / completedCount)
        : 0;

    if (!user) return null;

    return (
        <div className="min-h-screen text-white flex flex-col relative overflow-hidden bg-transparent font-sans">

            {/* Background Premium */}
            <div className="fixed inset-0 pointer-events-none z-0">
                <div className="absolute top-0 right-0 w-[800px] h-[800px] rounded-full blur-[160px] opacity-10" style={{ background: 'radial-gradient(circle, #00f2ff 0%, transparent 70%)', transform: 'translate(40%, -40%)' }} />
                <div className="absolute bottom-0 left-0 w-[600px] h-[600px] rounded-full blur-[140px] opacity-5" style={{ background: 'radial-gradient(circle, #31D22D 0%, transparent 70%)', transform: 'translate(-30%, 30%)' }} />
            </div>

            <AnimatePresence>
                {activeCourse && (
                    <div className="fixed inset-0 z-[100] bg-black/65 backdrop-blur-md flex flex-col overflow-hidden">
                        {/* Header Superior Consolidado */}
                        <div className="flex items-center justify-between px-3 sm:px-6 py-3 sm:py-4 border-b border-white/10 bg-black/40 backdrop-blur-xl z-[110]">
                            <div className="flex items-center gap-4">
                                <div className="w-10 h-10 rounded-xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                    <div className="w-2 h-2 rounded-full bg-brand animate-pulse shadow-[0_0_10px_#31D22D]" />
                                </div>
                                <div>
                                    <h3 className="text-xs sm:text-sm font-bold text-white uppercase tracking-wider line-clamp-1 max-w-[180px] sm:max-w-none">{activeCourse.course.name}</h3>
                                    <p className="text-[10px] text-white/40 uppercase tracking-widest font-black">Plataforma de Capacitación</p>
                                </div>
                            </div>
                            <button 
                                onClick={confirmExitCourse}
                                className="p-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-white hover:text-brand transition-all border border-white/10 flex items-center gap-2 group"
                            >
                                <span className="text-xs font-bold uppercase tracking-widest lg:hidden">{t.exit_course}</span>
                                <X className="w-5 h-5" />
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
                                                setActiveCourse(null);
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
                                                        onClose={confirmExitCourse}
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
                                            onClose={confirmExitCourse}
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

                                                // Si aprobó (>60%), verificar si hay encuestas pendientes antes de descargar
                                                if (finalScore >= 60) {
                                                    setTimeout(async () => {
                                                        // Buscar el enrollment actualizado
                                                        const { data: updatedEnrollment, error } = await supabase
                                                            .from('enrollments')
                                                            .select('*, courses(*, course_modules(*, module_items(*)))')
                                                            .eq('id', activeCourse.id)
                                                            .single();
                                                        
                                                        if (updatedEnrollment) {
                                                            // Verificar si hay alguna encuesta obligatoria pendiente
                                                            const hasPendingSurvey = updatedEnrollment.courses?.course_modules?.some((cm: any) => 
                                                                cm.module_items?.some((item: any) => 
                                                                    item.type === 'survey' && 
                                                                    item.content?.is_mandatory && 
                                                                    !updatedEnrollment.survey_completed
                                                                )
                                                            );

                                                            // Solo descargar si NO hay encuesta pendiente
                                                            if (!hasPendingSurvey) {
                                                                handleDownloadCertificate(updatedEnrollment);
                                                            } else {
                                                                console.log("Certificado pendiente de encuesta");
                                                            }
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

            <header className="sticky top-0 z-50 w-full bg-black/65 backdrop-blur-2xl border-b border-white/10 px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between shadow-2xl">
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

                    <div className="hidden sm:flex flex-col items-end mr-2">
                        <span className="text-sm font-black tracking-tight">{user.first_name} {user.last_name}</span>
                        <span className="text-xs text-white/40 font-mono">{user.rut}</span>
                    </div>
                    <button onClick={handleLogout} title={t?.logout} className="p-2.5 rounded-xl bg-white/5 hover:bg-red-500/10 hover:text-red-400 transition-all border border-white/10 group">
                        <LogOut className="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
                    </button>
                </div>
            </header>

            <main className="flex-1 max-w-7xl mx-auto w-full p-3 sm:p-4 md:p-8 space-y-8 sm:space-y-10 relative z-10">
                <section className="relative overflow-hidden rounded-[32px] p-5 sm:p-8 md:p-14 glass border-brand/20 shadow-[0_0_80px_rgba(49,210,45,0.08)]">
                    <div className="absolute top-0 left-0 w-64 h-64 bg-brand/10 blur-[100px] -ml-32 -mt-32 pointer-events-none" />
                    <div className="absolute bottom-0 right-0 w-64 h-64 bg-blue-500/10 blur-[100px] -mr-32 -mb-32 pointer-events-none" />

                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="space-y-6 relative z-10">
                        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand/10 border border-brand/20 text-brand text-xs font-black uppercase tracking-widest">
                            <div className="w-1.5 h-1.5 rounded-full bg-brand animate-pulse" />
                            {companyInfo?.name} Training Hub
                        </div>
                        <h2 className="text-3xl sm:text-4xl md:text-6xl font-black tracking-tighter leading-none">
                            {t?.welcome},<br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-brand to-blue-400">{user.first_name}</span>
                        </h2>
                        <p className="text-white/50 text-sm sm:text-lg max-w-2xl font-medium leading-relaxed">
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
                            
                            // Verificar si existe contenido de encuesta obligatoria (YA CALCULADO EN FETCH)
                            const hasMandatorySurvey = enrollment.has_mandatory_survey;

                            // Mostrar "FALTA ENCUESTA" solo cuando la evaluación final fue aprobada realmente.
                            const evaluationApproved = enrollment.last_exam_passed === true || isCompleted;
                            const surveyPending = evaluationApproved && hasMandatorySurvey && !enrollment.survey_completed;
                            
                            const course = enrollment.course;

                            return (
                                <motion.div
                                    key={enrollment.id}
                                    initial={{ opacity: 0, y: 30 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: i * 0.15 }}
                                    className={`glass group rounded-[2.5rem] overflow-hidden flex flex-col transition-all duration-500 hover:shadow-[0_20px_50px_rgba(0,0,0,0.5)] border-white/5 active:scale-[0.98] ${isCompleted ? 'border-brand/40 bg-brand/[0.02]' : surveyPending ? 'border-yellow-500/40 bg-yellow-500/[0.02]' : 'hover:border-white/20'}`}
                                >
                                    <div className="h-44 bg-white/[0.03] relative flex flex-col items-center justify-center border-b border-white/5 group-hover:bg-white/[0.05] transition-all">
                                        {isCompleted ? (
                                            <div className="flex flex-col items-center gap-2 relative z-10">
                                                <div className="w-14 h-14 bg-brand/20 rounded-full flex items-center justify-center border border-brand/50 shadow-[0_0_30px_rgba(49,210,45,0.3)]">
                                                    <CheckCircle2 className="w-8 h-8 text-brand" />
                                                </div>
                                                <span className="text-brand font-black text-xl tracking-tighter">NOTA: {enrollment.best_score}%</span>
                                            </div>
                                        ) : surveyPending ? (
                                            <div className="flex flex-col items-center gap-2 relative z-10">
                                                <div className="w-14 h-14 bg-yellow-500/20 rounded-full flex items-center justify-center border border-yellow-500/50 shadow-[0_0_30px_rgba(234,179,8,0.3)]">
                                                    <ClipboardList className="w-8 h-8 text-yellow-500" />
                                                </div>
                                                <span className="text-yellow-500 font-black text-xl tracking-tighter">FALTA ENCUESTA</span>
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

                                        <div className={`mt-3 text-[9px] px-3 py-1 rounded-full font-black uppercase tracking-[0.15em] border z-10 backdrop-blur-md ${isCompleted ? 'bg-brand/20 text-brand border-brand/40 shadow-[0_5px_15px_rgba(49,210,45,0.2)]' : surveyPending ? 'bg-yellow-500/20 text-yellow-500 border-yellow-500/40' : enrollment.partial_progress > 0 ? 'bg-brand/10 text-brand/60 border-brand/20' : 'bg-white/5 text-white/40 border-white/10'}`}>
                                            {isCompleted ? t?.completed_course : surveyPending ? 'ENCUESTA PENDIENTE' : enrollment.partial_progress > 0 ? `${enrollment.partial_progress}% ${t?.completed}` : t?.available}
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

                                        <div className="flex gap-3 flex-wrap">
                                            {/* Solo mostrar botón de acción si NO está completado o si falta encuesta */}
                                            {(!isCompleted || surveyPending) && (
                                            <button
                                                onClick={() => setActiveCourse(enrollment)}
                                                className={`flex-1 py-4 font-black uppercase tracking-widest text-xs rounded-2xl transition-all shadow-xl flex items-center justify-center gap-2 ${!isCompleted && !surveyPending ? 'bg-brand text-black hover:bg-white hover:scale-[1.03] shadow-brand/20' : surveyPending ? 'bg-yellow-500 text-black hover:bg-yellow-400 hover:scale-[1.03] shadow-yellow-500/20' : 'bg-white/5 text-white/40 border border-white/10 hover:bg-white/10'}`}
                                            >
                                                {surveyPending ? (
                                                    <>IR A ENCUESTA <ClipboardList className="w-4 h-4" /></>
                                                ) : (
                                                    <>{t?.start} <ChevronRight className="w-4 h-4" /></>
                                                )}
                                            </button>
                                            )}
                                            
                                            {isCompleted && !surveyPending && (() => {
                                                const cf = certFlagsMap[enrollment.course_id] || { participacion: false, aprobacion: false, irl: false, irlRoleIds: [] };
                                                const roleMatchesIrl = (cf.irlRoleIds || []).length === 0 || (cf.irlRoleIds || []).includes(user.role_id);
                                                const irlDocs = irlDocsByCourse[enrollment.course_id] || [];
                                                const hasIrlDocs = irlDocs.length > 0;
                                                const irlAvailable = cf.irl && roleMatchesIrl && hasIrlDocs;
                                                const hasAnyCert = cf.participacion || cf.aprobacion || irlAvailable;
                                                const irlConfirmed = irlConsentByEnrollment[enrollment.id] === true;
                                                return (
                                                    <>
                                                        {!hasAnyCert ? (
                                                            <div className="flex-1 py-4 flex items-center justify-center text-white/30 font-black uppercase tracking-widest text-xs">
                                                                Finalizado
                                                            </div>
                                                        ) : !hasUserSignature ? (
                                                            <button
                                                                onClick={() => {
                                                                    setSignatureTargetCourse(enrollment.course?.name || '');
                                                                    setShowSignatureModal(true);
                                                                }}
                                                                className="flex-1 py-4 bg-yellow-500 text-black border border-yellow-400/40 rounded-2xl hover:bg-yellow-400 transition-all flex items-center justify-center gap-2 font-black uppercase tracking-widest text-xs shadow-lg shadow-yellow-500/20"
                                                            >
                                                                <ClipboardList className="w-5 h-5" /> {t?.sign_now}
                                                            </button>
                                                        ) : (
                                                            <>
                                                                {cf.participacion && (
                                                                    <button
                                                                        onClick={() => handleDownloadCertificate(enrollment)}
                                                                        disabled={isGeneratingCert}
                                                                        className="flex-1 py-4 bg-brand text-black border border-brand/30 rounded-2xl hover:bg-white transition-all flex items-center justify-center gap-2 font-black uppercase tracking-widest text-xs shadow-lg shadow-brand/20 disabled:opacity-50"
                                                                    >
                                                                        <Award className="w-5 h-5" /> Cert. Participación
                                                                    </button>
                                                                )}
                                                                {cf.aprobacion && (
                                                                    <button
                                                                        onClick={() => handleDownloadAprobacion(enrollment)}
                                                                        className="flex-1 py-4 bg-purple-600 text-white border border-purple-500/30 rounded-2xl hover:bg-purple-500 transition-all flex items-center justify-center gap-2 font-black uppercase tracking-widest text-xs shadow-lg shadow-purple-600/20"
                                                                    >
                                                                        <Award className="w-5 h-5" /> Cert. Aprobación
                                                                    </button>
                                                                )}
                                                                {irlAvailable && (
                                                                    <div className="w-full mt-2 p-3 rounded-xl border border-cyan-500/20 bg-cyan-500/5 space-y-2">
                                                                        {irlDocs.length > 0 && (
                                                                            <div className="flex flex-col gap-1.5">
                                                                                {irlDocs.map((doc) => (
                                                                                    <a
                                                                                        key={doc.id}
                                                                                        href={doc.file_url}
                                                                                        target="_blank"
                                                                                        rel="noopener noreferrer"
                                                                                        className="text-[11px] text-cyan-200 hover:text-cyan-100 underline underline-offset-2"
                                                                                    >
                                                                                        {doc.title}
                                                                                    </a>
                                                                                ))}
                                                                            </div>
                                                                        )}
                                                                        <label className="flex items-start gap-2 text-[11px] text-white/80">
                                                                            <input
                                                                                type="checkbox"
                                                                                checked={irlConfirmed}
                                                                                onChange={(e) => handleToggleIrlConsent(enrollment.id, e.target.checked)}
                                                                                className="mt-0.5"
                                                                            />
                                                                            <span>confirmo estar en conocimiento de los riesgos laborales que involucra mi cargo.</span>
                                                                        </label>
                                                                        <button
                                                                            onClick={() => handleDownloadIrlCertificate(enrollment)}
                                                                            disabled={!irlConfirmed || isGeneratingCert}
                                                                            className="w-full py-3 bg-cyan-600 text-white border border-cyan-500/30 rounded-xl hover:bg-cyan-500 transition-all flex items-center justify-center gap-2 font-black uppercase tracking-widest text-[11px] disabled:opacity-50 disabled:cursor-not-allowed"
                                                                        >
                                                                            <Award className="w-4 h-4" /> Cert. IRL
                                                                        </button>
                                                                    </div>
                                                                )}
                                                            </>
                                                        )}
                                                    </>
                                                );
                                            })()}
                                        </div>
                                    </div>
                                </motion.div>
                            );
                        })}
                    </div>
                </section>
            </main>

            <AnimatePresence>
                {showSignatureModal && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-[120] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
                    >
                        <motion.div
                            initial={{ y: 20, opacity: 0 }}
                            animate={{ y: 0, opacity: 1 }}
                            exit={{ y: 20, opacity: 0 }}
                            className="w-full max-w-2xl bg-[#111827] border border-white/10 rounded-3xl p-5 sm:p-7"
                        >
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg sm:text-xl font-black text-white uppercase tracking-wider">{t?.sign_now}</h3>
                                <button
                                    onClick={() => {
                                        setShowSignatureModal(false);
                                        setSignatureTargetCourse("");
                                    }}
                                    className="p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                            <p className="text-sm text-white/70 mb-5">{t?.sign_to_unlock_cert}{signatureTargetCourse ? `: ${signatureTargetCourse}` : ''}</p>
                            <SignatureCanvas onSave={handleSaveMissingSignature} isLight={false} />
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* ── Sacyr IRL forms section ──────────────────────────────── */}
            {user?.client_id === SACYR_COMPANY_ID && sacyrIrlAssignments.length > 0 && (
                <div className="mt-8 px-4 sm:px-6 max-w-3xl mx-auto">
                    <div className="mb-4 flex items-center gap-2">
                        <ClipboardList className="w-4 h-4 text-orange-400" />
                        <h2 className="text-base font-black uppercase tracking-widest text-orange-300">Formularios IRL Sacyr</h2>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        {sacyrIrlAssignments.map(assignment => {
                            const form = SACYR_IRL_FORMS.find(f => f.slug === assignment.form_slug);
                            if (!form) return null;
                            const isCompleted = assignment.status === 'completed';

                            return (
                                <div key={assignment.id} className={`flex flex-col gap-3 p-4 rounded-2xl border ${isCompleted ? 'bg-green-900/15 border-green-500/25' : 'bg-orange-900/15 border-orange-500/25'}`}>
                                    <div className="flex items-start gap-2.5">
                                        {isCompleted
                                            ? <CheckCircle2 className="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" />
                                            : <Lock className="w-4 h-4 text-orange-400 flex-shrink-0 mt-0.5" />
                                        }
                                        <div className="min-w-0">
                                            <p className="font-bold text-sm text-white leading-tight">{form.cargo_name}</p>
                                            <p className="text-xs text-white/40 mt-0.5">{isCompleted ? 'Completado' : 'Pendiente de completar'}</p>
                                        </div>
                                    </div>
                                    {isCompleted ? (
                                        <button
                                            onClick={async () => {
                                                // Re-generate PDF from stored response
                                                const { data: resp } = await supabase
                                                    .from('sacyr_irl_responses')
                                                    .select('*')
                                                    .eq('assignment_id', assignment.id)
                                                    .single();
                                                if (!resp) { alert('No se encontró la respuesta guardada.'); return; }
                                                const cfg = companyInfo?.cert_signature_config as { irl?: number[] } | null;
                                                const irlIdx = (cfg?.irl ?? [0])[0] ?? 0;
                                                const relUrl = (companyInfo as any)?.[`signature_url_${irlIdx + 1}`] || null;
                                                const relName = (companyInfo as any)?.[`signature_name_${irlIdx + 1}`] || null;
                                                const relRole = (companyInfo as any)?.[`signature_role_${irlIdx + 1}`] || null;
                                                await generateSacyrIrlPdf({
                                                    form,
                                                    studentName: resp.student_name,
                                                    studentRut: resp.student_rut,
                                                    jobName: form.cargo_name,
                                                    companyName: companyInfo?.name || 'Sacyr',
                                                    motivo: resp.motivo,
                                                    induccion: resp.induccion_data || undefined,
                                                    respuestas_parte1: resp.respuestas_parte1 || {},
                                                    riesgos_identificados: resp.riesgos_identificados || [],
                                                    imagen_riesgo_1: resp.imagen_riesgo_1 || '',
                                                    imagen_medidas_1: resp.imagen_medidas_1 || '',
                                                    imagen_riesgo_2: resp.imagen_riesgo_2 || '',
                                                    imagen_medidas_2: resp.imagen_medidas_2 || '',
                                                    studentSignatureUrl: resp.student_signature_url,
                                                    relatorSignatureUrl: relUrl,
                                                    relatorName: relName,
                                                    relatorRole: relRole,
                                                });
                                            }}
                                            className="w-full flex items-center justify-center gap-1.5 px-4 py-2 bg-green-500/15 text-green-400 border border-green-500/30 rounded-xl text-xs font-black uppercase hover:bg-green-500/25 transition-all"
                                        >
                                            <Download className="w-3.5 h-3.5" /> PDF
                                        </button>
                                    ) : (
                                        <button
                                            onClick={() => setActiveSacyrIrl(assignment.id)}
                                            className="w-full flex items-center justify-center gap-1.5 px-4 py-2 bg-orange-500/15 text-orange-300 border border-orange-500/30 rounded-xl text-xs font-black uppercase hover:bg-orange-500/25 transition-all"
                                        >
                                            <ChevronRight className="w-3.5 h-3.5" /> Completar
                                        </button>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Sacyr IRL form modal */}
            {activeSacyrIrl && (() => {
                const assignment = sacyrIrlAssignments.find(a => a.id === activeSacyrIrl);
                if (!assignment) return null;
                const form = SACYR_IRL_FORMS.find(f => f.slug === assignment.form_slug);
                if (!form) return null;
                const cfg = companyInfo?.cert_signature_config as { irl?: number[] } | null;
                const irlIdx = (cfg?.irl ?? [0])[0] ?? 0;
                return (
                    <SacyrIrlFormModal
                        assignmentId={assignment.id}
                        formSlug={form.slug}
                        studentId={user?.id || ''}
                        studentName={`${user?.first_name || ''} ${user?.last_name || ''}`.trim()}
                        studentRut={user?.rut || ''}
                        jobName={form.cargo_name}
                        companyName={companyInfo?.name || 'Sacyr'}
                        relatorSignatureUrl={(companyInfo as any)?.[`signature_url_${irlIdx + 1}`] || null}
                        relatorName={(companyInfo as any)?.[`signature_name_${irlIdx + 1}`] || null}
                        relatorRole={(companyInfo as any)?.[`signature_role_${irlIdx + 1}`] || null}
                        onComplete={() => {
                            setActiveSacyrIrl(null);
                            setSacyrIrlAssignments(prev => prev.map(a =>
                                a.id === activeSacyrIrl ? { ...a, status: 'completed' } : a
                            ));
                        }}
                        onClose={() => setActiveSacyrIrl(null)}
                    />
                );
            })()}

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
