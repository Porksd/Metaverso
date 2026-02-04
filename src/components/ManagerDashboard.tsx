"use client";

import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import { Users, BookOpen, Award, TrendingUp, Clock, CheckCircle2, AlertCircle } from "lucide-react";
import { supabase } from "@/lib/supabase";

interface DashboardStats {
    totalStudents: number;
    totalCourses: number;
    totalEnrollments: number;
    completedEnrollments: number;
    avgScore: number;
    courseStats: Array<{
        courseName: string;
        enrolled: number;
        completed: number;
        avgScore: number;
    }>;
}

export default function ManagerDashboard({ companyId }: { companyId: string }) {
    const [stats, setStats] = useState<DashboardStats | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchStats();
    }, [companyId]);

    const fetchStats = async () => {
        setLoading(true);

        // Total de estudiantes
        const { count: totalStudents } = await supabase
            .from('students')
            .select('*', { count: 'exact', head: true })
            .eq('client_id', companyId);

        // Total de cursos (asumiendo que todos los cursos están disponibles)
        const { count: totalCourses } = await supabase
            .from('courses')
            .select('*', { count: 'exact', head: true });

        // Enrollments
        const { data: enrollments } = await supabase
            .from('enrollments')
            .select('*, students!inner(client_id), courses(name)')
            .eq('students.client_id', companyId);

        const totalEnrollments = enrollments?.length || 0;
        const completedEnrollments = enrollments?.filter(e => e.status === 'completed').length || 0;
        const avgScore = completedEnrollments > 0
            ? Math.round(enrollments!.filter(e => e.status === 'completed').reduce((acc, curr) => acc + (parseFloat(curr.best_score) || 0), 0) / completedEnrollments)
            : 0;

        // Stats por curso
        const courseMap = new Map<string, { enrolled: number; completed: number; scores: number[] }>();
        enrollments?.forEach(e => {
            const courseName = e.courses?.name || 'Sin nombre';
            if (!courseMap.has(courseName)) {
                courseMap.set(courseName, { enrolled: 0, completed: 0, scores: [] });
            }
            const stat = courseMap.get(courseName)!;
            stat.enrolled++;
            if (e.status === 'completed') {
                stat.completed++;
                stat.scores.push(parseFloat(e.best_score) || 0);
            }
        });

        const courseStats = Array.from(courseMap.entries()).map(([courseName, data]) => ({
            courseName,
            enrolled: data.enrolled,
            completed: data.completed,
            avgScore: data.scores.length > 0 ? Math.round(data.scores.reduce((a, b) => a + b, 0) / data.scores.length) : 0
        }));

        setStats({
            totalStudents: totalStudents || 0,
            totalCourses: totalCourses || 0,
            totalEnrollments,
            completedEnrollments,
            avgScore,
            courseStats
        });

        setLoading(false);
    };

    if (loading) {
        return <div className="text-white/40 animate-pulse p-8">Cargando estadísticas...</div>;
    }

    if (!stats) return null;

    const completionRate = stats.totalEnrollments > 0
        ? Math.round((stats.completedEnrollments / stats.totalEnrollments) * 100)
        : 0;

    return (
        <div className="space-y-8">
            {/* KPIs Principales */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {[
                    { label: "Colaboradores", value: stats.totalStudents, icon: Users, color: "brand" },
                    { label: "Cursos Activos", value: stats.totalCourses, icon: BookOpen, color: "blue-400" },
                    { label: "Tasa Completitud", value: `${completionRate}%`, icon: TrendingUp, color: "brand" },
                    { label: "Promedio Global", value: `${stats.avgScore}%`, icon: Award, color: "yellow-400" },
                ].map((stat, i) => (
                    <motion.div
                        key={i}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: i * 0.1 }}
                        className="glass p-6 rounded-3xl border-white/5 relative overflow-hidden group hover:border-white/20 transition-all"
                    >
                        <div className={`absolute top-0 right-0 w-32 h-32 bg-${stat.color}/5 blur-3xl group-hover:bg-${stat.color}/10 transition-all`} />
                        <div className="relative z-10 space-y-3">
                            <div className="flex items-center justify-between">
                                <stat.icon className={`w-8 h-8 text-${stat.color} opacity-60`} />
                                <span className="text-xs text-white/30 font-black uppercase tracking-widest">{stat.label}</span>
                            </div>
                            <p className="text-4xl font-black tracking-tighter">{stat.value}</p>
                        </div>
                    </motion.div>
                ))}
            </div>

            {/* Estadísticas por Curso */}
            <div className="glass p-8 rounded-3xl border-white/5">
                <div className="flex items-center justify-between mb-6">
                    <h3 className="text-2xl font-black tracking-tight flex items-center gap-3">
                        <div className="w-2.5 h-8 bg-brand rounded-full shadow-[0_0_15px_#31D22D]" />
                        Rendimiento por Curso
                    </h3>
                </div>

                <div className="space-y-4">
                    {stats.courseStats.length === 0 ? (
                        <div className="text-center py-12 text-white/40">
                            <BookOpen className="w-12 h-12 mx-auto mb-4 opacity-20" />
                            <p>No hay cursos asignados aún</p>
                        </div>
                    ) : (
                        stats.courseStats.map((course, i) => {
                            const courseCompletionRate = course.enrolled > 0
                                ? Math.round((course.completed / course.enrolled) * 100)
                                : 0;

                            return (
                                <motion.div
                                    key={i}
                                    initial={{ opacity: 0, x: -20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    transition={{ delay: i * 0.1 }}
                                    className="bg-white/[0.02] border border-white/5 rounded-2xl p-6 hover:bg-white/[0.04] transition-all"
                                >
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="flex-1">
                                            <h4 className="font-bold text-lg mb-1">{course.courseName}</h4>
                                            <div className="flex items-center gap-4 text-xs text-white/40">
                                                <span className="flex items-center gap-1">
                                                    <Users className="w-3 h-3" />
                                                    {course.enrolled} inscritos
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <CheckCircle2 className="w-3 h-3" />
                                                    {course.completed} completados
                                                </span>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <div className="text-2xl font-black text-brand">{course.avgScore}%</div>
                                            <div className="text-[10px] text-white/30 uppercase tracking-widest font-black">Promedio</div>
                                        </div>
                                    </div>

                                    {/* Barra de progreso */}
                                    <div className="space-y-2">
                                        <div className="flex justify-between text-xs text-white/40">
                                            <span>Completitud</span>
                                            <span className="font-bold">{courseCompletionRate}%</span>
                                        </div>
                                        <div className="h-2 bg-white/5 rounded-full overflow-hidden">
                                            <motion.div
                                                initial={{ width: 0 }}
                                                animate={{ width: `${courseCompletionRate}%` }}
                                                transition={{ duration: 1, delay: i * 0.1 }}
                                                className="h-full bg-gradient-to-r from-brand to-blue-400 rounded-full"
                                            />
                                        </div>
                                    </div>
                                </motion.div>
                            );
                        })
                    )}
                </div>
            </div>

            {/* Alertas y Recomendaciones */}
            <div className="glass p-8 rounded-3xl border-white/5">
                <h3 className="text-xl font-black mb-4 flex items-center gap-2">
                    <AlertCircle className="w-5 h-5 text-yellow-400" />
                    Recomendaciones
                </h3>
                <div className="space-y-3 text-sm">
                    {completionRate < 50 && (
                        <div className="flex items-start gap-3 p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-xl">
                            <AlertCircle className="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-yellow-400">Tasa de completitud baja</p>
                                <p className="text-white/60">Considera enviar recordatorios a los colaboradores para completar sus cursos.</p>
                            </div>
                        </div>
                    )}
                    {stats.avgScore < 70 && stats.completedEnrollments > 0 && (
                        <div className="flex items-start gap-3 p-4 bg-orange-500/10 border border-orange-500/20 rounded-xl">
                            <AlertCircle className="w-5 h-5 text-orange-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-orange-400">Promedio general bajo</p>
                                <p className="text-white/60">El promedio de calificaciones está por debajo del 70%. Considera reforzar los contenidos.</p>
                            </div>
                        </div>
                    )}
                    {stats.totalEnrollments === 0 && (
                        <div className="flex items-start gap-3 p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl">
                            <BookOpen className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-blue-400">Comienza asignando cursos</p>
                                <p className="text-white/60">Cambia a Vista Capacitador para asignar cursos a tus colaboradores.</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
