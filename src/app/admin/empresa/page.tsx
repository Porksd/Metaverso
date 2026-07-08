"use client";

import { useState, useEffect, useRef, useMemo } from "react";
import { motion } from "framer-motion";
import {
    Users, BookOpen, Search, Download, CheckCircle2,
    Shield, UserCog, X, Trash2, LogOut, UserPlus, Settings, Building2, Lock, Award as AwardIcon, Pencil, Eye, EyeOff, RefreshCw
} from "lucide-react";
import { supabase } from "@/lib/supabase";
import CertificateCanvas from "@/components/CertificateCanvas";
import ManagerDashboard from "@/components/ManagerDashboard";
import EnhancedManagerDashboard from "@/components/EnhancedManagerDashboard";
import CompanyConfig from "@/components/CompanyConfig";
import RichTextEditor from "@/components/RichTextEditor";
import { jsPDF } from "jspdf";
import { useRouter } from "next/navigation";
import { resolveAdminRole } from "@/lib/adminAuth";
import { generateMetaversoCert } from "@/lib/generateMetaversoCert";
import { generateIrlCert } from "@/lib/generateIrlCert";

// Utility functions for RUT validation
const cleanRut = (rut: string) => {
    return rut.replace(/[^0-9kK]/g, "").toUpperCase();
};

const validateRut = (rut: string) => {
    if (!rut) return false;
    const clean = cleanRut(rut);
    if (clean.length < 2) return false;

    const body = clean.slice(0, -1);
    const dv = clean.slice(-1);
    let sum = 0;
    let multiplier = 2;

    for (let i = body.length - 1; i >= 0; i--) {
        sum += parseInt(body[i]) * multiplier;
        multiplier = multiplier === 7 ? 2 : multiplier + 1;
    }

    const expectedDv = 11 - (sum % 11);
    const calculatedDv = expectedDv === 11 ? "0" : expectedDv === 10 ? "K" : expectedDv.toString();

    return calculatedDv === dv;
};

const formatRut = (rut: string) => {
    const clean = cleanRut(rut);
    if (clean.length < 2) return clean;
    
    const body = clean.slice(0, -1);
    const dv = clean.slice(-1);
    
    return `${body}-${dv}`;
};

const MONTHS_ES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const calcExpirationDate = (completedAt?: string | null, validezAnios?: number | null): string | undefined => {
    if (!completedAt || !validezAnios) return undefined;
    const d = new Date(completedAt);
    if (isNaN(d.getTime())) return undefined;
    d.setFullYear(d.getFullYear() + validezAnios);
    return `${d.getDate()} de ${MONTHS_ES[d.getMonth()]} de ${d.getFullYear()}`;
};

const formatDateEsCL = (value?: string | null) => {
    const parsedDate = value ? new Date(value) : new Date();
    if (Number.isNaN(parsedDate.getTime())) return '';
    return parsedDate.toLocaleDateString('es-CL');
};

const COMPANY_CONTEXT_KEYS = ["empresa_id", "empresa_name", "empresa_slug", "is_master_admin", "master_role", "master_return_url", "master_entry_mode"] as const;

const getStoredCompanyValue = (key: (typeof COMPANY_CONTEXT_KEYS)[number]) => {
    return sessionStorage.getItem(key) ?? localStorage.getItem(key);
};

const clearCompanyContext = ({ clearLocalStorage }: { clearLocalStorage: boolean }) => {
    COMPANY_CONTEXT_KEYS.forEach((key) => {
        sessionStorage.removeItem(key);
        if (clearLocalStorage) {
            localStorage.removeItem(key);
        }
    });
};

