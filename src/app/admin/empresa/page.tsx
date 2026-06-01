"use client";

import { useState, useEffect, useRef } from "react";
import { motion } from "framer-motion";
import {
    Users, BookOpen, Search, Download, CheckCircle2,
    Shield, UserCog, X, Trash2, LogOut, UserPlus, Settings, Building2, Lock, Award as AwardIcon, Pencil, Eye, EyeOff
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
    const [courseCertFlags, setCourseCertFlags] = useState<Record<string, { participacion: boolean; aprobacion: boolean }>>({});
    const [diplomaConfig, setDiplomaConfig] = useState<any>(null);
    const [isGeneratingCert, setIsGeneratingCert] = useState(false);
    const certGenerationLock = useRef(false);
    const [sortConfig, setSortConfig] = useState<{ key: string; direction: 'asc' | 'desc' } | null>(null);
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
                // Verificar si es un Meta Admin (SuperAdmin o Editor)
                const email = session.user.email?.toLowerCase();
                const { role: roleToSet } = await resolveAdminRole(supabase, email, '/admin/empresa');

                if (roleToSet) {
                    setIsMasterAdmin(true);
                    setMasterRole(roleToSet);
                    sessionStorage.setItem('is_master_admin', 'true');
                    sessionStorage.setItem('master_role', roleToSet);
                    console.log(`Acceso Maestro Detectado: ${roleToSet}. Omitiendo cierre de sesión.`);
                    setIsAuthenticating(false);
                    return;
                }

                // Si no es un Meta Admin, cerramos la sesión para evitar conflictos (flujo original)
                console.warn("Sesión de Supabase común detectada. Cerrando sesión...");
                await supabase.auth.signOut({ scope: 'local' });
            } else if (sessionStorage.getItem('is_master_admin') === 'true' || localStorage.getItem('is_master_admin') === 'true') {
                // Si esperábamos ser master admin pero no hay sesión, algo falló o expiró
                // pero no bloqueamos por ahora para permitir el flujo normal de contraseña si falló el cross-login
            }
            setIsAuthenticating(false);
        };
        checkAuth();
        fetchData();
    }, [role, searchTerm, companyId]);

    const fetchData = async () => {
        if (!companyId) return;

        try {
            // Fetch students with enrollments details
            const { data: stData, error: stError } = await supabase
                .from('students')
                .select('*, company_roles(name), enrollments(course_id, status, best_score, completed_at, current_attempt, max_attempts)')
                .eq('client_id', companyId)
                .or(`first_name.ilike.%${searchTerm}%,last_name.ilike.%${searchTerm}%,rut.ilike.%${searchTerm}%`)
                .order('last_name');
            if (stError) console.error("Error fetching students:", stError);
            setStudents(stData || []);

            // 1. Fetch assignments for this company to know which global/external roles are allowed
            const { data: assignments, error: assignError } = await supabase
                .from('role_company_assignments')
                .select('role_id')
                .eq('company_id', companyId);
            
            const assignedRoleIds = (assignments || []).map(a => a.role_id);

            // 2. Fetch roles that are EITHER owned by the company OR assigned to it
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
                // Fallback to old behavior if table doesn't exist
                rolesQuery = rolesQuery.or(`company_id.eq.${companyId},company_id.is.null`);
            } else {
                // Strictly owned or assigned
                if (assignedRoleIds.length > 0) {
                    rolesQuery = rolesQuery.or(`company_id.eq.${companyId},id.in.(${assignedRoleIds.map(id => `"${id}"`).join(',')})`);
                } else {
                    rolesQuery = rolesQuery.eq('company_id', companyId);
                }
            }

            const { data: cgData } = await rolesQuery.order('name');
            setCargos(cgData || []);

            // Fetch ONLY assigned courses for this company (with cert flags)
            const [{ data: assignedData, error: assignedError }, { data: dipConfig }] = await Promise.all([
                supabase
                    .from('company_courses')
                    .select('course_id, cert_participacion_enabled, diploma_metaverso_enabled, start_date, validez_anios, courses(*)')
                    .eq('company_id', companyId),
                supabase
                    .from('diploma_config')
                    .select('*')
                    .eq('id', '00000000-0000-0000-0000-000000000001')
                    .single()
            ]);
            
            if (assignedError) {
                console.error("Error fetching assigned courses:", assignedError);
            }

            // Build cert flags map
            const flags: Record<string, { participacion: boolean; aprobacion: boolean }> = {};
            (assignedData || []).forEach((ad: any) => {
                flags[ad.course_id] = {
                    participacion: resolveParticipationFlag(ad),
                    aprobacion: ad.diploma_metaverso_enabled === true,
                };
            });
            setCourseCertFlags(flags);
            setDiplomaConfig(dipConfig || null);
            
            const filteredCourses = (assignedData || [])
                .map((ad: any) => ({
                    ...(ad.courses || {}),
                    company_course_validez_anios: ad.validez_anios ?? null,
                }))
                .filter((course: any) => !!course.id);
            setCourses(filteredCourses);
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
            gender: student.gender || null
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

        // Validación de RUT Chileno
        if (usingRut && documentValue) {
            if (!validateRut(documentValue)) {
                alert("El RUT ingresado no es válido. Por favor verifique el dígito verificador.");
                return;
            }
            // Formatear RUT antes de guardar
            normalizedDocument = formatRut(documentValue); 
        }

        const payload = { 
            first_name: newStudent.first_name,
            last_name: newStudent.last_name,
            rut: normalizedDocument, // Se guarda el RUT o Pasaporte aquí
            email: newStudent.email || null,
            password: newStudent.password || '123456',
            client_id: companyId, 
            role_id: newStudent.role_id,
            age: isAgeVisible && newStudent.age ? parseInt(newStudent.age, 10) : null,
            gender: isGenderVisible ? (newStudent.gender || null) : null,
            // Nota: Si la tabla 'students' no tiene columna 'doc_type', este dato se perderá,
            // pero la validación ya ocurrió. Si se requiere persistir el tipo, se debe agregar la columna.
            // Por ahora asumimos que solo se valida.
        };

        console.log("Intentando crear alumno con payload:", payload);

        const { error } = await supabase
            .from('students')
            .insert(payload);
            
        if (error) {
            console.error("Error creating student details:", JSON.stringify(error, null, 2));
            if (error.message.includes("foreign key")) {
                alert("Error Crítico de Base de Datos: El ID de tu empresa (" + companyId + ") no fue reconocido por el sistema de alumnos. Por favor contacta al administrador Master para verificar que tu ficha de empresa existe correctamente.");
            } else if (error.message.includes("policy")) {
                alert("Error de Permisos (RLS): No tienes permiso para crear trabajadores. Verifica la migración 007.");
            } else {
                alert("Error al crear alumno: " + error.message);
            }
        } else { 
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
                gender: ""
            });
            fetchData(); 
        }
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
            doc_type: validateRut(student.rut || '') ? 'RUT' : 'PASSPORT'
        });
    };

    const sortedStudents = [...students].sort((a, b) => {
        if (!sortConfig) return 0;
        const { key, direction } = sortConfig;
        let aVal = key === 'name' ? `${a.first_name} ${a.last_name}` : (key === 'cargo' ? (a.company_roles?.name || '') : '');
        let bVal = key === 'name' ? `${b.first_name} ${b.last_name}` : (key === 'cargo' ? (b.company_roles?.name || '') : '');
        if (aVal < bVal) return direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return direction === 'asc' ? 1 : -1;
        return 0;
    });

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

            // Fallback: si el navegador bloquea window.close(), forzamos salida inmediata
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
                                    <tr><th className="px-6 py-4">Colaborador</th><th className="px-6 py-4">Cargo</th><th className="px-6 py-4">Curso Asignado</th><th className="px-6 py-4">Estado</th><th className="px-6 py-4">Intentos</th><th className="px-6 py-4">Certificado</th><th className="px-6 py-4">Bloqueado</th><th className="px-6 py-4 text-right">Gestión</th></tr>
                                </thead>
                                <tbody className="divide-y divide-white/5">
                                    {sortedStudents.flatMap((st) => {
                                        // If student has no enrollments, show once
                                        const validEnrollments = (st.enrollments || []).filter((en: any) =>
                                            courses.some((c: any) => c.id === en.course_id)
                                        );

                                        if (!validEnrollments || validEnrollments.length === 0) {
                                            return [(
                                                <tr key={`${st.id}-no-course`} className="hover:bg-white/[0.02] text-sm font-medium">
                                                    <td className="px-6 py-4"><p className="font-bold">{st.first_name} {st.last_name}</p><p className="text-[10px] text-white/40 font-mono">{st.rut} • {companyName}</p></td>
                                                    <td className="px-6 py-4">{st.company_roles?.name || "Sin Cargo"}</td>
                                                    <td className="px-6 py-4"><span className="text-[8px] text-white/20 uppercase font-bold">Sin Cursos</span></td>
                                                    <td className="px-6 py-4">-</td>
                                                    <td className="px-6 py-4">-</td>
                                                    <td className="px-6 py-4">-</td>
                                                    <td className="px-6 py-4">
                                                        {st.is_locked ? (
                                                            <button onClick={async () => { await supabase.from('students').update({ is_locked: false, login_attempts: 0 }).eq('id', st.id); fetchData(); }} className="flex items-center gap-1 px-2 py-1 bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg text-[9px] font-black hover:bg-red-500/20 transition-all"><Lock className="w-3 h-3" /> Desbloquear</button>
                                                        ) : (
                                                            <span className="text-[9px] text-white/20 font-bold">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                                        <button onClick={() => openEditStudent(st)} className="p-2 rounded-xl bg-white/5 border border-white/10"><UserCog className="w-4 h-4" /></button>
                                                        <button onClick={() => handleDeleteStudent(st.id)} className="p-2 rounded-xl bg-white/5 border border-white/10 text-red-400"><Trash2 className="w-4 h-4" /></button>
                                                    </td>
                                                </tr>
                                            )];
                                        }

                                        // Show one row per enrollment (course)
                                        return validEnrollments.map((en: any, idx: number) => {
                                            const course = courses.find(c => c.id === en.course_id);
                                            const courseName = course?.name || "Curso Desconocido";

                                            const isCompleted = en.status === 'completed';
                                            const statusText = isCompleted ? 'Completado' : en.status === 'in_progress' ? 'En Progreso' : 'No Iniciado';
                                            const statusColor = isCompleted ? 'text-brand' : en.status === 'in_progress' ? 'text-yellow-400' : 'text-white/40';
                                            const attemptCount = en.current_attempt || 0;
                                            const maxAttempts = en.max_attempts || course?.max_attempts || 3;
                                            const attemptExhausted = attemptCount >= maxAttempts;

                                            return (
                                                <tr key={`${st.id}-${en.course_id}`} className="hover:bg-white/[0.02] text-sm font-medium">
                                                    <td className="px-6 py-4">
                                                        <p className="font-bold">{st.first_name} {st.last_name}</p>
                                                        <p className="text-[10px] text-white/40 font-mono">{st.rut} • {companyName}</p>
                                                    </td>
                                                    <td className="px-6 py-4">{st.company_roles?.name || "Sin Cargo"}</td>
                                                    <td className="px-6 py-4">
                                                        <span className="text-[8px] bg-brand/10 text-brand px-2 py-0.5 rounded-full font-black uppercase border border-brand/20">
                                                            {courseName}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <span className={`text-[10px] font-black uppercase ${statusColor}`}>
                                                            {statusText}
                                                            {isCompleted && en.best_score && ` (${en.best_score}%)`}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <span className={`text-[10px] font-black tabular-nums ${attemptExhausted ? 'text-red-400' : 'text-white/60'}`}>
                                                            {attemptCount}/{maxAttempts}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        {isCompleted ? (() => {
                                                            const certF = courseCertFlags[en.course_id] || { participacion: false, aprobacion: false };
                                                            const fetchStudentComp = async () => {
                                                                if (!companyId) return null;
                                                                const { data: comp } = await supabase.from('companies').select('*').eq('id', companyId).single();
                                                                const studentData = {
                                                                    digital_signature_url: st.digital_signature_url,
                                                                    age: st.age,
                                                                    gender: st.gender,
                                                                    client_id: st.client_id,
                                                                    job_position: st.job_position
                                                                };
                                                                let jobName = st.company_roles?.name || studentData?.job_position;
                                                                if (studentData?.job_position && !st.company_roles?.name) {
                                                                    const { data: jobInfo } = await supabase.from('job_positions').select('name_es').eq('code', studentData.job_position).single();
                                                                    if (jobInfo) jobName = jobInfo.name_es;
                                                                }
                                                                return { comp, studentData, jobName };
                                                            };
                                                            return (
                                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                                    {certF.participacion && (
                                                                        <button
                                                                            onClick={async () => {
                                                                                if (isGeneratingCert || certGenerationLock.current) return;
                                                                                const r = await fetchStudentComp();
                                                                                if (r?.comp) {
                                                                                    certGenerationLock.current = true;
                                                                                    setIsGeneratingCert(true);
                                                                                    setCertData({
                                                                                    studentName: `${st.first_name} ${st.last_name}`,
                                                                                    rut: st.rut,
                                                                                    courseName: courseName.toUpperCase(),
                                                                                    date: new Date(en.completed_at || Date.now()).toLocaleDateString(),
                                                                                    score: en.best_score ?? 100,
                                                                                    signatures: [
                                                                                        { url: r.comp.signature_url_1, name: r.comp.signature_name_1, role: r.comp.signature_role_1 },
                                                                                        { url: r.comp.signature_url_2, name: r.comp.signature_name_2, role: r.comp.signature_role_2 },
                                                                                        { url: r.comp.signature_url_3, name: r.comp.signature_name_3, role: r.comp.signature_role_3 }
                                                                                    ].filter(s => s.url || s.name),
                                                                                    studentSignature: normalizeStudentSignature(r.studentData?.digital_signature_url),
                                                                                    companyLogo: r.comp.logo_url,
                                                                                    companyName: r.comp.name,
                                                                                    jobPosition: r.jobName,
                                                                                    age: r.studentData?.age,
                                                                                    gender: r.studentData?.gender
                                                                                });
                                                                                }
                                                                            }}
                                                                            disabled={isGeneratingCert}
                                                                            className="p-2 rounded-lg bg-green-500/10 text-green-400 text-[10px] font-black flex items-center gap-1 border border-green-500/30 hover:bg-green-500 hover:text-black transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                                                            title="Certificado Participación"
                                                                        >
                                                                            <AwardIcon className="w-3 h-3" /> Participación
                                                                        </button>
                                                                    )}
                                                                    {certF.aprobacion && (
                                                                        <button
                                                                            onClick={async () => {
                                                                                if (!diplomaConfig) { alert('No hay configuración de diploma.'); return; }
                                                                                const r = await fetchStudentComp();
                                                                                if (!r?.comp) return;
                                                                                const fc = diplomaConfig.fields_config || {};
                                                                                await generateMetaversoCert({
                                                                                    studentName: `${st.first_name} ${st.last_name}`,
                                                                                    rut: st.rut,
                                                                                    companyName: r.comp.name,
                                                                                    companyRut: r.comp.rut || '',
                                                                                    companyId: r.studentData?.client_id || r.comp.id,
                                                                                    courseId: course?.id || en.course_id,
                                                                                    courseName: courseName.toUpperCase(),
                                                                                    courseCode: course?.code || '',
                                                                                    hours: course?.config?.hours,
                                                                                    date: en.completed_at
                                                                                        ? new Date(en.completed_at).toLocaleDateString('es-CL')
                                                                                        : new Date().toLocaleDateString('es-CL'),
                                                                                    expirationDate: calcExpirationDate(en.completed_at, course?.company_course_validez_anios),
                                                                                    backgroundUrl: diplomaConfig.background_url,
                                                                                    layoutConfig: fc.layout,
                                                                                    fieldsConfig: fc,
                                                                                });
                                                                            }}
                                                                            className="p-2 rounded-lg bg-purple-500/10 text-purple-400 text-[10px] font-black flex items-center gap-1 border border-purple-500/30 hover:bg-purple-500 hover:text-black transition-all"
                                                                            title="Certificado Aprobación"
                                                                        >
                                                                            <AwardIcon className="w-3 h-3" /> Aprobación
                                                                        </button>
                                                                    )}
                                                                    {!certF.participacion && !certF.aprobacion && (
                                                                        <span className="text-[9px] text-white/30 font-bold uppercase">Sin cert.</span>
                                                                    )}
                                                                </div>
                                                            );
                                                        })() : (
                                                            <div className="flex items-center gap-1 text-white/20">
                                                                <Lock className="w-3 h-3" />
                                                                <span className="text-[8px] font-bold uppercase">Pendiente</span>
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        {idx === 0 && st.is_locked ? (
                                                            <button
                                                                onClick={async () => { await supabase.from('students').update({ is_locked: false, login_attempts: 0 }).eq('id', st.id); fetchData(); }}
                                                                className="flex items-center gap-1 px-2 py-1 bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg text-[9px] font-black hover:bg-red-500/20 transition-all whitespace-nowrap"
                                                            ><Lock className="w-3 h-3" /> Desbloquear</button>
                                                        ) : idx === 0 ? (
                                                            <span className="text-[9px] text-white/20 font-bold">—</span>
                                                        ) : null}
                                                    </td>
                                                    <td className="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                                        {idx === 0 && (
                                                            <>
                                                                <button onClick={() => openEditStudent(st)} className="p-2 rounded-xl bg-white/5 border border-white/10"><UserCog className="w-4 h-4" /></button>
                                                                <button onClick={() => handleDeleteStudent(st.id)} className="p-2 rounded-xl bg-white/5 border border-white/10 text-red-400"><Trash2 className="w-4 h-4" /></button>
                                                            </>
                                                        )}
                                                        <button 
                                                            onClick={async () => {
                                                                try {
                                                                    if (!confirm(`¿Desvincular a ${st.first_name} del curso "${courseName}"? Se eliminarán todas sus estadísticas.`)) return;
                                                                    
                                                                    console.log('Iniciando desvinculación...', { student_id: st.id, course_id: en.course_id });
                                                                    
                                                                    // Delete enrollment and all related data
                                                                    const { data: enrollmentData, error: fetchError } = await supabase
                                                                        .from('enrollments')
                                                                        .select('id')
                                                                        .eq('student_id', st.id)
                                                                        .eq('course_id', en.course_id)
                                                                        .single();

                                                                    if (fetchError) {
                                                                        console.error('Error fetching enrollment:', fetchError);
                                                                        alert(`Error al obtener inscripción: ${fetchError.message}`);
                                                                        return;
                                                                    }

                                                                    if (!enrollmentData) {
                                                                        alert('No se encontró la inscripción');
                                                                        return;
                                                                    }

                                                                    console.log('Enrollment encontrado:', enrollmentData.id);

                                                                    // Delete course_progress
                                                                    const { error: progressError } = await supabase
                                                                        .from('course_progress')
                                                                        .delete()
                                                                        .eq('enrollment_id', enrollmentData.id);

                                                                    if (progressError) {
                                                                        console.error('Error deleting progress:', progressError);
                                                                    }

                                                                    // Delete activity_logs
                                                                    const { error: logsError } = await supabase
                                                                        .from('activity_logs')
                                                                        .delete()
                                                                        .eq('enrollment_id', enrollmentData.id);

                                                                    if (logsError) {
                                                                        console.error('Error deleting logs:', logsError);
                                                                    }

                                                                    // Delete enrollment
                                                                    const { error: deleteError } = await supabase
                                                                        .from('enrollments')
                                                                        .delete()
                                                                        .eq('id', enrollmentData.id);

                                                                    if (deleteError) {
                                                                        console.error('Error deleting enrollment:', deleteError);
                                                                        alert(`Error al eliminar inscripción: ${deleteError.message}`);
                                                                        return;
                                                                    }
                                                                    
                                                                    // Clear student signature if this was their only course
                                                                    const { data: remainingEnrollments } = await supabase
                                                                        .from('enrollments')
                                                                        .select('id')
                                                                        .eq('student_id', st.id);
                                                                    
                                                                    if (!remainingEnrollments || remainingEnrollments.length === 0) {
                                                                        await supabase
                                                                            .from('students')
                                                                            .update({ digital_signature_url: null })
                                                                            .eq('id', st.id);
                                                                    }
                                                                    
                                                                    alert('✅ Alumno desvinculado exitosamente');
                                                                    fetchData();
                                                                } catch (error: any) {
                                                                    console.error('Error inesperado:', error);
                                                                    alert(`Error inesperado: ${error.message}`);
                                                                }
                                                            }}
                                                            className="p-2 rounded-xl bg-white/5 border border-white/10 text-orange-400 hover:bg-orange-500/10"
                                                            title="Desvincular del curso"
                                                        >
                                                            <X className="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            );
                                        });
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {certData && <CertificateCanvas {...certData} onReady={(blob) => {
                    if (!certGenerationLock.current) return;
                    certGenerationLock.current = false;
                    const reader = new FileReader(); 
                    reader.readAsDataURL(blob);
                    reader.onloadend = () => { 
                        const base64data = reader.result as string;
                        const pdf = new jsPDF("p", "px", [1414, 2000]); // Portrait como el alumno
                        pdf.addImage(base64data, "PNG", 0, 0, 1414, 2000); 
                        pdf.save(`Certificado_${certData.rut}.pdf`); 
                        setCertData(null); 
                        setIsGeneratingCert(false);
                    };
                }} />}

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
