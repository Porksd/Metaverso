"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import {
    Users, BookOpen, Search, Download, CheckCircle2,
    Shield, UserCog, X, Trash2, LogOut, UserPlus, Settings, Building2, Lock, Award as AwardIcon
} from "lucide-react";
import { supabase } from "@/lib/supabase";
import CertificateCanvas from "@/components/CertificateCanvas";
import ManagerDashboard from "@/components/ManagerDashboard";
import EnhancedManagerDashboard from "@/components/EnhancedManagerDashboard";
import CompanyConfig from "@/components/CompanyConfig";
import { jsPDF } from "jspdf";
import { useRouter } from "next/navigation";

export default function EmpresaAdmin() {
    const router = useRouter();
    const [role, setRole] = useState<'manager' | 'trainer'>('manager');
    const [searchTerm, setSearchTerm] = useState("");
    const [students, setStudents] = useState<any[]>([]);
    const [cargos, setCargos] = useState<any[]>([]);
    const [courses, setCourses] = useState<any[]>([]);
    const [isEditing, setIsEditing] = useState<any>(null);
    const [showCargoManager, setShowCargoManager] = useState(false);
    const [showConfig, setShowConfig] = useState(false);
    const [isCreating, setIsCreating] = useState(false);
    const [certData, setCertData] = useState<any>(null);
    const [sortConfig, setSortConfig] = useState<{ key: string; direction: 'asc' | 'desc' } | null>(null);
    const [companyId, setCompanyId] = useState<string | null>(null);
    const [companyName, setCompanyName] = useState<string>("Cargando...");
    const [newStudent, setNewStudent] = useState<any>({ 
        first_name: "", 
        last_name: "", 
        rut: "", 
        email: "", 
        password: "", 
        company_name: "", 
        role_id: null 
    });

    useEffect(() => {
        const storedId = localStorage.getItem('empresa_id');
        const storedName = localStorage.getItem('empresa_name');
        
        if (!storedId) {
            window.location.href = "/admin/empresa/login";
            return;
        }

        setCompanyId(storedId);
        setCompanyName(storedName || "Mi Empresa");
    }, []);

    useEffect(() => {
        if (!companyId) return;
        
        const checkAuth = async () => {
            const { data: { session } } = await supabase.auth.getSession();
            // Si hay una sesión activa de Supabase, la cerramos para evitar conflictos de tokens (Error 401)
            // ya que el Portal Corporativo opera de forma dinámica sin Auth estándar de Supabase.
            if (session) {
                console.warn("Sesión de Supabase detectada en Portal Corporativo. Cerrando sesión para evitar errores 401...");
                await supabase.auth.signOut();
            }
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
                .select('*, company_roles(name), enrollments(course_id, status, best_score, completed_at)')
                .eq('client_id', companyId)
                .or(`first_name.ilike.%${searchTerm}%,last_name.ilike.%${searchTerm}%,rut.ilike.%${searchTerm}%`)
                .order('last_name');
            if (stError) console.error("Error fetching students:", stError);
            setStudents(stData || []);

            const { data: cgData, error: cgError } = await supabase
                .from('company_roles')
                .select('*')
                .eq('company_id', companyId);
            if (cgError) console.error("Error fetching cargos:", cgError);
            setCargos(cgData || []);

            // Fetch ONLY assigned courses for this company
            const { data: assignedData, error: assignedError } = await supabase
                .from('company_courses')
                .select('course_id, courses(*)')
                .eq('company_id', companyId);
            
            if (assignedError) {
                console.error("Error fetching assigned courses:", assignedError);
            }
            
            const filteredCourses = (assignedData || []).map((ad: any) => ad.courses).filter(Boolean);
            setCourses(filteredCourses);
        } catch (err) {
            console.error("Unexpected error in fetchData:", err);
        }
    };

    const handleUpdateStudent = async (student: any) => {
        const payload = {
            first_name: student.first_name,
            last_name: student.last_name,
            email: student.email,
            company_name: student.company_name, // Added company_name
            role_id: (student.role_id && student.role_id !== "") ? student.role_id : null,
            password: student.password
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
        
        if (!newStudent.first_name || !newStudent.last_name || !newStudent.rut) {
            alert("Por favor complete los campos obligatorios (Nombre, Apellido, RUT)");
            return;
        }

        const payload = { 
            first_name: newStudent.first_name,
            last_name: newStudent.last_name,
            rut: newStudent.rut,
            email: newStudent.email || null,
            password: newStudent.password || '123456',
            client_id: companyId, 
            company_name: newStudent.company_name || companyName,
            role_id: newStudent.role_id
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
            setNewStudent({ first_name: "", last_name: "", rut: "", email: "", password: "", company_name: "", role_id: null });
            fetchData(); 
        }
    };

    const handleDeleteStudent = async (id: string) => {
        if (!confirm("¿Eliminar trabajador?")) return;
        await supabase.from('students').delete().eq('id', id);
        fetchData();
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

    const handleLogout = async () => {
        const slug = localStorage.getItem('empresa_slug');
        await supabase.auth.signOut();
        window.sessionStorage.clear();
        window.localStorage.clear();
        
        if (slug) {
            router.push(`/admin/empresa/portal/${slug}`);
        } else {
            router.push("/admin/empresa/login");
        }
    };

    const [showCompanyManager, setShowCompanyManager] = useState(false);
    const [allCompanies, setAllCompanies] = useState<any[]>([]);

    const fetchCompanyList = async () => {
        try {
            const { data, error } = await supabase.from('companies_list').select('*').order('name_es');
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
    }, [showCompanyManager]);

    const handleCreateCompany = async (name: string) => {
        const code = name.toUpperCase().replace(/\s+/g, '_');
        const { error } = await supabase.from('companies_list').insert({ name_es: name, code });
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
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex bg-black/40 p-1.5 rounded-2xl border border-white/5 text-[10px] font-black uppercase">
                            <button onClick={() => setRole('manager')} className={`px-6 py-2.5 rounded-xl transition-all ${role === 'manager' ? 'bg-brand text-black shadow-lg' : 'text-white/40'}`}>Vista Gerente</button>
                            <button onClick={() => setRole('trainer')} className={`px-6 py-2.5 rounded-xl transition-all ${role === 'trainer' ? 'bg-brand text-black shadow-lg' : 'text-white/40'}`}>Vista Capacitador</button>
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
                    <EnhancedManagerDashboard companyName={companyName} companyId={companyId || undefined} />
                ) : (
                    <div className="space-y-6">
                        <div className="flex flex-col md:flex-row gap-4 justify-between items-center">
                            <div className="relative w-full md:max-w-md">
                                <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                                <input type="text" placeholder="Buscar..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm outline-none" />
                            </div>
                            <div className="flex gap-3">
                                <button onClick={() => setShowCompanyManager(true)} className="p-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2"><BookOpen className="w-4 h-4" /> Empresas</button>
                                <button onClick={() => setShowCargoManager(true)} className="p-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2"><Shield className="w-4 h-4" /> Cargos</button>
                                <button onClick={() => setIsCreating(true)} className="p-4 bg-brand text-black rounded-2xl text-[10px] font-black uppercase flex items-center gap-2"><UserPlus className="w-4 h-4" /> Nuevo</button>
                            </div>
                        </div>

                        <div className="glass overflow-hidden">
                            <table className="w-full text-left">
                                <thead className="bg-white/5 text-[10px] font-black uppercase text-white/40">
                                    <tr><th className="px-6 py-4">Colaborador</th><th className="px-6 py-4">Cargo</th><th className="px-6 py-4">Curso Asignado</th><th className="px-6 py-4">Estado</th><th className="px-6 py-4">Certificado</th><th className="px-6 py-4 text-right">Gestión</th></tr>
                                </thead>
                                <tbody className="divide-y divide-white/5">
                                    {sortedStudents.flatMap((st) => {
                                        // If student has no enrollments, show once
                                        if (!st.enrollments || st.enrollments.length === 0) {
                                            return [(
                                                <tr key={`${st.id}-no-course`} className="hover:bg-white/[0.02] text-sm font-medium">
                                                    <td className="px-6 py-4"><p className="font-bold">{st.first_name} {st.last_name}</p><p className="text-[10px] text-white/40 font-mono">{st.rut} • {st.company_name}</p></td>
                                                    <td className="px-6 py-4">{st.company_roles?.name || "Sin Cargo"}</td>
                                                    <td className="px-6 py-4"><span className="text-[8px] text-white/20 uppercase font-bold">Sin Cursos</span></td>
                                                    <td className="px-6 py-4">-</td>
                                                    <td className="px-6 py-4">-</td>
                                                    <td className="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                                        <button onClick={() => setIsEditing(st)} className="p-2 rounded-xl bg-white/5 border border-white/10"><UserCog className="w-4 h-4" /></button>
                                                        <button onClick={() => handleDeleteStudent(st.id)} className="p-2 rounded-xl bg-white/5 border border-white/10 text-red-400"><Trash2 className="w-4 h-4" /></button>
                                                    </td>
                                                </tr>
                                            )];
                                        }

                                        // Show one row per enrollment (course)
                                        return st.enrollments.map((en: any, idx: number) => {
                                            const course = courses.find(c => c.id === en.course_id);
                                            const courseName = course?.name || "Curso Desconocido";

                                            const isCompleted = en.status === 'completed';
                                            const statusText = isCompleted ? 'Completado' : en.status === 'in_progress' ? 'En Progreso' : 'No Iniciado';
                                            const statusColor = isCompleted ? 'text-brand' : en.status === 'in_progress' ? 'text-yellow-400' : 'text-white/40';

                                            return (
                                                <tr key={`${st.id}-${en.course_id}`} className="hover:bg-white/[0.02] text-sm font-medium">
                                                    <td className="px-6 py-4">
                                                        <p className="font-bold">{st.first_name} {st.last_name}</p>
                                                        <p className="text-[10px] text-white/40 font-mono">{st.rut} • {st.company_name}</p>
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
                                                        {isCompleted ? (
                                                            <button 
                                                                onClick={async () => {
                                                                    if (!companyId) return;
                                                                    const { data: comp } = await supabase.from('companies').select('*').eq('id', companyId).single();
                                                                    
                                                                    // Fetch student signature and details
                                                                    const { data: studentData } = await supabase
                                                                        .from('students')
                                                                        .select('digital_signature_url, age, gender, company_name, job_position')
                                                                        .eq('id', st.id)
                                                                        .single();

                                                                    // Obtener nombre del cargo
                                                                    let jobName = st.company_roles?.name || studentData?.job_position;
                                                                    if (studentData?.job_position && !st.company_roles?.name) {
                                                                        const { data: jobInfo } = await supabase
                                                                            .from('job_positions')
                                                                            .select('name_es')
                                                                            .eq('code', studentData.job_position)
                                                                            .single();
                                                                        if (jobInfo) jobName = jobInfo.name_es;
                                                                    }

                                                                    if (comp) setCertData({ 
                                                                        studentName: `${st.first_name} ${st.last_name}`, 
                                                                        rut: st.rut, 
                                                                        courseName: courseName.toUpperCase(), 
                                                                        date: new Date(en.completed_at || Date.now()).toLocaleDateString(), 
                                                                        signatures: [
                                                                            { url: comp.signature_url_1, name: comp.signature_name_1, role: comp.signature_role_1 }, 
                                                                            { url: comp.signature_url_2, name: comp.signature_name_2, role: comp.signature_role_2 }, 
                                                                            { url: comp.signature_url_3, name: comp.signature_name_3, role: comp.signature_role_3 }
                                                                        ].filter(s => s.url || s.name),
                                                                        studentSignature: studentData?.digital_signature_url,
                                                                        companyLogo: comp.logo_url,
                                                                        companyName: studentData?.company_name || comp.name,
                                                                        jobPosition: jobName,
                                                                        age: studentData?.age,
                                                                        gender: studentData?.gender
                                                                    });
                                                                }} 
                                                                className="p-2 rounded-lg bg-brand/10 text-brand text-[10px] font-black flex items-center gap-1 border border-brand/30 hover:bg-brand hover:text-black transition-all"
                                                                title="Descargar Certificado"
                                                            >
                                                                <AwardIcon className="w-3 h-3" /> Certificado
                                                            </button>
                                                        ) : (
                                                            <div className="flex items-center gap-1 text-white/20">
                                                                <Lock className="w-3 h-3" />
                                                                <span className="text-[8px] font-bold uppercase">Pendiente</span>
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                                        {idx === 0 && (
                                                            <>
                                                                <button onClick={() => setIsEditing(st)} className="p-2 rounded-xl bg-white/5 border border-white/10"><UserCog className="w-4 h-4" /></button>
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
                    const reader = new FileReader(); 
                    reader.readAsDataURL(blob);
                    reader.onloadend = () => { 
                        const base64data = reader.result as string;
                        const pdf = new jsPDF("p", "px", [1414, 2000]); // Portrait como el alumno
                        pdf.addImage(base64data, "PNG", 0, 0, 1414, 2000); 
                        pdf.save(`Certificado_${certData.rut}.pdf`); 
                        setCertData(null); 
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
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Nombre</label>
                                    <input value={isEditing.first_name} onChange={(e) => setIsEditing({ ...isEditing, first_name: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Apellido</label>
                                    <input value={isEditing.last_name} onChange={(e) => setIsEditing({ ...isEditing, last_name: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                                </div>
                            </div>

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
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Empresa</label>
                                    <select 
                                        value={isEditing.company_name || ""} 
                                        onChange={(e) => setIsEditing({ ...isEditing, company_name: e.target.value })}
                                        className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-white outline-none"
                                        style={{ colorScheme: 'dark' }}
                                    >
                                        <option value="" className="bg-neutral-900 text-white">Seleccionar Empresa</option>
                                        {allCompanies.map(c => <option key={c.id} value={c.name_es} className="bg-neutral-900 text-white">{c.name_es}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Cargo</label>
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
                            </div>

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
                                                        const { data: updatedEnrollment, error } = await supabase.from('enrollments').upsert({
                                                            student_id: isEditing.id,
                                                            course_id: course.id,
                                                            status: 'not_started',
                                                            best_score: 0,
                                                            quiz_score: 0,
                                                            scorm_score: 0,
                                                            current_module_index: 0,
                                                            progress: null, // Reset to null to trigger dynamic calc or fresh start
                                                            completed_at: null,
                                                            last_exam_passed: null,
                                                            last_exam_score: null,
                                                            survey_completed: false,
                                                            current_attempt: 0,
                                                            max_attempts: 3
                                                        }, { onConflict: 'student_id,course_id' }).select();

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

                            <div className="space-y-1">
                                <label className="text-[10px] font-black uppercase text-white/40 ml-2">RUT (Sin puntos con guión) *</label>
                                <input placeholder="12345678-9" value={newStudent.rut} onChange={(e) => setNewStudent({ ...newStudent, rut: e.target.value })} className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm" />
                            </div>

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
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Empresa Sub-contratista</label>
                                    <select 
                                        value={newStudent.company_name || ""} 
                                        onChange={(e) => setNewStudent({ ...newStudent, company_name: e.target.value })}
                                        className="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-white outline-none"
                                        style={{ colorScheme: 'dark' }}
                                    >
                                        <option value="" className="bg-neutral-900 text-white">Seleccionar Empresa</option>
                                        {allCompanies.map(c => <option key={c.id} value={c.name_es} className="bg-neutral-900 text-white">{c.name_es}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40 ml-2">Cargo</label>
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
                            </div>

                            <div className="flex gap-4">
                                <button onClick={() => setIsCreating(false)} className="flex-1 p-4 bg-white/5 rounded-xl uppercase font-black text-[10px]">Cancelar</button>
                                <button onClick={handleCreateStudent} className="flex-1 p-4 bg-brand text-black rounded-xl uppercase font-black text-[10px]">Crear Alumno</button>
                            </div>
                        </div>
                    </div>
                )}

                {showCargoManager && (
                    <div className="fixed inset-0 z-[100] bg-black/80 flex items-center justify-center p-4">
                        <div className="glass p-10 w-full max-w-2xl space-y-6">
                            <h3 className="text-xl font-black uppercase tracking-tight text-brand">Gestionar Cargos</h3>
                            
                            <div className="bg-white/5 p-6 rounded-2xl border border-white/5 space-y-4">
                                <p className="text-[10px] font-black uppercase text-white/30 tracking-widest">Añadir Nuevo Cargo</p>
                                <div className="grid grid-cols-2 gap-4">
                                    <input id="newCargoName" placeholder="Nombre (Español)..." className="bg-white/5 p-3 rounded-xl text-sm border border-white/10" />
                                    <input id="newCargoNameHT" placeholder="Nombre (Creole)..." className="bg-white/5 p-3 rounded-xl text-sm border border-white/10" />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <textarea id="newCargoDesc" placeholder="Descripción / Tip (Español)..." rows={2} className="bg-white/5 p-3 rounded-xl text-xs border border-white/10" />
                                    <textarea id="newCargoDescHT" placeholder="Descripción / Tip (Creole)..." rows={2} className="bg-white/5 p-3 rounded-xl text-xs border border-white/10" />
                                </div>
                                <button 
                                    onClick={async () => { 
                                        const n = document.getElementById('newCargoName') as HTMLInputElement; 
                                        const nHT = document.getElementById('newCargoNameHT') as HTMLInputElement; 
                                        const d = document.getElementById('newCargoDesc') as HTMLTextAreaElement; 
                                        const dHT = document.getElementById('newCargoDescHT') as HTMLTextAreaElement; 
                                        
                                        if(!n.value || !companyId) return; 
                                        
                                        const {error} = await supabase.from('company_roles').insert({ 
                                            name: n.value, 
                                            name_ht: nHT.value,
                                            description: d.value,
                                            description_ht: dHT.value,
                                            company_id: companyId 
                                        }); 
                                        
                                        if(error) alert(error.message); 
                                        else {
                                            n.value = ""; nHT.value = ""; d.value = ""; dHT.value = "";
                                            fetchData(); 
                                        }
                                    }} 
                                    className="w-full bg-brand text-black py-4 rounded-xl font-black text-xs uppercase hover:scale-[1.02] transition-all"
                                >
                                    Agregar Cargo
                                </button>
                            </div>

                            <div className="max-h-60 overflow-auto space-y-2 pr-2 custom-scrollbar">
                                {cargos.map(c => (
                                    <div key={c.id} className="bg-white/5 p-4 rounded-xl border border-white/5">
                                        <div className="flex justify-between items-start mb-1">
                                            <div>
                                                <span className="font-bold text-sm text-white">{c.name}</span>
                                                <span className="text-[10px] text-white/20 ml-2 italic">{c.name_ht || 'Sin nombre Creole'}</span>
                                            </div>
                                            <button onClick={async () => { if(confirm('¿Eliminar cargo?')) { await supabase.from('company_roles').delete().eq('id', c.id); fetchData(); } }} className="text-white/20 hover:text-red-500 transition-colors">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-4 mt-2 border-t border-white/5 pt-2">
                                            <div>
                                                <p className="text-[9px] font-black text-white/20 uppercase mb-1">Tip (ES)</p>
                                                <p className="text-[10px] text-white/40 italic">{c.description || 'N/A'}</p>
                                            </div>
                                            <div>
                                                <p className="text-[9px] font-black text-white/20 uppercase mb-1">Tip (HT)</p>
                                                <p className="text-[10px] text-white/40 italic">{c.description_ht || 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <button onClick={() => setShowCargoManager(false)} className="w-full p-4 bg-white/5 rounded-xl uppercase font-black text-[10px] hover:bg-white/10 transition-colors">Cerrar</button>
                        </div>
                    </div>
                )}

                {showCompanyManager && (
                    <div className="fixed inset-0 z-[100] bg-black/80 flex items-center justify-center p-4">
                        <div className="glass p-10 w-full max-w-lg space-y-6">
                            <h3 className="text-xl font-black uppercase">Gestionar Empresas</h3>
                            <div className="flex gap-2">
                                <input id="newCompanyName" placeholder="Nombre Empresa..." className="flex-1 bg-white/5 p-3 rounded-xl text-sm" />
                                <button onClick={() => { 
                                    const i = document.getElementById('newCompanyName') as HTMLInputElement; 
                                    if(i.value) handleCreateCompany(i.value); 
                                    i.value = ""; 
                                }} className="bg-brand text-black px-4 rounded-xl font-black text-[10px]">Agregar</button>
                            </div>
                            <div className="max-h-60 overflow-auto space-y-2">
                                {allCompanies.map(c => (
                                    <div key={c.id} className="flex justify-between items-center bg-white/5 p-4 rounded-xl mb-2 text-sm">
                                        <span>{c.name_es}</span>
                                        <button onClick={async () => { await supabase.from('companies_list').delete().eq('id', c.id); fetchCompanyList(); }} className="text-red-500"><Trash2 className="w-4 h-4" /></button>
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
