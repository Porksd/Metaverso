"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import {
    Building2, Users, BookOpen, Layers, Plus, Search,
    Settings, Save, Upload, Trash2, PieChart, ShieldCheck, X,
    ChevronUp, ChevronDown, ArrowUpDown, Filter, UserPlus, Globe,
    Copy, Check
} from "lucide-react";
import { supabase } from "@/lib/supabase";
import Image from "next/image";
import { useRouter } from "next/navigation";
import ContentUploader from "@/components/ContentUploader";

export default function MetaversoAdmin() {
    const router = useRouter();
    const [companies, setCompanies] = useState<any[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [editingCompany, setEditingCompany] = useState<any>(null);
    const [signatureModal, setSignatureModal] = useState<any>(null);
    const [assignCoursesModal, setAssignCoursesModal] = useState(false);
    const [courses, setCourses] = useState<any[]>([]);
    const [selectedCompanyId, setSelectedCompanyId] = useState<string | null>(null);
    const [selectedCourseIds, setSelectedCourseIds] = useState<string[]>([]);
    const [courseModes, setCourseModes] = useState<Record<string, string>>({});
    const [copiedId, setCopiedId] = useState<string | null>(null);

    const copyToClipboard = (text: string, id: string) => {
        navigator.clipboard.writeText(text);
        setCopiedId(id);
        setTimeout(() => setCopiedId(null), 2000);
    };

    const [view, setView] = useState<'companies' | 'participants'>('companies');
    const [participants, setParticipants] = useState<any[]>([]);
    const [roles, setRoles] = useState<any[]>([]);
    const [participantSearch, setParticipantSearch] = useState("");
    const [pPage, setPPage] = useState(1);
    const [pLimit, setPLimit] = useState(20);
    const [sortConfig, setSortConfig] = useState<{ key: string; direction: 'asc' | 'desc' }>({ key: 'full_name', direction: 'asc' });
    const [filters, setFilters] = useState({ company: '', course: '', status: '' });
    const [editingStudent, setEditingStudent] = useState<any>(null);
    const [isCreatingStudent, setIsCreatingStudent] = useState(false);

    useEffect(() => {
        fetchCompanies();
        fetchParticipants();
        fetchCourses();
        fetchRoles();
    }, []);

    const fetchRoles = async () => {
        const { data } = await supabase.from('company_roles').select('*').order('name');
        setRoles(data || []);
    };

    useEffect(() => {
        fetchParticipants();
    }, [pPage, pLimit]);

    const fetchParticipants = async () => {
        // Fetch students directly to ensure we see all participants
        const { data, error } = await supabase
            .from('students')
            .select(`
                *,
                companies:client_id(id, name),
                company_roles(id, name),
                enrollments(*, courses(name))
            `)
            .order('created_at', { ascending: false });
        
        if (!error) setParticipants(data || []);
    };

    const handleSaveStudent = async (student: any) => {
        const { id, enrollments, companies: companyRef, company_roles, ...data } = student;
        
        // Validación de cupos para nuevos alumnos o cambio de empresa
        if (!id && data.client_id) {
            const company = companies.find(c => c.id === data.client_id);
            if (company && company.used_quotas >= company.total_quotas) {
                alert(`Error: La empresa ${company.name} no tiene cupos disponibles (${company.used_quotas}/${company.total_quotas}).`);
                return;
            }
        }

        let error;
        if (id) {
            const { error: err } = await supabase.from('students').update(data).eq('id', id);
            error = err;
        } else {
            const { error: err } = await supabase.from('students').insert({ ...data, password: data.password || '123456' });
            error = err;
        }

        if (error) alert("Error: " + error.message);
        else {
            setEditingStudent(null);
            setIsCreatingStudent(false);
            fetchParticipants();
            fetchCompanies(); // Recargar empresas para actualizar contador de cupos
        }
    };

    const handleDeleteStudent = async (id: string) => {
        if (!confirm("¿Eliminar alumno permanentemente?")) return;
        const { error } = await supabase.from('students').delete().eq('id', id);
        if (error) alert(error.message);
        else {
            fetchParticipants();
            fetchCompanies(); // Refrescar cupos
        }
    };

    const sortedAndFilteredParticipants = participants
        .filter(p => {
            const matchesSearch = !participantSearch || 
                p.rut.includes(participantSearch) || 
                `${p.first_name} ${p.last_name}`.toLowerCase().includes(participantSearch.toLowerCase());
            
            const matchesCompany = !filters.company || (p.companies?.name === filters.company || p.company_name === filters.company);
            const matchesCourse = !filters.course || (p.enrollments?.some((e: any) => e.courses?.name === filters.course));
            const matchesStatus = !filters.status || (
                filters.status === 'completed' ? p.enrollments?.some((e: any) => e.status === 'completed' && (e.best_score === null || e.best_score >= 70)) :
                filters.status === 'failed' ? p.enrollments?.some((e: any) => e.status === 'failed' || (e.status === 'completed' && e.best_score !== null && e.best_score < 70)) :
                filters.status === 'pending' ? (
                    !p.enrollments?.some((e: any) => e.status === 'completed' || e.status === 'failed') ||
                    p.enrollments?.some((e: any) => e.status === 'not_started' || e.status === 'in_progress')
                ) :
                true
            );

            return matchesSearch && matchesCompany && matchesCourse && matchesStatus;
        })
        .sort((a, b) => {
            const { key, direction } = sortConfig;
            let aVal: any = a[key] || '';
            let bVal: any = b[key] || '';

            if (key === 'full_name') {
                aVal = `${a.first_name} ${a.last_name}`.toLowerCase();
                bVal = `${b.first_name} ${b.last_name}`.toLowerCase();
            } else if (key === 'company') {
                aVal = (a.companies?.name || a.company_name || '').toLowerCase();
                bVal = (b.companies?.name || b.company_name || '').toLowerCase();
            } else if (key === 'course') {
                aVal = (a.enrollments?.[0]?.courses?.name || '').toLowerCase();
                bVal = (b.enrollments?.[0]?.courses?.name || '').toLowerCase();
            }

            if (aVal < bVal) return direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return direction === 'asc' ? 1 : -1;
            return 0;
        });

    const paginatedParticipants = sortedAndFilteredParticipants.slice((pPage - 1) * pLimit, pPage * pLimit);

    const toggleSort = (key: string) => {
        setSortConfig(prev => ({
            key,
            direction: prev.key === key && prev.direction === 'asc' ? 'desc' : 'asc'
        }));
    };

    // Extract unique values for filters with useMemo for performance and reliability
    const uniqueCompanies = Array.from(new Set(participants.map(p => p.companies?.name || p.company_name).filter(Boolean))).sort();
    const uniqueCourses = Array.from(new Set(participants.flatMap(p => p.enrollments?.map((e: any) => e.courses?.name)).filter(Boolean))).sort();

    useEffect(() => {
        if (assignCoursesModal) fetchCourses();
    }, [assignCoursesModal]);

    useEffect(() => {
        if (selectedCompanyId) fetchCompanyAssignments(selectedCompanyId);
        else setSelectedCourseIds([]);
    }, [selectedCompanyId]);

    const fetchCourses = async () => {
        try {
            const { data, error } = await supabase.from('courses').select('*').order('name');
            if (error) throw error;
            setCourses(data || []);
        } catch (e: any) {
            console.error('Error fetching courses:', e.message || e);
            // Non-ordered fallback
            const { data } = await supabase.from('courses').select('*');
            setCourses(data || []);
        }
    };

    const fetchCompanyAssignments = async (companyId: string) => {
        const { data, error } = await supabase.from('company_courses').select('course_id, registration_mode').eq('company_id', companyId);
        if (error) {
            console.log('Error fetching company assignments:', error.message);
            setSelectedCourseIds([]);
            setCourseModes({});
        } else {
            setSelectedCourseIds((data || []).map((r: any) => r.course_id));
            const modes: Record<string, string> = {};
            data?.forEach((r: any) => {
                if (r.registration_mode) modes[r.course_id] = r.registration_mode;
            });
            setCourseModes(modes);
        }
    };

    const toggleCourseSelection = (courseId: string) => {
        setSelectedCourseIds(prev => prev.includes(courseId) ? prev.filter(id => id !== courseId) : [...prev, courseId]);
    };

    const saveCompanyCourses = async () => {
        if (!selectedCompanyId) return alert('Selecciona primero una empresa');

        // Validación de cupos proyectada
        const company = companies.find(c => c.id === selectedCompanyId);
        const studentCount = participants.filter(p => (p.companies?.id === selectedCompanyId || p.client_id === selectedCompanyId)).length;
        const estimatedEnrollments = selectedCourseIds.length * studentCount;

        if (company && estimatedEnrollments > company.total_quotas) {
            alert(`Error: Se proyectan ${estimatedEnrollments} matrículas (${selectedCourseIds.length} cursos x ${studentCount} alumnos), pero la empresa solo dispone de ${company.total_quotas} cupos totales.`);
            return;
        }

        // Delete existing assignments
        const { error: delErr } = await supabase.from('company_courses').delete().eq('company_id', selectedCompanyId);
        if (delErr) {
            alert('Error eliminando asignaciones previas: ' + delErr.message);
            return;
        }
        if (selectedCourseIds.length === 0) {
            alert('Asignaciones guardadas (sin cursos)');
            setAssignCoursesModal(false);
            fetchCompanies(); // Refrescar para estar seguros
            return;
        }
        const rows = selectedCourseIds.map(id => ({ 
            company_id: selectedCompanyId, 
            course_id: id,
            registration_mode: courseModes[id] || 'open'
        }));
        const { error: insErr } = await supabase.from('company_courses').insert(rows);
        if (insErr) {
            alert('Error guardando asignaciones: ' + insErr.message);
        } else {
            alert('Asignaciones guardadas correctamente');
            setAssignCoursesModal(false);
            fetchCompanies(); // Refrescar contadores
        }
    };

    const handleDeleteCompany = async (company: any) => {
        if (!company || !company.id) return;
        const companyId = company.id;

        try {
            // 1. Comprobar asignaciones de cursos
            const { data: assignedCourses, error: acErr } = await supabase
                .from('company_courses')
                .select('course_id')
                .eq('company_id', companyId)
                .limit(1);
            
            if (acErr) throw acErr;
            if (assignedCourses && assignedCourses.length > 0) {
                return alert('No se puede eliminar la empresa: primero elimine los cursos asignados para esta empresa.');
            }

            // 2. Comprobar estudiantes asociados (usando client_id que es la columna correcta)
            const { data: students, error: sErr } = await supabase
                .from('students')
                .select('id')
                .eq('client_id', companyId)
                .limit(1);
            
            if (sErr && sErr.code !== 'PGRST116') { // Ignorar error si la columna no existe (probar fallback)
                console.warn('Error comprobando estudiantes:', sErr);
            }

            if (students && students.length > 0) {
                return alert('No se puede eliminar la empresa: tiene alumnos/empleados asociados en la plataforma.');
            }

            // 3. Confirmar
            if (!confirm(`¿Confirmar eliminación definitiva de ${company.name}? Esta acción no se puede deshacer.`)) return;

            // 4. Ejecutar eliminación
            const { error: delErr, count } = await supabase
                .from('companies')
                .delete({ count: 'exact' })
                .eq('id', companyId);

            if (delErr) {
                throw delErr;
            }

            if (count === 0) {
                alert('⚠️ No se eliminó ninguna fila. Esto puede deberse a permisos de base de datos (RLS) o a que el registro ya no existe.');
            } else {
                alert('✅ Empresa eliminada correctamente');
                fetchCompanies();
            }
            
        } catch (err: any) {
            console.error('Error en handleDeleteCompany:', err);
            alert('Error al intentar eliminar: ' + (err.message || 'Error desconocido'));
        }
    };

    const fetchCompanies = async () => {
        setIsLoading(true);
        try {
            // 1. Obtener empresas básicas
            const { data: companiesData, error: compError } = await supabase
                .from('companies')
                .select('*, company_courses(course_id)')
                .order('name');
            
            if (compError) throw compError;

            // 2. Obtener conteo real de matrículas (enrollments) agrupado por empresa (vía student.client_id)
            const { data: enrollmentsData, error: enrollError } = await supabase
                .from('enrollments')
                .select('id, students!inner(client_id)');
            
            if (enrollError) throw enrollError;

            // 3. Procesar conteo
            const enrollmentCounts: Record<string, number> = {};
            enrollmentsData?.forEach((en: any) => {
                const clientId = en.students?.client_id;
                if (clientId) {
                    enrollmentCounts[clientId] = (enrollmentCounts[clientId] || 0) + 1;
                }
            });

            // 4. Integrar datos
            const updatedCompanies = (companiesData || []).map(c => ({
                ...c,
                used_quotas: enrollmentCounts[c.id] || 0
            }));

            setCompanies(updatedCompanies);
        } catch (err: any) {
            console.error('Error fetching dynamic quotas:', err.message);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSaveCompany = async () => {
        if (!editingCompany) return;
        const { id, ...data } = editingCompany;
        let result: any;
        let error: any;
        
        const companyPayload = {
            name: data.name,
            tax_id: data.tax_id,
            address: data.address,
            phone: data.phone,
            email: data.email,
            slug: data.slug,
            welcome_title: data.welcome_title,
            welcome_message: data.welcome_message,
            total_quotas: data.total_quotas || 0, // RESTAURADO
            password: data.password,
            is_active: data.is_active,
            logo_url: data.logo_url,
            primary_color: data.primary_color,
            secondary_color: data.secondary_color
        };

        if (!id) {
            // Insert new company, return inserted row
            result = await supabase
                .from('companies')
                .insert(companyPayload)
                .select();
            error = result.error;
        } else {
            // Update existing and return updated row
            result = await supabase
                .from('companies')
                .update(companyPayload)
                .eq('id', id)
                .select();
            error = result.error;
        }

        console.log('handleSaveCompany result:', result);

        if (error) {
            alert('Error guardando empresa: ' + error.message);
        } else {
            setEditingCompany(null);
            fetchCompanies();
        }
    };

    const handleSaveSignatures = async () => {
        if (!signatureModal) return;
        const { id, ...data } = signatureModal;
        const { error } = await supabase
            .from('companies')
            .update({
                signature_name_1: data.signature_name_1,
                signature_role_1: data.signature_role_1,
                signature_url_1: data.signature_url_1,
                signature_name_2: data.signature_name_2,
                signature_role_2: data.signature_role_2,
                signature_url_2: data.signature_url_2,
                signature_name_3: data.signature_name_3,
                signature_role_3: data.signature_role_3,
                signature_url_3: data.signature_url_3,
            })
            .eq('id', id);

        if (error) alert(error.message);
        else {
            setSignatureModal(null);
            fetchCompanies();
        }
    };

    const handleLogout = async () => {
        try {
            await supabase.auth.signOut();
            window.sessionStorage.clear();
            window.localStorage.clear();
            router.push("/admin");
        } catch (e) {
            window.location.href = "/admin";
        }
    };

    return (
        <div className="min-h-screen bg-transparent text-white p-4 md:p-10 font-sans">
            <div className="max-w-7xl mx-auto space-y-10">

                {/* Header Master */}
                <header className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-brand text-xs font-black uppercase tracking-widest bg-brand/10 w-fit px-3 py-1 rounded-full border border-brand/20">
                            <ShieldCheck className="w-3 h-3" /> Area de Control Maestro
                        </div>
                        <h1 className="text-4xl md:text-5xl font-black tracking-tight">Metaverso <span className="text-brand">Admin</span></h1>
                        <p className="text-white/40 font-medium">Gestión global de empresas, cupos y ecosistema educativo.</p>
                    </div>

                    <div className="flex gap-4">
                        <button
                            onClick={handleLogout}
                            className="bg-white/5 text-white/40 px-6 py-4 rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-red-500/10 hover:text-red-400 transition-all border border-white/5"
                        >
                            Cerrar Sesión
                        </button>
                        <button
                            onClick={() => setEditingCompany({ name: "", tax_id: null, is_active: true, total_quotas: 0, primary_color: "#AEFF00", secondary_color: "#000000" })}
                            className="bg-brand text-black px-8 py-4 rounded-xl font-black uppercase tracking-widest text-[10px] hover:scale-105 active:scale-95 transition-all shadow-xl shadow-brand/20 flex items-center gap-2"
                        >
                            <Plus className="w-4 h-4" /> Registrar Nueva Empresa
                        </button>
                    </div>
                </header>

                <div className="flex border-b border-white/10 gap-8">
                    <button 
                        onClick={() => setView('companies')} 
                        className={`pb-4 text-xs font-black uppercase tracking-widest transition-all border-b-2 ${view === 'companies' ? 'text-brand border-brand' : 'text-white/40 border-transparent hover:text-white'}`}
                    >
                        Empresas / Clientes
                    </button>
                    <button 
                        onClick={() => setView('participants')} 
                        className={`pb-4 text-xs font-black uppercase tracking-widest transition-all border-b-2 ${view === 'participants' ? 'text-brand border-brand' : 'text-white/40 border-transparent hover:text-white'}`}
                    >
                        Gestión de Participantes
                    </button>
                </div>

                {view === 'companies' ? (
                    <>
                {/* Global Statistics */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {[
                        { label: "Empresas Activas", value: companies.filter(c => c.is_active).length, icon: Building2, color: "brand" },
                        { label: "Matrículas Totales", value: companies.reduce((acc, c) => acc + (c.used_quotas || 0), 0).toLocaleString(), icon: Layers, color: "brand" },
                        { label: "Participantes Registrados", value: participants.length.toLocaleString(), icon: Users, color: "brand" },
                        { label: "Cursos en Catálogo", value: courses.length, icon: BookOpen, color: "brand" },
                    ].map((stat, i) => (
                        <motion.div key={i} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.1 }}
                            className="glass p-6 flex flex-col gap-4 group glass-hover">
                            <div className="flex items-center justify-between">
                                <span className="text-white/40 text-[10px] font-black uppercase tracking-widest">{stat.label}</span>
                                <stat.icon className="w-5 h-5 text-brand/50 group-hover:text-brand transition-colors" />
                            </div>
                            <p className="text-3xl font-black tracking-tighter">{stat.value}</p>
                        </motion.div>
                    ))}
                </div>

                {/* Enterprise Management Table */}
                <section className="space-y-6">
                    <h3 className="text-xl font-black flex items-center gap-3">
                        <div className="w-2 h-6 bg-brand rounded-full" />
                        Listado de Clientes Corporativos
                    </h3>

                    <div className="glass overflow-hidden border-white/5">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-white/5 text-[10px] font-black uppercase tracking-widest text-white/40 border-b border-white/10">
                                    <th className="px-6 py-4">Empresa</th>
                                    <th className="px-6 py-4">RUT / Estado</th>
                                    <th className="px-6 py-4">Cursos Asignados</th>
                                    <th className="px-6 py-4">Cupos</th>
                                    <th className="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {companies.map((company) => (
                                    <tr key={company.id} className="hover:bg-white/[0.02] transition-all group">
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center border border-white/10 overflow-hidden">
                                                    {company.logo_url ? <img src={company.logo_url} className="w-full h-full object-contain p-1" /> : <Building2 className="w-5 h-5 text-brand" />}
                                                </div>
                                                <div className="flex flex-col">
                                                    <span className="font-bold">{company.name}</span>
                                                    <span className="text-[10px] text-white/20 uppercase font-black">{company.email}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="flex flex-col gap-1">
                                                <span className="font-mono text-xs text-white/60">{company.tax_id}</span>
                                                <span className={`text-[8px] font-black uppercase px-2 py-0.5 rounded-full w-fit ${company.is_active ? 'bg-brand/10 text-brand border border-brand/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'}`}>
                                                    {company.is_active ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="flex flex-wrap gap-1 max-w-[200px]">
                                                {company.company_courses && company.company_courses.length > 0 ? (
                                                    company.company_courses.map((cc: any, idx: number) => {
                                                        const course = courses.find(c => c.id === cc.course_id);
                                                        return (
                                                            <span key={idx} className="text-[8px] bg-white/10 px-2 py-0.5 rounded-md border border-white/10">
                                                                {course?.name || course?.title || 'Cargando...'}
                                                            </span>
                                                        );
                                                    })
                                                ) : (
                                                    <span className="text-[8px] text-white/20 italic">Sin cursos asignados</span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-2 text-xs font-bold text-white/40">
                                                <Layers className="w-3 h-3 text-brand/40" />
                                                <span className="text-white">{company.used_quotas || 0}</span>
                                                <span className="opacity-30">/</span>
                                                <span className="text-brand font-black">{company.total_quotas || 0}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5 text-right space-x-2 whitespace-nowrap">
                                            {company.slug && (
                                                <>
                                                    <button 
                                                        onClick={() => copyToClipboard(`${window.location.origin}/portal/${company.slug}`, `${company.id}_portal`)}
                                                        className={`p-2.5 rounded-xl transition-all border ${copiedId === `${company.id}_portal` ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-white/5 text-white/40 border-white/10 hover:bg-brand/10 hover:text-brand hover:border-brand/20'}`}
                                                        title="Copiar Portal Alumnos"
                                                    >
                                                        {copiedId === `${company.id}_portal` ? <Check className="w-4 h-4" /> : <Globe className="w-4 h-4" />}
                                                    </button>
                                                    <button 
                                                        onClick={() => copyToClipboard(`${window.location.origin}/admin/empresa/portal/${company.slug}`, `${company.id}_admin`)}
                                                        className={`p-2.5 rounded-xl transition-all border ${copiedId === `${company.id}_admin` ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-white/5 text-white/40 border-white/10 hover:bg-white/10 hover:text-white'}`}
                                                        title="Copiar Panel Admin"
                                                    >
                                                        {copiedId === `${company.id}_admin` ? <Check className="w-4 h-4" /> : <ShieldCheck className="w-4 h-4" />}
                                                    </button>
                                                </>
                                            )}
                                            <button onClick={() => setEditingCompany(company)} className="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all border border-white/10" title="Configurar Empresa">
                                                <Settings className="w-4 h-4" />
                                            </button>
                                            <button onClick={() => setSignatureModal(company)} className="p-2.5 rounded-xl bg-white/5 hover:bg-brand/10 text-white/40 hover:text-brand transition-all border border-white/10" title="Firmas y Certificados">
                                                <Save className="w-4 h-4" />
                                            </button>
                                                                <button onClick={() => handleDeleteCompany(company)} className="p-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 transition-all border border-red-500/20" title="Eliminar Empresa">
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
                </>
                ) : (
                    <section className="space-y-6">
                        <div className="flex flex-col md:flex-row justify-between items-center gap-4">
                            <h3 className="text-xl font-black flex items-center gap-3 w-full">
                                <div className="w-2 h-6 bg-brand rounded-full" />
                                Gestión Global de Participantes
                            </h3>
                            <div className="flex gap-3 w-full md:w-auto">
                                <div className="relative flex-1 md:w-72">
                                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                                    <input 
                                        type="text" 
                                        placeholder="Buscar por RUT o Nombre..." 
                                        className="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-xs outline-none focus:border-brand/40"
                                        value={participantSearch}
                                        onChange={(e) => setParticipantSearch(e.target.value)}
                                    />
                                </div>
                                <button 
                                    onClick={() => {
                                        setEditingStudent({ first_name: '', last_name: '', rut: '', email: '', client_id: '' });
                                        setIsCreatingStudent(true);
                                    }}
                                    className="bg-brand text-black px-4 py-2.5 rounded-xl font-black uppercase text-[10px] flex items-center gap-2 hover:scale-105 transition-all"
                                >
                                    <UserPlus className="w-4 h-4" /> Agregar Alumno
                                </button>
                            </div>
                        </div>

                        <div className="glass overflow-hidden border-white/5 overflow-x-auto">
                            <table className="w-full text-left border-collapse min-w-[1100px]">
                                <thead>
                                    <tr className="bg-white/5 text-[10px] font-black uppercase tracking-widest text-white/40 border-b border-white/10">
                                        <th className="px-6 py-5 cursor-pointer hover:text-brand transition-colors" onClick={() => toggleSort('rut')}>
                                            <div className="flex items-center gap-2">
                                                RUT {sortConfig.key === 'rut' && (sortConfig.direction === 'asc' ? <ChevronUp className="w-3 h-3"/> : <ChevronDown className="w-3 h-3"/>)}
                                            </div>
                                        </th>
                                        <th className="px-6 py-5 cursor-pointer hover:text-brand transition-colors" onClick={() => toggleSort('full_name')}>
                                            <div className="flex items-center gap-2">
                                                Participante {sortConfig.key === 'full_name' && (sortConfig.direction === 'asc' ? <ChevronUp className="w-3 h-3"/> : <ChevronDown className="w-3 h-3"/>)}
                                            </div>
                                        </th>
                                        <th className="px-6 py-5">Cargo / Función</th>
                                        <th className="px-6 py-5">
                                            <div className="flex flex-col gap-2">
                                                <div className="flex items-center gap-2 cursor-pointer hover:text-brand" onClick={() => toggleSort('course')}>
                                                    Capacitación {sortConfig.key === 'course' && (sortConfig.direction === 'asc' ? <ChevronUp className="w-3 h-3"/> : <ChevronDown className="w-3 h-3"/>)}
                                                </div>
                                                <select 
                                                    className="bg-black/60 border border-white/10 rounded-lg px-2 py-1.5 text-[10px] outline-none w-full font-bold text-white focus:border-brand/50 transition-all cursor-pointer"
                                                    value={filters.course}
                                                    onChange={(e) => setFilters({...filters, course: e.target.value})}
                                                >
                                                    <option value="" className="bg-zinc-900 italic">-- Curso --</option>
                                                    {uniqueCourses.map(c => (
                                                        <option key={c as string} value={c as string} className="bg-zinc-900">{c as string}</option>
                                                    ))}
                                                </select>
                                            </div>
                                        </th>
                                        <th className="px-6 py-5">
                                            <div className="flex flex-col gap-2">
                                                <span className="flex items-center gap-2 italic opacity-60"><Filter className="w-2.5 h-2.5"/> Evaluación</span>
                                                <select 
                                                    className="bg-black/60 border border-white/10 rounded-lg px-2 py-1.5 text-[10px] outline-none w-full font-bold text-white focus:border-brand/50 transition-all cursor-pointer"
                                                    value={filters.status}
                                                    onChange={(e) => setFilters({...filters, status: e.target.value})}
                                                >
                                                    <option value="" className="bg-zinc-900 italic">-- Estado --</option>
                                                    <option value="completed" className="bg-zinc-900">Aprobado / Certificado</option>
                                                    <option value="failed" className="bg-zinc-900">Reprobado / No Aprobado</option>
                                                    <option value="pending" className="bg-zinc-900">Pendiente / En Curso</option>
                                                </select>
                                            </div>
                                        </th>
                                        <th className="px-6 py-5 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/5">
                                    {paginatedParticipants.map((p) => {
                                        const isAprobado = p.enrollments?.some((e: any) => e.status === 'completed');
                                        const courseNames = p.enrollments?.map((e: any) => e.courses?.name).filter(Boolean).join(', ') || 'Sin Matrícula';

                                        return (
                                            <tr key={p.id} className="hover:bg-white/[0.02] transition-all text-[11px] group">
                                                <td className="px-6 py-4 font-mono text-white/40">{p.rut}</td>
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-bold capitalize text-white hover:text-brand transition-colors cursor-default">{p.first_name} {p.last_name}</span>
                                                        <span className="text-[9px] text-white/20 font-mono tracking-tighter">{p.email || 'no-email@system.cl'}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-bold text-white/80">{p.company_roles?.name || 'Sin Cargo'}</span>
                                                        <span className="text-[9px] text-white/30 uppercase">{p.company_name || 'Particular'}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 max-w-[180px] truncate" title={courseNames}>
                                                    <div className="flex items-center gap-1.5">
                                                        <div className="w-1 h-1 bg-brand rounded-full shrink-0"/>
                                                        {courseNames}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {(() => {
                                                        const isAprobado = p.enrollments?.some((e: any) => e.status === 'completed' && (e.best_score === null || e.best_score >= 70));
                                                        const isReprobado = p.enrollments?.some((e: any) => e.status === 'failed' || (e.status === 'completed' && e.best_score !== null && e.best_score < 70));
                                                        
                                                        if (isAprobado) return <span className="px-2.5 py-1 rounded-full font-black uppercase text-[8px] border bg-brand/10 text-brand border-brand/20">✓ Certificado</span>;
                                                        if (isReprobado) return <span className="px-2.5 py-1 rounded-full font-black uppercase text-[8px] border bg-red-500/10 text-red-400 border-red-500/20">✕ No Aprobado</span>;
                                                        return <span className="px-2.5 py-1 rounded-full font-black uppercase text-[8px] border bg-white/5 text-white/40 border-white/10">⋯ En Proceso</span>;
                                                    })()}
                                                </td>
                                                <td className="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                                                    <button onClick={() => setEditingStudent(p)} className="p-2.5 rounded-xl bg-white/5 hover:bg-brand/10 text-white/20 hover:text-brand border border-white/10 transition-all opacity-0 group-hover:opacity-100" title="Editar Perfil"><Settings className="w-3.5 h-3.5" /></button>
                                                    <button onClick={() => handleDeleteStudent(p.id)} className="p-2.5 rounded-xl bg-white/5 hover:bg-red-500/10 text-white/20 hover:text-red-400 border border-white/10 transition-all opacity-0 group-hover:opacity-100" title="Eliminar Registro"><Trash2 className="w-3.5 h-3.5" /></button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                            {/* Pagination bar */}
                            <div className="p-6 bg-white/[0.02] border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4 text-[10px] font-black uppercase text-white/40">
                                <div className="flex items-center gap-6">
                                    <div className="flex items-center gap-2">
                                        <span>Mostrar:</span>
                                        <select 
                                            value={pLimit} 
                                            onChange={(e) => { setPLimit(Number(e.target.value)); setPPage(1); }}
                                            className="bg-black/40 border border-white/10 rounded px-2 py-1 text-white outline-none cursor-pointer hover:border-brand/40 transition-all"
                                        >
                                            <option value={20}>20</option>
                                            <option value={50}>50</option>
                                            <option value={100}>100</option>
                                        </select>
                                    </div>
                                    <div className="h-4 w-px bg-white/10"/>
                                    <div>Visualizando {(pPage-1)*pLimit+1} - {Math.min(pPage*pLimit, sortedAndFilteredParticipants.length)} de <span className="text-brand">{sortedAndFilteredParticipants.length}</span> registros filtrados</div>
                                </div>
                                
                                <div className="flex items-center gap-3">
                                    <button 
                                        onClick={() => setPPage(Math.max(1, pPage - 1))} 
                                        disabled={pPage === 1} 
                                        className="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-xl hover:bg-white/10 hover:text-white disabled:opacity-20 disabled:hover:bg-white/5 transition-all"
                                    >
                                        Anterior
                                    </button>
                                    <div className="flex items-center gap-1.5 px-3 py-2 bg-brand/5 border border-brand/20 rounded-xl text-brand font-black">
                                        Pág. {pPage}
                                    </div>
                                    <button 
                                        onClick={() => setPPage(pPage + 1)} 
                                        className="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-xl hover:bg-white/10 hover:text-white disabled:opacity-20 disabled:hover:bg-white/5 transition-all" 
                                        disabled={pPage*pLimit >= sortedAndFilteredParticipants.length}
                                    >
                                        Siguiente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                )}

                {/* Modal: Edición de Empresa */}
                {editingCompany && (
                    <div className="fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                        <motion.div initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="glass p-10 w-full max-w-2xl space-y-8 border-brand/20 overflow-y-auto max-h-[90vh]">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="text-2xl font-black tracking-tighter text-brand">/config_empresa</h3>
                                    <p className="text-white/40 text-xs font-bold uppercase tracking-widest">Datos Generales y Acceso</p>
                                </div>
                                <button onClick={() => setEditingCompany(null)} className="p-2 rounded-lg bg-white/5 hover:bg-white/10"><X className="w-5 h-5 text-white/40" /></button>
                            </div>

                            <div className="grid grid-cols-2 gap-6">
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Nombre Fantasía</label>
                                    <input value={editingCompany.name} onChange={(e) => setEditingCompany({ ...editingCompany, name: e.target.value })} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">RUT Empresa</label>
                                    <input value={editingCompany.tax_id} onChange={(e) => setEditingCompany({ ...editingCompany, tax_id: e.target.value })} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                </div>
                                <div className="space-y-1.5 col-span-2">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Dirección Casa Matriz</label>
                                    <input value={editingCompany.address || ""} onChange={(e) => setEditingCompany({ ...editingCompany, address: e.target.value })} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Fono Contacto</label>
                                    <input value={editingCompany.phone || ""} onChange={(e) => setEditingCompany({ ...editingCompany, phone: e.target.value })} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Cupos Totales Contratados</label>
                                    <input 
                                        type="number" 
                                        value={editingCompany.total_quotas || 0} 
                                        onChange={(e) => setEditingCompany({ ...editingCompany, total_quotas: parseInt(e.target.value) })} 
                                        className="w-full bg-brand/10 border border-brand/30 rounded-xl px-4 py-3 text-sm text-brand font-black focus:border-brand outline-none" 
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Estado del Servicio</label>
                                    <div className="flex gap-2">
                                        <button onClick={() => setEditingCompany({ ...editingCompany, is_active: true })} className={`flex-1 py-3 rounded-xl text-[10px] font-black uppercase border ${editingCompany.is_active ? 'bg-brand/20 border-brand text-brand' : 'bg-white/5 border-white/10 text-white/40'}`}>Activo</button>
                                        <button onClick={() => setEditingCompany({ ...editingCompany, is_active: false })} className={`flex-1 py-3 rounded-xl text-[10px] font-black uppercase border ${!editingCompany.is_active ? 'bg-red-500/20 border-red-500 text-red-400' : 'bg-white/5 border-white/10 text-white/40'}`}>Inactivo</button>
                                    </div>
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Email de Acceso (Admin)</label>
                                    <input value={editingCompany.email || ""} onChange={(e) => setEditingCompany({ ...editingCompany, email: e.target.value })} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Contraseña</label>
                                    <input type="password" value={editingCompany.password || ""} onChange={(e) => setEditingCompany({ ...editingCompany, password: e.target.value })} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Slug URL del Portal</label>
                                    <input 
                                        type="text" 
                                        placeholder="ej: sacyr-chile"
                                        value={editingCompany.slug || ""} 
                                        onChange={(e) => setEditingCompany({ ...editingCompany, slug: e.target.value.toLowerCase().replace(/\s+/g, '-') })} 
                                        className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none text-brand font-bold" 
                                    />
                                    <p className="text-[8px] text-white/20 pl-1">URL: /portal/{editingCompany.slug || '...'}</p>
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Título de Bienvenida</label>
                                    <input type="text" value={editingCompany.welcome_title || ""} onChange={(e) => setEditingCompany({ ...editingCompany, welcome_title: e.target.value })} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                </div>
                                <div className="space-y-1.5 col-span-2">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Mensaje de Bienvenida Portal</label>
                                    <textarea 
                                        value={editingCompany.welcome_message || ""} 
                                        onChange={(e) => setEditingCompany({ ...editingCompany, welcome_message: e.target.value })} 
                                        className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none min-h-[80px]" 
                                    />
                                </div>

                                <div className="space-y-1.5 col-span-2 border-t border-white/10 pt-4">
                                    <label className="text-[10px] font-black uppercase text-brand tracking-widest pl-1">Identidad de Marca</label>
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Color Primario</label>
                                    <div className="flex gap-2">
                                        <input type="color" value={editingCompany.primary_color || "#AEFF00"} onChange={(e) => setEditingCompany({ ...editingCompany, primary_color: e.target.value })} className="w-12 h-12 bg-transparent border-none p-0 cursor-pointer" />
                                        <input value={editingCompany.primary_color || ""} onChange={(e) => setEditingCompany({ ...editingCompany, primary_color: e.target.value })} placeholder="#AEFF00" className="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                    </div>
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Color Secundario</label>
                                    <div className="flex gap-2">
                                        <input type="color" value={editingCompany.secondary_color || "#000000"} onChange={(e) => setEditingCompany({ ...editingCompany, secondary_color: e.target.value })} className="w-12 h-12 bg-transparent border-none p-0 cursor-pointer" />
                                        <input value={editingCompany.secondary_color || ""} onChange={(e) => setEditingCompany({ ...editingCompany, secondary_color: e.target.value })} placeholder="#000000" className="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand/40 outline-none" />
                                    </div>
                                </div>

                                <div className="space-y-1.5 col-span-2">
                                    <label className="text-[10px] font-black uppercase text-white/40 pl-1">Logo Empresa</label>
                                    <ContentUploader
                                        courseId={`company_${editingCompany.id || 'new'}`}
                                        sectionKey="logo"
                                        label="Subir Logo Corporativo"
                                        accept="image/*"
                                        currentValue={editingCompany.logo_url}
                                        onUploadComplete={(url) => setEditingCompany({ ...editingCompany, logo_url: url })}
                                    />
                                    {editingCompany.logo_url && (
                                        <div className="mt-2 flex justify-center p-4 bg-white/5 rounded-xl border border-white/10">
                                            <img src={editingCompany.logo_url} alt="Logo" className="h-12 max-w-xs object-contain" />
                                        </div>
                                    )}
                                </div>
                            </div>

                            <button onClick={handleSaveCompany} className="w-full py-5 rounded-2xl bg-brand text-black font-black uppercase tracking-widest text-xs shadow-xl shadow-brand/20 hover:scale-[1.02] active:scale-95 transition-all mt-4">Actualizar Ficha Técnica</button>
                        </motion.div>
                    </div>
                )}

                {/* Modal: Firmas Digitales (3 slots) */}
                {signatureModal && (
                    <div className="fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                        <motion.div initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="glass p-10 w-full max-w-4xl space-y-8 border-brand/20 overflow-y-auto max-h-[90vh]">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="text-2xl font-black lowercase tracking-tighter text-brand">/firmas_y_autoridades</h3>
                                    <p className="text-white/40 text-xs font-bold uppercase tracking-widest">Configuración de Certificación para {signatureModal.name}</p>
                                </div>
                                <X onClick={() => setSignatureModal(null)} className="w-6 h-6 text-white/40 cursor-pointer hover:text-white" />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {[1, 2, 3].map(i => (
                                    <div key={i} className="p-6 rounded-2xl bg-white/5 border border-white/5 space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-[10px] font-black text-brand uppercase tracking-widest">Autoridad #{i}</span>
                                            {signatureModal[`signature_url_${i}`] && <div className="w-10 h-10 bg-white p-1 rounded-lg border border-white/20"><img src={signatureModal[`signature_url_${i}`]} className="w-full h-full object-contain" /></div>}
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[8px] font-black uppercase text-white/20">Nombre Completo</label>
                                            <input
                                                value={signatureModal[`signature_name_${i}`] || ""}
                                                onChange={(e) => setSignatureModal({ ...signatureModal, [`signature_name_${i}`]: e.target.value })}
                                                className="w-full bg-black/20 border border-white/10 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand/40"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[8px] font-black uppercase text-white/20">Cargo / Posición</label>
                                            <input
                                                value={signatureModal[`signature_role_${i}`] || ""}
                                                onChange={(e) => setSignatureModal({ ...signatureModal, [`signature_role_${i}`]: e.target.value })}
                                                className="w-full bg-black/20 border border-white/10 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand/40"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[8px] font-black uppercase text-white/20">URL Rúbrica (PNG Transparente)</label>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    value={signatureModal[`signature_url_${i}`] || ""}
                                                    onChange={(e) => setSignatureModal({ ...signatureModal, [`signature_url_${i}`]: e.target.value })}
                                                    placeholder="https://..."
                                                    className="flex-1 bg-black/20 border border-white/10 rounded-lg px-3 py-2 text-xs outline-none focus:border-brand/40 font-mono"
                                                />
                                                <label className={`cursor-pointer bg-brand/10 hover:bg-brand/20 text-brand border border-brand/30 px-3 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2`}>
                                                    <Upload className="w-3 h-3" />
                                                    <span>Subir</span>
                                                    <input
                                                        type="file"
                                                        accept="image/*"
                                                        style={{ display: 'none' }}
                                                        onChange={async (e) => {
                                                            const file = e.target.files?.[0];
                                                            if (!file) return;
                                                            const fileName = `${Date.now()}_${file.name}`;
                                                            const companyId = signatureModal.id;
                                                            const path = companyId ? `uploads/companies/${companyId}/signatures/${fileName}` : `uploads/companies/${fileName}`;
                                                            const { data: upData, error: upErr } = await supabase.storage
                                                                .from('company-logos')
                                                                .upload(path, file, { upsert: true });
                                                            if (upErr) {
                                                                alert('Error subiendo firma: ' + upErr.message);
                                                            } else {
                                                                const { data: urlData } = supabase.storage.from('company-logos').getPublicUrl(path);
                                                                setSignatureModal({ ...signatureModal, [`signature_url_${i}`]: urlData?.publicUrl });
                                                            }
                                                        }}
                                                    />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="flex gap-4">
                                <button onClick={() => setSignatureModal(null)} className="flex-1 py-4 rounded-xl bg-white/5 text-white/40 font-black uppercase text-[10px] tracking-widest">Cerrar</button>
                                <button onClick={handleSaveSignatures} className="flex-1 py-4 rounded-xl bg-brand text-black font-black uppercase text-[10px] tracking-widest shadow-xl shadow-brand/20">Guardar 3 Firmas</button>
                            </div>
                        </motion.div>
                    </div>
                )}

                {/* Course Assignment */}
                <section className="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div className="glass p-8 space-y-6 border-brand/20">
                        <div className="flex items-center justify-between">
                            <h4 className="text-lg font-black uppercase tracking-tight">Asignación de Cursos</h4>
                            <div className="p-2 rounded-lg bg-brand/10 text-brand"><BookOpen className="w-5 h-5" /></div>
                        </div>
                        <p className="text-white/40 text-sm leading-relaxed">Activa los cursos que estarán disponibles para cada empresa. Los empleados y capacitadores de la empresa verán solo los cursos asignados.</p>
                        <button onClick={() => setAssignCoursesModal(true)} className="w-full py-4 rounded-xl border-2 border-brand/20 hover:border-brand/50 text-brand font-black uppercase tracking-widest text-xs transition-all">Asignar Cursos</button>
                    </div>

                    <div className="glass p-8 space-y-6 border-brand/20 border-dashed text-brand">
                        <div className="flex items-center justify-between">
                            <h4 className="text-lg font-black uppercase tracking-tight">Gestión de Participantes</h4>
                            <div className="p-2 rounded-lg bg-brand/10 text-brand"><Users className="w-5 h-5" /></div>
                        </div>
                        <p className="text-white/40 text-sm leading-relaxed">Administra la base de datos global de alumnos, edita sus perfiles, asigna empresas y descarga certificados.</p>
                        <button onClick={() => setView('participants')} className="w-full py-4 rounded-xl border-2 border-brand/20 hover:border-brand/50 text-brand font-black uppercase tracking-widest text-xs transition-all">Ir a Participantes</button>
                    </div>
                </section>

                {/* Assign Courses Modal */}
                {assignCoursesModal && (
                    <div className="fixed inset-0 z-[110] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                        <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="glass p-8 w-full max-w-3xl space-y-6 border-brand/20 max-h-[90vh] overflow-y-auto">
                            <div className="flex justify-between items-center">
                                <h3 className="text-xl font-black">Asignar Cursos por Empresa</h3>
                                <button onClick={() => setAssignCoursesModal(false)} className="p-2 rounded-lg bg-white/5 hover:bg-white/10"><X className="w-5 h-5 text-white/40" /></button>
                            </div>

                            <div>
                                <label className="text-sm font-bold text-white/60">Selecciona la empresa</label>
                                <select 
                                    className="w-full mt-2 bg-white/5 border border-white/10 rounded-lg p-3 text-white" 
                                    style={{ colorScheme: 'dark' }}
                                    onChange={(e) => setSelectedCompanyId(e.target.value)} 
                                    value={selectedCompanyId || ''}
                                >
                                    <option value="" className="bg-neutral-900 text-white">-- Seleccionar Empresa --</option>
                                    {companies.map(c => (<option key={c.id} value={c.id} className="bg-neutral-900 text-white">{c.name}</option>))}
                                </select>
                            </div>

                            <div>
                                <label className="text-sm font-bold text-white/60">Cursos disponibles</label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mt-3">
                                    {courses.map(course => {
                                        const isSelected = selectedCourseIds.includes(course.id);
                                        return (
                                            <div key={course.id} className={`flex flex-col bg-black/20 p-3 rounded-lg border ${isSelected ? 'border-brand/40' : 'border-transparent'}`}>
                                                <label className="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" checked={isSelected} onChange={(e) => toggleCourseSelection(course.id)} />
                                                    <div className="flex flex-col">
                                                        <span className="font-bold">{course.name || course.title || 'Sin título'}</span>
                                                        <span className="text-xs text-white/40">{course.description || ''}</span>
                                                    </div>
                                                </label>
                                                
                                                {isSelected && (
                                                    <div className="mt-2 pl-6 animate-in fade-in slide-in-from-top-1">
                                                        <label className="text-[10px] uppercase font-bold text-white/40 mb-1 block">Modo de Registro</label>
                                                        <select 
                                                            value={courseModes[course.id] || 'open'} 
                                                            onChange={(e) => setCourseModes(prev => ({ ...prev, [course.id]: e.target.value }))}
                                                            className="w-full bg-white/5 border border-white/10 rounded-md p-1.5 text-xs outline-none focus:border-brand"
                                                        >
                                                            <option value="open" className="bg-[#060606]">Abierto (Auto-inscripción)</option>
                                                            <option value="restricted" className="bg-[#060606]">Restringido (Solo lista)</option>
                                                        </select>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="flex gap-3">
                                <button onClick={saveCompanyCourses} className="flex-1 py-3 rounded-xl bg-brand text-black font-black uppercase text-xs">Guardar asignaciones</button>
                                <button onClick={() => { setSelectedCompanyId(null); setSelectedCourseIds([]); }} className="flex-1 py-3 rounded-xl bg-white/5 text-white/40 font-black uppercase text-xs">Limpiar</button>
                            </div>
                        </motion.div>
                    </div>
                )}

                {/* Modal: Editar/Crear Alumno */}
                {(editingStudent || isCreatingStudent) && (
                    <div className="fixed inset-0 z-[120] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                        <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="glass p-8 w-full max-w-xl space-y-6 border-brand/20">
                            <div className="flex justify-between items-center">
                                <h3 className="text-xl font-black">{editingStudent ? 'Editar Alumno' : 'Registrar Nuevo Alumno'}</h3>
                                <button onClick={() => { setEditingStudent(null); setIsCreatingStudent(false); }} className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/40"><X className="w-5 h-5" /></button>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40">RUT</label>
                                    <input 
                                        className="w-full bg-black/20 border border-white/10 rounded-lg p-3 text-sm font-mono"
                                        placeholder="12.345.678-9"
                                        value={editingStudent?.rut || ''}
                                        onChange={(e) => setEditingStudent({...editingStudent, rut: e.target.value})} 
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40">Email</label>
                                    <input 
                                        className="w-full bg-black/20 border border-white/10 rounded-lg p-3 text-sm"
                                        placeholder="correo@empresa.cl"
                                        value={editingStudent?.email || ''}
                                        onChange={(e) => setEditingStudent({...editingStudent, email: e.target.value})}
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40">Nombres</label>
                                    <input 
                                        className="w-full bg-black/20 border border-white/10 rounded-lg p-3 text-sm"
                                        value={editingStudent?.first_name || ''}
                                        onChange={(e) => setEditingStudent({...editingStudent, first_name: e.target.value})}
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40">Apellidos</label>
                                    <input 
                                        className="w-full bg-black/20 border border-white/10 rounded-lg p-3 text-sm"
                                        value={editingStudent?.last_name || ''}
                                        onChange={(e) => setEditingStudent({...editingStudent, last_name: e.target.value})}
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40">Cargo / Función</label>
                                    <select 
                                        className="w-full bg-black/20 border border-white/10 rounded-lg p-3 text-sm"
                                        value={editingStudent?.role_id || ''}
                                        onChange={(e) => setEditingStudent({...editingStudent, role_id: e.target.value})}
                                    >
                                        <option value="">-- Seleccionar Cargo --</option>
                                        {roles.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40">Empresa (Texto)</label>
                                    <input 
                                        className="w-full bg-black/20 border border-white/10 rounded-lg p-3 text-sm"
                                        placeholder="Ej: Sacyr / Particular"
                                        value={editingStudent?.company_name || ''}
                                        onChange={(e) => setEditingStudent({...editingStudent, company_name: e.target.value})}
                                    />
                                </div>
                                <div className="col-span-2 space-y-1">
                                    <label className="text-[10px] font-black uppercase text-white/40">Cliente Corporativo Asiciado (Empresa Madre en Sistema)</label>
                                    <select 
                                        className="w-full bg-black/20 border border-white/10 rounded-lg p-3 text-sm"
                                        value={editingStudent?.client_id || editingStudent?.companies?.id || ''}
                                        onChange={(e) => setEditingStudent({...editingStudent, client_id: e.target.value, companies: { id: e.target.value }})}
                                    >
                                        <option value="">-- Seleccionar Cliente --</option>
                                        {companies.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                    </select>
                                </div>
                            </div>

                            <button 
                                onClick={() => handleSaveStudent(editingStudent || {})}
                                className="w-full py-4 bg-brand text-black font-black uppercase text-xs rounded-xl shadow-lg shadow-brand/20 hover:scale-[1.02] transition-all"
                            >
                                {editingStudent?.id ? 'Actualizar Ficha de Alumno' : 'Registrar Nuevo Participante'}
                            </button>
                        </motion.div>
                    </div>
                )}

            </div>
        </div>
    );
}
