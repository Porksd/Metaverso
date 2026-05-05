"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { CheckCircle2, AlertCircle, ArrowRight, Home, Download, Lock, Check, Lightbulb } from "lucide-react";
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
    feedback?: string; // Retroalimentación opcional por pregunta
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
        activity_done: "¡Actividad Realizada!",
        activity_done_desc: "Has respondido todas las preguntas correctamente.",
        activity_pending: "Actividad Pendiente",
        activity_pending_desc: "Algunas respuestas son incorrectas. Inténtalo nuevamente hasta responder todo correctamente.",
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
        next_question: "Siguiente Pregunta",
        confirm_answers: "Finalizar Actividad",
        summary_title: "Resumen de actividad",
        quiz_status_line: "Quiz:",
        finalized: "Finalizado",
        answer_label: "Respuesta",
        no_answer: "Sin respuesta",
        correct: "Correcta",
        incorrect: "Incorrecta",
        eval_intro_title: "Evaluación Final del Curso",
        eval_intro_subtitle: "¡Es el momento de demostrar lo que aprendiste!",
        eval_intro_body: "Lee cada pregunta con calma antes de responder. Una vez que selecciones tu respuesta y avances, no podrás volver atrás. Recuerda que cuentas con hasta 3 intentos para alcanzar el puntaje mínimo de aprobación. ¡Confía en tus conocimientos!",
        eval_intro_start: "Comenzar Evaluación →"
    },
    ht: {
        error: "Erè: Kesyon pa jwenn",
        quiz_finished: "Egzamen fini!",
        activity_completed: "Aktivite konplè!",
        activity_done: "Aktivite Reyalize!",
        activity_done_desc: "Ou reponn tout kesyon yo kòrèkteman.",
        activity_pending: "Aktivite An Atant",
        activity_pending_desc: "Gen kèk repons ki mal. Eseye ankò jiskaske ou reponn tout kòrèkteman.",
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
        next_question: "Kesyon Pwochen",
        confirm_answers: "Fini Aktivite",
        summary_title: "Rezime aktivite",
        quiz_status_line: "Egzamen:",
        finalized: "Fini",
        answer_label: "Repons",
        no_answer: "Pa gen repons",
        correct: "Kòrèk",
        incorrect: "Pa kòrèk",
        eval_intro_title: "Evalyasyon Final Kou a",
        eval_intro_subtitle: "Li lè pou montre sa ou te aprann!",
        eval_intro_body: "Li chak kesyon dousman anvan ou reponn. Yon fwa ou chwazi repons ou epi ou avanse, ou pa ka retounen. Sonje ou gen jiska 3 eseye pou rive nan nòt minimòm pou pase. Fè konfyans nan konesans ou!",
        eval_intro_start: "Kòmanse Evalyasyon →"
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
    const [wasPassed, setWasPassed] = useState<boolean | null>(forceFinished ? true : null);
    const [questionSummaries, setQuestionSummaries] = useState<Array<{ questionId: string; selectedText: string; correct: boolean }>>([]);
    const [showEvalIntro, setShowEvalIntro] = useState(persistScore && !forceFinished && !(currentEnrollment?.status === 'completed'));

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
        setWasPassed(true);
        setScore(currentEnrollment?.quiz_score || currentEnrollment?.last_exam_score || currentEnrollment?.best_score || 0);
    }, [forceFinished, currentEnrollment?.quiz_score, currentEnrollment?.last_exam_score, currentEnrollment?.best_score]);

    // Resolve props vs config
    const finalPassingScore = propPassingScore || config?.passingScore || 60;

    const currentQuestion = finalQuestions[currentQuestionIdx];
    const questionType: QuestionType = resolveQuestionType(currentQuestion);

    const getOptionText = (question: Question, optionId: string): string => {
        const option = question.options.find((opt) => opt.id === optionId);
        if (!option) return optionId;
        return (language === 'ht' && option.text_ht) ? option.text_ht : option.text;
    };

    const getSelectedAnswerText = (question: Question, userAnswer: string | string[] | undefined): string => {
        const selectedIds = Array.isArray(userAnswer)
            ? userAnswer
            : userAnswer
                ? [userAnswer]
                : [];

        if (selectedIds.length === 0) return t.no_answer;
        return selectedIds.map((id) => getOptionText(question, id)).join(', ');
    };

    if (showEvalIntro) {
        return (
            <div className="relative mx-auto w-full max-w-2xl overflow-hidden rounded-[28px] border border-white/10 bg-[#050a08] shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
                <div
                    className="absolute inset-0 opacity-50"
                    style={{
                        backgroundImage: "linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px)",
                        backgroundSize: "48px 48px"
                    }}
                />
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(49,210,45,0.12),transparent_34%),radial-gradient(circle_at_82%_18%,rgba(34,211,238,0.12),transparent_30%),linear-gradient(135deg,rgba(0,0,0,0.78),rgba(2,8,6,0.55),rgba(3,13,18,0.72))]" />

                <div className="absolute -right-14 top-1/2 hidden h-[260px] w-[260px] -translate-y-1/2 md:block">
                    <div className="absolute inset-0 rounded-full border border-brand/15" />
                    <div className="absolute inset-8 rounded-full border border-cyan-400/10" />
                    <div className="absolute inset-16 rounded-full border border-white/10" />
                    <div className="absolute left-6 right-6 top-1/2 h-px bg-gradient-to-r from-transparent via-brand/40 to-transparent" />
                    <div className="absolute bottom-12 left-1/2 h-40 w-px -translate-x-1/2 bg-gradient-to-b from-transparent via-cyan-400/30 to-transparent" />
                    <div className="absolute right-10 top-10 h-24 w-24 rounded-2xl border border-white/10 bg-white/[0.03] backdrop-blur-sm" />
                    <div className="absolute bottom-10 left-8 h-20 w-28 rounded-full border border-brand/15 bg-brand/[0.05]" />
                    <div className="absolute right-24 top-[42%] h-3 w-3 rounded-full bg-brand shadow-[0_0_18px_rgba(49,210,45,0.65)]" />
                </div>

                <div className="relative flex min-h-[480px] items-center px-6 py-12 sm:px-8 md:px-10">
                    <div className="max-w-xl space-y-6">
                        <div className="inline-flex items-center gap-2 rounded-full border border-brand/20 bg-brand/10 px-4 py-2 text-[11px] font-black uppercase tracking-[0.22em] text-brand">
                            <div className="h-1.5 w-1.5 rounded-full bg-brand animate-pulse" />
                            Evaluacion final
                        </div>

                        <div className="space-y-3">
                            <h2 className="text-4xl font-black leading-none tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-brand via-emerald-300 to-cyan-400 sm:text-5xl">
                                {t.eval_intro_title}
                            </h2>
                            <p className="text-sm font-semibold uppercase tracking-[0.28em] text-white/45 sm:text-base">
                                {t.eval_intro_subtitle}
                            </p>
                        </div>

                        <div className="max-w-2xl rounded-[28px] border border-white/10 bg-black/30 p-6 backdrop-blur-md sm:p-7">
                            <p className="text-sm leading-relaxed text-white/82 sm:text-[15px]">
                                {t.eval_intro_body}
                            </p>
                        </div>

                        <button
                            onClick={() => setShowEvalIntro(false)}
                            className="mt-2 rounded-xl bg-brand px-8 py-3 text-sm font-black tracking-wide text-black shadow-lg shadow-brand/20 transition-all hover:bg-brand/90 active:scale-95"
                        >
                            {t.eval_intro_start}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

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
        // Equal-weight scoring by default: each question contributes the same percentage.
        // This avoids legacy point/weight data inflating results unexpectedly.
        let totalWeight = 0;
        let earnedWeight = 0;
        const perQuestion: Array<{ id: string; correct: boolean; weight: number }> = [];
        const summaries: Array<{ questionId: string; selectedText: string; correct: boolean }> = [];

        finalQuestions.forEach((q: Question) => {
            const qWeight = 1;
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
            summaries.push({
                questionId: q.id,
                selectedText: getSelectedAnswerText(q, userAnswer),
                correct
            });
        });

        const finalScore = totalWeight > 0 ? Math.round((earnedWeight / totalWeight) * 100) : 0;
        const totalCorrect = perQuestion.filter(p => p.correct).length;
        
        const passed2 = finalScore >= finalPassingScore;

        setScore(finalScore);
        setCorrectCount(totalCorrect);
        setQuestionSummaries(summaries);
        setWasPassed(passed2);
        setIsFinished(true);

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
                            status: passed2 ? 'completed' : 'in_progress',
                            best_score: finalScore,
                            completed_at: passed2 ? new Date().toISOString() : null
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
        if (onComplete) onComplete(finalScore, passed2);
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
        const resolvedPassed = typeof wasPassed === 'boolean'
            ? wasPassed
            : (questionSummaries.length > 0 ? questionSummaries.every((item) => item.correct) : correctCount === finalQuestions.length);

        const statusTitle = persistScore
            ? t.quiz_finished
            : (resolvedPassed ? t.activity_done : t.activity_pending);

        const statusDescription = persistScore
            ? t.answered_correctly(correctCount, finalQuestions.length)
            : (resolvedPassed ? t.activity_done_desc : t.activity_pending_desc);

        // Quiz en módulo de CONTENIDO (no evaluación)
        if (!persistScore) {
            if (resolvedPassed) {
                // Actividad completada correctamente — bloqueada, no se puede repetir
                return (
                    <div className="w-full max-w-2xl mx-auto p-4 space-y-6">
                        <div className="flex flex-col items-center justify-center p-6 space-y-3 text-center">
                            <div className="w-20 h-20 bg-brand/10 rounded-full flex items-center justify-center border-2 border-brand/30">
                                <CheckCircle2 className="w-12 h-12 text-brand" />
                            </div>
                            <div className="space-y-1">
                                <h2 className="text-2xl font-bold">{statusTitle}</h2>
                                <p className="text-white/60 text-sm">{statusDescription}</p>
                            </div>
                        </div>

                        <div className="glass p-5 border-white/10 rounded-2xl text-left space-y-3">
                            <h3 className="text-sm uppercase tracking-widest text-white/50 font-black">{t.summary_title}</h3>
                            <p className="text-sm text-white/80 font-bold">{t.quiz_status_line} <span className="text-brand">{t.finalized}</span></p>

                            <div className="space-y-2 pt-1">
                                {finalQuestions.map((question, idx) => {
                                    const result = questionSummaries.find((item) => item.questionId === question.id);
                                    const questionText = (language === 'ht' && question.text_ht) ? question.text_ht : question.text;
                                    return (
                                        <div key={`summary-${question.id}-${idx}`} className="rounded-xl border border-white/10 bg-white/[0.02] p-3">
                                            <p className="text-sm font-bold text-white">{t.question} {idx + 1}: <span className="text-white/80">{questionText}</span></p>
                                            <p className="text-xs text-white/60 mt-1">{t.answer_label}: {result?.selectedText || t.no_answer}</p>
                                            <p className={`text-xs mt-1 font-bold ${result?.correct ? 'text-brand' : 'text-red-400'}`}>
                                                {result?.correct ? t.correct : t.incorrect}
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                );
            } else {
                // Actividad pendiente — se puede repetir
                return (
                    <div className="w-full max-w-2xl mx-auto p-4 space-y-6">
                        <div className="flex flex-col items-center justify-center p-6 space-y-3 text-center">
                            <div className="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center border-2 border-red-500/30">
                                <AlertCircle className="w-12 h-12 text-red-400" />
                            </div>
                            <div className="space-y-1">
                                <h2 className="text-2xl font-bold text-red-400">{statusTitle}</h2>
                                <p className="text-white/60 text-sm">{statusDescription}</p>
                            </div>
                        </div>

                        <div className="glass p-5 border-white/10 rounded-2xl text-left space-y-3">
                            <h3 className="text-sm uppercase tracking-widest text-white/50 font-black">{t.summary_title}</h3>
                            <p className="text-sm text-white/80 font-bold">{t.quiz_status_line} <span className="text-red-400">{t.activity_pending}</span></p>

                            <div className="space-y-2 pt-1">
                                {finalQuestions.map((question, idx) => {
                                    const result = questionSummaries.find((item) => item.questionId === question.id);
                                    const questionText = (language === 'ht' && question.text_ht) ? question.text_ht : question.text;
                                    return (
                                        <div key={`summary-${question.id}-${idx}`} className="rounded-xl border border-white/10 bg-white/[0.02] p-3">
                                            <p className="text-sm font-bold text-white">{t.question} {idx + 1}: <span className="text-white/80">{questionText}</span></p>
                                            <p className="text-xs text-white/60 mt-1">{t.answer_label}: {result?.selectedText || t.no_answer}</p>
                                            <p className={`text-xs mt-1 font-bold ${result?.correct ? 'text-brand' : 'text-red-400'}`}>
                                                {result?.correct ? t.correct : t.incorrect}
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        <button
                            onClick={() => {
                                setAnswers({});
                                setQuestionSummaries([]);
                                setCurrentQuestionIdx(0);
                                setIsFinished(false);
                                setWasPassed(null);
                                if (quizDraftKey) localStorage.removeItem(quizDraftKey);
                            }}
                            className="w-full max-w-xs mx-auto py-4 bg-white/5 text-white/60 font-bold rounded-xl hover:bg-white/10 transition-all text-xs uppercase block"
                        >
                            {t.repeat_activity}
                        </button>
                    </div>
                );
            }
        }

        // Quiz de EVALUACIÓN — mostrar puntaje y opción de reintentar
        return (
            <div className="w-full max-w-2xl mx-auto p-4 space-y-6">
                <div className="flex flex-col items-center justify-center p-6 space-y-3 text-center">
                    <div className="w-20 h-20 bg-brand/10 rounded-full flex items-center justify-center">
                        <CheckCircle2 className="w-12 h-12 text-brand" />
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-3xl font-bold">{statusTitle}</h2>
                        <p className="text-white/60">{statusDescription}</p>
                    </div>
                </div>

                <div className="glass p-5 border-white/10 rounded-2xl text-left space-y-3">
                    <h3 className="text-sm uppercase tracking-widest text-white/50 font-black">{t.summary_title}</h3>
                    <p className="text-sm text-white/80 font-bold">{t.quiz_status_line} <span className="text-brand">{t.finalized}</span></p>

                    <div className="space-y-2 pt-1">
                        {finalQuestions.map((question, idx) => {
                            const result = questionSummaries.find((item) => item.questionId === question.id);
                            const questionText = (language === 'ht' && question.text_ht) ? question.text_ht : question.text;
                            return (
                                <div key={`summary-${question.id}-${idx}`} className="rounded-xl border border-white/10 bg-white/[0.02] p-3">
                                    <p className="text-sm font-bold text-white">{t.question} {idx + 1}: <span className="text-white/80">{questionText}</span></p>
                                    <p className="text-xs text-white/60 mt-1">{t.answer_label}: {result?.selectedText || t.no_answer}</p>
                                    <p className={`text-xs mt-1 font-bold ${result?.correct ? 'text-brand' : 'text-red-400'}`}>
                                        {result?.correct ? t.correct : t.incorrect}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 w-full">
                    <div className="glass p-4 border-brand/20">
                        <p className="text-[10px] text-white/40 uppercase font-bold tracking-widest">{t.hits}</p>
                        <p className="text-2xl font-black text-brand">{score}%</p>
                    </div>
                    <div className="glass p-4 border-brand/20">
                        <p className="text-[10px] text-white/40 uppercase font-bold tracking-widest">{t.weight}</p>
                        <p className="text-2xl font-black text-brand">{contribution}%</p>
                    </div>
                </div>

                <div className="w-full h-2 bg-white/5 rounded-full overflow-hidden">
                    <div className="h-full bg-brand transition-all duration-1000" style={{ width: `${score}%` }} />
                </div>

                <p className="text-xs text-brand/60 font-medium italic text-center">
                    {t.progress_saved}
                </p>

                <div className="flex flex-col w-full gap-3">
                    <button
                        onClick={() => {
                            setAnswers({});
                            setQuestionSummaries([]);
                            setCurrentQuestionIdx(0);
                            setIsFinished(false);
                            setWasPassed(null);
                            if (quizDraftKey) {
                                localStorage.removeItem(quizDraftKey);
                            }
                        }}
                        className="w-full py-4 bg-white/5 text-white/60 font-bold rounded-xl hover:bg-white/10 transition-all text-xs uppercase"
                    >
                        {t.try_again}
                    </button>
                </div>
            </div>
        );
    }

    const hasAnswered = answers[currentQuestion.id] && (Array.isArray(answers[currentQuestion.id]) ? (answers[currentQuestion.id] as string[]).length > 0 : true);
    const progressPercent = Math.round(((currentQuestionIdx + 1) / finalQuestions.length) * 100);

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
                </div>
                <div className="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                    <div className="h-full bg-brand transition-all duration-500" style={{ width: `${progressPercent}%` }} />
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

                {/* Feedback opcional por pregunta */}
                {currentQuestion.feedback && (
                    <div className="mt-2 p-4 bg-yellow-500/10 rounded-xl border border-yellow-400/40 flex items-start gap-3 shadow-[0_0_20px_rgba(250,204,21,0.08)]">
                        <div className="w-8 h-8 rounded-full bg-yellow-400/15 border border-yellow-400/40 flex items-center justify-center shrink-0">
                            <Lightbulb className="w-4 h-4 text-yellow-300" />
                        </div>
                        <p className="text-sm text-yellow-100 leading-relaxed font-medium">{currentQuestion.feedback}</p>
                    </div>
                )}
            </motion.div>

            <div className="pt-8">
                <button
                    onClick={nextQuestion}
                    disabled={!hasAnswered}
                    className="group w-full py-5 bg-brand disabled:opacity-20 disabled:grayscale disabled:cursor-not-allowed text-black font-black uppercase text-sm tracking-widest rounded-2xl flex items-center justify-center gap-3 hover:scale-[1.01] active:scale-[0.98] transition-all shadow-xl shadow-brand/10 font-mono"
                >
                    {currentQuestionIdx === finalQuestions.length - 1
                        ? (persistScore ? t.finish_eval : t.confirm_answers)
                        : t.next_question}
                    <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                </button>
            </div>
        </div>
    );
}
