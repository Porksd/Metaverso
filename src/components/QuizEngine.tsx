"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { CheckCircle2, AlertCircle, ArrowRight, Home, Download, Lock, Check } from "lucide-react";
import { supabase } from "@/lib/supabase";

type QuestionType = 'single' | 'multiple' | 'truefalse';

interface Question {
    id: string;
    type?: QuestionType;
    text: string;
    text_ht?: string; // Soporte para creole
    options: { id: string; text: string; text_ht?: string }[]; // Soporte para creole
    correctAnswer: string | string[];
    weight?: number; // optional weight for this question (default 1)
}

interface CourseConfig {
    id: string;
    title: string;
    questions: Question[];
    passingScore: number;
}

interface QuizEngineProps {
    config?: CourseConfig;
    questions?: Question[]; // Direct prop support
    passingScore?: number; // Direct prop support
    user?: any;
    courseId?: string; // New prop for persistence
    enrollmentId?: string; // New prop for persistence
    onFinish?: (score: number) => void;
    onComplete?: (score: number, passed: boolean) => void; // New callback
    currentEnrollment?: any;
    persistScore?: boolean; // New prop to control side-effects
    language?: string; // Nuevo: soporte para idioma
    forceFinished?: boolean;
}

const translations: any = {
    es: {
        error: "Error: Pregunta no encontrada",
        quiz_finished: "¡Quiz Finalizado!",
        activity_completed: "¡Actividad Completada!",
        answered_correctly: (correct: number, total: number) => `Has respondido correctamente ${correct} de ${total} preguntas.`,
        activity_success: "Has completado satisfactoriamente los ejercicios de esta sección.",
        hits: "Aciertos",
        weight: "Ponderación (80%)",
        try_again: "Intentar Nuevamente",
        repeat_activity: "Repetir Actividad",
        progress_saved: "* Tu progreso ha sido guardado. Completa el contenido SCORM para alcanzar el 90% requerido para aprobar.",
        question: "Pregunta",
        of: "de",
        multiple_selection: "Múltiple Selección",
        finish_eval: "Finalizar Evaluación",
        next_question: "Siguiente Pregunta"
    },
    ht: {
        error: "Erè: Kesyon pa jwenn",
        quiz_finished: "Egzamen fini!",
        activity_completed: "Aktivite konplè!",
        answered_correctly: (correct: number, total: number) => `Ou reponn kòrèkteman ${correct} nan ${total} kesyon.`,
        activity_success: "Ou te konplete avèk siksè egzèsis yo nan seksyon sa a.",
        hits: "Siksè",
        weight: "Pwa (80%)",
        try_again: "Eseye ankò",
        repeat_activity: "Repete aktivite",
        progress_saved: "* Pwogrè ou sove. Ranpli kontni SCORM pou rive nan 90% obligatwa pou pase.",
        question: "Kesyon",
        of: "nan",
        multiple_selection: "Plis pase yon chwa",
        finish_eval: "Fini Evalyasyon",
        next_question: "Kesyon Pwochen"
    }
};

