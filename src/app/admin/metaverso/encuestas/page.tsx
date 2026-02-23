"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { 
    Plus, Trash2, Edit2, Search, ClipboardList, 
    X, Check, ShieldAlert, BarChart3, Globe, Settings,
    Clock, Loader2
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import SurveyBuilder from "@/components/SurveyBuilder";

interface Survey {
    id: string;
    title_es: string;
    title_ht?: string;
    description_es?: string;
    description_ht?: string;
    settings?: any;
    created_at?: string;
}

export default function SurveysAdmin() {
    const router = useRouter();
    const [surveys, setSurveys] = useState<Survey[]>([]);
    const [loading, setLoading] = useState(true);
    const [isAuthorized, setIsAuthorized] = useState<boolean | null>(null);
    const [userRole, setUserRole] = useState<'superadmin' | 'editor' | null>(null);
    const [isResetting, setIsResetting] = useState<string | null>(null);
    const [searchTerm, setSearchTerm] = useState("");
    const [isEditing, setIsEditing] = useState<Survey | null>(null);
    const [showForm, setShowForm] = useState(false);
    const [activeSurveyForQuestions, setActiveSurveyForQuestions] = useState<Survey | null>(null);

    useEffect(() => {
        checkAuth();
    }, []);

    const checkAuth = async () => {
        const { data: { session } } = await supabase.auth.getSession();
        
        if (!session) {
            router.push("/admin/metaverso/login?returnUrl=/admin/metaverso/encuestas");
            return;
        }

        const email = session.user.email?.toLowerCase();
        
        // Check in admin_profiles table
        const { data: profile } = await supabase
            .from('admin_profiles')
            .select('role')
            .eq('email', email)
            .single();

        if (profile) {
            setUserRole(profile.role);
            setIsAuthorized(true);
        } else {
            // Fallback
            const allowedEmails = ['admin@metaversotec.com', 'porksde@gmail.com', 'apacheco@lobus.cl'];
            if (email && allowedEmails.includes(email)) {
                setUserRole('superadmin');
                setIsAuthorized(true);
            } else {
                setIsAuthorized(false);
                return;
            }
        }
        
        loadSurveys();
    };

    const handleResetSurveyData = async (surveyId: string, title: string) => {
        if (!confirm(`¿Deseas RESETEAR todos los datos de la encuesta "${title}"? Esta acción eliminará permanentemente todas las respuestas recolectadas hasta ahora.`)) return;
        
        setIsResetting(surveyId);
        try {
            const { error } = await supabase
                .from('survey_responses')
                .delete()
                .eq('survey_id', surveyId);

            if (error) throw error;
            alert("Respuestas reseteadas correctamente.");
        } catch (err: any) {
            alert("Error al resetear datos: " + err.message);
        } finally {
            setIsResetting(null);
        }
    };

    const loadSurveys = async () => {
        setLoading(true);
        const { data, error } = await supabase
            .from('surveys')
            .select('*')
            .order('created_at', { ascending: false });
        
        if (data) setSurveys(data);
        if (error) console.error("Error loading surveys:", error);
        setLoading(false);
    };

    const handleSaveSurvey = async (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget as HTMLFormElement);
        const surveyData = {
            title_es: formData.get('title_es') as string,
            title_ht: formData.get('title_ht') as string,
            description_es: formData.get('description_es') as string,
            description_ht: formData.get('description_ht') as string,
        };

        if (isEditing) {
            await supabase.from('surveys').update(surveyData).eq('id', isEditing.id);
        } else {
            await supabase.from('surveys').insert([surveyData]);
        }

        setShowForm(false);
        setIsEditing(null);
        loadSurveys();
    };

    const handleDeleteSurvey = async (id: string) => {
        if (!confirm("¿Seguro que desea eliminar esta encuesta? Se borrarán todas sus preguntas y respuestas asociadas.")) return;
        await supabase.from('surveys').delete().eq('id', id);
        loadSurveys();
    };

    const filteredSurveys = surveys.filter(s => 
        s.title_es.toLowerCase().includes(searchTerm.toLowerCase()) ||
        s.title_ht?.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (isAuthorized === null) return (
        <div className="min-h-screen bg-black flex items-center justify-center">
            <div className="text-brand font-black animate-pulse uppercase tracking-widest text-xs">Verificando Protocolo...</div>
        </div>
    );

    if (isAuthorized === false) return (
        <div className="min-h-screen bg-black flex flex-col items-center justify-center p-8 text-center space-y-6">
            <ShieldAlert className="w-20 h-20 text-red-500" />
            <h1 className="text-4xl font-black italic tracking-tighter uppercase">Acceso Denegado</h1>
            <button onClick={() => router.push("/admin/metaverso")} className="bg-white text-black px-8 py-4 rounded-2xl font-black uppercase text-xs">Regresar</button>
        </div>
    );

    return (
        <div className="min-h-screen bg-[#060606] text-white p-4 md:p-8 font-sans">
            <div className="max-w-6xl mx-auto space-y-8">
                
                {/* Header */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-brand text-[10px] font-black uppercase tracking-[0.2em] mb-2">
                             <BarChart3 className="w-3 h-3" /> Sistema de Retroalimentación
                        </div>
                        <h1 className="text-3xl font-black uppercase tracking-tight flex items-center gap-3">
                            <ClipboardList className="w-8 h-8 text-brand" />
                            Gestión de Encuestas
                        </h1>
                        <p className="text-white/40 font-medium text-sm">Crea plantillas de encuestas para medir la satisfacción de los alumnos</p>
                    </div>
                    <div className="flex gap-3">
                        <button onClick={() => router.push('/admin/metaverso')} className="px-6 py-3 rounded-xl border border-white/10 text-white/40 font-black uppercase text-[10px] hover:text-white transition-all">Volver</button>
                        <button
                            onClick={() => { setIsEditing(null); setShowForm(true); }}
                            className="bg-brand text-black px-6 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest flex items-center gap-2 hover:scale-105 transition-all shadow-xl shadow-brand/20"
                        >
                            <Plus className="w-4 h-4" /> Nueva Encuesta
                        </button>
                    </div>
                </div>

                {/* Main Content */}
                <div className="space-y-4">
                    <div className="relative group">
                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20 group-focus-within:text-brand transition-colors" />
                        <input
                            type="text"
                            placeholder="Buscar encuestas..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full bg-white/[0.02] border border-white/5 rounded-2xl py-4 pl-12 pr-4 focus:border-brand/40 outline-none transition-all font-medium text-sm"
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-3">
                        {loading ? (
                            <div className="py-20 text-center text-white/20 font-black uppercase tracking-widest text-xs">Cargando Encuestas...</div>
                        ) : filteredSurveys.length === 0 ? (
                            <div className="py-20 text-center bg-white/[0.02] border border-dashed border-white/10 rounded-3xl">
                                <p className="text-white/20 font-black uppercase tracking-widest text-xs">No se encontraron encuestas</p>
                            </div>
                        ) : filteredSurveys.map(survey => (
                            <motion.div 
                                layoutId={survey.id}
                                key={survey.id} 
                                className="group bg-white/[0.02] hover:bg-white/[0.04] border border-white/5 hover:border-brand/20 rounded-2xl p-6 flex flex-col md:flex-row justify-between items-center gap-6 transition-all"
                            >
                                <div className="flex gap-4 items-center flex-1">
                                    <div className="w-12 h-12 rounded-xl bg-brand/10 border border-brand/20 flex items-center justify-center shrink-0">
                                        <ClipboardList className="w-6 h-6 text-brand" />
                                    </div>
                                    <div>
                                        <h3 className="font-black text-lg group-hover:text-brand transition-colors">{survey.title_es}</h3>
                                        <p className="text-white/40 text-[10px] font-bold uppercase tracking-widest">{survey.title_ht || 'Sin traducción Kreyòl'}</p>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2">
                                    <button 
                                        onClick={() => setActiveSurveyForQuestions(survey)}
                                        className="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-[10px] font-black uppercase border border-white/5 transition-all text-white/60 hover:text-white"
                                    >
                                        <Edit2 className="w-3 h-3" /> Preguntas
                                    </button>
                                    <button 
                                        onClick={() => router.push(`/admin/metaverso/encuestas/${survey.id}/stats`)}
                                        className="flex items-center gap-2 px-4 py-2 bg-brand/5 hover:bg-brand/10 rounded-xl text-[10px] font-black uppercase border border-brand/10 transition-all text-brand"
                                    >
                                        <BarChart3 className="w-3 h-3" /> Estadísticas
                                    </button>
                                    <div className="w-px h-6 bg-white/10 mx-2" />
                                    {userRole === 'superadmin' && (
                                        <button 
                                            onClick={() => handleResetSurveyData(survey.id, survey.title_es)} 
                                            className={`p-2.5 rounded-xl bg-white/5 hover:bg-orange-500/10 text-white/20 hover:text-orange-400 border border-white/5 transition-all ${isResetting === survey.id ? 'animate-pulse' : ''}`}
                                            title="Resetear Datos de Encuesta"
                                        >
                                            {isResetting === survey.id ? <Loader2 className="w-4 h-4 animate-spin" /> : <Clock className="w-4 h-4" />}
                                        </button>
                                    )}
                                    <button onClick={() => { setIsEditing(survey); setShowForm(true); }} className="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white border border-white/5 transition-all"><Settings className="w-4 h-4" /></button>
                                    {userRole === 'superadmin' && (
                                        <button onClick={() => handleDeleteSurvey(survey.id)} className="p-2.5 rounded-xl bg-white/5 hover:bg-red-500/10 text-white/20 hover:text-red-400 border border-white/5 transition-all"><Trash2 className="w-4 h-4" /></button>
                                    )}
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </div>

                {/* Modal Form */}
                <AnimatePresence>
                    {showForm && (
                        <div className="fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                            <motion.div initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.9 }} className="glass p-8 w-full max-w-xl border-brand/20 space-y-8">
                                <div className="flex justify-between items-center">
                                    <h2 className="text-2xl font-black uppercase italic tracking-tighter text-brand">{isEditing ? 'Editar Encuesta' : 'Nueva Encuesta'}</h2>
                                    <button onClick={() => { setShowForm(false); setIsEditing(null); }} className="text-white/20 hover:text-white"><X className="w-6 h-6" /></button>
                                </div>

                                <form onSubmit={handleSaveSurvey} className="space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-4">
                                            <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-brand/60">Versión Español</h4>
                                            <div className="space-y-1.5">
                                                <label className="text-[10px] uppercase font-black text-white/20 pl-1">Título</label>
                                                <input name="title_es" defaultValue={isEditing?.title_es} required className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand outline-none transition-all" />
                                            </div>
                                            <div className="space-y-1.5">
                                                <label className="text-[10px] uppercase font-black text-white/20 pl-1">Descripción</label>
                                                <textarea name="description_es" defaultValue={isEditing?.description_es} rows={3} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand outline-none transition-all resize-none" />
                                            </div>
                                        </div>
                                        <div className="space-y-4">
                                            <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-white/40">Versión Kreyòl</h4>
                                            <div className="space-y-1.5">
                                                <label className="text-[10px] uppercase font-black text-white/20 pl-1">Tit</label>
                                                <input name="title_ht" defaultValue={isEditing?.title_ht} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand outline-none transition-all" />
                                            </div>
                                            <div className="space-y-1.5">
                                                <label className="text-[10px] uppercase font-black text-white/20 pl-1">Deskripsyon</label>
                                                <textarea name="description_ht" defaultValue={isEditing?.description_ht} rows={3} className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand outline-none transition-all resize-none" />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="pt-4 flex gap-3">
                                        <button type="button" onClick={() => { setShowForm(false); setIsEditing(null); }} className="flex-1 py-4 rounded-xl border border-white/10 text-white/40 font-black uppercase text-xs tracking-widest hover:bg-white/5 transition-all">Cancelar</button>
                                        <button type="submit" className="flex-1 py-4 bg-brand text-black rounded-xl font-black uppercase text-xs tracking-widest hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                                            <Check className="w-4 h-4" /> {isEditing ? 'Actualizar' : 'Crear Encuesta'}
                                        </button>
                                    </div>
                                </form>
                            </motion.div>
                        </div>
                    )}
                </AnimatePresence>

                {/* Modal for Questions Builder */}
                <AnimatePresence>
                    {activeSurveyForQuestions && (
                        <div className="fixed inset-0 z-[100] bg-black/90 backdrop-blur-xl flex items-center justify-center p-0 md:p-10">
                            <motion.div initial={{ y: 50, opacity: 0 }} animate={{ y: 0, opacity: 1 }} className="w-full h-full max-w-5xl bg-[#0A0A0A] md:rounded-3xl border border-white/10 flex flex-col overflow-hidden">
                                <div className="p-6 border-b border-white/5 flex justify-between items-center shrink-0">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 rounded-xl bg-brand/10 border border-brand/20 flex items-center justify-center">
                                            <Plus className="w-5 h-5 text-brand" />
                                        </div>
                                        <div>
                                            <h2 className="text-xl font-black uppercase tracking-tight italic">Preguntas: <span className="text-brand">{activeSurveyForQuestions.title_es}</span></h2>
                                            <p className="text-white/20 text-[10px] font-black uppercase tracking-widest">Editor de Cuestionario de Satisfacción</p>
                                        </div>
                                    </div>
                                    <button onClick={() => setActiveSurveyForQuestions(null)} className="p-3 rounded-xl bg-white/5 hover:bg-red-500/10 text-white/20 hover:text-red-400 group transition-all">
                                        <X className="w-6 h-6 group-hover:scale-110" />
                                    </button>
                                </div>

                                <div className="flex-1 overflow-y-auto">
                                    <SurveyBuilder 
                                        surveyId={activeSurveyForQuestions.id} 
                                        onClose={() => setActiveSurveyForQuestions(null)}
                                    />
                                </div>
                            </motion.div>
                        </div>
                    )}
                </AnimatePresence>
            </div>
        </div>
    );
}
