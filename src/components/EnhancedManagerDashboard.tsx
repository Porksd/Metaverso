"use client";

import { useEffect, useState, useMemo } from "react";
import { motion } from "framer-motion";
import {
    Users, BookOpen, Award, TrendingUp, Clock, CheckCircle2,
    AlertCircle, Download, Calendar, Filter, X, BarChart3
} from "lucide-react";
import { supabase } from "@/lib/supabase";
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
    ChartOptions,
    ArcElement
} from 'chart.js';
import { Bar, Line } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
);

interface Enrollment {
    id: string;
    student_id: string;
    course_id: string;
    status: string;
    best_score: string;
    completed_at: string | null;
    created_at: string;
    students: {
        rut: string;
        first_name: string;
        last_name: string;
        age: number;
        gender: string;
        company_name: string;
    };
    courses: {
        name: string;
        code: string;
    };
}

interface DailyActivity {
    date: string;
    uniqueStudents: number;
    completedCourses: number;
    avgScore: number;
}

interface CourseStats {
    courseName: string;
    courseCode: string;
    enrolled: number;
    inProgress: number;
    completed: number;
    avgScore: number;
    medianScore: number;
    totalResponses: number;
}

interface StudentCourseCount {
    rut: string;
    studentName: string;
    totalCourses: number;
    completedCourses: number;
}

interface DemographicStats {
    ageGroups: { [key: string]: { avg: number; median: number; count: number } };
    genderGroups: { [key: string]: { avg: number; median: number; count: number } };
}

