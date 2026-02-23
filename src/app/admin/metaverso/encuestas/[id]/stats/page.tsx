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
import * as XLSX from 'xlsx';
import { Download, Filter, Search as SearchIcon, FileSpreadsheet, ShieldAlert, Trash2 } from "lucide-react";

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
    const [filteredResponses, setFilteredResponses] = useState<any[]>([]);
    const [totalEnrollmentsCount, setTotalEnrollmentsCount] = useState(0);
    const [loading, setLoading] = useState(true);
    const [isAuthorized, setIsAuthorized] = useState<boolean | null>(null);
    const [userRole, setUserRole] = useState<'superadmin' | 'editor' | null>(null);
    const [filters, setFilters] = useState({
        startDate: '',
        endDate: '',
        course: '',
        company: '',
        colabCompany: '',
        position: '',
        search: ''
    });

    useEffect(() => {
        checkAuth();
    }, []);

    const checkAuth = async () => {
        const { data: { session } } = await supabase.auth.getSession();
        
        if (!session) {
            router.push(`/admin/metaverso/login?returnUrl=/admin/metaverso/encuestas/${surveyId}/stats`);
            return;
        }

        const email = session.user.email?.toLowerCase();
        const { data: profile } = await supabase
            .from('admin_profiles')
            .select('role')
            .eq('email', email)
            .maybeSingle();

        if (profile) {
            setUserRole(profile.role);
            setIsAuthorized(true);
        } else {
            const allowedEmails = ['apacheco@lobus.cl', 'porksde@gmail.com'];
            if (email && allowedEmails.includes(email)) {
                setUserRole('superadmin');
                setIsAuthorized(true);
            } else {
                setIsAuthorized(false);
                return;
            }
        }
        
        fetchData();
    };

    const handleDeleteResponse = async (id: string) => {
        if (!confirm("¿Eliminar esta respuesta permanentemente?")) return;
        const { error } = await supabase.from('survey_responses').delete().eq('id', id);
        if (error) alert("Error: " + error.message);
        else fetchData();
    };

    const fetchData = async () => {
        setLoading(true);
        const { data: s } = await supabase.from('surveys').select('*').eq('id', surveyId).single();
        const { data: q } = await supabase.from('survey_questions').select('*').eq('survey_id', surveyId).order('order_index');
        
        // Extended fetch to get student and course info
        const { data: r } = await supabase
            .from('survey_responses')
            .select(`
                *,
                students (
                    first_name, 
                    last_name, 
                    rut, 
                    passport, 
                    position, 
                    company_name,
                    companies (name)
                ),
                enrollments (
                    courses (id, name)
                )
            `)
            .eq('survey_id', surveyId);

        if (s) setSurvey(s);
        if (q) setQuestions(q);
        
        // Try to estimate total potential respondents (enrollments in courses containing this survey)
        try {
            // Find all courses that have this survey in their modules
            const { data: itemData } = await supabase
                .from('module_items')
                .select('module_id')
                .eq('type', 'survey')
                .eq('item_id', surveyId);
            
            if (itemData && itemData.length > 0) {
                const moduleIds = itemData.map(m => m.module_id);
                const { data: courseModules } = await supabase
                    .from('course_modules')
                    .select('course_id')
                    .in('id', moduleIds);
                
                if (courseModules && courseModules.length > 0) {
                    const courseIds = [...new Set(courseModules.map(cm => cm.course_id))];
                    const { count } = await supabase
                        .from('enrollments')
                        .select('*', { count: 'exact', head: true })
                        .in('course_id', courseIds);
                    setTotalEnrollmentsCount(count || 0);
                }
            }
        } catch (e) {
            console.error("Error estimating potential respondents", e);
        }

        if (r) {
            // Process responses to normalize metadata (some might have it in answers._metadata, others via joins)
            const processed = r.map(res => {
                const meta = res.answers?._metadata || {};
                const student = res.students as any || {};
                const enrollment = res.enrollments as any || {};
                const course = enrollment.courses as any || {};
                
                return {
                    ...res,
                    displayData: {
                        fecha: res.created_at,
                        nombre: meta.nombre_completo || `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'Desconocido',
                        identificacion: meta.identificacion || student.rut || student.passport || 'N/A',
                        empresa: meta.empresa_principal || student.companies?.name || 'N/A',
                        colab_empresa: meta.empresa_colaboradora || student.company_name || 'N/A',
                        cargo: meta.cargo || student.position || 'N/A',
                        curso: meta.nombre_curso || course.name || 'N/A'
                    }
                };
            });
            setResponses(processed);
            setFilteredResponses(processed);
        }
        setLoading(false);
    };

    useEffect(() => {
        applyFilters();
    }, [filters, responses]);

    const applyFilters = () => {
        let result = [...responses];

        if (filters.startDate) {
            result = result.filter(r => new Date(r.created_at) >= new Date(filters.startDate));
        }
        if (filters.endDate) {
            const end = new Date(filters.endDate);
            end.setHours(23, 59, 59, 999);
            result = result.filter(r => new Date(r.created_at) <= end);
        }
        if (filters.course) {
            result = result.filter(r => r.displayData.curso.toLowerCase().includes(filters.course.toLowerCase()));
        }
        if (filters.company) {
            result = result.filter(r => r.displayData.empresa.toLowerCase().includes(filters.company.toLowerCase()));
        }
        if (filters.colabCompany) {
            result = result.filter(r => r.displayData.colab_empresa.toLowerCase().includes(filters.colabCompany.toLowerCase()));
        }
        if (filters.position) {
            result = result.filter(r => r.displayData.cargo.toLowerCase().includes(filters.position.toLowerCase()));
        }

        setFilteredResponses(result);
    };

    const exportToExcel = () => {
        const dataToExport = filteredResponses.map(r => {
            const row: any = {
                'Fecha': new Date(r.displayData.fecha).toLocaleDateString(),
                'Alumno': r.displayData.nombre,
                'RUT/Pasaporte': r.displayData.identificacion,
                'Empresa Principal': r.displayData.empresa,
                'Empresa Colaboradora': r.displayData.colab_empresa,
                'Cargo': r.displayData.cargo,
                'Curso': r.displayData.curso,
            };

            // Add questions as columns
            questions.forEach(q => {
                row[q.text_es] = r.answers[q.id];
            });

            return row;
        });

        const worksheet = XLSX.utils.json_to_sheet(dataToExport);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Resultados");
        XLSX.writeFile(workbook, `Encuesta_${survey?.title_es}_${new Date().toISOString().split('T')[0]}.xlsx`);
    };

    const getChartData = (question: any) => {
        if (question.question_type === 'rating') {
            const counts = [0, 0, 0, 0, 0];
            filteredResponses.forEach(r => {
                const val = r.answers[question.id];
                if (val >= 1 && val <= 5) counts[val - 1]++;
            });
            return {
                labels: ['1 Estrellla', '2 Estrellas', '3 Estrellas', '4 Estrellas', '5 Estrellas'],
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
            filteredResponses.forEach(r => {
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
            filteredResponses.forEach(r => {
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

    const getGeneralStats = () => {
        const companyCounts: Record<string, number> = {};
        const courseCounts: Record<string, number> = {};

        filteredResponses.forEach(r => {
            const c = r.displayData.empresa;
            const crs = r.displayData.curso;
            companyCounts[c] = (companyCounts[c] || 0) + 1;
            courseCounts[crs] = (courseCounts[crs] || 0) + 1;
        });

        const sortedCompanies = Object.entries(companyCounts).sort((a,b) => b[1] - a[1]).slice(0, 5);
        const sortedCourses = Object.entries(courseCounts).sort((a,b) => b[1] - a[1]).slice(0, 5);

        return { sortedCompanies, sortedCourses };
    };

    if (isAuthorized === null) return (
        <div className="min-h-screen bg-black flex items-center justify-center">
            <div className="text-brand font-black animate-pulse uppercase tracking-widest text-xs">Analizando Reportes...</div>
        </div>
    );

    if (isAuthorized === false) return (
        <div className="min-h-screen bg-black flex flex-col items-center justify-center p-8 text-center space-y-6">
            <ShieldAlert className="w-20 h-20 text-red-500" />
            <h1 className="text-4xl font-black italic tracking-tighter uppercase text-white">Acceso Restringido</h1>
            <button onClick={() => router.push("/admin/metaverso/encuestas")} className="bg-white text-black px-8 py-4 rounded-xl font-black uppercase text-xs">Regresar</button>
        </div>
    );

    if (loading) return <div className="min-h-screen bg-black flex items-center justify-center text-brand font-black animate-pulse">ANALIZANDO DATOS...</div>;

    const { sortedCompanies, sortedCourses } = getGeneralStats();

    return (
        <div className="min-h-screen bg-[#060606] text-white p-6 md:p-10 font-sans">
            <div className="max-w-6xl mx-auto space-y-10 pb-20">
                <header className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <button onClick={() => router.back()} className="flex items-center gap-2 text-white/40 hover:text-white transition-colors text-xs font-black uppercase tracking-widest mb-4">
                            <ArrowLeft className="w-4 h-4" /> Volver a Encuestas
                        </button>
                        <h1 className="text-4xl font-black tracking-tight italic uppercase">Estadísticas: <span className="text-brand">{survey?.title_es}</span></h1>
                        <p className="text-white/40 font-medium">Análisis de retroalimentación de alumnos</p>
                    </div>
                    <div className="flex flex-wrap gap-4">
                        <div className="flex bg-white/5 border border-white/10 p-4 rounded-2xl items-center gap-4">
                            <div className="text-center min-w-[80px] border-r border-white/10 pr-4">
                                <span className="text-[10px] font-black uppercase text-white/40 block">Completadas</span>
                                <span className="text-2xl font-black text-brand">{filteredResponses.length}</span>
                            </div>
                            {totalEnrollmentsCount > 0 && (
                                <>
                                    <div className="text-center min-w-[80px]">
                                        <span className="text-[10px] font-black uppercase text-white/40 block">Total Inscritos</span>
                                        <span className="text-2xl font-black text-white">{totalEnrollmentsCount}</span>
                                    </div>
                                    <div className="w-12 h-12">
                                        <Pie 
                                            data={{
                                                labels: ['Hecho', 'Pendiente'],
                                                datasets: [{
                                                    data: [filteredResponses.length, Math.max(0, totalEnrollmentsCount - filteredResponses.length)],
                                                    backgroundColor: ['#AEFF00', 'rgba(255,255,255,0.1)'],
                                                    borderColor: 'transparent'
                                                }]
                                            }}
                                            options={{ plugins: { legend: { display: false } } }}
                                        />
                                    </div>
                                </>
                            )}
                        </div>
                        <button 
                            onClick={exportToExcel}
                            className="flex items-center gap-2 bg-brand text-black px-6 py-4 rounded-2xl font-black uppercase text-xs hover:scale-105 transition-all shadow-lg shadow-brand/20"
                        >
                            <FileSpreadsheet className="w-5 h-5" /> Exportar a Excel
                        </button>
                    </div>
                </header>

                {/* Filters Section */}
                <section className="bg-white/[0.02] border border-white/5 rounded-3xl p-6 md:p-8 space-y-6">
                    <div className="flex items-center gap-2 text-white/40 mb-2">
                        <Filter className="w-4 h-4" />
                        <h2 className="text-xs font-black uppercase tracking-widest">Filtros de Búsqueda</h2>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div className="space-y-1">
                            <label className="text-[10px] font-bold text-white/30 uppercase">Desde</label>
                            <input type="date" className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2 text-sm text-white" value={filters.startDate} onChange={e => setFilters({...filters, startDate: e.target.value})} />
                        </div>
                        <div className="space-y-1">
                            <label className="text-[10px] font-bold text-white/30 uppercase">Hasta</label>
                            <input type="date" className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2 text-sm text-white" value={filters.endDate} onChange={e => setFilters({...filters, endDate: e.target.value})} />
                        </div>
                        <div className="space-y-1">
                            <label className="text-[10px] font-bold text-white/30 uppercase">Empresa Principal</label>
                            <input type="text" placeholder="Buscar..." className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2 text-sm text-white" value={filters.company} onChange={e => setFilters({...filters, company: e.target.value})} />
                        </div>
                        <div className="space-y-1">
                            <label className="text-[10px] font-bold text-white/30 uppercase">Empresa Colaboradora</label>
                            <input type="text" placeholder="Buscar..." className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2 text-sm text-white" value={filters.colabCompany} onChange={e => setFilters({...filters, colabCompany: e.target.value})} />
                        </div>
                        <div className="space-y-1">
                            <label className="text-[10px] font-bold text-white/30 uppercase">Nombre del Curso</label>
                            <input type="text" placeholder="Buscar..." className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2 text-sm text-white" value={filters.course} onChange={e => setFilters({...filters, course: e.target.value})} />
                        </div>
                        <div className="space-y-1">
                            <label className="text-[10px] font-bold text-white/30 uppercase">Cargo</label>
                            <input type="text" placeholder="Buscar..." className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2 text-sm text-white" value={filters.position} onChange={e => setFilters({...filters, position: e.target.value})} />
                        </div>
                        <div className="flex items-end">
                            <button 
                                onClick={() => setFilters({ startDate: '', endDate: '', course: '', company: '', colabCompany: '', position: '', search: '' })}
                                className="w-full bg-white/5 hover:bg-white/10 text-white/40 hover:text-white py-2 rounded-xl text-[10px] font-black uppercase transition-all"
                            > Limpiar Filtros </button>
                        </div>
                    </div>
                </section>

                {/* Students Table */}
                <section className="bg-white/[0.02] border border-white/5 rounded-3xl overflow-hidden">
                    <div className="p-6 border-b border-white/5 flex items-center justify-between">
                        <h2 className="text-lg font-black italic uppercase tracking-tight">Registro de Participantes</h2>
                        <span className="text-[10px] font-bold text-white/40 uppercase bg-white/5 px-2 py-1 rounded-lg">{filteredResponses.length} resultados</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-white/[0.02]">
                                    <th className="px-6 py-4 text-[10px] font-black uppercase text-white/30 border-b border-white/5">Fecha</th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase text-white/30 border-b border-white/5">Empresa</th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase text-white/30 border-b border-white/5">Alumno</th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase text-white/30 border-b border-white/5">RUT/Passport</th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase text-white/30 border-b border-white/5">Empresa Colab.</th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase text-white/30 border-b border-white/5">Curso</th>
                                    {userRole === 'superadmin' && <th className="px-6 py-4 text-[10px] font-black uppercase text-white/30 border-b border-white/5 text-right">Acciones</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {filteredResponses.map((r, idx) => (
                                    <tr key={idx} className="hover:bg-white/[0.01] transition-colors group">
                                        <td className="px-6 py-4 text-xs text-white/60">{new Date(r.displayData.fecha).toLocaleDateString()}</td>
                                        <td className="px-6 py-4 text-xs text-white/60 uppercase font-medium">{r.displayData.empresa}</td>
                                        <td className="px-6 py-4 text-xs font-bold text-white">{r.displayData.nombre}</td>
                                        <td className="px-6 py-4 text-xs text-white/40">{r.displayData.identificacion}</td>
                                        <td className="px-6 py-4 text-xs text-white/60">{r.displayData.colab_empresa}</td>
                                        <td className="px-6 py-4 text-xs text-brand/80 font-medium">{r.displayData.curso}</td>
                                        {userRole === 'superadmin' && (
                                            <td className="px-6 py-4 text-right">
                                                <button onClick={() => handleDeleteResponse(r.id)} className="p-2 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                {/* Metrics Charts */}
                <div className="grid grid-cols-1 gap-10">
                    <div className="flex items-center gap-2 mb-[-20px]">
                        <BarChart3 className="w-5 h-5 text-brand" />
                        <h2 className="text-xl font-black italic uppercase italic tracking-tighter">Resultados de Preguntas</h2>
                    </div>
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
                                            {filteredResponses.map((r, rIdx) => (
                                                r.answers[q.id] && (
                                                    <div key={rIdx} className="bg-white/5 p-4 rounded-xl border border-white/5">
                                                        <div className="flex justify-between items-center mb-2">
                                                            <span className="text-[10px] font-black text-brand uppercase">{r.displayData.nombre}</span>
                                                            <span className="text-[9px] text-white/20">{new Date(r.created_at).toLocaleDateString()}</span>
                                                        </div>
                                                        <p className="text-sm text-white/70 italic leading-relaxed">"{r.answers[q.id]}"</p>
                                                    </div>
                                                )
                                            ))}
                                            {!filteredResponses.some(r => r.answers[q.id]) && <p className="text-white/20 text-xs text-center py-10">No hay comentarios aún.</p>}
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

                {/* General Stats section */}
                <section className="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div className="bg-white/[0.02] border border-white/5 rounded-3xl p-8 space-y-6">
                        <div className="flex items-center gap-2 text-brand">
                            <PieChart className="w-5 h-5" />
                            <h3 className="text-xl font-black italic uppercase italic tracking-tighter">Empresas con más Participación</h3>
                        </div>
                        <div className="space-y-4">
                            {sortedCompanies.map(([name, count], idx) => (
                                <div key={idx} className="space-y-1">
                                    <div className="flex justify-between text-xs font-bold px-1">
                                        <span className="text-white/60">{name}</span>
                                        <span className="text-brand">{count} encuestas</span>
                                    </div>
                                    <div className="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                        <div className="h-full bg-brand" style={{ width: `${(count / filteredResponses.length) * 100}%` }} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="bg-white/[0.02] border border-white/5 rounded-3xl p-8 space-y-6">
                        <div className="flex items-center gap-2 text-brand">
                            <BarChart3 className="w-5 h-5" />
                            <h3 className="text-xl font-black italic uppercase italic tracking-tighter">Cursos más Completados</h3>
                        </div>
                        <div className="space-y-4">
                            {sortedCourses.map(([name, count], idx) => (
                                <div key={idx} className="space-y-1">
                                    <div className="flex justify-between text-xs font-bold px-1">
                                        <span className="text-white/60">{name}</span>
                                        <span className="text-brand">{count} encuestas</span>
                                    </div>
                                    <div className="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                        <div className="h-full bg-blue-500" style={{ width: `${(count / filteredResponses.length) * 100}%` }} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
}
