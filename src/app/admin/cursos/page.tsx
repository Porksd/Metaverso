"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import {
    Plus, Save, Trash2, Edit3, Settings2,
    HelpCircle, CheckCircle2, Package, Percent, LogOut, Eye, X
} from "lucide-react";
import { supabase } from "@/lib/supabase";
import { useRouter } from "next/navigation";

export default function CursosAdmin() {
    const [courses, setCourses] = useState<any[]>([]);
    const [editingCourse, setEditingCourse] = useState<any>(null);
    const [isSaving, setIsSaving] = useState(false);
    const [isAuthenticated, setIsAuthenticated] = useState(false);
    const [showPreview, setShowPreview] = useState(false);
    const router = useRouter();

    useEffect(() => {
        const auth = localStorage.getItem('cursos_auth');
        if (!auth) {
            router.push("/admin/cursos/login");
        } else {
            setIsAuthenticated(true);
            fetchCourses();
        }
    }, []);

    const fetchCourses = async () => {
        const { data } = await supabase.from('courses').select('*').order('name');
        setCourses(data || []);
    };

    const handleSave = async () => {
        if (!editingCourse) return;
        setIsSaving(true);

        const { error } = await supabase
            .from('courses')
            .update({
                name: editingCourse.name,
                code: editingCourse.code,
                config: editingCourse.config
            })
            .eq('id', editingCourse.id);

        if (error) {
            alert("Error al guardar: " + error.message);
        } else {
            await fetchCourses();
            alert("Configuración de curso guardada con éxito.");
        }
        setIsSaving(false);
    };

    const updateConfig = (key: string, value: any) => {
        setEditingCourse({
            ...editingCourse,
            config: { ...editingCourse.config, [key]: value }
        });
    };

    const updateQuestion = (index: number, field: string, value: any) => {
        const newQuestions = [...editingCourse.config.questions];
        newQuestions[index] = { ...newQuestions[index], [field]: value };
        updateConfig('questions', newQuestions);
    };

    return (
        <div className="min-h-screen bg-transparent text-white p-4 md:p-10 font-sans">
            <div className="max-w-7xl mx-auto space-y-10">

                <header className="flex justify-between items-end">
                    <div className="space-y-1">
                        <h1 className="text-4xl font-black tracking-tight uppercase">Configuración <span className="text-brand">Técnica</span></h1>
                        <p className="text-white/40 font-bold uppercase tracking-widest text-[10px]">Arquitectura de Aprendizaje & SCORM</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => { localStorage.removeItem('cursos_auth'); window.location.href = '/admin/cursos/login'; }}
                            className="p-3 rounded-xl bg-white/5 border border-white/10 text-white/40 hover:text-red-400 hover:bg-red-500/10 transition-all group"
                            title="Cerrar Sesión"
                        >
                            <LogOut className="w-4 h-4 group-hover:rotate-180 transition-transform duration-500" />
                        </button>
                        <button
                            onClick={async () => {
                                const newCourse = {
                                    code: "NEW-" + Math.floor(Math.random() * 1000),
                                    name: "Nuevo Curso Demo",
                                    description: "Descripción del curso...",
                                    is_active: true,
                                    config: { passing_score: 60, weight_scorm: 50, weight_quiz: 50, questions: [] }
                                };
                                const { data, error } = await supabase.from('courses').insert(newCourse).select().single();
                                if (error) alert(error.message);
                                else {
                                    // Crear módulos por defecto vía API Admin
                                    const defaultModules = [
                                        { course_id: data.id, title: "Contenido del Curso", type: 'content', order_index: 0 },
                                        { course_id: data.id, title: "Evaluación Final", type: 'evaluation', order_index: 1 }
                                    ];

                                    for (const mod of defaultModules) {
                                        await fetch('/api/admin/content', {
                                            method: 'POST',
                                            body: JSON.stringify({ table: 'course_modules', data: mod })
                                        });
                                    }

                                    await fetchCourses();
                                    setEditingCourse(data);
                                }
                            }}
                            className="bg-brand text-black px-6 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest hover:scale-105 active:scale-95 transition-all outline-none border-none">
                            <Plus className="w-4 h-4 inline mr-2" /> Nuevo Curso
                        </button>
                    </div>
                </header>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <div className="space-y-4">
                        <h3 className="text-xs font-black text-white/30 uppercase tracking-[0.2em] mb-6">Catálogo Activo</h3>
                        {courses.map((c) => (
                            <div
                                key={c.id}
                                onClick={() => setEditingCourse(c)}
                                className={`p-6 rounded-2xl border transition-all cursor-pointer group ${editingCourse?.id === c.id ? 'bg-brand/10 border-brand' : 'bg-white/5 border-white/5 hover:border-white/20'}`}
                            >
                                <div className="flex justify-between items-start mb-2">
                                    <span className="text-[9px] font-black bg-white/10 px-2 py-0.5 rounded text-white/60 uppercase">{c.code}</span>
                                    <Settings2 className={`w-4 h-4 ${editingCourse?.id === c.id ? 'text-brand' : 'text-white/20'}`} />
                                </div>
                                <h4 className="font-bold text-sm tracking-tight">{c.name}</h4>
                            </div>
                        ))}
                    </div>

                    <div className="lg:col-span-2 space-y-8">
                        {editingCourse ? (
                            <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} className="glass p-10 space-y-10 border-brand/20">

                                <section className="space-y-6">
                                    <div className="flex items-start gap-4">
                                        <div className="w-10 h-10 rounded-xl bg-brand/10 flex items-center justify-center border border-brand/20 mt-1 shrink-0">
                                            <Percent className="w-5 h-5 text-brand" />
                                        </div>
                                        <div className="flex-1 space-y-6">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/40 uppercase tracking-widest pl-1">Nombre del Curso</label>
                                                    <input
                                                        type="text"
                                                        value={editingCourse.name || ""}
                                                        onChange={(e) => setEditingCourse({ ...editingCourse, name: e.target.value })}
                                                        className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-bold"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/40 uppercase tracking-widest pl-1">Código Único</label>
                                                    <input
                                                        type="text"
                                                        value={editingCourse.code || ""}
                                                        onChange={(e) => setEditingCourse({ ...editingCourse, code: e.target.value })}
                                                        className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-bold font-mono"
                                                    />
                                                </div>
                                            </div>

                                            <div className="space-y-2">
                                                <label className="text-[10px] font-black text-white/40 uppercase tracking-widest pl-1">URL de Contenido SCORM / Paquete Interactivo</label>
                                                <input
                                                    type="text"
                                                    placeholder="https://content.metaversotec.cl/cursos/altura-vr/..."
                                                    value={editingCourse.config?.scorm_url || ""}
                                                    onChange={(e) => updateConfig('scorm_url', e.target.value)}
                                                    className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-brand font-medium placeholder:text-white/10"
                                                />
                                            </div>

                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/40 uppercase tracking-widest pl-1">Exigencia (%)</label>
                                                    <input
                                                        type="number"
                                                        value={editingCourse.config?.passing_score || 0}
                                                        onChange={(e) => updateConfig('passing_score', parseInt(e.target.value))}
                                                        className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-brand font-black"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/40 uppercase tracking-widest pl-1">Peso SCORM (%)</label>
                                                    <input
                                                        type="number"
                                                        value={editingCourse.config?.weight_scorm || 0}
                                                        onChange={(e) => updateConfig('weight_scorm', parseInt(e.target.value))}
                                                        className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white/60"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <label className="text-[10px] font-black text-white/40 uppercase tracking-widest pl-1">Peso Preguntas (%)</label>
                                                    <input
                                                        type="number"
                                                        value={editingCourse.config?.weight_quiz || 0}
                                                        onChange={(e) => updateConfig('weight_quiz', parseInt(e.target.value))}
                                                        className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white/60"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section className="space-y-6">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-4">
                                            <div className="w-10 h-10 rounded-xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                                <HelpCircle className="w-5 h-5 text-brand" />
                                            </div>
                                            <h3 className="text-xl font-black">Banco de Preguntas</h3>
                                        </div>
                                        <button
                                            onClick={() => {
                                                const q = { id: Date.now(), text: "Nueva Pregunta", options: [{ id: 'a', text: 'Opción A' }, { id: 'b', text: 'Opción B' }], correctAnswer: 'a' };
                                                updateConfig('questions', [...(editingCourse.config?.questions || []), q]);
                                            }}
                                            className="text-brand text-[10px] font-black uppercase tracking-widest hover:underline"
                                        >+ Añadir Item</button>
                                    </div>

                                    <div className="space-y-4">
                                        {(editingCourse.config?.questions || []).map((q: any, qi: number) => (
                                            <div key={qi} className="p-6 rounded-2xl bg-black/40 border border-white/5 space-y-4">
                                                <div className="flex justify-between items-center">
                                                    <div className="flex items-center gap-3">
                                                        <span className="text-[10px] font-black text-white/20">PREGUNTA #0{qi + 1}</span>
                                                        <div className="flex items-center gap-2 bg-white/5 px-2 py-1 rounded-lg border border-white/10">
                                                            <Percent className="w-3 h-3 text-brand" />
                                                            <input
                                                                type="number"
                                                                value={q.points || 10}
                                                                onChange={(e) => updateQuestion(qi, 'points', parseInt(e.target.value))}
                                                                className="w-8 bg-transparent text-[10px] font-black text-brand focus:outline-none"
                                                                title="Puntaje de esta pregunta"
                                                            />
                                                            <span className="text-[10px] font-black text-white/40">PTS</span>
                                                        </div>
                                                    </div>
                                                    <Trash2
                                                        onClick={() => {
                                                            const newQs = editingCourse.config.questions.filter((_: any, i: number) => i !== qi);
                                                            updateConfig('questions', newQs);
                                                        }}
                                                        className="w-4 h-4 text-red-500/50 hover:text-red-400 cursor-pointer transition-colors"
                                                    />
                                                </div>
                                                <input
                                                    value={q.text}
                                                    onChange={(e) => updateQuestion(qi, 'text', e.target.value)}
                                                    className="w-full bg-transparent border-b border-white/10 pb-2 text-sm font-medium focus:outline-none focus:border-brand/40"
                                                />
                                                <div className="grid grid-cols-2 gap-4">
                                                    {q.options.map((opt: any, oi: number) => (
                                                        <div key={oi} className="flex items-center gap-2">
                                                            <div
                                                                onClick={() => updateQuestion(qi, 'correctAnswer', opt.id)}
                                                                className={`w-2 h-2 rounded-full cursor-pointer ${q.correctAnswer === opt.id ? 'bg-brand shadow-[0_0_10px_#31D22D]' : 'bg-white/10'}`}
                                                            />
                                                            <input
                                                                value={opt.text}
                                                                onChange={(e) => {
                                                                    const newOpts = [...q.options];
                                                                    newOpts[oi] = { ...newOpts[oi], text: e.target.value };
                                                                    updateQuestion(qi, 'options', newOpts);
                                                                }}
                                                                className="bg-transparent text-xs text-white/60 border-none focus:outline-none w-full"
                                                            />
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </section>

                                <footer className="flex gap-4 pt-8 border-t border-white/10">
                                    <button
                                        onClick={() => setShowPreview(true)}
                                        className="flex-1 bg-white/5 border border-white/10 text-white px-6 py-4 rounded-xl font-black uppercase text-[10px] tracking-widest hover:bg-white/10 transition-all flex items-center justify-center gap-3 group"
                                    >
                                        Vista Previa
                                        <Eye className="w-4 h-4 group-hover:scale-110 transition-transform" />
                                    </button>
                                    <button
                                        onClick={handleSave}
                                        disabled={isSaving}
                                        className="flex-1 bg-brand text-black px-6 py-4 rounded-xl font-black uppercase text-[10px] tracking-widest hover:scale-105 active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-3"
                                    >
                                        {isSaving ? "Guardando..." : "Guardar Cambios"}
                                        {!isSaving && <Save className="w-4 h-4" />}
                                    </button>
                                </footer>
                            </motion.div>
                        ) : (
                            <div className="h-full min-h-[400px] flex flex-col items-center justify-center text-center space-y-4 glass border-dashed">
                                <div className="p-5 bg-white/5 rounded-full mb-4">
                                    <Package className="w-12 h-12 text-white/10" />
                                </div>
                                <h3 className="text-lg font-black text-white/40">Selecciona un curso para configurar</h3>
                                <p className="text-white/20 text-xs max-w-xs">Puedes ajustar el puntaje de aprobación, requisitos SCORM y el banco de preguntas interactivo.</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Preview Modal */}
                {showPreview && editingCourse && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                        onClick={() => setShowPreview(false)}
                    >
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            onClick={(e) => e.stopPropagation()}
                            className="bg-gradient-to-br from-gray-900 to-black border border-brand/30 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto p-8 shadow-2xl"
                        >
                            <header className="flex justify-between items-start mb-8 pb-6 border-b border-white/10">
                                <div>
                                    <h2 className="text-3xl font-black text-white mb-2">Vista Previa del Curso</h2>
                                    <p className="text-white/40 text-sm">Configuración actual antes de guardar</p>
                                </div>
                                <button
                                    onClick={() => setShowPreview(false)}
                                    className="p-2 rounded-xl bg-white/5 hover:bg-red-500/20 text-white/40 hover:text-red-400 transition-all"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </header>

                            <div className="space-y-6">
                                {/* Basic Info */}
                                <section className="glass p-6 rounded-xl">
                                    <h3 className="text-xs font-black text-brand uppercase tracking-widest mb-4">Información Básica</h3>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <p className="text-[10px] text-white/40 uppercase tracking-wider mb-1">Nombre</p>
                                            <p className="text-white font-bold">{editingCourse.name}</p>
                                        </div>
                                        <div>
                                            <p className="text-[10px] text-white/40 uppercase tracking-wider mb-1">Código</p>
                                            <p className="text-brand font-mono font-bold">{editingCourse.code}</p>
                                        </div>
                                    </div>
                                    {editingCourse.config?.scorm_url && (
                                        <div className="mt-4">
                                            <p className="text-[10px] text-white/40 uppercase tracking-wider mb-1">URL SCORM</p>
                                            <p className="text-white/60 text-sm break-all">{editingCourse.config.scorm_url}</p>
                                        </div>
                                    )}
                                </section>

                                {/* Scoring Config */}
                                <section className="glass p-6 rounded-xl">
                                    <h3 className="text-xs font-black text-brand uppercase tracking-widest mb-4">Configuración de Evaluación</h3>
                                    <div className="grid grid-cols-3 gap-4">
                                        <div className="text-center p-4 bg-black/40 rounded-xl border border-white/10">
                                            <p className="text-[10px] text-white/40 uppercase tracking-wider mb-2">Exigencia</p>
                                            <p className="text-3xl font-black text-brand">{editingCourse.config?.passing_score || 0}%</p>
                                        </div>
                                        <div className="text-center p-4 bg-black/40 rounded-xl border border-white/10">
                                            <p className="text-[10px] text-white/40 uppercase tracking-wider mb-2">Peso SCORM</p>
                                            <p className="text-3xl font-black text-white">{editingCourse.config?.weight_scorm || 0}%</p>
                                        </div>
                                        <div className="text-center p-4 bg-black/40 rounded-xl border border-white/10">
                                            <p className="text-[10px] text-white/40 uppercase tracking-wider mb-2">Peso Quiz</p>
                                            <p className="text-3xl font-black text-white">{editingCourse.config?.weight_quiz || 0}%</p>
                                        </div>
                                    </div>
                                </section>

                                {/* Questions Bank */}
                                {editingCourse.config?.questions && editingCourse.config.questions.length > 0 && (
                                    <section className="glass p-6 rounded-xl">
                                        <h3 className="text-xs font-black text-brand uppercase tracking-widest mb-4">
                                            Banco de Preguntas ({editingCourse.config.questions.length})
                                        </h3>
                                        <div className="space-y-3">
                                            {editingCourse.config.questions.map((q: any, idx: number) => (
                                                <div key={idx} className="bg-black/40 p-4 rounded-xl border border-white/10">
                                                    <div className="flex items-start gap-3">
                                                        <span className="text-brand font-black text-sm">#{idx + 1}</span>
                                                        <div className="flex-1">
                                                            <p className="text-white font-medium mb-2">{q.text}</p>
                                                            <div className="grid grid-cols-2 gap-2">
                                                                {q.options?.map((opt: string, i: number) => (
                                                                    <div
                                                                        key={i}
                                                                        className={`text-xs px-3 py-2 rounded-lg ${i === q.correct
                                                                                ? 'bg-green-500/20 text-green-400 border border-green-500/30'
                                                                                : 'bg-white/5 text-white/60'
                                                                            }`}
                                                                    >
                                                                        {opt}
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </section>
                                )}
                            </div>

                            <footer className="mt-8 pt-6 border-t border-white/10 flex justify-end gap-3">
                                <button
                                    onClick={() => setShowPreview(false)}
                                    className="px-6 py-3 rounded-xl bg-white/5 text-white font-bold hover:bg-white/10 transition-all"
                                >
                                    Cerrar
                                </button>
                                <button
                                    onClick={() => {
                                        setShowPreview(false);
                                        handleSave();
                                    }}
                                    className="px-6 py-3 rounded-xl bg-brand text-black font-black hover:scale-105 transition-all flex items-center gap-2"
                                >
                                    Guardar Ahora
                                    <Save className="w-4 h-4" />
                                </button>
                            </footer>
                        </motion.div>
                    </motion.div>
                )}

            </div>
        </div>
    );
}