export default function QuizEngine({ config, questions: propQuestions, passingScore: propPassingScore, user, courseId, enrollmentId, onFinish, onComplete, currentEnrollment, persistScore = true, language = 'es', forceFinished = false }: QuizEngineProps) {
    const t = translations[language] || translations.es;
    const baseQuestions = propQuestions || config?.questions || [];
    const seenKeys = new Set<string>();
    const finalQuestions: Question[] = Array.isArray(baseQuestions)
        ? baseQuestions.filter((q) => {
            const key = (q.id || q.text || '').trim().toLowerCase();
            if (!key) return true;
            if (seenKeys.has(key)) return false;
            seenKeys.add(key);
            return true;
        })
        : [];

    const resolveQuestionType = (question?: Question): QuestionType => {
        if (!question) return 'single';
        const answersArray = Array.isArray(question.correctAnswer)
            ? question.correctAnswer.filter(Boolean)
            : typeof question.correctAnswer === 'string'
                ? question.correctAnswer.split('|').map((a) => a.trim()).filter(Boolean)
                : [];
        if (question.type === 'multiple' && answersArray.length > 1) return 'multiple';
        return 'single';
    };

    const normalizeCorrectAnswers = (question: Question): string[] => {
        if (Array.isArray(question.correctAnswer)) return question.correctAnswer.filter(Boolean);
        if (typeof question.correctAnswer === 'string') {
            return question.correctAnswer
                .split('|')
                .map((a) => a.trim())
                .filter(Boolean);
        }
        return [];
    };

    const [currentQuestionIdx, setCurrentQuestionIdx] = useState(0);
    const [answers, setAnswers] = useState<{ [key: string]: string | string[] }>({});
    const [isFinished, setIsFinished] = useState(currentEnrollment?.status === 'completed' || forceFinished);
    const [score, setScore] = useState(currentEnrollment?.quiz_score || currentEnrollment?.last_exam_score || currentEnrollment?.best_score || currentEnrollment?.score || 0);
    const [correctCount, setCorrectCount] = useState(0);

    const targetEnrollmentId = enrollmentId || currentEnrollment?.id;
    const targetCourseId = courseId || config?.id;
    const quizDraftKey = targetEnrollmentId ? `quiz-draft:${targetEnrollmentId}` : (targetCourseId ? `quiz-draft:course:${targetCourseId}` : null);

    useEffect(() => {
        if (!quizDraftKey || isFinished || forceFinished) return;
        try {
            const rawDraft = localStorage.getItem(quizDraftKey);
            if (!rawDraft) return;
            const parsedDraft = JSON.parse(rawDraft);
            if (parsedDraft?.answers && typeof parsedDraft.answers === 'object') {
                setAnswers(parsedDraft.answers);
            }
            if (typeof parsedDraft?.currentQuestionIdx === 'number' && parsedDraft.currentQuestionIdx >= 0 && parsedDraft.currentQuestionIdx < finalQuestions.length) {
                setCurrentQuestionIdx(parsedDraft.currentQuestionIdx);
            }
        } catch (error) {
            console.error('Error restoring quiz draft:', error);
        }
    }, [quizDraftKey, forceFinished, isFinished, finalQuestions.length]);

    useEffect(() => {
        if (!quizDraftKey || isFinished || forceFinished) return;
        try {
            localStorage.setItem(quizDraftKey, JSON.stringify({
                answers,
                currentQuestionIdx
            }));
        } catch (error) {
            console.error('Error saving quiz draft:', error);
        }
    }, [answers, currentQuestionIdx, quizDraftKey, isFinished, forceFinished]);

    useEffect(() => {
        if (!forceFinished) return;
        setIsFinished(true);
        setScore(currentEnrollment?.quiz_score || currentEnrollment?.last_exam_score || currentEnrollment?.best_score || 0);
    }, [forceFinished, currentEnrollment?.quiz_score, currentEnrollment?.last_exam_score, currentEnrollment?.best_score]);

    // Resolve props vs config
    const finalPassingScore = propPassingScore || config?.passingScore || 60;

    const currentQuestion = finalQuestions[currentQuestionIdx];
    const questionType: QuestionType = resolveQuestionType(currentQuestion);

    if (!currentQuestion && !isFinished) return <div className="text-center text-white/40">{t.error}</div>;

    const handleAnswer = (optionId: string) => {
        if (questionType === 'multiple') {
            const rawAnswers = answers[currentQuestion.id];
            const currentAnswers = Array.isArray(rawAnswers)
                ? rawAnswers
                : rawAnswers
                    ? [rawAnswers]
                    : [];

            if (currentAnswers.includes(optionId)) {
                setAnswers({ ...answers, [currentQuestion.id]: currentAnswers.filter(id => id !== optionId) });
            } else {
                setAnswers({ ...answers, [currentQuestion.id]: [...currentAnswers, optionId] });
            }
        } else {
            // Single or True/False: always overwrite with a single string
            setAnswers({ ...answers, [currentQuestion.id]: optionId });
        }
    };

    const nextQuestion = async () => {
        if (currentQuestionIdx < finalQuestions.length - 1) {
            setCurrentQuestionIdx(currentQuestionIdx + 1);
        } else {
            await finishQuiz();
        }
    };

    const finishQuiz = async () => {
        // Weighted scoring: each question may have a weight (default 1).
        // For single/truefalse: full weight if answer matches. For multiple: full weight only if all correct selected and no incorrect ones.
        let totalWeight = 0;
        let earnedWeight = 0;
        const perQuestion: Array<{ id: string; correct: boolean; weight: number }> = [];

        finalQuestions.forEach((q: Question) => {
            const qWeight = typeof q.weight === 'number' && q.weight > 0 ? q.weight : 1;
            totalWeight += qWeight;

            const userAnswer = answers[q.id];
            const type = resolveQuestionType(q);
            const correctArr = normalizeCorrectAnswers(q);
            let correct = false;

            if (type === 'multiple') {
                const userArr = Array.isArray(userAnswer)
                    ? userAnswer
                    : userAnswer
                        ? [userAnswer]
                        : [];

                const allSelected = correctArr.every(id => userArr.includes(id));
                const noIncorrect = userArr.every(id => correctArr.includes(id));
                if (allSelected && noIncorrect && correctArr.length > 0) correct = true;
            } else {
                const expected = correctArr[0] ?? (typeof q.correctAnswer === 'string' ? q.correctAnswer : '');
                const userSingle = Array.isArray(userAnswer) ? userAnswer[0] : userAnswer;
                if (expected && userSingle === expected) correct = true;
            }

            if (correct) earnedWeight += qWeight;
            perQuestion.push({ id: q.id, correct, weight: qWeight });
        });

        const finalScore = totalWeight > 0 ? Math.round((earnedWeight / totalWeight) * 100) : 0;
        const totalCorrect = perQuestion.filter(p => p.correct).length;
        
        setScore(finalScore);
        setCorrectCount(totalCorrect);
        setIsFinished(true);

        const passed = finalScore >= finalPassingScore;

        // Persistir en Supabase
        // Solo persistir si tenemos un enrollment ID válido (los dummy IDs o preview-admin se saltan)
        const isValidUUID = targetEnrollmentId && targetEnrollmentId.length > 20 && targetEnrollmentId !== 'preview-admin' && !targetEnrollmentId.includes('dummy');

        if (isValidUUID) {
            try {
                // SOLO persistir estado directamente si NO hay un handler onComplete externo.
                // Cuando onComplete existe (ej: CoursePlayer evaluación), el componente padre
                // maneja la lógica de estado con verificación de encuestas pendientes.
                if (persistScore && !onComplete) {
                    await supabase
                        .from('enrollments')
                        .update({
                            status: passed ? 'completed' : 'in_progress',
                            best_score: finalScore,
                            completed_at: passed ? new Date().toISOString() : null
                        })
                        .eq('id', targetEnrollmentId);
                    console.log("QuizEngine: Enrollment updated directly (persistScore=true, no onComplete)");
                }

                // 2. Guardar en course_progress para tracking de completitud
                await supabase.from("course_progress").insert({
                    enrollment_id: targetEnrollmentId,
                    module_type: "quiz",
                    raw_score: finalScore,
                    scaled_score: finalScore,
                    completed_at: new Date().toISOString()
                });

                // 3. Guardar log detallado (incluyendo respuestas)
                await supabase.from('activity_logs').insert({
                    enrollment_id: targetEnrollmentId,
                    course_id: targetCourseId,
                    attempt_number: 1, // Podríamos incrementarlo si tuviéramos seguimiento de intentos
                    raw_data: {
                        answers,
                        finalScore,
                        perQuestion,
                        type: 'evaluation_quiz'
                    },
                    score: finalScore,
                    interaction_type: 'final_quiz'
                });

                console.log("QuizEngine: Progress and answers saved successfully");
            } catch (err) {
                console.error("Error saving quiz results:", err);
            }
        }

        if (quizDraftKey) {
            localStorage.removeItem(quizDraftKey);
        }

        if (onFinish) onFinish(finalScore);
        if (onComplete) onComplete(finalScore, passed);
    };

    const isOptionSelected = (optionId: string) => {
        const userAnswer = answers[currentQuestion.id];
        if (questionType === 'multiple') {
            const userArr = Array.isArray(userAnswer)
                ? userAnswer
                : userAnswer
                    ? [userAnswer]
                    : [];
            return userArr.includes(optionId);
        }
        // Single-choice: coerce any previous array to single check against current option
        if (Array.isArray(userAnswer)) {
            return userAnswer[0] === optionId;
        }
        return userAnswer === optionId;
    };

    if (isFinished) {
        // En un sistema ponderado, queremos mostrar cuánto aportó este quiz
        const contribution = Math.round((score / 100) * 80); // Asumiendo 80% de peso para el quiz

        return (
            <div className="flex flex-col items-center justify-center p-8 space-y-6 text-center">
                <div className="w-20 h-20 bg-brand/10 rounded-full flex items-center justify-center">
                    <CheckCircle2 className="w-12 h-12 text-brand" />
                </div>
                <div className="space-y-2">
                    <h2 className="text-3xl font-bold">
                        {persistScore ? t.quiz_finished : t.activity_completed}
                    </h2>
                    <p className="text-white/60">
                        {persistScore 
                            ? t.answered_correctly(correctCount, finalQuestions.length)
                            : t.activity_success
                        }
                    </p>
                </div>

                {persistScore && (
                    <>
                        <div className="grid grid-cols-2 gap-4 w-full max-w-sm">
                            <div className="glass p-4 border-brand/20">
                                <p className="text-[10px] text-white/40 uppercase font-bold tracking-widest">{t.hits}</p>
                                <p className="text-2xl font-black text-brand">{score}%</p>
                            </div>
                            <div className="glass p-4 border-brand/20">
                                <p className="text-[10px] text-white/40 uppercase font-bold tracking-widest">{t.weight}</p>
                                <p className="text-2xl font-black text-brand">{contribution}%</p>
                            </div>
                        </div>

                        <div className="w-full max-w-sm h-2 bg-white/5 rounded-full overflow-hidden">
                            <div className="h-full bg-brand transition-all duration-1000" style={{ width: `${score}%` }} />
                        </div>

                        <p className="text-xs text-brand/60 font-medium italic">
                            {t.progress_saved}
                        </p>
                    </>
                )}

                <div className="flex flex-col w-full max-w-sm gap-3">
                    <button
                        onClick={() => {
                            setAnswers({});
                            setCurrentQuestionIdx(0);
                            setIsFinished(false);
                            if (quizDraftKey) {
                                localStorage.removeItem(quizDraftKey);
                            }
                        }}
                        className="w-full py-4 bg-white/5 text-white/60 font-bold rounded-xl hover:bg-white/10 transition-all text-xs uppercase"
                    >
                        {persistScore ? t.try_again : t.repeat_activity}
                    </button>
                </div>
            </div>
        );
    }

    const hasAnswered = answers[currentQuestion.id] && (Array.isArray(answers[currentQuestion.id]) ? (answers[currentQuestion.id] as string[]).length > 0 : true);

    return (
        <div className="max-w-2xl mx-auto w-full p-4 space-y-8">
            <div className="space-y-4">
                <div className="flex justify-between items-end">
                    <div className="space-y-1">
                        <span className="text-xs text-white/40 font-bold uppercase tracking-widest block">{t.question} {currentQuestionIdx + 1} {t.of} {finalQuestions.length}</span>
                        {questionType === 'multiple' && (
                            <span className="text-[10px] bg-brand/20 text-brand px-2 py-0.5 rounded font-black uppercase">{t.multiple_selection}</span>
                        )}
                    </div>
                    <span className="text-brand font-black tabular-nums">{Math.round(((currentQuestionIdx) / finalQuestions.length) * 100)}%</span>
                </div>
                <div className="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                    <div className="h-full bg-brand transition-all duration-500" style={{ width: `${((currentQuestionIdx + 1) / finalQuestions.length) * 100}%` }} />
                </div>
            </div>

            <motion.div
                key={currentQuestionIdx}
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                className="space-y-8"
            >
                <h3 className="text-2xl font-black leading-tight">
                    {(language === 'ht' && currentQuestion.text_ht) ? currentQuestion.text_ht : currentQuestion.text}
                </h3>

                <div className="grid grid-cols-1 gap-3">
                    {currentQuestion.options.map((opt, optIdx) => (
                        <button
                            key={`q${currentQuestion.id}-${opt.id}-${optIdx}`}
                            onClick={() => handleAnswer(opt.id)}
                            className={`p-5 text-left rounded-2xl border-2 transition-all flex items-center justify-between group relative overflow-hidden ${isOptionSelected(opt.id)
                                ? 'bg-brand/5 border-brand text-brand shadow-[0_0_20px_rgba(49,210,45,0.1)]'
                                : 'bg-white/[0.02] border-white/5 text-white/60 hover:bg-white/5 hover:border-white/10'
                                }`}
                        >
                            <span className="font-bold text-sm md:text-base pr-8 relative z-10">
                                {(language === 'ht' && opt.text_ht) ? opt.text_ht : opt.text}
                            </span>
                            <div className={`w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all shrink-0 relative z-10 ${isOptionSelected(opt.id) ? 'bg-brand border-brand' : 'border-white/10'
                                }`}>
                                {isOptionSelected(opt.id) && <Check className="w-4 h-4 text-black stroke-[4]" />}
                            </div>
                        </button>
                    ))}
                </div>
            </motion.div>

            <div className="pt-8">
                <button
                    onClick={nextQuestion}
                    disabled={!hasAnswered}
                    className="group w-full py-5 bg-brand disabled:opacity-20 disabled:grayscale disabled:cursor-not-allowed text-black font-black uppercase text-sm tracking-widest rounded-2xl flex items-center justify-center gap-3 hover:scale-[1.01] active:scale-[0.98] transition-all shadow-xl shadow-brand/10 font-mono"
                >
                    {currentQuestionIdx === finalQuestions.length - 1 ? t.finish_eval : t.next_question}
                    <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                </button>
            </div>
        </div>
    );
}