export default function EmpresaAdmin() {
    type CertificateType = 'participacion' | 'aprobacion' | 'irl';
    type CertificateFlags = { participacion: boolean; aprobacion: boolean; irl: boolean; irlRoleIds: string[] };
    type CertificateStudent = {
        id: string;
        first_name: string;
        last_name: string;
        rut: string;
        role_id?: string | null;
        digital_signature_url?: string | null;
        company_roles?: { name?: string | null } | null;
    };
    type CertificateEnrollment = {
        id: string;
        course_id: string;
        status?: string | null;
        best_score?: number | null;
        completed_at?: string | null;
    };
    type CertificateCourse = {
        id: string;
        name?: string | null;
        code?: string | null;
        config?: { hours?: number | null } | null;
        company_course_validez_anios?: number | null;
    };
    type CertificateCompany = {
        name: string;
        rut?: string | null;
        logo_url?: string | null;
        signature_url_1?: string | null;
        signature_name_1?: string | null;
        signature_role_1?: string | null;
        signature_url_2?: string | null;
        signature_name_2?: string | null;
        signature_role_2?: string | null;
        signature_url_3?: string | null;
        signature_name_3?: string | null;
        signature_role_3?: string | null;
        cert_signature_config?: { participacion?: number[]; aprobacion?: number[]; irl?: number[] } | null;
    };
    type CertificateStudentData = {
        digital_signature_url?: string | null;
        age?: number | string | null;
        gender?: string | null;
        company_name?: string | null;
        job_position?: string | null;
    };

    const router = useRouter();
    const [role, setRole] = useState<'manager' | 'trainer'>('manager');
    const [searchTerm, setSearchTerm] = useState("");
    const [students, setStudents] = useState<any[]>([]);
    const [cargos, setCargos] = useState<any[]>([]);
    const [courses, setCourses] = useState<any[]>([]);
    const [isEditing, setIsEditing] = useState<any>(null);
    const [showCargoManager, setShowCargoManager] = useState(false);
    const [showCreateCargo, setShowCreateCargo] = useState(false);
    const [editingCargo, setEditingCargo] = useState<any>(null);
    const [showConfig, setShowConfig] = useState(false);
    const [isCreating, setIsCreating] = useState(false);
    const [cargoDesc, setCargoDesc] = useState("");
    const [cargoDescHT, setCargoDescHT] = useState("");
    const [descLang, setDescLang] = useState<'es' | 'ht'>('es');
    const [certData, setCertData] = useState<any>(null);
    const [courseCertFlags, setCourseCertFlags] = useState<Record<string, { participacion: boolean; aprobacion: boolean; irl: boolean; irlRoleIds: string[] }>>({});
    const [irlDocsCountByCourse, setIrlDocsCountByCourse] = useState<Record<string, number>>({});
    const [diplomaConfig, setDiplomaConfig] = useState<any>(null);
    const [isGeneratingCert, setIsGeneratingCert] = useState(false);
    const certGenerationLock = useRef(false);
    const [lastIssuedSignatures, setLastIssuedSignatures] = useState<Record<string, string>>({});
    const [pendingCertificateIssue, setPendingCertificateIssue] = useState<{ enrollmentId: string; studentId: string; courseId: string; certificateType: CertificateType; contentSignature: string } | null>(null);
    const [sortConfig, setSortConfig] = useState<{ key: string; direction: 'asc' | 'desc' }>({ key: 'name', direction: 'asc' });
    const [trainerPage, setTrainerPage] = useState(1);
    const [trainerPageSize, setTrainerPageSize] = useState(20);
    const [selectedStudentCourses, setSelectedStudentCourses] = useState<any | null>(null);
    const [companyId, setCompanyId] = useState<string | null>(null);
    const [companyName, setCompanyName] = useState<string>("Cargando...");
    const [isMasterAdmin, setIsMasterAdmin] = useState<boolean>(false);
    const [masterRole, setMasterRole] = useState<'superadmin' | 'administrador' | 'editor' | null>(null);
    const [isAuthenticating, setIsAuthenticating] = useState<boolean>(true);
    const [newStudent, setNewStudent] = useState<any>({ 
        first_name: "", 
        last_name: "", 
        rut: "",
        doc_type: "RUT",
        email: "", 
        password: "", 
        role_id: null,
        age: "",
        gender: ""
    });

    const normalizeStudentSignature = (raw?: string | null) => {
        if (!raw) return null;
        const value = raw.trim();
        if (!value) return null;
        if (value.startsWith('data:image/')) return value;
        if (/^[A-Za-z0-9+/=\s]+$/.test(value) && value.length > 200) {
            return `data:image/png;base64,${value.replace(/\s+/g, '')}`;
        }
        return value;
    };

    const resolveParticipationFlag = (row: {
        cert_participacion_enabled?: boolean | null;
        diploma_metaverso_enabled?: boolean | null;
    }) => {
        if (row.cert_participacion_enabled === true) return true;
        if (row.cert_participacion_enabled === false) return false;
        return row.diploma_metaverso_enabled !== true;
    };

    const buildIssueKey = (enrollmentId: string, certType: 'participacion' | 'aprobacion' | 'irl') => `${enrollmentId}:${certType}`;

    /** Returns the company signature slots assigned to a given certificate type. */
    const getCompSigsForType = (
        comp: CertificateCompany,
        type: 'participacion' | 'aprobacion' | 'irl'
    ) => {
        const all = [
            { url: comp.signature_url_1, name: comp.signature_name_1, role: comp.signature_role_1 },
            { url: comp.signature_url_2, name: comp.signature_name_2, role: comp.signature_role_2 },
            { url: comp.signature_url_3, name: comp.signature_name_3, role: comp.signature_role_3 },
        ];
        const defaults: Record<string, number[]> = { participacion: [0, 1], aprobacion: [0, 1], irl: [0] };
        const indices = comp.cert_signature_config?.[type] ?? defaults[type];
        return indices.map((i: number) => all[i]).filter(s => s?.url || s?.name);
    };

    const buildCertificateSignature = ({
        certificateType,
        course,
        courseName,
        enrollment,
        certFlags,
        studentRoleId,
        currentCompanyName,
    }: {
        certificateType: CertificateType;
        course: CertificateCourse;
        courseName: string;
        enrollment: CertificateEnrollment;
        certFlags: CertificateFlags;
        studentRoleId?: string | null;
        currentCompanyName: string;
    }) => {
        const shared = {
            cert: certificateType,
            course_id: course?.id || enrollment?.course_id || null,
            course_code: course?.code || null,
            course_name: (courseName || '').toUpperCase(),
            hours: course?.config?.hours ?? null,
            validez_anios: course?.company_course_validez_anios ?? null,
            company_name: currentCompanyName,
            completed_at: enrollment?.completed_at || null,
        };

        if (certificateType === 'aprobacion') {
            return JSON.stringify({
                ...shared,
                diploma_background: diplomaConfig?.background_url || null,
                diploma_layout: diplomaConfig?.fields_config?.layout || null,
                diploma_fields: diplomaConfig?.fields_config || null,
            });
        }

        if (certificateType === 'irl') {
            const roleIds = Array.isArray(certFlags.irlRoleIds) ? [...certFlags.irlRoleIds].sort() : [];
            return JSON.stringify({
                ...shared,
                irl_enabled: certFlags.irl,
                irl_role_ids: roleIds,
                irl_role_match: roleIds.length === 0 || (!!studentRoleId && roleIds.includes(studentRoleId)),
            });
        }

        return JSON.stringify({
            ...shared,
            participacion_enabled: certFlags.participacion,
        });
    };

    const getEnrollmentCertificateTypes = (student: CertificateStudent, enrollment: CertificateEnrollment, course: CertificateCourse | undefined) => {
        if (!course || enrollment?.status !== 'completed') return [] as CertificateType[];

        const certFlags = courseCertFlags[enrollment.course_id] || { participacion: false, aprobacion: false, irl: false, irlRoleIds: [] } as CertificateFlags;
        const roleIds = certFlags.irlRoleIds || [];
        const studentRoleId = student?.role_id || null;
        const hasIrlDocs = (irlDocsCountByCourse[enrollment.course_id] || 0) > 0;
        const irlAllowed = certFlags.irl && hasIrlDocs && (roleIds.length === 0 || (!!studentRoleId && roleIds.includes(studentRoleId)));

        return [
            certFlags.participacion ? 'participacion' : null,
            certFlags.aprobacion ? 'aprobacion' : null,
            irlAllowed ? 'irl' : null,
        ].filter(Boolean) as CertificateType[];
    };

    const downloadCourseCertificate = async (student: CertificateStudent, enrollment: CertificateEnrollment, certificateType: CertificateType) => {
        if (isGeneratingCert || certGenerationLock.current) return;
        if (!companyId) return;

        const course = courses.find((c: any) => c.id === enrollment.course_id);
        if (!course) {
            alert('No se encontró el curso asociado para generar el certificado.');
            return;
        }

        const availableTypes = getEnrollmentCertificateTypes(student, enrollment, course);
        if (!availableTypes.includes(certificateType)) {
            alert('Este certificado no está disponible para ese curso.');
            return;
        }

        try {
            const [{ data: compRaw }, { data: studentDataRaw }] = await Promise.all([
                supabase.from('companies').select('*').eq('id', companyId).single(),
                supabase
                    .from('students')
                    .select('digital_signature_url, age, gender, company_name, job_position')
                    .eq('id', student.id)
                    .single(),
            ]);

            const comp = compRaw as CertificateCompany | null;
            const studentData = studentDataRaw as CertificateStudentData | null;

            if (!comp) {
                alert('No se encontró la configuración de la empresa para generar el certificado.');
                return;
            }

            let jobName = student.company_roles?.name || studentData?.job_position;
            if (studentData?.job_position && !student.company_roles?.name) {
                const { data: jobInfo } = await supabase
                    .from('job_positions')
                    .select('name_es')
                    .eq('code', studentData.job_position)
                    .single();

                if (jobInfo) jobName = jobInfo.name_es;
            }

            const currentCompanyName = studentData?.company_name || comp.name;
            const contentSignature = buildCertificateSignature({
                certificateType,
                course,
                courseName: course.name || 'Curso',
                enrollment,
                certFlags: courseCertFlags[enrollment.course_id] || { participacion: false, aprobacion: false, irl: false, irlRoleIds: [] },
                studentRoleId: student.role_id,
                currentCompanyName,
            });

            const certificateDate = formatDateEsCL(enrollment.completed_at);

            if (certificateType === 'aprobacion') {
                if (!diplomaConfig) {
                    alert('No hay configuración de diploma.');
                    return;
                }

                try {
                    certGenerationLock.current = true;
                    setIsGeneratingCert(true);

                    const fc = diplomaConfig.fields_config || {};

                    await generateMetaversoCert({
                        studentName: `${student.first_name} ${student.last_name}`,
                        rut: student.rut,
                        companyName: currentCompanyName,
                        companyRut: comp.rut || '',
                        companyId,
                        courseId: enrollment.course_id,
                        courseName: course.name?.toUpperCase() || 'CURSO',
                        courseCode: course.code || '',
                        hours: course.config?.hours,
                        date: enrollment.completed_at
                            ? new Date(enrollment.completed_at).toLocaleDateString('es-CL')
                            : new Date().toLocaleDateString('es-CL'),
                        expirationDate: calcExpirationDate(enrollment.completed_at, course.company_course_validez_anios),
                        backgroundUrl: diplomaConfig.background_url,
                        layoutConfig: fc.layout,
                        fieldsConfig: fc,
                    });

                    await trackCertificateIssuance({
                        enrollmentId: enrollment.id,
                        studentId: student.id,
                        courseId: enrollment.course_id,
                        certificateType,
                        contentSignature,
                    });
                } catch (error: any) {
                    console.error('Error generando certificado de aprobación:', error);
                    alert('No se pudo generar el certificado de aprobación.');
                } finally {
                    certGenerationLock.current = false;
                    setIsGeneratingCert(false);
                }

                return;
            }

            if (certificateType === 'irl') {
                certGenerationLock.current = true;
                setIsGeneratingCert(true);

                const irlSigs = getCompSigsForType(comp, 'irl');
                const irlSig = irlSigs[0];
                await generateIrlCert({
                    studentName: `${student.first_name} ${student.last_name}`,
                    rut: student.rut,
                    age: studentData?.age,
                    jobName: jobName || studentData?.job_position || student.company_roles?.name || 'Sin cargo',
                    date: certificateDate,
                    companyName: currentCompanyName,
                    studentSignatureUrl: normalizeStudentSignature(studentData?.digital_signature_url || student.digital_signature_url),
                    relatorSignatureUrl: irlSig?.url || null,
                    relatorName: irlSig?.name || null,
                    relatorRole: irlSig?.role || null,
                });

                await trackCertificateIssuance({
                    enrollmentId: enrollment.id,
                    studentId: student.id,
                    courseId: enrollment.course_id,
                    certificateType,
                    contentSignature,
                });

                certGenerationLock.current = false;
                setIsGeneratingCert(false);
                return;
            }

            setPendingCertificateIssue({
                enrollmentId: enrollment.id,
                studentId: student.id,
                courseId: enrollment.course_id,
                certificateType,
                contentSignature,
            });
            setCertData({
                certificateType,
                studentName: `${student.first_name} ${student.last_name}`,
                rut: student.rut,
                courseName: course.name?.toUpperCase() || 'CURSO',
                date: certificateDate,
                score: enrollment.best_score ?? 100,
                signatures: getCompSigsForType(comp, certificateType as 'participacion' | 'aprobacion'),
                studentSignature: normalizeStudentSignature(studentData?.digital_signature_url || student.digital_signature_url),
                companyLogo: comp.logo_url,
                companyName: currentCompanyName,
                jobPosition: jobName,
                age: studentData?.age,
                gender: studentData?.gender,
            });
            certGenerationLock.current = true;
            setIsGeneratingCert(true);
        } catch (error) {
            console.error('Error generando certificado:', error);
            certGenerationLock.current = false;
            setIsGeneratingCert(false);
            setPendingCertificateIssue(null);
            setCertData(null);
            alert('No se pudo generar el certificado. Intenta nuevamente.');
        }
    };

    const trackCertificateIssuance = async ({
        enrollmentId,
        studentId,
        courseId,
        certificateType,
        contentSignature,
    }: {
        enrollmentId: string;
        studentId: string;
        courseId: string;
        certificateType: 'participacion' | 'aprobacion' | 'irl';
        contentSignature: string;
    }) => {
        if (!companyId || !enrollmentId || !studentId || !courseId) return;

        const { error } = await supabase
            .from('certificate_issuances')
            .insert({
                company_id: companyId,
                student_id: studentId,
                enrollment_id: enrollmentId,
                course_id: courseId,
                certificate_type: certificateType,
                content_signature: contentSignature,
            });

        if (error) {
            console.error('Error registrando emisión de certificado:', error);
            return;
        }

        const key = buildIssueKey(enrollmentId, certificateType);
        setLastIssuedSignatures(prev => ({
            ...prev,
            [key]: contentSignature,
        }));
    };

    useEffect(() => {
        const storedId = getStoredCompanyValue('empresa_id');
        const storedName = getStoredCompanyValue('empresa_name');
        const storedMaster = getStoredCompanyValue('is_master_admin');
        const storedMasterRole = getStoredCompanyValue('master_role') as 'superadmin' | 'administrador' | 'editor' | null;

        if (!storedId) {
            window.location.href = "/admin/empresa/login";
            return;
        }

        if (storedMaster === 'true') {
            setIsMasterAdmin(true);
            if (storedMasterRole === 'superadmin' || storedMasterRole === 'administrador' || storedMasterRole === 'editor') {
                setMasterRole(storedMasterRole);
            }
        }

        setCompanyId(storedId);
        setCompanyName(storedName || "Mi Empresa");
    }, []);

    useEffect(() => {
        if (!companyId) return;

        const checkAuth = async () => {
            setIsAuthenticating(true);
            const { data: { session } } = await supabase.auth.getSession();

            if (session) {
                const email = session.user.email?.toLowerCase();
                const { role: roleToSet } = await resolveAdminRole(supabase, email, '/admin/empresa');

                if (roleToSet) {
                    setIsMasterAdmin(true);
                    setMasterRole(roleToSet);
                    sessionStorage.setItem('is_master_admin', 'true');
                    sessionStorage.setItem('master_role', roleToSet);
                    setIsAuthenticating(false);
                    return;
                }

                await supabase.auth.signOut({ scope: 'local' });
            }

            setIsAuthenticating(false);
        };

        checkAuth();
        fetchData();
    }, [role, searchTerm, companyId]);

    const fetchData = async () => {
        if (!companyId) return;

        try {
            const { data: stData, error: stError } = await supabase
                .from('students')
                .select('*, company_roles(name), enrollments(id, course_id, status, best_score, completed_at, current_attempt, max_attempts, irl_confirmed)')
                .eq('client_id', companyId)
                .or(`first_name.ilike.%${searchTerm}%,last_name.ilike.%${searchTerm}%,rut.ilike.%${searchTerm}%`)
                .order('last_name');
            if (stError) console.error("Error fetching students:", stError);
            setStudents(stData || []);

            const { data: assignments, error: assignError } = await supabase
                .from('role_company_assignments')
                .select('role_id')
                .eq('company_id', companyId);

            const assignedRoleIds = (assignments || []).map((a: any) => a.role_id);

            let rolesQuery = supabase
                .from('company_roles')
                .select(`
                    *,
                    role_company_assignments (
                        id,
                        is_visible,
                        company_id
                    )
                `);

            if (assignError) {
                rolesQuery = rolesQuery.or(`company_id.eq.${companyId},company_id.is.null`);
            } else {
                if (assignedRoleIds.length > 0) {
                    rolesQuery = rolesQuery.or(`company_id.eq.${companyId},id.in.(${assignedRoleIds.map((id: string) => `"${id}"`).join(',')})`);
                } else {
                    rolesQuery = rolesQuery.eq('company_id', companyId);
                }
            }

            const { data: cgData } = await rolesQuery.order('name');
            setCargos(cgData || []);

            const [{ data: assignedData, error: assignedError }, { data: dipConfig }] = await Promise.all([
                supabase
                    .from('company_courses')
                    .select('course_id, cert_participacion_enabled, diploma_metaverso_enabled, cert_irl_enabled, irl_role_id, irl_role_ids, start_date, validez_anios, courses(*)')
                    .eq('company_id', companyId),
                supabase
                    .from('diploma_config')
                    .select('*')
                    .eq('id', '00000000-0000-0000-0000-000000000001')
                    .single(),
            ]);

            if (assignedError) {
                console.error("Error fetching assigned courses:", assignedError);
            }

            const flags: Record<string, { participacion: boolean; aprobacion: boolean; irl: boolean; irlRoleIds: string[] }> = {};
            (assignedData || []).forEach((ad: any) => {
                const globalIrlEnabled = ad?.courses?.irl_certificate_enabled === true;
                const roleIds = Array.isArray(ad.irl_role_ids)
                    ? ad.irl_role_ids.filter(Boolean)
                    : (ad.irl_role_id ? [ad.irl_role_id] : []);
                const effectiveRoleIds = globalIrlEnabled ? [] : roleIds;

                flags[ad.course_id] = {
                    participacion: resolveParticipationFlag(ad),
                    aprobacion: ad.diploma_metaverso_enabled === true,
                    irl: ad.cert_irl_enabled === true || globalIrlEnabled,
                    irlRoleIds: effectiveRoleIds,
                };
            });
            setCourseCertFlags(flags);
            setDiplomaConfig(dipConfig || null);

            const assignedCourseIds = Array.from(new Set((assignedData || []).map((ad: any) => ad.course_id).filter(Boolean)));
            if (assignedCourseIds.length > 0) {
                const { data: irlDocs } = await supabase
                    .from('course_irl_documents')
                    .select('id, course_id')
                    .in('course_id', assignedCourseIds)
                    .eq('is_active', true);

                const docsCountMap: Record<string, number> = {};
                (irlDocs || []).forEach((doc: any) => {
                    docsCountMap[doc.course_id] = (docsCountMap[doc.course_id] || 0) + 1;
                });
                setIrlDocsCountByCourse(docsCountMap);
            } else {
                setIrlDocsCountByCourse({});
            }

            const filteredCourses = (assignedData || [])
                .map((ad: any) => ({
                    ...(ad.courses || {}),
                    company_course_validez_anios: ad.validez_anios ?? null,
                }))
                .filter((course: any) => !!course.id);
            setCourses(filteredCourses);

            const enrollmentIds = Array.from(new Set((stData || [])
                .flatMap((st: any) => (st.enrollments || []).map((en: any) => en.id))
                .filter(Boolean)));

            const signaturesMap: Record<string, string> = {};
            const { data: authData } = await supabase.auth.getSession();
            if (authData?.session && enrollmentIds.length > 0) {
                const chunkSize = 200;
                for (let i = 0; i < enrollmentIds.length; i += chunkSize) {
                    const idsChunk = enrollmentIds.slice(i, i + chunkSize);
                    const { data: issuances, error: issuanceError } = await supabase
                        .from('certificate_issuances')
                        .select('enrollment_id, certificate_type, content_signature, issued_at')
                        .eq('company_id', companyId)
                        .in('enrollment_id', idsChunk)
                        .order('issued_at', { ascending: false });

                    if (issuanceError) {
                        console.error('Error fetching certificate issuances:', issuanceError);
                        continue;
                    }

                    (issuances || []).forEach((issue: any) => {
                        const key = buildIssueKey(issue.enrollment_id, issue.certificate_type);
                        if (!(key in signaturesMap)) {
                            signaturesMap[key] = issue.content_signature;
                        }
                    });
                }
            }
            setLastIssuedSignatures(signaturesMap);
        } catch (err) {
            console.error("Unexpected error in fetchData:", err);
        }
    };

    const handleUpdateStudent = async (student: any) => {
        const documentValue = (student.rut || '').trim();
        const usingRut = isRutVisible && (isRutRequired || student.doc_type !== 'PASSPORT');

        if (!student.first_name || !student.last_name) {
            alert("Por favor complete nombre y apellido.");
            return;
        }

        if (isRutVisible && !documentValue) {
            alert("Por favor complete el documento del trabajador.");
            return;
        }

        if (isJobPositionRequired && !student.role_id) {
            alert("Por favor seleccione el cargo obligatorio.");
            return;
        }

        if (isGenderRequired && !student.gender) {
            alert("Por favor complete el género obligatorio.");
            return;
        }

        if (isAgeRequired) {
            const parsedAge = parseInt(student.age, 10);
            if (!student.age || Number.isNaN(parsedAge) || parsedAge < 1) {
                alert("Por favor complete la edad obligatoria.");
                return;
            }
        }

        let normalizedDocument = isRutVisible ? (documentValue || student.rut || null) : (student.rut || null);

        if (usingRut && documentValue) {
            if (!validateRut(documentValue)) {
                alert("El RUT ingresado no es válido. Por favor verifique el dígito verificador.");
                return;
            }

            normalizedDocument = formatRut(documentValue);
        }

        const payload = {
            first_name: student.first_name,
            last_name: student.last_name,
            rut: normalizedDocument,
            email: student.email,
            role_id: (student.role_id && student.role_id !== "") ? student.role_id : null,
            password: student.password,
            age: student.age ? parseInt(student.age, 10) : null,
            gender: student.gender || null,
        };

        const { error } = await supabase
            .from('students')
            .update(payload)
            .eq('id', student.id);

        if (error) alert("Error al actualizar: " + error.message);
        else {
            setIsEditing(null);
            fetchData();
        }
    };

    const handleCreateStudent = async () => {
        if (!companyId) {
            alert("No se detectó el ID de la empresa. Por favor, re-inicie sesión.");
            return;
        }

        const documentValue = (newStudent.rut || '').trim();
        const usingRut = isRutVisible && (isRutRequired || newStudent.doc_type !== 'PASSPORT');

        if (!newStudent.first_name || !newStudent.last_name || (isRutVisible && !documentValue)) {
            alert(`Por favor complete los campos obligatorios (${isRutVisible ? 'Nombre, Apellido, ID/RUT' : 'Nombre y Apellido'})`);
            return;
        }

        if (isJobPositionRequired && !newStudent.role_id) {
            alert("Por favor seleccione el cargo obligatorio.");
            return;
        }

        if (isGenderRequired && !newStudent.gender) {
            alert("Por favor complete el género obligatorio.");
            return;
        }

        if (isAgeRequired) {
            const parsedAge = parseInt(newStudent.age, 10);
            if (!newStudent.age || Number.isNaN(parsedAge) || parsedAge < 1) {
                alert("Por favor complete la edad obligatoria.");
                return;
            }
        }

        let normalizedDocument = isRutVisible ? documentValue : null;
        if (usingRut && documentValue) {
            if (!validateRut(documentValue)) {
                alert("El RUT ingresado no es válido. Por favor verifique el dígito verificador.");
                return;
            }
            normalizedDocument = formatRut(documentValue);
        }

        const payload = {
            first_name: newStudent.first_name,
            last_name: newStudent.last_name,
            rut: normalizedDocument,
            email: newStudent.email || null,
            password: newStudent.password || '123456',
            client_id: companyId,
            role_id: newStudent.role_id,
            age: isAgeVisible && newStudent.age ? parseInt(newStudent.age, 10) : null,
            gender: isGenderVisible ? (newStudent.gender || null) : null,
        };

        const { error } = await supabase
            .from('students')
            .insert(payload);

        if (error) {
            if (error.message.includes("foreign key")) {
                alert("Error de base de datos: el ID de empresa no fue reconocido.");
            } else if (error.message.includes("policy")) {
                alert("Error de permisos (RLS): no tienes permiso para crear trabajadores.");
            } else {
                alert("Error al crear alumno: " + error.message);
            }
            return;
        }

        setIsCreating(false);
        setNewStudent({
            first_name: "",
            last_name: "",
            rut: "",
            doc_type: isRutVisible && isRutRequired ? "RUT" : isRutVisible ? "RUT" : "PASSPORT",
            email: "",
            password: "",
            role_id: null,
            age: "",
            gender: "",
        });
        fetchData();
    };

    const handleDeleteStudent = async (id: string) => {
        if (!confirm("¿Eliminar trabajador?")) return;
        try {
            const response = await fetch(`/api/students/${id}`, { method: 'DELETE' });
            const result = await response.json();

            if (!response.ok) {
                alert("Error al eliminar trabajador: " + (result.error || 'Error desconocido'));
                return;
            }

            fetchData();
        } catch (error: any) {
            alert("Error al eliminar trabajador: " + (error?.message || 'Error inesperado'));
        }
    };

    const openEditStudent = (student: any) => {
        setIsEditing({
            ...student,
            doc_type: validateRut(student.rut || '') ? 'RUT' : 'PASSPORT',
        });
    };

    const toggleSort = (key: 'name' | 'cargo' | 'course' | 'status' | 'certificate') => {
        setSortConfig((prev) => {
            if (prev && prev.key === key) {
                return { key, direction: prev.direction === 'asc' ? 'desc' : 'asc' };
            }
            return { key, direction: 'asc' };
        });
    };

    const sortedStudentSummaries = useMemo(() => {
        const summaries = students.map((st: any) => {
            const validEnrollments = (st.enrollments || []).filter((en: any) =>
                courses.some((c: any) => c.id === en.course_id)
            );

            const totalCourses = validEnrollments.length;
            const completedEnrollments = validEnrollments.filter((en: any) => en.status === 'completed');
            const completedCount = completedEnrollments.length;
            const inProgressCount = validEnrollments.filter((en: any) => en.status === 'in_progress').length;

            let availableCerts = 0;
            completedEnrollments.forEach((en: any) => {
                const course = courses.find((c: any) => c.id === en.course_id);
                availableCerts += getEnrollmentCertificateTypes(st, en, course).length;
            });

            return {
                student: st,
                validEnrollments,
                totalCourses,
                completedCount,
                inProgressCount,
                availableCerts,
            };
        });

        const directionFactor = sortConfig.direction === 'asc' ? 1 : -1;
        return summaries.sort((a, b) => {
            if (sortConfig.key === 'name') {
                const av = `${a.student.first_name || ''} ${a.student.last_name || ''}`.toLowerCase();
                const bv = `${b.student.first_name || ''} ${b.student.last_name || ''}`.toLowerCase();
                return av.localeCompare(bv) * directionFactor;
            }
            if (sortConfig.key === 'cargo') {
                const av = (a.student.company_roles?.name || '').toLowerCase();
                const bv = (b.student.company_roles?.name || '').toLowerCase();
                return av.localeCompare(bv) * directionFactor;
            }
            if (sortConfig.key === 'course') {
                return (a.totalCourses - b.totalCourses) * directionFactor;
            }
            if (sortConfig.key === 'status') {
                const aStatus = a.inProgressCount > 0 ? 1 : a.completedCount > 0 ? 2 : 0;
                const bStatus = b.inProgressCount > 0 ? 1 : b.completedCount > 0 ? 2 : 0;
                return (aStatus - bStatus) * directionFactor;
            }
            return (a.availableCerts - b.availableCerts) * directionFactor;
        });
    }, [students, courses, courseCertFlags, irlDocsCountByCourse, sortConfig]);

    const totalTrainerPages = useMemo(() => {
        return Math.max(1, Math.ceil(sortedStudentSummaries.length / trainerPageSize));
    }, [sortedStudentSummaries.length, trainerPageSize]);

    const pagedStudentSummaries = useMemo(() => {
        const start = (trainerPage - 1) * trainerPageSize;
        return sortedStudentSummaries.slice(start, start + trainerPageSize);
    }, [sortedStudentSummaries, trainerPage, trainerPageSize]);

    useEffect(() => {
        setTrainerPage(1);
    }, [searchTerm, sortConfig.key, sortConfig.direction, trainerPageSize]);

    const returnToMasterAdmin = () => {
        const masterReturnUrl = sessionStorage.getItem('master_return_url') || '/admin/metaverso';
        clearCompanyContext({ clearLocalStorage: false });

        const openerWindow = window.opener as Window | null;
        if (openerWindow && !openerWindow.closed) {
            try {
                openerWindow.focus();
                openerWindow.location.href = masterReturnUrl;
            } catch (error) {
                console.warn('No se pudo enfocar/actualizar la pestaña origen:', error);
            }

            window.close();
            window.setTimeout(() => {
                window.location.replace(masterReturnUrl);
            }, 220);
            return;
        }

        window.location.replace(masterReturnUrl);
    };

    const handleLogout = async () => {
        const slug = getStoredCompanyValue('empresa_slug');
        const isMasterContext = sessionStorage.getItem('is_master_admin') === 'true';

        if (isMasterContext) {
            returnToMasterAdmin();
            return;
        }

        await supabase.auth.signOut({ scope: 'local' });
        clearCompanyContext({ clearLocalStorage: true });
        
        if (slug) {
            router.push(`/admin/empresa/portal/${slug}`);
        } else {
            router.push("/admin/empresa/login");
        }
    };

    const [showCompanyManager, setShowCompanyManager] = useState(false);
    const [allCompanies, setAllCompanies] = useState<any[]>([]);
    const [editingCompanyListId, setEditingCompanyListId] = useState<string | null>(null);
    const [editingCompanyListName, setEditingCompanyListName] = useState("");
    const [userRegistrationConfig, setUserRegistrationConfig] = useState<Record<string, any> | null>(null);

    const getRegistrationFieldConfig = (field: string) => userRegistrationConfig?.[field] || null;
    const isRegistrationFieldVisible = (field: string) => getRegistrationFieldConfig(field)?.visible !== false;
    const isRegistrationFieldRequired = (field: string) => getRegistrationFieldConfig(field)?.required === true;

    const isCompanyCollabVisible = isRegistrationFieldVisible('company_collab');
    const isJobPositionVisible = isRegistrationFieldVisible('job_position');
    const isGenderVisible = isRegistrationFieldVisible('gender');
    const isAgeVisible = isRegistrationFieldVisible('age');
    const isRutVisible = isRegistrationFieldVisible('rut');

    const isCompanyCollabRequired = isRegistrationFieldRequired('company_collab');
    const isJobPositionRequired = isRegistrationFieldRequired('job_position');
    const isGenderRequired = isRegistrationFieldRequired('gender');
    const isAgeRequired = isRegistrationFieldRequired('age');
    const isRutRequired = isRegistrationFieldRequired('rut');

    const buildCompanyCollaboratorCode = (name: string) => {
        const normalizedName = name
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_|_$/g, '');
        const companySuffix = (companyId || '').replace(/-/g, '').slice(0, 8) || 'COMPANY';
        return `${companySuffix}_${normalizedName}`.slice(0, 50);
    };

    const fetchUserRegistrationConfig = async () => {
        if (!companyId) return;

        try {
            const { data, error } = await supabase
                .from('companies')
                .select('user_registration_config')
                .eq('id', companyId)
                .single();

            if (error) {
                console.error('Error fetching user registration config:', error);
                return;
            }

            setUserRegistrationConfig(data?.user_registration_config || null);
        } catch (err) {
            console.error('Unexpected error in fetchUserRegistrationConfig:', err);
        }
    };

    useEffect(() => {
        setNewStudent((prev: any) => {
            const next = { ...prev };

            if (!isRutVisible) {
                next.doc_type = 'RUT';
                next.rut = '';
            } else if (isRutRequired) {
                next.doc_type = 'RUT';
            }

            if (!isGenderVisible) next.gender = '';
            if (!isAgeVisible) next.age = '';
            if (!isJobPositionVisible) next.role_id = null;

            return next;
        });
    }, [isRutVisible, isRutRequired, isGenderVisible, isAgeVisible, isCompanyCollabVisible, isJobPositionVisible]);

    const fetchCompanyList = async () => {
        if (!companyId) return;

        try {
            const { data, error } = await supabase
                .from('companies_list')
                .select('*')
                .eq('company_id', companyId)
                .order('name_es');
            if (error) {
                console.error("Error fetching companies_list:", error);
                if (error.code === '42P01') alert("La tabla companies_list no existe en la base de datos.");
                else if (error.message.includes("policy")) alert("Error de permisos (RLS): No puedes ver la lista de empresas.");
                return;
            }
            setAllCompanies(data || []);
        } catch (err) {
            console.error("Unexpected error in fetchCompanyList:", err);
        }
    };

    useEffect(() => {
        if (showCompanyManager) fetchCompanyList();
    }, [showCompanyManager, companyId]);

    // Also load companies on initial page load so the edit modal has them
    useEffect(() => {
        if (companyId) {
            fetchCompanyList();
            fetchUserRegistrationConfig();
        }
    }, [companyId]);

    const handleCreateCompany = async (name: string) => {
        if (!companyId) return;

        const trimmedName = name.trim();
        if (!trimmedName) return;

        const code = buildCompanyCollaboratorCode(trimmedName);
        const { error } = await supabase.from('companies_list').insert({
            name_es: trimmedName,
            code,
            company_id: companyId
        });
        if (error) {
            console.error("Error creating company:", error);
            if (error.message.includes("policy")) {
                alert("Error de Permisos (RLS): No tienes permiso para agregar empresas. Por favor asegúrate de haber ejecutado la migración 007.");
            } else {
                alert("Error al crear empresa: " + error.message);
            }
        }
        else fetchCompanyList();
    };

    if (!companyId) return null;

    if (isAuthenticating) return (
        <div className="min-h-screen bg-black flex items-center justify-center">
            <div className="text-brand font-black animate-pulse uppercase tracking-widest">Validando Acceso Maestro...</div>
        </div>
    );

    return (
        <div className="min-h-screen bg-transparent text-white p-4 md:p-8 font-sans">
            <div className="max-w-7xl mx-auto space-y-8">
                <header className="flex flex-col md:flex-row justify-between items-center gap-6 glass p-6 rounded-3xl border-white/5">
                    <div className="flex items-center gap-5">
                        <div className="w-14 h-14 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                            <Building2 className="w-8 h-8 text-brand" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-black">{companyName}</h1>
                            <p className="text-xs text-white/40 uppercase font-bold tracking-widest">Portal de Gestión Corporativa</p>
                            {isMasterAdmin && (
                                <div className="mt-2 inline-flex items-center gap-2 rounded-full border border-brand/20 bg-brand/10 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-brand">
                                    <Shield className="w-3.5 h-3.5" />
                                    <span>Sesión Metaverso</span>
                                    <span className="text-white/70">{masterRole === 'superadmin' ? 'Super Admin' : masterRole === 'administrador' ? 'Administrador' : 'Editor'}</span>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        {isMasterAdmin && (
                            <button
                                onClick={() => {
                                    returnToMasterAdmin();
                                }}
                                className="p-3 rounded-2xl bg-brand/10 border border-brand/20 text-brand hover:bg-brand hover:text-black transition-all"
                                title={`Volver a Metaverso Admin${masterRole ? ` (${masterRole})` : ''}`}
                            >
                                <Shield className="w-5 h-5" />
                            </button>
                        )}
                        <div className="flex bg-black/40 p-1.5 rounded-2xl border border-white/5 text-[10px] font-black uppercase">
                            <button onClick={() => setRole('manager')} className={`px-6 py-2.5 rounded-xl transition-all ${role === 'manager' ? 'bg-brand text-black shadow-lg' : 'text-white/40'}`}>Vista General</button>
                            <button onClick={() => setRole('trainer')} className={`px-6 py-2.5 rounded-xl transition-all ${role === 'trainer' ? 'bg-brand text-black shadow-lg' : 'text-white/40'}`}>Gestión de Alumnos</button>
                        </div>
                        <button onClick={() => setShowConfig(true)} className="p-3 rounded-2xl bg-white/5 border border-white/10 text-white/60 hover:text-brand transition-all" title="Configuración de Firmas">
                            <Settings className="w-5 h-5" />
                        </button>
                        <button onClick={handleLogout} className="p-3 rounded-2xl bg-white/5 border border-white/10 text-white/40 hover:text-red-400 transition-all" title="Cerrar Sesión">
                            <LogOut className="w-5 h-5" />
                        </button>
                    </div>
                </header>

                {role === 'manager' ? (
                    <EnhancedManagerDashboard companyName={companyName} companyId={companyId || undefined} isMasterAdmin={masterRole === 'superadmin'} />
                ) : (
                    <div className="space-y-6">
                        <div className="flex flex-col md:flex-row gap-4 justify-between items-center">
                            <div className="relative w-full md:max-w-md">
                                <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                                <input type="text" placeholder="Buscar..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm outline-none" />
                            </div>
                            <div className="flex gap-3">
                                {isCompanyCollabVisible && (
                                    <button onClick={() => setShowCompanyManager(true)} className="p-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2 transition-all hover:bg-white/10"><BookOpen className="w-4 h-4" /> Empresas Colaboradoras</button>
                                )}
                                <button onClick={() => setShowCargoManager(true)} className="p-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2 transition-all hover:bg-white/10"><Shield className="w-4 h-4" /> Cargos</button>
                                <button onClick={() => setIsCreating(true)} className="p-4 bg-brand text-black rounded-2xl text-[10px] font-black uppercase flex items-center gap-2 transition-all hover:scale-105 shadow-lg shadow-brand/10"><UserPlus className="w-4 h-4" /> Nuevo</button>
                            </div>
                        </div>

                        <div className="glass overflow-hidden">
                            <table className="w-full text-left">
                                <thead className="bg-white/5 text-[10px] font-black uppercase text-white/40">
                                    <tr>
                                        <th className="px-6 py-4 cursor-pointer" onClick={() => toggleSort('name')}>Colaborador</th>
                                        <th className="px-6 py-4 cursor-pointer" onClick={() => toggleSort('cargo')}>Cargo</th>
                                        <th className="px-6 py-4 cursor-pointer" onClick={() => toggleSort('course')}>Cursos</th>
                                        <th className="px-6 py-4 cursor-pointer" onClick={() => toggleSort('status')}>Estado</th>
                                        <th className="px-6 py-4 cursor-pointer" onClick={() => toggleSort('certificate')}>Certificados</th>
                                        <th className="px-6 py-4">Bloqueado</th>
                                        <th className="px-6 py-4 text-right">Gestión</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/5">
                                    {pagedStudentSummaries.map(({ student: st, validEnrollments, totalCourses, completedCount, inProgressCount, availableCerts }) => (
                                        <tr key={st.id} className="hover:bg-white/[0.02] text-sm font-medium">
                                            <td className="px-6 py-4">
                                                <p className="font-bold">{st.first_name} {st.last_name}</p>
                                                <p className="text-[10px] text-white/40 font-mono">{st.rut} • {companyName}</p>
                                            </td>
                                            <td className="px-6 py-4">{st.company_roles?.name || 'Sin Cargo'}</td>
                                            <td className="px-6 py-4">
                                                <button
                                                    onClick={() => setSelectedStudentCourses({ student: st, enrollments: validEnrollments })}
                                                    className="px-2 py-1 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-[11px] font-bold"
                                                >
                                                    {totalCourses} curso{totalCourses === 1 ? '' : 's'}
                                                </button>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="text-[11px] font-bold text-white/70">{completedCount} completados</span>
                                                {inProgressCount > 0 && <span className="ml-2 text-[10px] text-yellow-300">{inProgressCount} en progreso</span>}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="text-[11px] font-bold text-brand">{availableCerts} disponibles</span>
                                            </td>
                                            <td className="px-6 py-4">
                                                {st.is_locked ? (
                                                    <button
                                                        onClick={async () => { await supabase.from('students').update({ is_locked: false, login_attempts: 0 }).eq('id', st.id); fetchData(); }}
                                                        className="flex items-center gap-1 px-2 py-1 bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg text-[9px] font-black hover:bg-red-500/20 transition-all whitespace-nowrap"
                                                    ><Lock className="w-3 h-3" /> Desbloquear</button>
                                                ) : (
                                                    <span className="text-[9px] text-white/20 font-bold">—</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                                <button
                                                    onClick={() => setSelectedStudentCourses({ student: st, enrollments: validEnrollments })}
                                                    className="p-2 rounded-xl bg-white/5 border border-white/10"
                                                    title="Ver cursos"
                                                ><BookOpen className="w-4 h-4" /></button>
                                                <button onClick={() => openEditStudent(st)} className="p-2 rounded-xl bg-white/5 border border-white/10" title="Editar"><UserCog className="w-4 h-4" /></button>
                                                <button onClick={() => handleDeleteStudent(st.id)} className="p-2 rounded-xl bg-white/5 border border-white/10 text-red-400" title="Eliminar"><Trash2 className="w-4 h-4" /></button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            <div className="px-6 py-3 border-t border-white/10 flex flex-wrap items-center justify-between gap-2 text-xs text-white/60">
                                <div>Mostrando {pagedStudentSummaries.length} de {sortedStudentSummaries.length} alumnos</div>
                                <div className="flex items-center gap-2">
                                    <label>Filas</label>
                                    <select
                                        value={trainerPageSize}
                                        onChange={(e) => { setTrainerPageSize(Number(e.target.value)); setTrainerPage(1); }}
                                        className="bg-white/5 border border-white/10 rounded-lg px-2 py-1 text-xs text-white [&>option]:bg-slate-100 [&>option]:text-slate-900"
                                    >
                                        <option className="bg-slate-100 text-slate-900" value={10}>10</option>
                                        <option className="bg-slate-100 text-slate-900" value={20}>20</option>
                                        <option className="bg-slate-100 text-slate-900" value={50}>50</option>
                                    </select>
                                    <button onClick={() => setTrainerPage((p) => Math.max(1, p - 1))} disabled={trainerPage <= 1} className="px-2 py-1 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed">Anterior</button>
                                    <span>Página {trainerPage} de {totalTrainerPages}</span>
                                    <button onClick={() => setTrainerPage((p) => Math.min(totalTrainerPages, p + 1))} disabled={trainerPage >= totalTrainerPages} className="px-2 py-1 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed">Siguiente</button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {certData && <CertificateCanvas {...certData} onReady={(blob) => {
                    if (!certGenerationLock.current) return;
                    certGenerationLock.current = false;
                    const reader = new FileReader(); 
                    reader.readAsDataURL(blob);
                    reader.onloadend = async () => { 
                        const base64data = reader.result as string;
                        const pdf = new jsPDF("p", "px", [1414, 2000]); // Portrait como el alumno
                        pdf.addImage(base64data, "PNG", 0, 0, 1414, 2000); 
                        const fileSuffix = certData.certificateType === 'aprobacion' ? 'Aprobacion' : certData.certificateType === 'irl' ? 'IRL' : 'Participacion';
                        pdf.save(`Certificado_${fileSuffix}_${certData.rut}.pdf`); 
                        if (pendingCertificateIssue) {
                            await trackCertificateIssuance({
                                enrollmentId: pendingCertificateIssue.enrollmentId,
                                studentId: pendingCertificateIssue.studentId,
                                courseId: pendingCertificateIssue.courseId,
                                certificateType: pendingCertificateIssue.certificateType,
                                contentSignature: pendingCertificateIssue.contentSignature,
                            });
                        }
                        setPendingCertificateIssue(null);
                        setCertData(null); 
                        setIsGeneratingCert(false);
                    };
                }} />}

                {selectedStudentCourses && (
                    <div className="fixed inset-0 z-[95] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                        <div className="glass w-full max-w-4xl rounded-2xl border border-white/10 p-5 max-h-[85vh] overflow-y-auto">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h3 className="text-xl font-black">Cursos de {selectedStudentCourses.student.first_name} {selectedStudentCourses.student.last_name}</h3>
                                    <p className="text-xs text-white/40">{selectedStudentCourses.enrollments.length} curso(s) asociado(s)</p>
                                </div>
                                <button onClick={() => setSelectedStudentCourses(null)} className="p-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10"><X className="w-4 h-4" /></button>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="text-white/40 border-b border-white/10">
                                        <tr>
                                            <th className="text-left py-2 pr-3">Curso</th>
                                            <th className="text-left py-2 pr-3">Estado</th>
                                            <th className="text-left py-2 pr-3">Intentos</th>
                                            <th className="text-left py-2 pr-3">Nota</th>
                                            <th className="text-left py-2">Completado</th>
                                            <th className="text-left py-2 pl-3">Certificados</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {selectedStudentCourses.enrollments.map((en: any) => {
                                            const course = courses.find((c: any) => c.id === en.course_id);
                                            const courseName = course?.name || 'Curso desconocido';
                                            const attempts = en.current_attempt || 0;
                                            const availableCertificateTypes = getEnrollmentCertificateTypes(selectedStudentCourses.student, en, course);
                                            return (
                                                <tr key={en.id || `${selectedStudentCourses.student.id}-${en.course_id}`} className="border-b border-white/5">
                                                    <td className="py-2 pr-3 text-white/90">{courseName}</td>
                                                    <td className="py-2 pr-3 text-white/70">{en.status || 'pending'}</td>
                                                    <td className="py-2 pr-3 text-white/70">{attempts}</td>
                                                    <td className="py-2 pr-3 text-white/70">{Number(en.best_score || 0)}%</td>
                                                    <td className="py-2 text-white/70">{en.completed_at ? new Date(en.completed_at).toLocaleDateString('es-CL') : '—'}</td>
                                                    <td className="py-2 pl-3">
                                                        {availableCertificateTypes.length > 0 ? (
                                                            <div className="flex flex-wrap gap-2">
                                                                {availableCertificateTypes.map((certificateType) => {
                                                                    const certificateButtonClass =
                                                                        certificateType === 'participacion'
                                                                            ? 'border-brand/30 bg-brand/10 text-brand hover:bg-brand hover:text-black'
                                                                            : certificateType === 'aprobacion'
                                                                                ? 'border-fuchsia-500/40 bg-fuchsia-500/15 text-fuchsia-300 hover:bg-fuchsia-500 hover:text-black'
                                                                                : 'border-cyan-500/40 bg-cyan-500/15 text-cyan-300 hover:bg-cyan-400 hover:text-black';

                                                                    return (
                                                                        <button
                                                                            key={`${en.id}-${certificateType}`}
                                                                            onClick={() => downloadCourseCertificate(selectedStudentCourses.student, en, certificateType)}
                                                                            disabled={isGeneratingCert}
                                                                            className={`inline-flex items-center gap-1 rounded-lg border px-3 py-1 text-[10px] font-black uppercase transition-all disabled:cursor-not-allowed disabled:opacity-50 ${certificateButtonClass}`}
                                                                            title={certificateType === 'participacion' ? 'Descargar certificado de participación' : certificateType === 'aprobacion' ? 'Descargar certificado de aprobación' : 'Descargar certificado IRL'}
                                                                        >
                                                                            <Download className="w-3 h-3" />
                                                                            {certificateType === 'participacion' ? 'Participación' : certificateType === 'aprobacion' ? 'Aprobación' : 'IRL'}
                                                                        </button>
                                                                    );
                                                                })}
                                                            </div>
                                                        ) : (
                                                            <span className="text-[10px] font-bold uppercase text-white/20">No disponible</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {isEditing && (
                    <div className="fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                        <div className="glass p-10 w-full max-w-lg space-y-6 max-h-[90vh] overflow-y-auto custom-scrollbar relative">
                            {/* Botón Cerrar */}
                            <button 
                                onClick={() => setIsEditing(null)}
                                className="absolute top-4 right-4 p-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white/60 hover:text-white transition-all"
                            >
                                <X className="w-5 h-5" />
                            </button>
                            
                            <h3 className="text-xl font-black uppercase tracking-tight">Editar Trabajador</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Nombre *</label>
                                    <input value={isEditing.first_name} onChange={(e) => setIsEditing({ ...isEditing, first_name: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Apellido *</label>
                                    <input value={isEditing.last_name} onChange={(e) => setIsEditing({ ...isEditing, last_name: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                            </div>

                            {isRutVisible && (
                                <div className="space-y-2">
                                    <div className="flex items-center gap-4">
                                        <label className="text-[10px] font-black uppercase text-white/40 ml-2">Tipo de Documento:</label>
                                        {isRutVisible && !isRutRequired ? (
                                            <div className="flex bg-white/5 p-1 rounded-lg">
                                                <button
                                                    onClick={() => setIsEditing({ ...isEditing, doc_type: 'RUT' })}
                                                    className={`px-4 py-1 rounded-md text-[10px] font-black uppercase transition-all ${(!isEditing.doc_type || isEditing.doc_type === 'RUT') ? 'bg-brand text-black shadow-lg shadow-brand/20' : 'text-white/40 hover:text-white'}`}
                                                >
                                                    RUT Chileno
                                                </button>
                                                <button
                                                    onClick={() => setIsEditing({ ...isEditing, doc_type: 'PASSPORT' })}
                                                    className={`px-4 py-1 rounded-md text-[10px] font-black uppercase transition-all ${isEditing.doc_type === 'PASSPORT' ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'text-white/40 hover:text-white'}`}
                                                >
                                                    Pasaporte
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="px-4 py-2 rounded-lg bg-white/5 text-[10px] font-black uppercase text-white/60">
                                                RUT Chileno
                                            </div>
                                        )}
                                    </div>

                                    <div className="space-y-1">
                                        <label className="text-[10px] font-black uppercase text-white/40 ml-2">
                                            {(!isEditing.doc_type || isEditing.doc_type === 'RUT') ? `RUT (Sin puntos, con guión)${isRutRequired ? ' *' : ''}` : 'Pasaporte / ID Extranjero *'}
                                        </label>
                                        <input
                                            placeholder={(!isEditing.doc_type || isEditing.doc_type === 'RUT') ? '12345678-K' : 'A1234567'}
                                            value={isEditing.rut || ''}
                                            onChange={(e) => setIsEditing({ ...isEditing, rut: e.target.value })}
                                            className={`w-full bg-white/5 border p-3 rounded-xl text-sm uppercase ${(!isEditing.doc_type || isEditing.doc_type === 'RUT') && isEditing.rut && !/^[0-9]+-[0-9kK]{1}$/.test(isEditing.rut) ? 'border-red-500/50 text-red-200' : 'border-white/10'}`}
                                        />
                                        {(!isEditing.doc_type || isEditing.doc_type === 'RUT') && (
                                            <p className="text-[9px] text-white/30 ml-2">Formato: 12345678-K (Sin puntos)</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Email</label>
                                    <input value={isEditing.email || ""} onChange={(e) => setIsEditing({ ...isEditing, email: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Contraseña</label>
                                    <input value={isEditing.password || ""} onChange={(e) => setIsEditing({ ...isEditing, password: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                {isJobPositionVisible && (
                                    <div className="space-y-1">
                                        <label className="text-[10px] font-black uppercase text-white/40 ml-2">Cargo{isJobPositionRequired ? ' *' : ''}</label>
                                        <select
                                            value={isEditing.role_id || ""}
                                            onChange={(e) => {
                                                const val = e.target.value;
                                                setIsEditing({ ...isEditing, role_id: val === "" ? null : val });
                                            }}
                                            className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-white outline-none"
                                            style={{ colorScheme: 'dark' }}
                                        >
                                            <option value="" className="bg-neutral-900 text-white">Seleccionar Cargo</option>
                                            {cargos.map(c => <option key={c.id} value={c.id} className="bg-neutral-900 text-white">{c.name}</option>)}
                                        </select>
                                    </div>
                                )}
                            </div>

                            {(isAgeVisible || isGenderVisible) && (
                                <div className="grid grid-cols-2 gap-4">
                                    {isAgeVisible && (
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 ml-2">Edad{isAgeRequired ? ' *' : ''}</label>
                                            <input type="number" min="1" max="120" value={isEditing.age || ""} onChange={(e) => setIsEditing({ ...isEditing, age: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" placeholder="Ej: 35" />
                                        </div>
                                    )}
                                    {isGenderVisible && (
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 ml-2">Género{isGenderRequired ? ' *' : ''}</label>
                                            <select
                                                value={isEditing.gender || ""}
                                                onChange={(e) => setIsEditing({ ...isEditing, gender: e.target.value || null })}
                                                className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-white outline-none"
                                                style={{ colorScheme: 'dark' }}
                                            >
                                                <option value="" className="bg-neutral-900 text-white">Seleccionar</option>
                                                <option value="Masculino" className="bg-neutral-900 text-white">Masculino</option>
                                                <option value="Femenino" className="bg-neutral-900 text-white">Femenino</option>
                                                <option value="No binario" className="bg-neutral-900 text-white">No binario</option>
                                                <option value="Prefiero no decir" className="bg-neutral-900 text-white">Prefiero no decir</option>
                                            </select>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="flex gap-4">
                                <button onClick={() => setIsEditing(null)} className="flex-1 p-4 bg-white/5 rounded-xl uppercase font-black text-[10px]">Cancelar</button>
                                <button onClick={() => handleUpdateStudent(isEditing)} className="flex-1 p-4 bg-brand text-black rounded-xl uppercase font-black text-[10px]">Guardar</button>
                            </div>

                            <div className="pt-6 border-t border-white/10 space-y-4">
                                <h4 className="text-sm font-black uppercase text-white/40">Asignar Capacitación</h4>
                                <div className="space-y-2 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                                    {courses.map(course => {
                                        // Check if student is already enrolled in this course
                                        const isAssigned = students.find(s => s.id === isEditing.id)?.enrollments?.some((e: any) => e.course_id === course.id) || false;

                                        return (
                                            <div key={course.id} className={`flex items-center justify-between p-3 rounded-xl border transition-colors ${isAssigned
                                                ? 'bg-brand/10 border-brand/30'
                                                : 'bg-white/5 border-white/5 hover:border-brand/30'
                                                }`}>
                                                <div className="flex items-center gap-2 flex-1">
                                                    <span className="text-xs font-bold truncate max-w-[150px]">{course.name}</span>
                                                    {isAssigned && (
                                                        <span className="text-[8px] px-2 py-0.5 bg-brand/20 text-brand rounded-full font-black uppercase border border-brand/30">
                                                            Asignado
                                                        </span>
                                                    )}
                                                </div>
                                                <button
                                                    onClick={async () => {
                                                        // Resetear TODOS los campos de progreso para asegurar un reinicio limpio
                                                        // NOTA: Los campos de examen y encuesta se agregan condicionalmente si existen en la BD
                                                        const baseUpdateData: any = {
                                                            student_id: isEditing.id,
                                                            course_id: course.id,
                                                            status: 'not_started',
                                                            best_score: 0,
                                                            quiz_score: 0,
                                                            scorm_score: 0,
                                                            current_module_index: 0,
                                                            completed_at: null,
                                                            current_attempt: 0,
                                                            max_attempts: 3
                                                        };

                                                        let updatedEnrollment = null;
                                                        let error = null;

                                                        // PRIMER INTENTO: Upsert COMPLETO (Ideal)
                                                        // Incluye 'progress' si existe
                                                        const { data: res1, error: err1 } = await supabase
                                                            .from('enrollments')
                                                            .upsert({ ...baseUpdateData, progress: 0 }, { onConflict: 'student_id,course_id' })
                                                            .select();
                                                        
                                                        if (!err1) {
                                                            updatedEnrollment = res1;
                                                        } else {
                                                            console.warn("Error en upsert completo (quizás falte columna progress):", err1.message);
                                                            // SEGUNDO INTENTO: Upsert DEGRADADO (Si falla progress)
                                                            const { data: res2, error: err2 } = await supabase
                                                                .from('enrollments')
                                                                .upsert(baseUpdateData, { onConflict: 'student_id,course_id' })
                                                                .select();
                                                            
                                                            updatedEnrollment = res2;
                                                            error = err2;
                                                        }
                                                        
                                                        // Si logramos hacer el upsert básico, intentar limpiar columnas nuevas
                                                        if (!error && updatedEnrollment?.[0]?.id) {
                                                            // Intentar limpiar los campos nuevos en una llamada separada que puede fallar silenciosamente
                                                            try {
                                                                await supabase.from('enrollments').update({
                                                                    last_exam_passed: null,
                                                                    last_exam_score: null,
                                                                    survey_completed: false
                                                                }).eq('id', updatedEnrollment[0].id);
                                                            } catch (e) {
                                                                console.warn("Columnas de examen no existen aún, saltando reset extendido");
                                                            }
                                                        }

                                                        if (error) alert("Error: " + error.message);
                                                        else {
                                                            // Si estamos reiniciando, limpiar también logs detallados
                                                            if (isAssigned && updatedEnrollment?.[0]?.id) {
                                                                await supabase.from('course_progress').delete().eq('enrollment_id', updatedEnrollment[0].id);
                                                            }

                                                            alert(`Curso ${course.name} ${isAssigned ? 'reiniciado' : 'asignado'}.`);
                                                            fetchData(); // Refresh to show updated status
                                                        }
                                                    }}
                                                    className={`px-3 py-1.5 text-[9px] font-black uppercase rounded-lg transition-all ${isAssigned
                                                        ? 'bg-white/10 text-white/60 hover:bg-white/20'
                                                        : 'bg-brand/10 text-brand hover:bg-brand hover:text-black'
                                                        }`}
                                                >
                                                    {isAssigned ? 'Reiniciar' : 'Habilitar'}
                                                </button>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {isCreating && (
                    <div className="fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                        <div className="glass p-10 w-full max-w-lg space-y-6 max-h-[90vh] overflow-y-auto custom-scrollbar">
                            <h3 className="text-xl font-black uppercase tracking-tight">Nuevo Trabajador</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Nombre *</label>
                                    <input placeholder="Juan" value={newStudent.first_name} onChange={(e) => setNewStudent({ ...newStudent, first_name: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Apellido *</label>
                                    <input placeholder="Pérez" value={newStudent.last_name} onChange={(e) => setNewStudent({ ...newStudent, last_name: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                            </div>

                            {isRutVisible && (
                                <div className="space-y-2">
                                    <div className="flex items-center gap-4">
                                        <label className="text-[10px] font-black uppercase text-white/40 ml-2">Tipo de Documento: </label>
                                        {!isRutRequired ? (
                                            <div className="flex bg-white/5 p-1 rounded-lg">
                                                <button 
                                                    onClick={() => setNewStudent({...newStudent, doc_type: 'RUT'})}
                                                    className={`px-4 py-1 rounded-md text-[10px] font-black uppercase transition-all ${(!newStudent.doc_type || newStudent.doc_type === 'RUT') ? 'bg-brand text-black shadow-lg shadow-brand/20' : 'text-white/40 hover:text-white'}`}
                                                >
                                                    RUT Chileno
                                                </button>
                                                <button 
                                                    onClick={() => setNewStudent({...newStudent, doc_type: 'PASSPORT'})}
                                                    className={`px-4 py-1 rounded-md text-[10px] font-black uppercase transition-all ${newStudent.doc_type === 'PASSPORT' ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'text-white/40 hover:text-white'}`}
                                                >
                                                    Pasaporte
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="px-4 py-2 rounded-lg bg-white/5 text-[10px] font-black uppercase text-white/60">
                                                RUT Chileno
                                            </div>
                                        )}
                                    </div>

                                    <div className="space-y-1">
                                        <label className="text-[10px] font-black uppercase text-white/40 ml-2">
                                            {(!newStudent.doc_type || newStudent.doc_type === 'RUT') ? `RUT (Sin puntos, con guión)${isRutRequired ? ' *' : ''}` : 'Pasaporte / ID Extranjero *'}
                                        </label>
                                        <input 
                                            placeholder={(!newStudent.doc_type || newStudent.doc_type === 'RUT') ? "12345678-K" : "A1234567"} 
                                            value={newStudent.rut} 
                                            onChange={(e) => {
                                                const val = e.target.value;
                                                setNewStudent({ ...newStudent, rut: val });
                                            }} 
                                            className={`w-full bg-white/5 border p-3 rounded-xl text-sm uppercase ${
                                                (!newStudent.doc_type || newStudent.doc_type === 'RUT') && newStudent.rut && !/^[0-9]+-[0-9kK]{1}$/.test(newStudent.rut) 
                                                    ? 'border-red-500/50 text-red-200' 
                                                    : 'border-white/10'
                                            }`} 
                                        />
                                        {(!newStudent.doc_type || newStudent.doc_type === 'RUT') && (
                                            <p className="text-[9px] text-white/30 ml-2">Formato: 12345678-K (Sin puntos)</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Email / Correo Alumno</label>
                                    <input placeholder="alumno@correo.cl" value={newStudent.email} onChange={(e) => setNewStudent({ ...newStudent, email: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Contraseña de Acceso</label>
                                    <input placeholder="123456" type="text" value={newStudent.password} onChange={(e) => setNewStudent({ ...newStudent, password: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                {isJobPositionVisible && (
                                    <div className="space-y-1">
                                        <label className="text-[10px] font-black uppercase text-white/40 ml-2">Cargo{isJobPositionRequired ? ' *' : ''}</label>
                                        <select
                                            value={newStudent.role_id || ""}
                                            onChange={(e) => {
                                                const val = e.target.value;
                                                setNewStudent({ ...newStudent, role_id: val === "" ? null : val });
                                            }}
                                            className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-white outline-none"
                                            style={{ colorScheme: 'dark' }}
                                        >
                                            <option value="" className="bg-neutral-900 text-white">Seleccionar Cargo</option>
                                            {cargos.map(c => <option key={c.id} value={c.id} className="bg-neutral-900 text-white">{c.name}</option>)}
                                        </select>
                                    </div>
                                )}
                            </div>

                            {(isGenderVisible || isAgeVisible) && (
                                <div className="grid grid-cols-2 gap-4">
                                    {isGenderVisible && (
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 ml-2">Género{isGenderRequired ? ' *' : ''}</label>
                                            <select
                                                value={newStudent.gender || ""}
                                                onChange={(e) => setNewStudent({ ...newStudent, gender: e.target.value })}
                                                className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-white outline-none"
                                                style={{ colorScheme: 'dark' }}
                                            >
                                                <option value="" className="bg-neutral-900 text-white">Seleccionar</option>
                                                <option value="Masculino" className="bg-neutral-900 text-white">Masculino</option>
                                                <option value="Femenino" className="bg-neutral-900 text-white">Femenino</option>
                                                <option value="No binario" className="bg-neutral-900 text-white">No binario</option>
                                                <option value="Prefiero no decir" className="bg-neutral-900 text-white">Prefiero no decir</option>
                                            </select>
                                        </div>
                                    )}
                                    {isAgeVisible && (
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 ml-2">Edad{isAgeRequired ? ' *' : ''}</label>
                                            <input type="number" min="1" max="120" value={newStudent.age || ""} onChange={(e) => setNewStudent({ ...newStudent, age: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" placeholder="Ej: 35" />
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="flex gap-4">
                                <button onClick={() => setIsCreating(false)} className="flex-1 p-4 bg-white/5 rounded-xl uppercase font-black text-[10px]">Cancelar</button>
                                <button onClick={handleCreateStudent} className="flex-1 p-4 bg-brand text-black rounded-xl uppercase font-black text-[10px]">Crear Alumno</button>
                            </div>
                        </div>
                    </div>
                )}

                {showCargoManager && (
                    <div className="fixed inset-0 z-[100] bg-black/80 flex items-center justify-center p-4">
                        <div className="glass p-10 w-full max-w-2xl space-y-6 max-h-[90vh] overflow-y-auto custom-scrollbar">
                            <div className="flex justify-between items-center">
                                <h3 className="text-xl font-black uppercase tracking-tight text-brand">Gestionar Cargos</h3>
                                {!showCreateCargo && !editingCargo && (
                                    <button 
                                        onClick={() => setShowCreateCargo(true)}
                                        className="bg-brand text-black px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:scale-105 transition-all"
                                    >
                                        + Crear Nuevo Cargo
                                    </button>
                                )}
                            </div>
                            
                            {/* Formulario crear/editar */}
                            {(showCreateCargo || editingCargo) && (
                                <div className="bg-white/5 p-6 rounded-2xl border border-white/5 space-y-4 animate-in fade-in slide-in-from-top-4 duration-300">
                                    <p className="text-[10px] font-black uppercase text-white/30 tracking-widest">
                                        {editingCargo ? '✏️ Editando Cargo' : 'Añadir Nuevo Cargo'}
                                    </p>
                                    <div className="grid grid-cols-2 gap-4">
                                        <input id="newCargoName" placeholder="Nombre (Español)..." className="bg-white/5 p-3 rounded-xl text-sm border border-white/10 text-white" defaultValue={editingCargo?.name || ''} />
                                        <input id="newCargoNameHT" placeholder="Nombre (Creole)..." className="bg-white/5 p-3 rounded-xl text-sm border border-white/10 text-white" defaultValue={editingCargo?.name_ht || ''} />
                                    </div>
                                    <div className="space-y-4">
                                        <div className="flex justify-between items-center mb-2">
                                            <p className="text-[10px] font-black uppercase text-white/30 tracking-widest">Descripción</p>
                                            <div className="flex bg-white/5 rounded-lg p-1 gap-1">
                                                <button
                                                    onClick={() => setDescLang('es')}
                                                    className={`px-3 py-1 rounded-md text-[10px] font-black uppercase transition-all ${descLang === 'es' ? 'bg-brand text-black shadow-lg shadow-brand/20' : 'text-white/40 hover:text-white'}`}
                                                >
                                                    Español
                                                </button>
                                                <button
                                                    onClick={() => setDescLang('ht')}
                                                    className={`px-3 py-1 rounded-md text-[10px] font-black uppercase transition-all ${descLang === 'ht' ? 'bg-brand text-black shadow-lg shadow-brand/20' : 'text-white/40 hover:text-white'}`}
                                                >
                                                    Haitiano (Creole)
                                                </button>
                                            </div>
                                        </div>
                                        <div className="bg-white/5 rounded-2xl overflow-hidden border border-white/10 min-h-[200px]">
                                            {descLang === 'es' ? (
                                                <RichTextEditor
                                                    content={cargoDesc}
                                                    onChange={setCargoDesc}
                                                />
                                            ) : (
                                                <RichTextEditor
                                                    content={cargoDescHT}
                                                    onChange={setCargoDescHT}
                                                />
                                            )}
                                        </div>
                                        <div className="flex gap-4 pt-2">
                                            <button
                                                onClick={() => {
                                                    setShowCreateCargo(false);
                                                    setEditingCargo(null);
                                                    setCargoDesc("");
                                                    setCargoDescHT("");
                                                    const n = document.getElementById('newCargoName') as HTMLInputElement;
                                                    const nHT = document.getElementById('newCargoNameHT') as HTMLInputElement;
                                                    if(n) n.value = '';
                                                    if(nHT) nHT.value = '';
                                                }}
                                                className="px-6 bg-white/10 text-white py-4 rounded-xl font-black text-xs uppercase hover:bg-white/20 transition-all text-center"
                                            >
                                                Cancelar
                                            </button>
                                        <button 
                                            onClick={async () => { 
                                                const n = document.getElementById('newCargoName') as HTMLInputElement; 
                                                const nHT = document.getElementById('newCargoNameHT') as HTMLInputElement; 
                                                
                                                if(!n.value || !companyId) return; 
                                                
                                                if (editingCargo) {
                                                    // Update existing
                                                    const { error } = await supabase.from('company_roles').update({ 
                                                        name: n.value, 
                                                        name_ht: nHT.value || null,
                                                        description: cargoDesc || null,
                                                        description_ht: cargoDescHT || null
                                                    }).eq('id', editingCargo.id);
                                                    if(error) alert(error.message);
                                                    else {
                                                        setEditingCargo(null);
                                                        setCargoDesc("");
                                                        setCargoDescHT("");
                                                        n.value = ''; nHT.value = '';
                                                        fetchData();
                                                    }
                                                } else {
                                                    // Insert new
                                                    const { data: newRole, error } = await supabase.from('company_roles').insert({ 
                                                        name: n.value, 
                                                        name_ht: nHT.value || null,
                                                        description: cargoDesc || null,
                                                        description_ht: cargoDescHT || null,
                                                        company_id: companyId 
                                                    }).select(); 
                                                    
                                                    if(error) alert(error.message); 
                                                    else {
                                                        if (newRole && newRole[0]) {
                                                            // Auto assign to company
                                                            await supabase.from('role_company_assignments').insert({
                                                                role_id: newRole[0].id,
                                                                company_id: companyId,
                                                                is_visible: true
                                                            });
                                                        }
                                                        n.value = ''; nHT.value = '';
                                                        setCargoDesc("");
                                                        setCargoDescHT("");
                                                        setShowCreateCargo(false);
                                                        fetchData(); 
                                                    }
                                                }
                                            }} 
                                            className="flex-1 bg-brand text-black py-4 rounded-xl font-black text-xs uppercase hover:scale-[1.02] transition-all"
                                        >
                                            {editingCargo ? 'Guardar Cambios' : 'Agregar Cargo'}
                                        </button>
                                    </div>
                                </div>
                                </div>
                            )}

                            {/* Lista de cargos */}
                            <div className="max-h-72 overflow-auto space-y-2 pr-2 custom-scrollbar">
                                {cargos.filter(c => !c.company_id || c.company_id !== companyId).length > 0 && (
                                    <p className="text-[9px] font-black uppercase text-white/20 tracking-widest mb-1 mt-2">Cargos Globales / Administrador</p>
                                )}
                                {cargos.filter(c => !c.company_id || c.company_id !== companyId).map(c => {
                                    const assignment = c.role_company_assignments?.find((a: any) => a.company_id === companyId);
                                    // Si no hay tabla de asignaciones, mostramos todo por defecto
                                    const isVisible = assignment ? assignment.is_visible : (c.role_company_assignments === undefined ? true : false);
                                    const isInactive = c.active === false;
                                    
                                    return (
                                        <div key={c.id} className={`bg-white/5 p-4 rounded-xl border ${editingCargo?.id === c.id ? 'border-brand/50' : 'border-white/5'} ${!isVisible || isInactive ? 'opacity-50' : ''}`}>
                                            <div className="flex justify-between items-center mb-1">
                                                <div className="flex-1">
                                                    <span className={`font-bold text-sm ${isInactive ? 'text-white/40' : 'text-white'}`}>{c.name}</span>
                                                    <span className="text-[10px] text-white/20 ml-2 italic">{c.name_ht || 'Sin nombre Creole'}</span>
                                                    <span className="ml-2 text-[8px] bg-blue-500/20 text-blue-400 px-1.5 py-0.5 rounded font-bold uppercase">Global</span>
                                                    {isInactive && (
                                                        <span className="ml-2 text-[8px] bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded font-bold uppercase">Desactivado por Admin</span>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <button 
                                                        disabled={isInactive}
                                                        onClick={async () => {
                                                            if (assignment) {
                                                                await supabase.from('role_company_assignments').update({ is_visible: !isVisible }).eq('id', assignment.id);
                                                            } else {
                                                                await supabase.from('role_company_assignments').insert({ role_id: c.id, company_id: companyId, is_visible: true });
                                                            }
                                                            fetchData();
                                                        }}
                                                        className={`p-2 rounded-lg transition-all ${isInactive ? 'cursor-not-allowed opacity-20' : isVisible ? 'text-brand bg-brand/10' : 'text-white/20 bg-white/5'}`}
                                                        title={isInactive ? 'Inactivo por Administrador' : isVisible ? 'Ocultar' : 'Mostrar'}
                                                    >
                                                        {isVisible ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}

                                {cargos.filter(c => c.company_id === companyId).length > 0 && (
                                    <p className="text-[9px] font-black uppercase text-white/20 tracking-widest mb-1 mt-4">Cargos de esta Empresa</p>
                                )}
                                {cargos.filter(c => c.company_id === companyId).map(c => {
                                    const assignment = c.role_company_assignments?.find((a: any) => a.company_id === companyId);
                                    const isVisible = assignment ? assignment.is_visible : true; // Defaults to visible if owned
                                    const isInactive = c.active === false;

                                    return (
                                        <div key={c.id} className={`bg-white/5 p-4 rounded-xl border ${editingCargo?.id === c.id ? 'border-brand/50' : 'border-white/5'} ${!isVisible || isInactive ? 'opacity-50' : ''}`}>
                                            <div className="flex justify-between items-center mb-1">
                                                <div className="flex-1">
                                                    <span className={`font-bold text-sm ${isInactive ? 'text-white/40' : 'text-white'}`}>{c.name}</span>
                                                    <span className="text-[10px] text-white/20 ml-2 italic">{c.name_ht || 'Sin nombre Creole'}</span>
                                                    {isInactive && (
                                                        <span className="ml-2 text-[8px] bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded font-bold uppercase">Desactivado por Admin</span>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <button 
                                                        disabled={isInactive}
                                                        onClick={async () => {
                                                            if (assignment) {
                                                                await supabase.from('role_company_assignments').update({ is_visible: !isVisible }).eq('id', assignment.id);
                                                            } else {
                                                                await supabase.from('role_company_assignments').insert({ role_id: c.id, company_id: companyId, is_visible: false });
                                                            }
                                                            fetchData();
                                                        }}
                                                        className={`p-2 rounded-lg transition-all ${isInactive ? 'cursor-not-allowed opacity-20' : isVisible ? 'text-brand bg-brand/10' : 'text-white/20 bg-white/5'}`}
                                                        title={isInactive ? 'Inactivo por Administrador' : isVisible ? 'Ocultar' : 'Mostrar'}
                                                    >
                                                        {isVisible ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
                                                    </button>
                                                    <button 
                                                        disabled={isInactive}
                                                        onClick={() => {
                                                            setEditingCargo(c);
                                                            setCargoDesc(c.description || "");
                                                            setCargoDescHT(c.description_ht || "");
                                                            // Pre-fill the form inputs
                                                            setTimeout(() => {
                                                                const n = document.getElementById('newCargoName') as HTMLInputElement;
                                                                const nHT = document.getElementById('newCargoNameHT') as HTMLInputElement;
                                                                if(n) n.value = c.name || '';
                                                                if(nHT) nHT.value = c.name_ht || '';
                                                            }, 50);
                                                        }} 
                                                        className={`p-2 transition-colors ${isInactive ? 'cursor-not-allowed text-white/5' : 'text-white/20 hover:text-brand'}`} 
                                                        title="Editar"
                                                    >
                                                        <Pencil className="w-4 h-4" />
                                                    </button>
                                                    <button 
                                                        disabled={isInactive}
                                                        onClick={async () => { if(confirm('¿Eliminar cargo?')) { await supabase.from('company_roles').delete().eq('id', c.id); fetchData(); } }} 
                                                        className={`p-2 transition-colors ${isInactive ? 'cursor-not-allowed text-white/5' : 'text-white/20 hover:text-red-500'}`} 
                                                        title="Eliminar"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                            <button onClick={() => { setShowCargoManager(false); setEditingCargo(null); setCargoDesc(""); setCargoDescHT(""); }} className="w-full p-4 bg-white/5 rounded-xl uppercase font-black text-[10px] hover:bg-white/10 transition-colors">Cerrar</button>
                        </div>
                    </div>
                )}

                {showCompanyManager && isCompanyCollabVisible && (
                    <div className="fixed inset-0 z-[100] bg-black/80 flex items-center justify-center p-4">
                        <div className="glass p-10 w-full max-w-lg space-y-6">
                            <h3 className="text-xl font-black uppercase text-brand">Empresas Colaboradoras</h3>
                            <p className="text-[10px] text-white/40 font-bold uppercase tracking-widest">Gestionar listado de empresas sub-contratistas</p>
                            <div className="flex gap-2">
                                <input id="newCompanyName" placeholder="Nombre Empresa Colaboradora..." className="flex-1 bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-white" />
                                <button onClick={() => { 
                                    const i = document.getElementById('newCompanyName') as HTMLInputElement; 
                                    if(i.value) handleCreateCompany(i.value); 
                                    i.value = ""; 
                                }} className="bg-brand text-black px-6 rounded-xl font-black text-[10px] uppercase hover:scale-105 transition-all">Agregar</button>
                            </div>
                            <div className="max-h-60 overflow-auto space-y-2">
                                {allCompanies.map(c => (
                                    <div key={c.id} className="flex justify-between items-center bg-white/5 p-4 rounded-xl mb-2 text-sm gap-2">
                                        {editingCompanyListId === c.id ? (
                                            <input 
                                                value={editingCompanyListName} 
                                                onChange={(e) => setEditingCompanyListName(e.target.value)}
                                                className="bg-black/20 border border-brand/50 p-2 rounded text-white flex-1 text-sm focus:outline-none focus:border-brand"
                                                autoFocus
                                            />
                                        ) : (
                                            <span className="flex-1">{c.name_es}</span>
                                        )}
                                        
                                        <div className="flex gap-2">
                                            {editingCompanyListId === c.id ? (
                                                <>
                                                    <button onClick={async () => {
                                                        const { error } = await supabase
                                                            .from('companies_list')
                                                            .update({ name_es: editingCompanyListName.trim() })
                                                            .eq('id', c.id)
                                                            .eq('company_id', companyId);
                                                        if(error) alert("Error al actualizar: "+error.message);
                                                        setEditingCompanyListId(null);
                                                        fetchCompanyList();
                                                    }} className="text-brand hover:scale-110 transition-transform" title="Guardar"><CheckCircle2 className="w-4 h-4" /></button>
                                                    
                                                    <button onClick={() => {
                                                        setEditingCompanyListId(null);
                                                    }} className="text-white/40 hover:text-white transition-colors" title="Cancelar"><X className="w-4 h-4" /></button>
                                                </>
                                            ) : (
                                                <button onClick={() => {
                                                    setEditingCompanyListId(c.id);
                                                    setEditingCompanyListName(c.name_es);
                                                }} className="text-brand hover:text-white transition-colors" title="Editar"><Pencil className="w-4 h-4" /></button>
                                            )}

                                            <button onClick={async () => { 
                                                if(confirm('¿Eliminar esta empresa de la lista?')) {
                                                    await supabase.from('companies_list').delete().eq('id', c.id).eq('company_id', companyId); 
                                                    fetchCompanyList(); 
                                                }
                                            }} className="text-red-500 hover:text-red-400 transition-colors" title="Eliminar"><Trash2 className="w-4 h-4" /></button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <button onClick={() => setShowCompanyManager(false)} className="w-full p-4 bg-white/5 rounded-xl uppercase font-black text-[10px]">Cerrar</button>
                        </div>
                    </div>
                )}
            </div>

            {/* Configuración de Empresa */}
            {showConfig && (
                <CompanyConfig
                    companyId={companyId || ""}
                    onClose={() => {
                        setShowConfig(false);
                        fetchData(); // Refresh data after saving
                    }}
                />
            )}
        </div>
    );
}