export default function EnhancedManagerDashboard({ companyName, companyId }: { companyName: string, companyId?: string }) {
    const [enrollments, setEnrollments] = useState<Enrollment[]>([]);
    const [loading, setLoading] = useState(true);
    const [dateFilter, setDateFilter] = useState<string>("");
    const [showFilters, setShowFilters] = useState(false);

    useEffect(() => {
        fetchData();
    }, [companyName, companyId]);

    const fetchData = async () => {
        setLoading(true);

        let query = supabase
            .from('enrollments')
            .select(`
                *,
                students!inner(rut, first_name, last_name, age, gender, company_name, client_id),
                courses(name, code)
            `);

        if (companyId) {
            query = query.eq('students.client_id', companyId);
        } else {
            query = query.eq('students.company_name', companyName);
        }

        const { data, error } = await query.order('created_at', { ascending: false });

        if (error) {
            console.error('Error fetching enrollments:', error);
        } else {
            setEnrollments(data as any || []);
        }

        setLoading(false);
    };

    // Filtrar enrollments por fecha
    const filteredEnrollments = useMemo(() => {
        if (!dateFilter) return enrollments;

        return enrollments.filter(e => {
            const enrollDate = new Date(e.created_at).toISOString().split('T')[0];
            return enrollDate === dateFilter;
        });
    }, [enrollments, dateFilter]);

    // Calcular métricas principales
    const stats = useMemo(() => {
        const uniqueStudents = new Set(filteredEnrollments.map(e => e.students.rut)).size;
        const uniqueCourses = new Set(filteredEnrollments.map(e => e.course_id)).size;
        const totalEnrollments = filteredEnrollments.length;
        const completedEnrollments = filteredEnrollments.filter(e => e.status === 'completed').length;
        const inProgressEnrollments = filteredEnrollments.filter(e => e.status === 'in_progress').length;

        const completedScores = filteredEnrollments
            .filter(e => e.status === 'completed' && e.best_score)
            .map(e => parseFloat(e.best_score));

        const avgScore = completedScores.length > 0
            ? Math.round(completedScores.reduce((a, b) => a + b, 0) / completedScores.length)
            : 0;

        const completionRate = totalEnrollments > 0
            ? Math.round((completedEnrollments / totalEnrollments) * 100)
            : 0;

        return {
            uniqueStudents,
            uniqueCourses,
            totalEnrollments,
            completedEnrollments,
            inProgressEnrollments,
            avgScore,
            completionRate
        };
    }, [filteredEnrollments]);

    // Actividad diaria
    const dailyActivity = useMemo((): DailyActivity[] => {
        const dailyMap = new Map<string, { students: Set<string>; completed: number; scores: number[] }>();

        filteredEnrollments.forEach(e => {
            const date = new Date(e.created_at).toISOString().split('T')[0];
            if (!dailyMap.has(date)) {
                dailyMap.set(date, { students: new Set(), completed: 0, scores: [] });
            }

            const day = dailyMap.get(date)!;
            day.students.add(e.students.rut);

            if (e.status === 'completed') {
                day.completed++;
                if (e.best_score) {
                    day.scores.push(parseFloat(e.best_score));
                }
            }
        });

        return Array.from(dailyMap.entries())
            .map(([date, data]) => ({
                date,
                uniqueStudents: data.students.size,
                completedCourses: data.completed,
                avgScore: data.scores.length > 0
                    ? Math.round(data.scores.reduce((a, b) => a + b, 0) / data.scores.length)
                    : 0
            }))
            .sort((a, b) => a.date.localeCompare(b.date));
    }, [filteredEnrollments]);

    // Estadísticas por curso
    const courseStats = useMemo((): CourseStats[] => {
        const courseMap = new Map<string, {
            courseName: string;
            courseCode: string;
            enrolled: number;
            inProgress: number;
            completed: number;
            scores: number[];
        }>();

        filteredEnrollments.forEach(e => {
            const courseKey = e.course_id;
            const courseName = e.courses?.name || 'Sin nombre';
            const courseCode = e.courses?.code || '';

            if (!courseMap.has(courseKey)) {
                courseMap.set(courseKey, {
                    courseName,
                    courseCode,
                    enrolled: 0,
                    inProgress: 0,
                    completed: 0,
                    scores: []
                });
            }

            const course = courseMap.get(courseKey)!;
            course.enrolled++;

            if (e.status === 'in_progress') course.inProgress++;
            if (e.status === 'completed') {
                course.completed++;
                if (e.best_score) {
                    course.scores.push(parseFloat(e.best_score));
                }
            }
        });

        return Array.from(courseMap.values()).map(c => {
            const sortedScores = [...c.scores].sort((a, b) => a - b);
            const median = sortedScores.length > 0
                ? sortedScores.length % 2 === 0
                    ? (sortedScores[sortedScores.length / 2 - 1] + sortedScores[sortedScores.length / 2]) / 2
                    : sortedScores[Math.floor(sortedScores.length / 2)]
                : 0;

            return {
                courseName: c.courseName,
                courseCode: c.courseCode,
                enrolled: c.enrolled,
                inProgress: c.inProgress,
                completed: c.completed,
                avgScore: c.scores.length > 0
                    ? Math.round(c.scores.reduce((a, b) => a + b, 0) / c.scores.length)
                    : 0,
                medianScore: Math.round(median),
                totalResponses: c.enrolled
            };
        }).sort((a, b) => b.avgScore - a.avgScore);
    }, [filteredEnrollments]);

    // Distribución: cursos por estudiante
    const studentCourseDistribution = useMemo((): StudentCourseCount[] => {
        const studentMap = new Map<string, {
            studentName: string;
            totalCourses: number;
            completedCourses: number;
        }>();

        filteredEnrollments.forEach(e => {
            const rut = e.students.rut;
            const name = `${e.students.first_name} ${e.students.last_name}`;

            if (!studentMap.has(rut)) {
                studentMap.set(rut, { studentName: name, totalCourses: 0, completedCourses: 0 });
            }

            const student = studentMap.get(rut)!;
            student.totalCourses++;
            if (e.status === 'completed') {
                student.completedCourses++;
            }
        });

        return Array.from(studentMap.entries())
            .map(([rut, data]) => ({ rut, ...data }))
            .sort((a, b) => b.totalCourses - a.totalCourses);
    }, [filteredEnrollments]);

    // Distribución de cursos (histogram)
    const courseDistributionHistogram = useMemo(() => {
        const histogram: { [key: number]: number } = {};

        studentCourseDistribution.forEach(s => {
            const count = s.totalCourses;
            histogram[count] = (histogram[count] || 0) + 1;
        });

        const labels = Object.keys(histogram).sort((a, b) => Number(a) - Number(b));
        const data = labels.map(k => histogram[Number(k)]);

        return { labels, data };
    }, [studentCourseDistribution]);

    // Estudiantes con 8+ cursos
    const students8Plus = useMemo(() => {
        return studentCourseDistribution.filter(s => s.totalCourses >= 8).length;
    }, [studentCourseDistribution]);

    // Análisis demográfico (edad y género)
    const demographicStats = useMemo((): DemographicStats => {
        const ageGroups: { [key: string]: number[] } = {};
        const genderGroups: { [key: string]: number[] } = {};

        const completedEnrollments = filteredEnrollments.filter(e => e.status === 'completed' && e.best_score);

        completedEnrollments.forEach(e => {
            const score = parseFloat(e.best_score);
            const age = e.students.age;
            const gender = e.students.gender || 'No declarado';

            // Agrupar por edad
            let ageGroup = 'No declarado';
            if (age) {
                if (age < 18) ageGroup = '<18';
                else if (age < 25) ageGroup = '18-24';
                else if (age < 35) ageGroup = '25-34';
                else if (age < 50) ageGroup = '35-49';
                else ageGroup = '50+';
            }

            if (!ageGroups[ageGroup]) ageGroups[ageGroup] = [];
            ageGroups[ageGroup].push(score);

            // Agrupar por género
            if (!genderGroups[gender]) genderGroups[gender] = [];
            genderGroups[gender].push(score);
        });

        const processGroup = (groups: { [key: string]: number[] }) => {
            const result: { [key: string]: { avg: number; median: number; count: number } } = {};
            Object.entries(groups).forEach(([key, scores]) => {
                const sorted = [...scores].sort((a, b) => a - b);
                const median = sorted.length > 0
                    ? sorted.length % 2 === 0
                        ? (sorted[sorted.length / 2 - 1] + sorted[sorted.length / 2]) / 2
                        : sorted[Math.floor(sorted.length / 2)]
                    : 0;
                const avg = scores.length > 0 ? scores.reduce((a, b) => a + b, 0) / scores.length : 0;
                result[key] = {
                    avg: Math.round(avg),
                    median: Math.round(median),
                    count: scores.length
                };
            });
            return result;
        };

        return {
            ageGroups: processGroup(ageGroups),
            genderGroups: processGroup(genderGroups)
        };
    }, [filteredEnrollments]);

    // Exportar datos
    const exportData = () => {
        const exportPayload = {
            stats,
            dailyActivity,
            courseStats,
            studentCourseDistribution,
            demographicStats,
            generatedAt: new Date().toISOString()
        };

        // JSON
        const jsonBlob = new Blob([JSON.stringify(exportPayload, null, 2)], { type: 'application/json' });
        const jsonUrl = URL.createObjectURL(jsonBlob);
        const jsonLink = document.createElement('a');
        jsonLink.href = jsonUrl;
        jsonLink.download = `dashboard-${companyName}-${new Date().toISOString().split('T')[0]}.json`;
        jsonLink.click();
        URL.revokeObjectURL(jsonUrl);

        // CSV de cursos
        if (courseStats.length > 0) {
            const headers = ['Curso', 'Código', 'Inscritos', 'En Progreso', 'Completados', 'Promedio', 'Mediana'];
            const rows = courseStats.map(c => [
                c.courseName,
                c.courseCode,
                c.enrolled,
                c.inProgress,
                c.completed,
                c.avgScore,
                c.medianScore
            ]);
            const csvContent = [headers, ...rows].map(row => row.join(',')).join('\n');
            const csvBlob = new Blob([csvContent], { type: 'text/csv' });
            const csvUrl = URL.createObjectURL(csvBlob);
            const csvLink = document.createElement('a');
            csvLink.href = csvUrl;
            csvLink.download = `cursos-${companyName}-${new Date().toISOString().split('T')[0]}.csv`;
            csvLink.click();
            URL.revokeObjectURL(csvUrl);
        }
    };

    if (loading) {
        return <div className="text-white/40 animate-pulse p-8">Cargando dashboard...</div>;
    }

    // Configuración de gráficos
    const chartOptions: ChartOptions<'bar' | 'line'> = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
                labels: { color: '#cbd5e1' }
            },
            tooltip: {
                backgroundColor: '#0b1220',
                borderColor: '#1f2937',
                borderWidth: 1
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(148,163,184,0.18)' },
                ticks: { color: '#94a3b8' }
            },
            y: {
                grid: { color: 'rgba(148,163,184,0.18)' },
                ticks: { color: '#94a3b8' },
                beginAtZero: true
            }
        }
    };

    return (
        <div className="space-y-8">
            {/* Controles superiores */}
            <div className="glass p-4 rounded-2xl border-white/5 flex items-center justify-between flex-wrap gap-4">
                <div className="flex items-center gap-3">
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className={`px-4 py-2 rounded-xl border transition-all flex items-center gap-2 ${showFilters ? 'bg-brand/20 border-brand/40' : 'bg-white/5 border-white/10 hover:border-white/20'
                            }`}
                    >
                        <Filter className="w-4 h-4" />
                        Filtros
                    </button>

                    {showFilters && (
                        <motion.div
                            initial={{ opacity: 0, x: -10 }}
                            animate={{ opacity: 1, x: 0 }}
                            className="flex items-center gap-3"
                        >
                            <input
                                type="date"
                                value={dateFilter}
                                onChange={(e) => setDateFilter(e.target.value)}
                                className="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-brand/40"
                            />
                            {dateFilter && (
                                <button
                                    onClick={() => setDateFilter("")}
                                    className="p-2 hover:bg-white/5 rounded-lg transition-all"
                                    title="Limpiar filtro"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            )}
                        </motion.div>
                    )}
                </div>

                <button
                    onClick={exportData}
                    className="px-4 py-2 bg-brand/20 border border-brand/40 rounded-xl hover:bg-brand/30 transition-all flex items-center gap-2"
                >
                    <Download className="w-4 h-4" />
                    Exportar Datos
                </button>
            </div>

            {/* KPIs Principales */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {[
                    { label: "Estudiantes Únicos", value: stats.uniqueStudents, icon: Users, color: "brand" },
                    { label: "Cursos Activos", value: stats.uniqueCourses, icon: BookOpen, color: "blue-400" },
                    { label: "Tasa Completitud", value: `${stats.completionRate}%`, icon: TrendingUp, color: "brand" },
                    { label: "Promedio Global", value: `${stats.avgScore}%`, icon: Award, color: "yellow-400" },
                ].map((stat, i) => (
                    <motion.div
                        key={i}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: i * 0.05 }}
                        className="glass p-5 rounded-2xl border-white/5 relative overflow-hidden group hover:border-white/20 transition-all"
                    >
                        <div className="relative z-10 space-y-2">
                            <div className="flex items-center justify-between">
                                <stat.icon className={`w-6 h-6 text-${stat.color} opacity-60`} />
                            </div>
                            <p className="text-3xl font-black tracking-tighter">{stat.value}</p>
                            <span className="text-[10px] text-white/30 font-bold uppercase tracking-widest">{stat.label}</span>
                        </div>
                    </motion.div>
                ))}
            </div>

            {/* Stat especial: Estudiantes con 8+ cursos */}
            {stats.uniqueStudents > 0 && (
                <div className="glass p-5 rounded-2xl border-white/5">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-white/40">Estudiantes con 8+ cursos</p>
                            <p className="text-4xl font-black text-brand">{students8Plus}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-sm text-white/40">% del total</p>
                            <p className="text-2xl font-bold">
                                {Math.round((students8Plus / stats.uniqueStudents) * 100)}%
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Gráficos principales en grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Actividad diaria */}
                <div className="glass p-6 rounded-2xl border-white/5 space-y-4">
                    <div className="flex items-center gap-2">
                        <Calendar className="w-5 h-5 text-brand" />
                        <h3 className="text-lg font-bold">Actividad Diaria</h3>
                    </div>
                    <div className="h-64">
                        {dailyActivity.length > 0 ? (
                            <Line
                                options={chartOptions as any}
                                data={{
                                    labels: dailyActivity.map(d => d.date),
                                    datasets: [
                                        {
                                            label: 'Estudiantes Únicos',
                                            data: dailyActivity.map(d => d.uniqueStudents),
                                            borderColor: '#31D22D',
                                            backgroundColor: 'rgba(49, 210, 45, 0.1)',
                                            tension: 0.4
                                        },
                                        {
                                            label: 'Cursos Completados',
                                            data: dailyActivity.map(d => d.completedCourses),
                                            borderColor: '#60a5fa',
                                            backgroundColor: 'rgba(96, 165, 250, 0.1)',
                                            tension: 0.4
                                        }
                                    ]
                                }}
                            />
                        ) : (
                            <div className="flex items-center justify-center h-full text-white/30">Sin datos</div>
                        )}
                    </div>
                </div>

                {/* Distribución de cursos por estudiante */}
                <div className="glass p-6 rounded-2xl border-white/5 space-y-4">
                    <div className="flex items-center gap-2">
                        <BarChart3 className="w-5 h-5 text-brand" />
                        <h3 className="text-lg font-bold">Cursos por Estudiante</h3>
                    </div>
                    <div className="h-64">
                        {courseDistributionHistogram.labels.length > 0 ? (
                            <Bar
                                options={chartOptions as any}
                                data={{
                                    labels: courseDistributionHistogram.labels.map(l => `${l} curso${Number(l) > 1 ? 's' : ''}`),
                                    datasets: [
                                        {
                                            label: 'Estudiantes',
                                            data: courseDistributionHistogram.data,
                                            backgroundColor: '#22d3ee',
                                            borderRadius: 6
                                        }
                                    ]
                                }}
                            />
                        ) : (
                            <div className="flex items-center justify-center h-full text-white/30">Sin datos</div>
                        )}
                    </div>
                    <p className="text-xs text-white/40 text-center">
                        Máximo: {Math.max(...studentCourseDistribution.map(s => s.totalCourses), 0)} cursos
                    </p>
                </div>

                {/* Análisis por edad */}
                {Object.keys(demographicStats.ageGroups).length > 0 && (
                    <div className="glass p-6 rounded-2xl border-white/5 space-y-4">
                        <h3 className="text-lg font-bold">Promedio por Edad</h3>
                        <div className="h-64">
                            <Bar
                                options={chartOptions as any}
                                data={{
                                    labels: Object.keys(demographicStats.ageGroups).sort(),
                                    datasets: [
                                        {
                                            label: 'Promedio (%)',
                                            data: Object.keys(demographicStats.ageGroups).sort().map(k => demographicStats.ageGroups[k].avg),
                                            backgroundColor: '#22c55e',
                                            borderRadius: 6
                                        },
                                        {
                                            label: 'Mediana (%)',
                                            data: Object.keys(demographicStats.ageGroups).sort().map(k => demographicStats.ageGroups[k].median),
                                            backgroundColor: '#a78bfa',
                                            borderRadius: 6
                                        }
                                    ]
                                }}
                            />
                        </div>
                    </div>
                )}

                {/* Análisis por género */}
                {Object.keys(demographicStats.genderGroups).length > 0 && (
                    <div className="glass p-6 rounded-2xl border-white/5 space-y-4">
                        <h3 className="text-lg font-bold">Promedio por Género</h3>
                        <div className="h-64">
                            <Bar
                                options={chartOptions as any}
                                data={{
                                    labels: Object.keys(demographicStats.genderGroups),
                                    datasets: [
                                        {
                                            label: 'Promedio (%)',
                                            data: Object.keys(demographicStats.genderGroups).map(k => demographicStats.genderGroups[k].avg),
                                            backgroundColor: '#f59e0b',
                                            borderRadius: 6
                                        },
                                        {
                                            label: 'Mediana (%)',
                                            data: Object.keys(demographicStats.genderGroups).map(k => demographicStats.genderGroups[k].median),
                                            backgroundColor: '#a78bfa',
                                            borderRadius: 6
                                        }
                                    ]
                                }}
                            />
                        </div>
                    </div>
                )}
            </div>

            {/* Tabla detallada de cursos */}
            <div className="glass p-6 rounded-2xl border-white/5">
                <h3 className="text-xl font-black mb-4 flex items-center gap-2">
                    <BookOpen className="w-5 h-5 text-brand" />
                    Rendimiento Detallado por Curso
                </h3>

                {courseStats.length === 0 ? (
                    <div className="text-center py-12 text-white/40">
                        <BookOpen className="w-12 h-12 mx-auto mb-4 opacity-20" />
                        <p>No hay cursos con datos disponibles</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-white/10 text-left text-white/40 uppercase text-xs tracking-wider">
                                    <th className="pb-3 font-bold">Curso</th>
                                    <th className="pb-3 font-bold text-center">Inscritos</th>
                                    <th className="pb-3 font-bold text-center">En Progreso</th>
                                    <th className="pb-3 font-bold text-center">Completados</th>
                                    <th className="pb-3 font-bold text-center">Promedio</th>
                                    <th className="pb-3 font-bold text-center">Mediana</th>
                                    <th className="pb-3 font-bold text-center">% Completitud</th>
                                </tr>
                            </thead>
                            <tbody>
                                {courseStats.map((course, i) => {
                                    const completionRate = course.enrolled > 0
                                        ? Math.round((course.completed / course.enrolled) * 100)
                                        : 0;

                                    return (
                                        <motion.tr
                                            key={i}
                                            initial={{ opacity: 0, x: -10 }}
                                            animate={{ opacity: 1, x: 0 }}
                                            transition={{ delay: i * 0.03 }}
                                            className="border-b border-white/5 hover:bg-white/[0.02] transition-all"
                                        >
                                            <td className="py-4">
                                                <div>
                                                    <p className="font-bold">{course.courseName}</p>
                                                    {course.courseCode && (
                                                        <p className="text-xs text-white/40">{course.courseCode}</p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 text-center font-semibold">{course.enrolled}</td>
                                            <td className="py-4 text-center">
                                                <span className="px-2 py-1 bg-blue-500/20 text-blue-400 rounded-lg text-xs font-bold">
                                                    {course.inProgress}
                                                </span>
                                            </td>
                                            <td className="py-4 text-center">
                                                <span className="px-2 py-1 bg-brand/20 text-brand rounded-lg text-xs font-bold">
                                                    {course.completed}
                                                </span>
                                            </td>
                                            <td className="py-4 text-center font-bold text-brand text-lg">
                                                {course.avgScore}%
                                            </td>
                                            <td className="py-4 text-center text-white/60">
                                                {course.medianScore}%
                                            </td>
                                            <td className="py-4 text-center">
                                                <div className="flex items-center gap-2">
                                                    <div className="flex-1 h-2 bg-white/5 rounded-full overflow-hidden">
                                                        <div
                                                            className="h-full bg-gradient-to-r from-brand to-blue-400 rounded-full transition-all duration-500"
                                                            style={{ width: `${completionRate}%` }}
                                                        />
                                                    </div>
                                                    <span className="text-xs font-bold w-10">{completionRate}%</span>
                                                </div>
                                            </td>
                                        </motion.tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Top estudiantes con más cursos */}
            {studentCourseDistribution.length > 0 && (
                <div className="glass p-6 rounded-2xl border-white/5">
                    <h3 className="text-xl font-black mb-4 flex items-center gap-2">
                        <Award className="w-5 h-5 text-brand" />
                        Top Estudiantes (por cursos inscritos)
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {studentCourseDistribution.slice(0, 6).map((student, i) => (
                            <motion.div
                                key={i}
                                initial={{ opacity: 0, scale: 0.95 }}
                                animate={{ opacity: 1, scale: 1 }}
                                transition={{ delay: i * 0.05 }}
                                className="bg-white/[0.02] border border-white/5 rounded-xl p-4 hover:bg-white/[0.04] transition-all"
                            >
                                <div className="flex items-center justify-between mb-2">
                                    <p className="font-bold text-sm">{student.studentName}</p>
                                    <span className="text-xs bg-brand/20 text-brand px-2 py-1 rounded-lg font-bold">
                                        #{i + 1}
                                    </span>
                                </div>
                                <p className="text-xs text-white/40 mb-3">{student.rut}</p>
                                <div className="flex items-center justify-between text-xs">
                                    <span className="text-white/60">Total cursos:</span>
                                    <span className="font-bold text-xl">{student.totalCourses}</span>
                                </div>
                                <div className="flex items-center justify-between text-xs mt-1">
                                    <span className="text-white/60">Completados:</span>
                                    <span className="font-bold text-brand">{student.completedCourses}</span>
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </div>
            )}

            {/* Alertas y recomendaciones */}
            <div className="glass p-6 rounded-2xl border-white/5">
                <h3 className="text-lg font-bold mb-4 flex items-center gap-2">
                    <AlertCircle className="w-5 h-5 text-yellow-400" />
                    Insights y Recomendaciones
                </h3>
                <div className="space-y-3 text-sm">
                    {stats.completionRate < 50 && (
                        <div className="flex items-start gap-3 p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-xl">
                            <AlertCircle className="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-yellow-400">Tasa de completitud baja ({stats.completionRate}%)</p>
                                <p className="text-white/60">Considera enviar recordatorios a los colaboradores para completar sus cursos.</p>
                            </div>
                        </div>
                    )}
                    {stats.avgScore < 70 && stats.completedEnrollments > 0 && (
                        <div className="flex items-start gap-3 p-4 bg-orange-500/10 border border-orange-500/20 rounded-xl">
                            <AlertCircle className="w-5 h-5 text-orange-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-orange-400">Promedio general bajo ({stats.avgScore}%)</p>
                                <p className="text-white/60">Considera revisar el nivel de dificultad o proporcionar material de apoyo adicional.</p>
                            </div>
                        </div>
                    )}
                    {stats.inProgressEnrollments > stats.completedEnrollments * 2 && stats.completedEnrollments > 0 && (
                        <div className="flex items-start gap-3 p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl">
                            <Clock className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-blue-400">Muchos cursos en progreso</p>
                                <p className="text-white/60">Hay {stats.inProgressEnrollments} cursos iniciados vs {stats.completedEnrollments} completados. Motiva a los estudiantes a finalizar.</p>
                            </div>
                        </div>
                    )}
                    {students8Plus > 0 && (
                        <div className="flex items-start gap-3 p-4 bg-brand/10 border border-brand/20 rounded-xl">
                            <Award className="w-5 h-5 text-brand flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-brand">¡Excelente compromiso!</p>
                                <p className="text-white/60">{students8Plus} estudiante{students8Plus > 1 ? 's han' : ' ha'} completado 8 o más cursos. Considera reconocer su esfuerzo.</p>
                            </div>
                        </div>
                    )}
                    {stats.totalEnrollments === 0 && (
                        <div className="flex items-start gap-3 p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl">
                            <BookOpen className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-blue-400">Comienza asignando cursos</p>
                                <p className="text-white/60">No hay inscripciones aún. Dirígete a la sección de administración para asignar cursos.</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
