import { useState, useEffect } from "react";
import { Plus, Trash2, Check, X, GripVertical, AlertCircle } from "lucide-react";

type QuestionType = 'single' | 'multiple' | 'truefalse';

type Question = {
    id: string;
    type: QuestionType;
    text: string;
    options: { id: string; text: string }[];
    correctAnswer: string | string[]; // string para single/truefalse, string[] para multiple
    points: number;
    weight?: number; // relative weight for this question
    feedback?: string;
};

interface QuizBuilderProps {
    initialQuestions?: Question[];
    onSave: (questions: Question[]) => void;
    onCancel: () => void;
}

export default function QuizBuilder({ initialQuestions = [], onSave, onCancel }: QuizBuilderProps) {
    // Normalizar preguntas iniciales para asegurar que tengan el campo type y opciones con IDs únicos
    const normalizeQuestions = (qs: any[]): Question[] => {
        return qs.map(q => {
            // Determinar el tipo de pregunta
            let type: QuestionType = 'single';
            if (q.type) {
                type = q.type;
            } else {
                // Inferir tipo si no está definido
                if (Array.isArray(q.correctAnswer)) {
                    type = 'multiple';
                } else if (q.options?.length === 2 &&
                    (q.options[0]?.text?.toLowerCase().includes('verdadero') ||
                        q.options[0]?.text?.toLowerCase().includes('true'))) {
                    type = 'truefalse';
                }
            }

            // Asegurar que las opciones tengan IDs únicos y válidos
            const seenOptionIds = new Set();
            const idMap = new Map();

            const options = (q.options || []).map((opt: any, idx: number) => {
                let optId = opt.id;
                if (!optId || seenOptionIds.has(optId)) {
                    const newId = crypto.randomUUID();
                    if (optId) idMap.set(optId, newId);
                    optId = newId;
                }
                seenOptionIds.add(optId);
                return {
                    id: optId,
                    text: opt.text || `Opción ${idx + 1}`
                };
            });

            // Si no hay opciones, crear dos por defecto
            if (options.length === 0) {
                options.push(
                    { id: crypto.randomUUID(), text: "Opción A" },
                    { id: crypto.randomUUID(), text: "Opción B" }
                );
            }

            // Normalizar correctAnswer según el tipo y mapeo de IDs
            let correctAnswer: string | string[];
            if (type === 'multiple') {
                const rawAnswers = Array.isArray(q.correctAnswer) ? q.correctAnswer : (q.correctAnswer ? [q.correctAnswer] : []);
                correctAnswer = rawAnswers.map((id: string) => idMap.get(id) || id)
                    .filter((id: string) => options.some((opt: any) => opt.id === id));
                if (correctAnswer.length === 0) correctAnswer = [options[0]?.id];
            } else {
                const rawAnswer = Array.isArray(q.correctAnswer) ? q.correctAnswer[0] : q.correctAnswer;
                const mappedId = idMap.get(rawAnswer) || rawAnswer;
                const isValidId = options.some((opt: any) => opt.id === mappedId);
                correctAnswer = isValidId ? mappedId : options[0]?.id;
            }

            return {
                id: q.id || crypto.randomUUID(),
                type,
                text: q.text || "Nueva Pregunta...",
                options,
                correctAnswer,
                points: q.points || 10,
                weight: q.weight || q.points || 1,
                feedback: q.feedback || ''
            };
        });
    };

    const [questions, setQuestions] = useState<Question[]>(() => {
        const normalized = normalizeQuestions(initialQuestions);
        return normalized.length > 0 ? normalized : [];
    });

    const [activeQuestion, setActiveQuestion] = useState<string | null>(() => {
        const normalized = normalizeQuestions(initialQuestions);
        return normalized[0]?.id || null;
    });

    // Initial load check
    useEffect(() => {
        if (questions.length === 0) {
            handleAddQuestion();
        }
    }, []);

    const handleAddQuestion = () => {
        const opt1Id = crypto.randomUUID();
        const opt2Id = crypto.randomUUID();
        const newQ: Question = {
            id: crypto.randomUUID(),
            type: 'single',
            text: "Nueva Pregunta...",
            options: [
                { id: opt1Id, text: "Opción A" },
                { id: opt2Id, text: "Opción B" }
            ],
            correctAnswer: opt1Id,
            points: 10
        };
        setQuestions(prevQuestions => [...prevQuestions, newQ]);
        setActiveQuestion(newQ.id);
    };

    const handleUpdateQuestion = (qId: string, field: keyof Question, value: any) => {
        setQuestions(prevQuestions => prevQuestions.map(q => q.id === qId ? { ...q, [field]: value } : q));
    };

    const handleChangeQuestionType = (qId: string, newType: QuestionType) => {
        setQuestions(prevQuestions => prevQuestions.map(q => {
            if (q.id !== qId) return q;

            // Cambiar opciones según el tipo
            let newOptions = [...q.options]; // Clonar opciones actuales
            let newCorrectAnswer: string | string[] = q.correctAnswer;

            if (newType === 'truefalse') {
                const trueId = crypto.randomUUID();
                const falseId = crypto.randomUUID();
                newOptions = [
                    { id: trueId, text: "Verdadero" },
                    { id: falseId, text: "Falso" }
                ];
                newCorrectAnswer = trueId;
            } else if (newType === 'multiple') {
                // Convertir a array si es single
                if (Array.isArray(q.correctAnswer)) {
                    // Validar que los IDs existen
                    newCorrectAnswer = q.correctAnswer.filter(id =>
                        newOptions.some(opt => opt.id === id)
                    );
                    if (newCorrectAnswer.length === 0) {
                        newCorrectAnswer = [newOptions[0]?.id];
                    }
                } else {
                    const isValid = newOptions.some(opt => opt.id === q.correctAnswer);
                    newCorrectAnswer = isValid ? [q.correctAnswer] : [newOptions[0]?.id];
                }
                // Asegurarse de que haya al menos 2 opciones
                if (newOptions.length < 2) {
                    const opt1Id = crypto.randomUUID();
                    const opt2Id = crypto.randomUUID();
                    newOptions = [
                        { id: opt1Id, text: "Opción A" },
                        { id: opt2Id, text: "Opción B" }
                    ];
                    newCorrectAnswer = [opt1Id];
                }
            } else if (newType === 'single') {
                // Convertir a string si es multiple
                if (Array.isArray(q.correctAnswer)) {
                    const firstValid = q.correctAnswer.find(id =>
                        newOptions.some(opt => opt.id === id)
                    );
                    newCorrectAnswer = firstValid || newOptions[0]?.id;
                } else {
                    const isValid = newOptions.some(opt => opt.id === q.correctAnswer);
                    newCorrectAnswer = isValid ? q.correctAnswer : newOptions[0]?.id;
                }
                // Asegurarse de que haya al menos 2 opciones
                if (newOptions.length < 2) {
                    const opt1Id = crypto.randomUUID();
                    const opt2Id = crypto.randomUUID();
                    newOptions = [
                        { id: opt1Id, text: "Opción A" },
                        { id: opt2Id, text: "Opción B" }
                    ];
                    newCorrectAnswer = opt1Id;
                }
            }

            return { ...q, type: newType, options: newOptions, correctAnswer: newCorrectAnswer };
        }));
    };

    const handleUpdateOption = (qId: string, optId: string, text: string) => {
        setQuestions(prevQuestions => prevQuestions.map(q => {
            if (q.id !== qId) return q;
            return {
                ...q,
                options: q.options.map(opt => opt.id === optId ? { ...opt, text } : opt)
            };
        }));
    };

    const handleAddOption = (qId: string) => {
        setQuestions(prevQuestions => prevQuestions.map(q => {
            if (q.id !== qId) return q;
            const newOptId = crypto.randomUUID();
            return {
                ...q,
                options: [...q.options, { id: newOptId, text: "Nueva Opción" }]
            };
        }));
    };

    const handleRemoveOption = (qId: string, optId: string) => {
        setQuestions(prevQuestions => prevQuestions.map(q => {
            if (q.id !== qId) return q;
            if (q.options.length <= 2) {
                alert("Mínimo 2 opciones requeridas");
                return q; // Minimum 2 opts
            }
            // If deleting correct answer, reset default
            const newOptions = q.options.filter(o => o.id !== optId);

            let newCorrect: string | string[];
            if (q.type === 'multiple') {
                const correctArray = Array.isArray(q.correctAnswer) ? q.correctAnswer : [q.correctAnswer];
                newCorrect = correctArray.filter(id => id !== optId);
                if (newCorrect.length === 0) newCorrect = [newOptions[0].id];
            } else {
                newCorrect = q.correctAnswer === optId ? newOptions[0].id : q.correctAnswer;
            }

            return { ...q, options: newOptions, correctAnswer: newCorrect };
        }));
    };

    const handleToggleMultipleAnswer = (qId: string, optId: string) => {
        setQuestions(prevQuestions => prevQuestions.map(q => {
            if (q.id !== qId || q.type !== 'multiple') return q;

            const currentAnswers = Array.isArray(q.correctAnswer) ? q.correctAnswer : [q.correctAnswer];
            const isSelected = currentAnswers.includes(optId);

            let newAnswers: string[];
            if (isSelected) {
                newAnswers = currentAnswers.filter(id => id !== optId);
                // Al menos una opción debe estar seleccionada
                if (newAnswers.length === 0) {
                    alert("Debe haber al menos una respuesta correcta");
                    return q;
                }
            } else {
                newAnswers = [...currentAnswers, optId];
            }

            return { ...q, correctAnswer: newAnswers };
        }));
    };

    const handleDeleteQuestion = (qId: string) => {
        setQuestions(prevQuestions => {
            if (prevQuestions.length <= 1) return prevQuestions; // Prevent deleting last
            const newQs = prevQuestions.filter(q => q.id !== qId);
            if (activeQuestion === qId) setActiveQuestion(newQs[0]?.id || null);
            return newQs;
        });
    };

    const currentQ = questions.find(q => q.id === activeQuestion);

    // Debug log
    useEffect(() => {
        if (currentQ) {
            console.log('Current Question:', {
                id: currentQ.id,
                type: currentQ.type,
                optionsCount: currentQ.options.length,
                options: currentQ.options,
                correctAnswer: currentQ.correctAnswer
            });
        }
    }, [currentQ]);

    return (
        <div className="fixed inset-0 z-[200] bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
            <div className="bg-[#0A0A0A] w-full max-w-5xl h-[80vh] rounded-3xl border border-white/10 flex overflow-hidden shadow-2xl">

                {/* Sidebar: Question List */}
                <div className="w-1/3 border-r border-white/5 bg-white/5 flex flex-col">
                    <div className="p-6 border-b border-white/5 flex justify-between items-center">
                        <h3 className="font-black uppercase tracking-widest text-brand">Estructura del Quiz</h3>
                        <span className="text-xs font-bold text-white/40">{questions.length} Items</span>
                    </div>
                    <div className="flex-1 overflow-y-auto p-4 space-y-2">
                        {questions.map((q, idx) => (
                            <div
                                key={q.id}
                                onClick={() => setActiveQuestion(q.id)}
                                className={`p-4 rounded-xl border cursor-pointer transition-all ${activeQuestion === q.id
                                    ? 'bg-brand/10 border-brand text-white'
                                    : 'bg-black/20 border-transparent hover:bg-white/5 text-white/40'
                                    }`}
                            >
                                <div className="flex justify-between items-start mb-1">
                                    <span className="text-[10px] font-black uppercase tracking-widest">Pregunta {idx + 1}</span>
                                    {questions.length > 1 && (
                                        <button onClick={(e) => { e.stopPropagation(); handleDeleteQuestion(q.id); }} className="hover:text-red-400">
                                            <Trash2 className="w-3 h-3" />
                                        </button>
                                    )}
                                </div>
                                <p className="text-xs font-medium line-clamp-2">{q.text}</p>
                            </div>
                        ))}
                    </div>
                    <div className="p-4 border-t border-white/5">
                        <button onClick={handleAddQuestion} className="w-full py-3 bg-white/5 hover:bg-white/10 rounded-xl font-bold uppercase text-xs flex items-center justify-center gap-2 transition-colors">
                            <Plus className="w-4 h-4" /> Agregar Pregunta
                        </button>
                    </div>
                </div>

                {/* Main: Question Editor */}
                <div className="flex-1 flex flex-col bg-[#0A0A0A]">
                    {currentQ ? (
                        <div className="flex-1 overflow-y-auto p-8 space-y-8">
                            {/* Question Type Selector */}
                            <div className="space-y-2">
                                <label className="text-[10px] font-black uppercase text-white/40">Tipo de Pregunta</label>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => handleChangeQuestionType(currentQ.id, 'single')}
                                        className={`flex-1 py-3 px-4 rounded-xl font-bold text-xs uppercase transition-all ${currentQ.type === 'single'
                                            ? 'bg-brand text-black'
                                            : 'bg-white/5 text-white/60 hover:bg-white/10'
                                            }`}
                                    >
                                        Alternativas
                                    </button>
                                    <button
                                        onClick={() => handleChangeQuestionType(currentQ.id, 'multiple')}
                                        className={`flex-1 py-3 px-4 rounded-xl font-bold text-xs uppercase transition-all ${currentQ.type === 'multiple'
                                            ? 'bg-brand text-black'
                                            : 'bg-white/5 text-white/60 hover:bg-white/10'
                                            }`}
                                    >
                                        Selección Múltiple
                                    </button>
                                    <button
                                        onClick={() => handleChangeQuestionType(currentQ.id, 'truefalse')}
                                        className={`flex-1 py-3 px-4 rounded-xl font-bold text-xs uppercase transition-all ${currentQ.type === 'truefalse'
                                            ? 'bg-brand text-black'
                                            : 'bg-white/5 text-white/60 hover:bg-white/10'
                                            }`}
                                    >
                                        Verdadero/Falso
                                    </button>
                                </div>
                            </div>

                            {/* Question Text & Score */}
                            <div className="space-y-4">
                                <div className="flex justify-between items-end">
                                    <label className="text-[10px] font-black uppercase text-white/40">Enunciado de la Pregunta</label>
                                    <div className="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-lg border border-white/10">
                                            <span className="text-[10px] font-black uppercase text-brand">Peso:</span>
                                            <input
                                                type="number"
                                                value={currentQ.weight || 1}
                                                onChange={(e) => handleUpdateQuestion(currentQ.id, 'weight', Number(e.target.value))}
                                                className="w-16 bg-transparent text-right font-bold outline-none"
                                                step="0.1"
                                                min={0}
                                            />
                                        </div>
                                </div>
                                <textarea
                                    value={currentQ.text}
                                    onChange={(e) => handleUpdateQuestion(currentQ.id, 'text', e.target.value)}
                                    className="w-full bg-white/5 border border-white/10 rounded-2xl p-6 text-lg font-medium outline-none focus:border-brand/40 min-h-[120px]"
                                    placeholder="Escribe tu pregunta aquí..."
                                />
                            </div>

                            {/* Options */}
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <label className="text-[10px] font-black uppercase text-white/40">
                                        Opciones de Respuesta
                                        {currentQ.type === 'multiple' && (
                                            <span className="ml-2 text-brand">(Seleccionar múltiples correctas)</span>
                                        )}
                                    </label>
                                </div>
                                <div className="grid grid-cols-1 gap-3">
                                    {currentQ.options.map((opt, idx) => {
                                        const isCorrect = currentQ.type === 'multiple'
                                            ? Array.isArray(currentQ.correctAnswer) && currentQ.correctAnswer.includes(opt.id)
                                            : currentQ.correctAnswer === opt.id;

                                        return (
                                            <div key={opt.id} className={`flex items-center gap-4 p-4 rounded-xl border transition-all ${isCorrect ? 'bg-brand/5 border-brand/40' : 'bg-white/5 border-white/5'
                                                }`}>
                                                <div
                                                    onClick={() => {
                                                        if (currentQ.type === 'multiple') {
                                                            handleToggleMultipleAnswer(currentQ.id, opt.id);
                                                        } else {
                                                            handleUpdateQuestion(currentQ.id, 'correctAnswer', opt.id);
                                                        }
                                                    }}
                                                    className={`w-6 h-6 ${currentQ.type === 'multiple' ? 'rounded-md' : 'rounded-full'} border-2 cursor-pointer flex items-center justify-center transition-all ${isCorrect
                                                        ? 'border-brand bg-brand text-black'
                                                        : 'border-white/20 hover:border-white/40'
                                                        }`}
                                                >
                                                    {isCorrect && <Check className="w-3 h-3" />}
                                                </div>
                                                <input
                                                    value={opt.text}
                                                    onChange={(e) => handleUpdateOption(currentQ.id, opt.id, e.target.value)}
                                                    className="flex-1 bg-transparent border-none outline-none font-medium"
                                                    placeholder={`Opción ${idx + 1}`}
                                                    disabled={currentQ.type === 'truefalse'}
                                                />
                                                {currentQ.type !== 'truefalse' && (
                                                    <button onClick={() => handleRemoveOption(currentQ.id, opt.id)} className="text-white/20 hover:text-red-400">
                                                        <X className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                                {currentQ.type !== 'truefalse' && (
                                    <button onClick={() => handleAddOption(currentQ.id)} className="text-xs font-bold text-brand uppercase tracking-wider hover:underline flex items-center gap-2">
                                        <Plus className="w-3 h-3" /> Añadir Alternativa
                                    </button>
                                )}
                            </div>

                            {/* Feedback */}
                            <div className="space-y-2 pt-4 border-t border-white/5">
                                <label className="text-[10px] font-black uppercase text-white/40 flex items-center gap-2">
                                    <AlertCircle className="w-3 h-3" /> Feedback (Opcional)
                                </label>
                                <input
                                    value={currentQ.feedback || ''}
                                    onChange={(e) => handleUpdateQuestion(currentQ.id, 'feedback', e.target.value)}
                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand/40"
                                    placeholder="Mensaje de retroalimentación para el alumno..."
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="flex-1 flex items-center justify-center text-white/20">Selecciona una pregunta</div>
                    )}

                    {/* Footer Actions */}
                    <div className="p-6 border-t border-white/10 bg-black/40 flex justify-end gap-4">
                        <button onClick={onCancel} className="px-6 py-3 rounded-xl font-bold uppercase text-xs text-white/40 hover:bg-white/5 hover:text-white transition-colors">
                            Descartar Cambios
                        </button>
                        <button onClick={() => onSave(questions)} className="px-8 py-3 rounded-xl bg-brand text-black font-black uppercase text-xs tracking-widest shadow-lg shadow-brand/20 hover:scale-105 active:scale-95 transition-all">
                            Guardar Quiz
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
