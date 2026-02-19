"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { useParams, useRouter } from "next/navigation";
import { 
    ArrowLeft, BarChart3, Users, Clock, 
    ChevronRight, Star, MessageSquare, PieChart 
} from "lucide-react";
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
} from 'chart.js';
import { Bar, Pie } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
);

export default function SurveyStats() {
    const params = useParams();
    const router = useRouter();
    const surveyId = params.id as string;

    const [survey, setSurvey] = useState<any>(null);
    const [questions, setQuestions] = useState<any[]>([]);
    const [responses, setResponses] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, [surveyId]);

    const fetchData = async () => {
        setLoading(true);
        const { data: s } = await supabase.from('surveys').select('*').eq('id', surveyId).single();
        const { data: q } = await supabase.from('survey_questions').select('*').eq('survey_id', surveyId).order('order_index');
        const { data: r } = await supabase.from('survey_responses').select('*, students(first_name, last_name, rut)').eq('survey_id', surveyId);

        if (s) setSurvey(s);
        if (q) setQuestions(q);
        if (r) setResponses(r);
        setLoading(false);
    };

    const getChartData = (question: any) => {
        if (question.question_type === 'rating') {
            const counts = [0, 0, 0, 0, 0];
            responses.forEach(r => {
                const val = r.answers[question.id];
                if (val >= 1 && val <= 5) counts[val - 1]++;
            });
            return {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Votos',
                    data: counts,
                    backgroundColor: 'rgba(174, 255, 0, 0.4)',
                    borderColor: '#AEFF00',
                    borderWidth: 1,
                }]
            };
        }

        if (question.question_type === 'boolean') {
            let yes = 0, no = 0;
            responses.forEach(r => {
                const val = r.answers[question.id];
                if (val === true) yes++;
                if (val === false) no++;
            });
            return {
                labels: ['Sí', 'No'],
                datasets: [{
                    data: [yes, no],
                    backgroundColor: ['rgba(174, 255, 0, 0.4)', 'rgba(255, 99, 132, 0.4)'],
                    borderColor: ['#AEFF00', '#FF6384'],
                    borderWidth: 1,
                }]
            };
        }

        if (question.question_type === 'multiple_choice') {
            const labels = question.options_es || [];
            const counts = labels.map(() => 0);
            responses.forEach(r => {
                const val = r.answers[question.id];
                const idx = labels.indexOf(val);
                if (idx !== -1) counts[idx]++;
            });
            return {
                labels,
                datasets: [{
                    label: 'Votos',
                    data: counts,
                    backgroundColor: 'rgba(54, 162, 235, 0.4)',
                    borderColor: '#36A2EB',
                    borderWidth: 1,
                }]
            };
        }

        return null;
    };

    if (loading) return <div className="min-h-screen bg-black flex items-center justify-center text-brand font-black animate-pulse">ANALIZANDO DATOS...</div>;

    return (
        <div className="min-h-screen bg-[#060606] text-white p-6 md:p-10 font-sans">
            <div className="max-w-5xl mx-auto space-y-10">
                <header className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <button onClick={() => router.back()} className="flex items-center gap-2 text-white/40 hover:text-white transition-colors text-xs font-black uppercase tracking-widest mb-4">
                            <ArrowLeft className="w-4 h-4" /> Volver a Encuestas
                        </button>
                        <h1 className="text-4xl font-black tracking-tight italic uppercase">Estadísticas: <span className="text-brand">{survey?.title_es}</span></h1>
                        <p className="text-white/40 font-medium">Análisis de retroalimentación de alumnos</p>
                    </div>
                    <div className="flex gap-4">
                        <div className="bg-white/5 border border-white/10 p-4 rounded-2xl text-center min-w-[120px]">
                            <span className="text-[10px] font-black uppercase text-white/40 block">Respuestas</span>
                            <span className="text-2xl font-black text-brand">{responses.length}</span>
                        </div>
                    </div>
                </header>

                <div className="grid grid-cols-1 gap-10">
                    {questions.map((q, idx) => {
                        const chartData = getChartData(q);
                        
                        return (
                            <div key={q.id} className="bg-white/[0.02] border border-white/5 rounded-3xl p-8 space-y-6">
                                <div className="flex items-start gap-4">
                                    <div className="w-10 h-10 rounded-xl bg-brand/10 border border-brand/20 flex items-center justify-center shrink-0 text-brand font-black italic">
                                        {idx + 1}
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-bold">{q.text_es}</h3>
                                        <p className="text-white/20 text-[10px] font-black uppercase tracking-widest mt-1">{q.question_type} questionnaire</p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                                    {chartData && (
                                        <div className="bg-black/40 p-6 rounded-2xl border border-white/5 aspect-video flex items-center justify-center">
                                            {q.question_type === 'boolean' ? (
                                                <div className="w-48"><Pie data={chartData} options={{ plugins: { legend: { labels: { color: 'white' } } } }} /></div>
                                            ) : (
                                                <Bar data={chartData} options={{ scales: { y: { ticks: { color: 'white' }, grid: { color: 'rgba(255,255,255,0.05)' } }, x: { ticks: { color: 'white' } } }, plugins: { legend: { display: false } } }} />
                                            )}
                                        </div>
                                    )}

                                    {q.question_type === 'text' ? (
                                        <div className="space-y-3 max-h-[300px] overflow-y-auto pr-4 custom-scrollbar md:col-span-2">
                                            {responses.map((r, rIdx) => (
                                                r.answers[q.id] && (
                                                    <div key={rIdx} className="bg-white/5 p-4 rounded-xl border border-white/5">
                                                        <div className="flex justify-between items-center mb-2">
                                                            <span className="text-[10px] font-black text-brand uppercase">{r.students?.first_name} {r.students?.last_name}</span>
                                                            <span className="text-[9px] text-white/20">{new Date(r.created_at).toLocaleDateString()}</span>
                                                        </div>
                                                        <p className="text-sm text-white/70 italic leading-relaxed">"{r.answers[q.id]}"</p>
                                                    </div>
                                                )
                                            ))}
                                            {!responses.some(r => r.answers[q.id]) && <p className="text-white/20 text-xs text-center py-10">No hay comentarios aún.</p>}
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            <div className="flex items-center gap-2 text-white/40">
                                                <Users className="w-4 h-4" />
                                                <span className="text-xs font-bold uppercase tracking-widest">Resumen de Datos</span>
                                            </div>
                                            <div className="grid grid-cols-2 gap-3">
                                                {chartData?.labels?.map((label: string, lIdx: number) => (
                                                    <div key={lIdx} className="bg-white/5 p-3 rounded-xl border border-white/5 flex justify-between items-center">
                                                        <span className="text-[10px] text-white/60 font-medium">{label}</span>
                                                        <span className="text-sm font-black text-white">{chartData.datasets[0].data[lIdx]}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
