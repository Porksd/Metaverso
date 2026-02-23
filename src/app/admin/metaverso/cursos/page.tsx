"use client";

import { useEffect, useState } from "react";
import { supabase } from "@/lib/supabase";
import { LogOut, BookOpen, Edit, Plus, Search, ArrowRight, X, Building2, Save, Settings, Check, Trash2, Medal } from "lucide-react";
import { useRouter } from "next/navigation";
import AdminSidebar from "@/components/AdminSidebar";

export default function CoursesAdmin() {
    const router = useRouter();
    const [courses, setCourses] = useState<any[]>([]);
    const [companies, setCompanies] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState("");
    const [userRole, setUserRole] = useState<string | null>(null);
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [newCourse, setNewCourse] = useState({ 
        name: '', 
        code: '', 
        company_ids: [] as string[], 
        registration_mode: 'open',
        passing_score: 90,
        weight_quiz: 80,
        weight_scorm: 20
    });
    const [selectedCompanyFilter, setSelectedCompanyFilter] = useState<string>("all");
    const [editingCourseId, setEditingCourseId] = useState<string | null>(null);

    useEffect(() => {
        const checkSession = async () => {
            const { data: { session } } = await supabase.auth.getSession();
            if (!session) {
                const returnUrl = encodeURIComponent(window.location.pathname);
                router.push(`/admin/metaverso/login?returnUrl=${returnUrl}`);
                return;
            }

            // RBAC Check
            const { data: profile } = await supabase
                .from('admin_profiles')
                .select('role')
                .eq('email', session.user.email)
                .maybeSingle();

            const superAdmins = ['apacheco@lobus.cl', 'soporte@lobus.cl', 'm.poblete.m@gmail.com', 'porksde@gmail.com'];
            const editorEmails = ['admin@metaversotec.com'];

            let role: 'superadmin' | 'editor' = 'editor';
            if (profile) {
                role = profile.role;
            } else if (session.user.email && superAdmins.includes(session.user.email)) {
                role = 'superadmin';
            } else if (session.user.email && editorEmails.includes(session.user.email)) {
                role = 'editor';
            } else {
                // If not in fallback list and no profile, sign out or redirect
                await supabase.auth.signOut();
                router.push("/admin/metaverso/login?error=unauthorized");
                return;
            }

            setUserRole(role);

            fetchCourses();
            fetchCompanies();
        };
        checkSession();
    }, []);

    const fetchCompanies = async () => {
        const { data } = await supabase.from('companies').select('id, name').order('name');
        if (data) setCompanies(data);
    };

    const fetchCourses = async () => {
        setLoading(true);
        const { data } = await supabase
            .from('courses')
            .select('*, company_courses(company_id, companies(name))')
            .order('created_at', { ascending: false });
        if (data) setCourses(data);
        setLoading(false);
    };

    const handleSaveCourse = async () => {
        if (editingCourseId) {
            // 1. Update Course Name, Code & Mode
            const currentCourse = courses.find(c => c.id === editingCourseId);
            const { error: courseError } = await supabase
                .from('courses')
                .update({ 
                    name: newCourse.name,
                    code: newCourse.code,
                    registration_mode: newCourse.registration_mode,
                    config: {
                        ...(currentCourse?.config || {}),
                        passing_score: newCourse.passing_score,
                        weight_quiz: newCourse.weight_quiz,
                        weight_scorm: newCourse.weight_scorm
                    }
                })
                .eq('id', editingCourseId);
            
            if (courseError) return alert("Error actualizando curso: " + courseError.message);

            // 2. Sync Evaluation Module Settings if it exists
            const { data: evalModules } = await supabase
                .from('course_modules')
                .select('id, settings')
                .eq('course_id', editingCourseId)
                .eq('type', 'evaluation');
            
            if (evalModules && evalModules.length > 0) {
                for (const mod of evalModules) {
                    const newSettings = {
                        ...(mod.settings || {}),
                        min_score: newCourse.passing_score,
                        quiz_percentage: newCourse.weight_quiz,
                        scorm_percentage: newCourse.weight_scorm
                    };
                    await supabase.from('course_modules').update({ settings: newSettings }).eq('id', mod.id);
                }
            }

            // 3. Sync Companies (Delete & Re-insert)
            await supabase.from('company_courses').delete().eq('course_id', editingCourseId);
            
            if (newCourse.company_ids.length > 0) {
                const assignments = newCourse.company_ids.map(id => ({
                    course_id: editingCourseId,
                    company_id: id
                }));
                const { error: assignError } = await supabase.from('company_courses').insert(assignments);
                if (assignError) alert("Error asignando empresas: " + assignError.message);
            }

            setIsCreateModalOpen(false);
            setEditingCourseId(null);
            setNewCourse({ 
                name: '', 
                code: '', 
                company_ids: [], 
                registration_mode: 'open',
                passing_score: 90,
                weight_quiz: 80,
                weight_scorm: 20
            });
            fetchCourses();
        } else {
            handleCreateCourse();
        }
    };

    const handleDeleteCourse = async (course: any) => {
        if (userRole !== 'superadmin') {
            return alert("No tienes permisos para eliminar cursos. Contacta a un SuperAdmin.");
        }
        // 1. Verificar alumnos
        const { count: enrollmentCount, error: enrollError } = await supabase
            .from('enrollments')
            .select('*', { count: 'exact', head: true })
            .eq('course_id', course.id);

        if (enrollError) return alert("Error al verificar inscripciones: " + enrollError.message);

        if (enrollmentCount && enrollmentCount > 0) {
            return alert(`No se puede eliminar el curso "${course.name}" porque tiene ${enrollmentCount} alumnos inscritos. Primero debes desvincular (eliminar inscripciones) de los alumnos.`);
        }

        // 2. Verificar empresas (desde la data ya cargada)
        const companyCount = course.company_courses?.length || 0;
        if (companyCount > 0) {
            return alert(`No se puede eliminar el curso "${course.name}" porque todavía está vinculado a ${companyCount} empresa(s). Primero entra en Ajustes y desvincula todas las empresas.`);
        }

        // 3. Confirmación final
        if (!confirm(`¿Estás COMPLETAMENTE SEGURO de eliminar el curso "${course.name}"? Se borrarán también todos los módulos y contenidos asociados. Esta acción es irreversible.`)) return;

        setLoading(true);
        const { error } = await supabase.from('courses').delete().eq('id', course.id);
        
        if (error) {
            alert("Error al eliminar el curso: " + error.message);
            setLoading(false);
        } else {
            fetchCourses();
        }
    };

    const handleCreateCourse = async () => {
        if (!newCourse.name) return alert("El nombre es obligatorio");
        
        const payload: any = {
            name: newCourse.name,
            code: newCourse.code || ("MOC-" + Math.floor(Math.random() * 10000)),
            is_active: true,
            registration_mode: newCourse.registration_mode,
            config: { 
                passing_score: newCourse.passing_score, 
                weight_scorm: newCourse.weight_scorm, 
                weight_quiz: newCourse.weight_quiz, 
                questions: [] 
            }
        };

        const { data: course, error: courseError } = await supabase
            .from('courses')
            .insert(payload)
            .select()
            .single();

        if (courseError) {
            alert("Error creando curso: " + courseError.message);
        } else if (course) {
            // Assign companies
            if (newCourse.company_ids.length > 0) {
                const assignments = newCourse.company_ids.map(id => ({
                    course_id: course.id,
                    company_id: id
                }));
                await supabase.from('company_courses').insert(assignments);
            }

            setIsCreateModalOpen(false);
            setNewCourse({ 
                name: '', 
                code: '', 
                company_ids: [], 
                registration_mode: 'open',
                passing_score: 90,
                weight_quiz: 80,
                weight_scorm: 20
            });
            fetchCourses();
        }
    };

    const handleLogout = async () => {
        await supabase.auth.signOut();
        router.push("/admin"); // Redirects to admin landing
    };

    const filteredCourses = courses.filter(c => {
        const matchesSearch = c.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                             c.company_courses?.some((cc: any) => cc.companies?.name.toLowerCase().includes(searchTerm.toLowerCase()));
        const matchesCompany = selectedCompanyFilter === "all" || 
                              (selectedCompanyFilter === "none" && (!c.company_courses || c.company_courses.length === 0)) ||
                              c.company_courses?.some((cc: any) => cc.company_id === selectedCompanyFilter);
        return matchesSearch && matchesCompany;
    });

    return (
        <AdminSidebar title="Gestión de Cursos">
            <div className="p-8 text-white min-h-screen pt-20">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-[10px] font-black uppercase text-brand mb-1">
                            <BookOpen className="w-3 h-3" /> Ecosistema Educativo
                        </div>
                        <h1 className="text-4xl font-black tracking-tight">Gestión de <span className="text-brand">Cursos</span></h1>
                        <p className="text-white/40 text-sm">Central de contenido, evaluaciones y despliegue multi-empresa.</p>
                    </div>
                    <div className="flex gap-2 w-full md:w-auto">
                        <button
                            onClick={() => {
                                setNewCourse({ 
                                    name: '', 
                                    code: '', 
                                    company_ids: [], 
                                    registration_mode: 'restricted',
                                    passing_score: 90,
                                    weight_quiz: 80,
                                    weight_scorm: 20
                                });
                                setEditingCourseId(null);
                                setIsCreateModalOpen(true);
                            }}
                            className="flex-1 md:flex-none bg-brand text-black px-6 py-3 rounded-xl font-black uppercase text-[10px] flex items-center justify-center gap-2 hover:bg-white transition-all shadow-lg shadow-brand/10"
                        >
                            <Plus className="w-4 h-4" /> Nuevo Curso
                        </button>
                        <button
                            onClick={() => router.push('/admin/metaverso/encuestas')}
                            className="bg-white/5 border border-white/10 text-white/40 px-6 py-3 rounded-xl font-black uppercase text-[10px] hover:bg-brand/10 hover:text-brand transition-all flex items-center gap-2"
                        >
                            <Settings className="w-4 h-4" /> Gestión de Encuestas
                        </button>
                    </div>
                </div>

            {/* Filters & Search */}
            <div className="flex flex-col md:flex-row gap-4 mb-8">
                <div className="flex-1 relative">
                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-white/30 w-5 h-5" />
                    <input
                        type="text"
                        placeholder="Buscar por nombre de curso..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full bg-white/5 border border-white/10 pl-12 pr-4 py-4 rounded-2xl text-white outline-none focus:border-brand transition-all font-medium"
                    />
                </div>
                
                <div className="flex items-center gap-2 bg-white/5 border border-white/10 px-4 py-2 rounded-2xl min-w-[250px]">
                    <Building2 className="w-5 h-5 text-white/30" />
                    <select 
                        value={selectedCompanyFilter}
                        onChange={(e) => setSelectedCompanyFilter(e.target.value)}
                        className="bg-transparent text-white outline-none w-full text-sm font-bold appearance-none cursor-pointer"
                    >
                        <option value="all" className="bg-[#111]">Todas las Empresas</option>
                        <option value="none" className="bg-[#111]">Solo Cursos Generales</option>
                        {companies.map(c => (
                            <option key={c.id} value={c.id} className="bg-[#111]">{c.name}</option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {filteredCourses.map(course => (
                    <div key={course.id} className="glass p-6 rounded-3xl border-white/5 hover:border-brand/30 transition-all group relative overflow-hidden">
                        <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <BookOpen className="w-24 h-24" />
                        </div>

                        <div className="relative z-10">
                            <div className="flex flex-wrap gap-1 mb-3">
                                {course.company_courses && course.company_courses.length > 0 ? (
                                    course.company_courses.map((cc: any, idx: number) => (
                                        <span key={idx} className="text-[9px] bg-brand/10 px-2 py-0.5 rounded text-brand font-black uppercase tracking-wider border border-brand/20">
                                            {cc.companies?.name}
                                        </span>
                                    ))
                                ) : (
                                    <span className="text-[10px] bg-white/10 px-2 py-1 rounded text-white/60 font-bold uppercase tracking-wider">
                                        General
                                    </span>
                                )}
                            </div>

                            <h3 className="text-xl font-bold mb-2 pr-8">{course.name}</h3>
                            <p className="text-sm text-white/40 mb-6 line-clamp-2">
                                ID: {course.id}
                            </p>

                            <div className="flex gap-3">
                                <button
                                    onClick={() => router.push(`/admin/metaverso/cursos/${course.id}/contenido`)}
                                    className="flex-1 py-3 bg-brand/10 text-brand border border-brand/20 rounded-xl font-bold text-sm hover:bg-brand hover:text-black transition-all flex items-center justify-center gap-2"
                                >
                                    <Edit className="w-4 h-4" />
                                    Contenido
                                </button>
                                <button
                                    onClick={() => {
                                        setNewCourse({ 
                                            name: course.name, 
                                            code: course.code,
                                            company_ids: course.company_courses?.map((cc: any) => cc.company_id) || [], 
                                            registration_mode: course.registration_mode || 'open',
                                            passing_score: course.config?.passing_score || 90,
                                            weight_quiz: course.config?.weight_quiz || 80,
                                            weight_scorm: course.config?.weight_scorm || 20
                                        });
                                        setEditingCourseId(course.id);
                                        setIsCreateModalOpen(true);
                                    }}
                                    className="p-3 bg-white/5 text-white/40 border border-white/10 rounded-xl hover:bg-white/10 hover:text-white transition-all"
                                    title="Configurar Curso"
                                >
                                    <Settings className="w-4 h-4" />
                                </button>
                                {userRole === 'superadmin' && (
                                    <button
                                        onClick={() => handleDeleteCourse(course)}
                                        className="p-3 bg-red-500/5 text-red-500/40 border border-red-500/10 rounded-xl hover:bg-red-500 hover:text-white transition-all"
                                        title="Eliminar Curso"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {!loading && filteredCourses.length === 0 && (
                <div className="text-center py-20 opacity-40 glass rounded-3xl border-dashed border-2 border-white/5">
                    <BookOpen className="w-16 h-16 mx-auto mb-4 opacity-20" />
                    <p className="font-bold">No se encontraron cursos activos</p>
                    <p className="text-xs">Prueba ajustando los filtros o crea uno nuevo.</p>
                </div>
            )}

            {/* Create Modal */}
            {isCreateModalOpen && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div className="absolute inset-0 bg-black/80 backdrop-blur-sm" onClick={() => setIsCreateModalOpen(false)} />
                    <div className="glass w-full max-w-md p-8 rounded-3xl border-white/10 relative z-10 animate-in zoom-in-95 duration-200">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-2xl font-black italic">{editingCourseId ? 'EDITAR' : 'CREAR'} <span className="text-brand">CURSO</span></h2>
                            <button onClick={() => { 
                                setIsCreateModalOpen(false); 
                                setEditingCourseId(null); 
                                setNewCourse({ 
                                    name: '', 
                                    code: '', 
                                    company_ids: [], 
                                    registration_mode: 'open',
                                    passing_score: 90,
                                    weight_quiz: 80,
                                    weight_scorm: 20
                                });
                            }} className="text-white/40 hover:text-white">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <div className="space-y-4 max-h-[80vh] overflow-y-auto pr-2 custom-scrollbar">
                            <div>
                                <label className="text-[10px] font-black uppercase text-white/40 mb-1 block">Nombre del Curso</label>
                                <input 
                                    type="text" 
                                    value={newCourse.name}
                                    onChange={(e) => setNewCourse(prev => ({ ...prev, name: e.target.value }))}
                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 outline-none focus:border-brand transition-all font-bold"
                                    placeholder="Ej: Seguridad Industrial v2"
                                />
                            </div>

                            <div>
                                <label className="text-[10px] font-black uppercase text-white/40 mb-1 block">Código / ID de Curso</label>
                                <input 
                                    type="text" 
                                    value={newCourse.code}
                                    onChange={(e) => setNewCourse(prev => ({ ...prev, code: e.target.value.toUpperCase().replace(/\s+/g, '-') }))}
                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 outline-none focus:border-brand transition-all font-mono text-sm"
                                    placeholder="Ej: SEG-IND-01"
                                />
                            </div>

                            <div>
                                <label className="text-[10px] font-black uppercase text-white/40 mb-1 block">Modo de Registro</label>
                                <select 
                                    value={newCourse.registration_mode}
                                    onChange={(e) => setNewCourse(prev => ({ ...prev, registration_mode: e.target.value }))}
                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 outline-none focus:border-brand transition-all font-bold text-sm"
                                >
                                    <option value="open" className="bg-[#060606]">Abierto (Cualquier alumno del portal se auto-inscribe)</option>
                                    <option value="restricted" className="bg-[#060606]">Restringido (Solo alumnos listados previamente)</option>
                                </select>
                            </div>

                            {/* Evaluación Section */}
                            <div className="p-4 bg-brand/5 border border-brand/20 rounded-2xl space-y-4">
                                <label className="text-[10px] font-black uppercase text-brand mb-1 block">Ajustes de Evaluación</label>
                                
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-[9px] font-black uppercase text-white/40 mb-1 block">Puntaje Mínimo (%)</label>
                                        <input 
                                            type="number" 
                                            value={newCourse.passing_score}
                                            onChange={(e) => setNewCourse(prev => ({ ...prev, passing_score: parseInt(e.target.value) }))}
                                            className="w-full bg-black/40 border border-white/5 rounded-lg px-3 py-2 text-brand font-black text-center"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-[9px] font-black uppercase text-white/40 mb-1 block">Ponderación Quiz (%)</label>
                                        <input 
                                            type="number" 
                                            value={newCourse.weight_quiz}
                                            onChange={(e) => {
                                                const quiz = parseInt(e.target.value);
                                                setNewCourse(prev => ({ ...prev, weight_quiz: quiz, weight_scorm: 100 - quiz }));
                                            }}
                                            className="w-full bg-black/40 border border-white/5 rounded-lg px-3 py-2 text-white font-bold text-center"
                                        />
                                    </div>
                                </div>
                                <p className="text-[8px] text-white/30 italic text-center">
                                    El peso SCORM se ajustará automáticamente ({newCourse.weight_scorm}%).
                                </p>
                            </div>

                            <div className="flex-1 overflow-hidden flex flex-col min-h-0">
                                <label className="text-[10px] font-black uppercase text-white/40 mb-2 block">Empresas Asociadas (Selección múltiple)</label>
                                <div className="grid grid-cols-1 gap-1.5 max-h-60 overflow-y-auto p-3 border border-white/10 rounded-xl bg-white/5 custom-scrollbar">
                                    {companies.map(c => {
                                        const isSelected = newCourse.company_ids?.includes(c.id);
                                        return (
                                            <button 
                                                key={c.id}
                                                onClick={() => {
                                                    const currentIds = newCourse.company_ids || [];
                                                    const newIds = isSelected 
                                                        ? currentIds.filter(id => id !== c.id)
                                                        : [...currentIds, c.id];
                                                    setNewCourse(prev => ({ ...prev, company_ids: newIds }));
                                                }}
                                                className={`flex items-center gap-3 p-2.5 rounded-lg text-left transition-all group ${
                                                    isSelected 
                                                        ? 'bg-brand/20 border border-brand/50' 
                                                        : 'hover:bg-white/5 border border-transparent'
                                                }`}
                                            >
                                                <div className={`w-5 h-5 rounded border-2 flex items-center justify-center transition-colors ${
                                                    isSelected ? 'bg-brand border-brand' : 'border-white/10 group-hover:border-white/30'
                                                }`}>
                                                    {isSelected && <Check className="w-3.5 h-3.5 text-black stroke-[4px]" />}
                                                </div>
                                                <span className={`text-sm font-bold ${isSelected ? 'text-brand' : 'text-white/60'}`}>
                                                    {c.name}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                                <p className="mt-3 text-[10px] text-white/20 leading-relaxed italic">
                                    Si no seleccionas ninguna empresa, el curso se considerará "General" y será visible para todos los alumnos en la plataforma.
                                </p>
                            </div>

                            <button 
                                onClick={handleSaveCourse}
                                className="w-full bg-brand text-black font-black uppercase py-4 rounded-xl mt-4 flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-brand/20"
                            >
                                <Medal className="w-4 h-4" />
                                {editingCourseId ? 'Actualizar Parametros' : 'Inicializar Curso'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminSidebar>
    );
}
