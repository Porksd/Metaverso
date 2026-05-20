"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { 
    Plus, Trash2, GripVertical, Check, X, 
    Type, Hash, ListTodo, Star, Save, AlertCircle 
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

interface Question {
    id?: string;
    survey_id: string;
    question_type: 'text' | 'multiple_choice' | 'rating' | 'boolean';
    text_es: string;
    text_ht?: string;
    options_es?: string[];
    options_ht?: string[];
    is_required: boolean;
    order_index: number;
}

interface SurveyBuilderProps {
    surveyId: string;
    onClose: () => void;
}

export default function SurveyBuilder({ surveyId, onClose }: SurveyBuilderProps) {
    const [questions, setQuestions] = useState<Question[]>([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        fetchQuestions();
    }, [surveyId]);

    const fetchQuestions = async () => {
        setLoading(true);
        const { data, error } = await supabase
            .from('survey_questions')
            .select('*')
            .eq('survey_id', surveyId)
            .order('order_index');
        
        if (data) setQuestions(data);
        setLoading(false);
    };

    const addQuestion = (type: Question['question_type']) => {
        const newQuestion: Question = {
            survey_id: surveyId,
            question_type: type,
            text_es: "",
            text_ht: "",
            is_required: true,
            order_index: questions.length,
            options_es: type === 'multiple_choice' ? ["Opción 1", "Opción 2"] : [],
            options_ht: type === 'multiple_choice' ? ["Opsyon 1", "Opsyon 2"] : [],
        };
        setQuestions([...questions, newQuestion]);
    };

    const updateQuestion = (index: number, data: Partial<Question>) => {
        const newQuestions = [...questions];
        newQuestions[index] = { ...newQuestions[index], ...data };
        setQuestions(newQuestions);
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            // First, delete old questions to recreate them (simpler than merge for now)
            // Or perform a smarter sync. For this implementation, we'll recreate all.
            await supabase.from('survey_questions').delete().eq('survey_id', surveyId);
            
            const toSave = questions.map((q, idx) => ({
                survey_id: surveyId,
                question_type: q.question_type,
                text_es: q.text_es,
                text_ht: q.text_ht,
                options_es: q.options_es,
                options_ht: q.options_ht,
                is_required: q.is_required,
                order_index: idx
            }));

            const { error } = await supabase.from('survey_questions').insert(toSave);
            if (error) throw error;
            
            alert("Preguntas guardadas correctamente");
            onClose();
        } catch (error: any) {
            alert("Error al guardar: " + error.message);
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <div className="p-20 text-center uppercase font-black text-white/20 text-xs tracking-widest">Cargando constructor...</div>;

    return (
        <div className="p-6 md:p-10 flex flex-col h-full bg-black/20">
            {/* Toolbar */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-10">
                <button onClick={() => addQuestion('rating')} className="p-4 bg-white/5 border border-white/10 rounded-2xl flex flex-col items-center gap-2 hover:bg-white/10 hover:border-brand/40 transition-all group">
                    <Star className="w-5 h-5 text-brand group-hover:scale-110 transition-transform" />
                    <span className="text-[10px] font-black uppercase tracking-widest">Valoración (1-5)</span>
                </button>
                <button onClick={() => addQuestion('multiple_choice')} className="p-4 bg-white/5 border border-white/10 rounded-2xl flex flex-col items-center gap-2 hover:bg-white/10 hover:border-brand/40 transition-all group">
                    <ListTodo className="w-5 h-5 text-brand group-hover:scale-110 transition-transform" />
                    <span className="text-[10px] font-black uppercase tracking-widest">Opción Múltiple</span>
                </button>
                <button onClick={() => addQuestion('text')} className="p-4 bg-white/5 border border-white/10 rounded-2xl flex flex-col items-center gap-2 hover:bg-white/10 hover:border-brand/40 transition-all group">
                    <Type className="w-5 h-5 text-brand group-hover:scale-110 transition-transform" />
                    <span className="text-[10px] font-black uppercase tracking-widest">Texto Abierto</span>
                </button>
                <button onClick={() => addQuestion('boolean')} className="p-4 bg-white/5 border border-white/10 rounded-2xl flex flex-col items-center gap-2 hover:bg-white/10 hover:border-brand/40 transition-all group">
                    <Check className="w-5 h-5 text-brand group-hover:scale-110 transition-transform" />
                    <span className="text-[10px] font-black uppercase tracking-widest">Sí / No</span>
                </button>
            </div>

            <div className="space-y-6 flex-1 pb-20">
                {questions.length === 0 && (
                    <div className="py-20 text-center bg-white/[0.02] border-2 border-dashed border-white/5 rounded-3xl">
                        <AlertCircle className="w-10 h-10 text-white/10 mx-auto mb-4" />
                        <p className="text-white/20 font-black uppercase tracking-widest text-[10px]">Agrega tipos de preguntas desde el panel superior</p>
                    </div>
                )}

                <AnimatePresence>
                    {questions.map((q, idx) => (
                        <motion.div 
                            key={idx} 
                            initial={{ opacity: 0, x: -20 }} 
                            animate={{ opacity: 1, x: 0 }} 
                            exit={{ opacity: 0, scale: 0.95 }}
                            className="bg-white/5 border border-white/10 rounded-2xl overflow-hidden group/item"
                        >
                            <div className="p-4 bg-white/[0.02] border-b border-white/5 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-lg bg-black/40 flex items-center justify-center text-[10px] font-black text-brand border border-white/5">{idx + 1}</div>
                                    <div className="text-[10px] font-black uppercase tracking-widest text-white/40">{q.question_type}</div>
                                </div>
                                <button 
                                    onClick={() => setQuestions(questions.filter((_, i) => i !== idx))}
                                    className="p-2 text-white/20 hover:text-red-500 transition-colors"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>

                            <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                                {/* Español */}
                                <div className="space-y-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <div className="w-1.5 h-1.5 rounded-full bg-brand" />
                                        <span className="text-[10px] font-black uppercase text-white/40">Pregunta (ES)</span>
                                    </div>
                                    <textarea 
                                        value={q.text_es}
                                        onChange={(e) => updateQuestion(idx, { text_es: e.target.value })}
                                        className="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-sm focus:border-brand/40 outline-none transition-all resize-none"
                                        placeholder="Escribe la pregunta en español..."
                                    />
                                    
                                    {q.question_type === 'multiple_choice' && (
                                        <div className="space-y-2 pl-4 border-l border-white/5">
                                            {q.options_es?.map((opt, oIdx) => (
                                                <div key={oIdx} className="flex gap-2">
                                                    <input 
                                                        value={opt}
                                                        onChange={(e) => {
                                                            const newOpt = [...(q.options_es || [])];
                                                            newOpt[oIdx] = e.target.value;
                                                            updateQuestion(idx, { options_es: newOpt });
                                                        }}
                                                        className="flex-1 bg-white/5 border border-white/5 rounded-lg px-3 py-2 text-xs focus:border-brand/20 outline-none"
                                                    />
                                                    <button onClick={() => {
                                                        const newOpt = q.options_es?.filter((_, i) => i !== oIdx);
                                                        updateQuestion(idx, { options_es: newOpt });
                                                    }} className="p-2 text-white/10 hover:text-red-500"><X className="w-3 h-3" /></button>
                                                </div>
                                            ))}
                                            <button onClick={() => updateQuestion(idx, { options_es: [...(q.options_es || []), "Nueva opción"] })} className="text-[9px] font-black uppercase text-brand/50 hover:text-brand flex items-center gap-1 transition-colors"><Plus className="w-3 h-3" /> Agregar Opción</button>
                                        </div>
                                    )}
                                </div>

                                {/* Kreyòl */}
                                <div className="space-y-4">
                                    <div className="flex items-center gap-2 mb-2">
                                        <div className="w-1.5 h-1.5 rounded-full bg-white/20" />
                                        <span className="text-[10px] font-black uppercase text-white/40">Keksyon (HT)</span>
                                    </div>
                                    <textarea 
                                        value={q.text_ht}
                                        onChange={(e) => updateQuestion(idx, { text_ht: e.target.value })}
                                        className="w-full bg-black/40 border border-white/10 rounded-xl p-4 text-sm focus:border-brand/40 outline-none transition-all resize-none"
                                        placeholder="Escribe la pregunta en kreyòl..."
                                    />

                                    {q.question_type === 'multiple_choice' && (
                                        <div className="space-y-2 pl-4 border-l border-white/5">
                                            {q.options_ht?.map((opt, oIdx) => (
                                                <div key={oIdx} className="flex gap-2">
                                                    <input 
                                                        value={opt}
                                                        onChange={(e) => {
                                                            const newOpt = [...(q.options_ht || [])];
                                                            newOpt[oIdx] = e.target.value;
                                                            updateQuestion(idx, { options_ht: newOpt });
                                                        }}
                                                        className="flex-1 bg-white/5 border border-white/5 rounded-lg px-3 py-2 text-xs focus:border-brand/20 outline-none"
                                                    />
                                                    <button onClick={() => {
                                                        const newOpt = q.options_ht?.filter((_, i) => i !== oIdx);
                                                        updateQuestion(idx, { options_ht: newOpt });
                                                    }} className="p-2 text-white/10 hover:text-red-500"><X className="w-3 h-3" /></button>
                                                </div>
                                            ))}
                                            <button onClick={() => updateQuestion(idx, { options_ht: [...(q.options_ht || []), "Opsyon nouvo"] })} className="text-[9px] font-black uppercase text-white/10 hover:text-white flex items-center gap-1 transition-colors"><Plus className="w-3 h-3" /> Ajoute Opsyon</button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </motion.div>
                    ))}
                </AnimatePresence>
            </div>

            {/* Footer Actions */}
            <div className="fixed bottom-0 left-0 right-0 p-6 bg-black/80 backdrop-blur-md border-t border-white/10 flex justify-center gap-4 z-50">
                <button 
                    onClick={onClose}
                    className="px-10 py-4 rounded-xl border border-white/10 text-white/40 font-black uppercase text-xs tracking-widest hover:text-white transition-all"
                >
                    Descartar Cambios
                </button>
                <button 
                    onClick={handleSave}
                    disabled={saving || questions.length === 0}
                    className="px-10 py-4 bg-brand text-black rounded-xl font-black uppercase text-xs tracking-widest hover:scale-[1.05] disabled:opacity-50 disabled:hover:scale-100 transition-all flex items-center gap-2 shadow-xl shadow-brand/20"
                >
                    {saving ? <div className="animate-spin w-4 h-4 border-2 border-black border-t-transparent rounded-full" /> : <Save className="w-4 h-4" />}
                    Guardar Configuración
                </button>
            </div>
        </div>
    );
}
