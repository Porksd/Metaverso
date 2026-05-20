"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { motion, AnimatePresence } from "framer-motion";
import { Star, Check, ArrowRight, ClipboardCheck } from "lucide-react";

interface Question {
    id: string;
    question_type: 'text' | 'multiple_choice' | 'rating' | 'boolean';
    text_es: string;
    text_ht?: string;
    options_es?: string[];
    options_ht?: string[];
    is_required: boolean;
}

interface SurveyEngineProps {
    surveyId: string;
    studentId: string;
    enrollmentId: string;
    onComplete: () => void;
    language?: 'es' | 'ht';
}

export default function SurveyEngine({ surveyId, studentId, enrollmentId, onComplete, language = 'es' }: SurveyEngineProps) {
    const [questions, setQuestions] = useState<Question[]>([]);
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const [loading, setLoading] = useState(true);
    const [submitted, setSubmitted] = useState(false);
    const [metadata, setMetadata] = useState<any>(null);

    useEffect(() => {
        const fetchSurveyAndMetadata = async () => {
            // 1. Fetch Questions
            const { data: qData } = await supabase
                .from('survey_questions')
                .select('*')
                .eq('survey_id', surveyId)
                .order('order_index');
            if (qData) setQuestions(qData);

            // 2. Fetch Metadata (Student, Company, Course)
            const { data: enrollment } = await supabase
                .from('enrollments')
                .select(`
                    id,
                    courses (id, name),
                    students (
                        id, 
                        first_name, 
                        last_name, 
                        rut, 
                        passport, 
                        position, 
                        company_name,
                        client_id,
                        companies (name)
                    )
                `)
                .eq('id', enrollmentId)
                .single();

            if (enrollment) {
                const s = enrollment.students as any;
                const c = enrollment.courses as any;
                setMetadata({
                    fecha: new Date().toISOString(),
                    empresa_principal: s.companies?.name || 'N/A',
                    nombre_completo: `${s.first_name} ${s.last_name}`,
                    identificacion: s.rut || s.passport || 'N/A',
                    cargo: s.position || 'N/A',
                    empresa_colaboradora: s.company_name || 'N/A',
                    nombre_curso: c.name || 'N/A'
                });
            }

            setLoading(false);
        };
        fetchSurveyAndMetadata();
    }, [surveyId, enrollmentId]);

    const handleAnswer = (questionId: string, value: any) => {
        setAnswers(prev => ({ ...prev, [questionId]: value }));
    };

    const handleSubmit = async () => {
        // Simple validation
        const missing = questions.filter(q => q.is_required && !answers[q.id]);
        if (missing.length > 0) {
            alert(language === 'es' ? "Por favor completa todas las preguntas obligatorias." : "Tanpri ranpli tout keksyon obligatwa yo.");
            return;
        }

        const { error } = await supabase.from('survey_responses').insert([{
            survey_id: surveyId,
            student_id: studentId,
            enrollment_id: enrollmentId,
            answers: { ...answers, _metadata: metadata }
        }]);

        if (error) {
            alert("Error: " + error.message);
        } else {
            setSubmitted(true);
            setTimeout(() => onComplete(), 2000);
        }
    };

    if (loading) return <div className="p-10 text-center animate-pulse text-white/40 uppercase font-black text-xs">Chajman...</div>;

    if (submitted) {
        return (
            <motion.div initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} className="p-10 text-center space-y-4">
                <div className="w-20 h-20 bg-brand/20 rounded-full flex items-center justify-center mx-auto border border-brand/50 shadow-[0_0_30px_rgba(174,255,0,0.3)]">
                    <ClipboardCheck className="w-10 h-10 text-brand" />
                </div>
                <h2 className="text-2xl font-black text-white italic">{language === 'es' ? '¡Gracias por tu Feedback!' : 'Mèsi pou repons ou!'}</h2>
                <p className="text-white/40 text-sm">{language === 'es' ? 'Tu respuesta ha sido enviada exitosamente.' : 'Repons ou voye kòrèkteman.'}</p>
            </motion.div>
        );
    }

    return (
        <div className="space-y-8 p-4 md:p-8 max-w-2xl mx-auto pb-20">
            <header className="text-center space-y-2">
                <h3 className="text-2xl font-black italic tracking-tighter text-white uppercase">
                    {language === 'es' ? 'Encuesta de Satisfacción' : 'Sondaj Satisfaksyon'}
                </h3>
                <p className="text-white/40 text-[10px] font-black uppercase tracking-[0.2em]">
                    {language === 'es' ? 'Tu opinión nos ayuda a mejorar' : 'Opinyon ou ede nou amelyore'}
                </p>
            </header>

            <div className="space-y-10">
                {questions.map((q, idx) => (
                    <motion.div 
                        initial={{ opacity: 0, y: 10 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        key={q.id} 
                        className="space-y-4"
                    >
                        <div className="flex gap-4">
                            <span className="text-brand font-black italic text-lg opacity-50">#0{idx+1}</span>
                            <p className="text-white font-bold leading-relaxed">
                                {language === 'es' ? q.text_es : (q.text_ht || q.text_es)}
                                {q.is_required && <span className="text-brand ml-1">*</span>}
                            </p>
                        </div>

                        {/* Rating 1-5 */}
                        {q.question_type === 'rating' && (
                            <div className="flex justify-between gap-2 max-w-sm mx-auto">
                                {[1, 2, 3, 4, 5].map(num => (
                                    <button
                                        key={num}
                                        onClick={() => handleAnswer(q.id, num)}
                                        className={`w-12 h-12 rounded-xl font-black transition-all flex items-center justify-center border ${
                                            answers[q.id] === num 
                                            ? 'bg-brand text-black border-brand scale-110 shadow-lg shadow-brand/20' 
                                            : 'bg-white/5 text-white/40 border-white/10 hover:border-brand/40'
                                        }`}
                                    >
                                        {num}
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Boolean (Yes/No) */}
                        {q.question_type === 'boolean' && (
                            <div className="grid grid-cols-2 gap-4">
                                {[
                                    { value: true, label_es: "SÍ", label_ht: "WI" },
                                    { value: false, label_es: "NO", label_ht: "NON" }
                                ].map(opt => (
                                    <button
                                        key={String(opt.value)}
                                        onClick={() => handleAnswer(q.id, opt.value)}
                                        className={`p-4 rounded-xl font-black transition-all border text-xs tracking-widest ${
                                            answers[q.id] === opt.value 
                                            ? 'bg-brand text-black border-brand' 
                                            : 'bg-white/5 text-white/40 border-white/10 hover:border-brand/40'
                                        }`}
                                    >
                                        {language === 'es' ? opt.label_es : opt.label_ht}
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Multiple Choice */}
                        {q.question_type === 'multiple_choice' && (
                            <div className="space-y-2">
                                {(language === 'es' ? q.options_es : (q.options_ht || q.options_es))?.map((opt, oIdx) => (
                                    <button
                                        key={oIdx}
                                        onClick={() => handleAnswer(q.id, opt)}
                                        className={`w-full p-4 rounded-xl font-bold text-left transition-all border text-sm flex justify-between items-center group ${
                                            answers[q.id] === opt 
                                            ? 'bg-brand/10 text-brand border-brand/50' 
                                            : 'bg-white/[0.02] text-white/60 border-white/5 hover:border-white/20'
                                        }`}
                                    >
                                        {opt}
                                        <div className={`w-4 h-4 rounded-full border-2 transition-all flex items-center justify-center ${
                                            answers[q.id] === opt ? 'border-brand bg-brand' : 'border-white/10'
                                        }`}>
                                            {answers[q.id] === opt && <Check className="w-3 h-3 text-black" />}
                                        </div>
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Text Input */}
                        {q.question_type === 'text' && (
                            <textarea
                                value={answers[q.id] || ""}
                                onChange={(e) => handleAnswer(q.id, e.target.value)}
                                placeholder={language === 'es' ? "Escribe aquí tu respuesta..." : "Ekri repons ou isit la..."}
                                className="w-full bg-white/5 border border-white/10 rounded-xl p-4 text-sm focus:border-brand outline-none transition-all min-h-[100px] resize-none"
                            />
                        )}
                    </motion.div>
                ))}
            </div>

            <button
                onClick={handleSubmit}
                className="w-full py-5 bg-brand text-black rounded-2xl font-black uppercase tracking-widest text-xs hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-2xl shadow-brand/30"
            >
                {language === 'es' ? 'Enviar Encuesta' : 'Voye Sondaj'}
                <ArrowRight className="w-4 h-4" />
            </button>
        </div>
    );
}
