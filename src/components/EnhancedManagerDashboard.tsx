"use client";

import { useEffect, useState, useMemo } from "react";
import { motion } from "framer-motion";
import {
    Users, BookOpen, Award, TrendingUp, Clock, CheckCircle2,
    AlertCircle, Download, Calendar, Filter, X, BarChart3, ClipboardList, HelpCircle
} from "lucide-react";
import { supabase } from "@/lib/supabase";
import * as XLSX from 'xlsx';
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

interface ActivityLog {
    id: string;
    enrollment_id: string;
    created_at: string;
    attempt_number?: number | null;
    score?: number | null;
    interaction_type?: string | null;
    raw_data?: any;
}

export default function EnhancedManagerDashboard({ companyName, companyId }: { companyName: string, companyId?: string }) {
    const [enrollments, setEnrollments] = useState<Enrollment[]>([]);
    const [activityLogs, setActivityLogs] = useState<ActivityLog[]>([]);
    const [loading, setLoading] = useState(true);
    const [dateFilter, setDateFilter] = useState({ startDate: "", endDate: "" });
    const [showFilters, setShowFilters] = useState(false);
    const [showParticipantsView, setShowParticipantsView] = useState(false);
    const [showGlobalFilters, setShowGlobalFilters] = useState(true);
    const [activeTab, setActiveTab] = useState<'listado' | 'barras' | 'preguntas' | 'sesiones'>('listado');
    const [lastUpdatedAt, setLastUpdatedAt] = useState<Date>(new Date());
    const [globalFilters, setGlobalFilters] = useState({
        course: 'Todos',
        student: 'Todos',
        search: '',
        range: '90d'
    });

    useEffect(() => {
        fetchData();
    }, [companyName, companyId]);

    const fetchData = async () => {
        setLoading(true);

        let query = supabase
            .from('enrollments')
            .select(`
                *,
                students!inner(rut, first_name, last_name, age, gender, company_name, position, email, client_id),
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
            const enrollmentData = data as any[] || [];
            setEnrollments(enrollmentData as any);
            setLastUpdatedAt(new Date());

            const enrollmentIds = enrollmentData.map((e: any) => e.id).filter(Boolean);
            if (enrollmentIds.length > 0) {
                const { data: logsData, error: logsError } = await supabase
                    .from('activity_logs')
                    .select('id, enrollment_id, created_at, attempt_number, score, interaction_type, raw_data')
                    .in('enrollment_id', enrollmentIds)
                    .order('created_at', { ascending: false });

                if (logsError) {
                    console.error('Error fetching activity logs:', logsError);
                    setActivityLogs([]);
                } else {
                    setActivityLogs((logsData as any) || []);
                }
            } else {
                setActivityLogs([]);
            }
        }

        setLoading(false);
    };

    // Filtrar enrollments por fecha
    const filteredEnrollments = useMemo(() => {
        if (!dateFilter.startDate && !dateFilter.endDate) return enrollments;

        const start = dateFilter.startDate ? new Date(`${dateFilter.startDate}T00:00:00`) : null;
        const end = dateFilter.endDate ? new Date(`${dateFilter.endDate}T23:59:59.999`) : null;

        return enrollments.filter(e => {
            const enrollDate = new Date(e.created_at);
            if (Number.isNaN(enrollDate.getTime())) return false;
            if (start && enrollDate < start) return false;
            if (end && enrollDate > end) return false;
            return true;
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

        // Generar los últimos 7 días por defecto si no hay datos, para que el gráfico no esté vacío
        const last7Days = Array.from({ length: 7 }, (_, i) => {
            const d = new Date();
            d.setDate(d.getDate() - (6 - i));
            return d.toISOString().split('T')[0];
        });

        last7Days.forEach(date => {
            dailyMap.set(date, { students: new Set(), completed: 0, scores: [] });
        });

        filteredEnrollments.forEach(e => {
            const date = new Date(e.created_at).toISOString().split('T')[0];
            // Solo registrar si está dentro de nuestro rango o si estamos viendo todos los datos
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
            .sort((a, b) => a.date.localeCompare(b.date))
            .slice(-30); // Limitar a los últimos 30 días para no saturar el gráfico
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
        const today = new Date().toISOString().split('T')[0];
        const hasDateFilters = Boolean(dateFilter.startDate || dateFilter.endDate);
        const hasGlobalFilters = globalFilters.course !== 'Todos' || globalFilters.student !== 'Todos' || Boolean(globalFilters.search.trim()) || globalFilters.range !== 'all';
        const exportRows = globalFilteredEnrollments;
        const rangeLabel = hasDateFilters
            ? `${dateFilter.startDate || 'inicio'}_a_${dateFilter.endDate || 'hoy'}`
            : 'sin-filtros';
        const filterScope = hasDateFilters || hasGlobalFilters ? 'filtrado' : 'completo';

        // XLSX con resumen, cursos y detalle de matrículas (respetando filtros activos).
        const workbook = XLSX.utils.book_new();

        const summaryRows = [
            { Metrica: 'Empresa', Valor: companyName },
            { Metrica: 'Fecha inicio filtro', Valor: dateFilter.startDate || 'N/A' },
            { Metrica: 'Fecha fin filtro', Valor: dateFilter.endDate || 'N/A' },
            { Metrica: 'Curso (filtro global)', Valor: globalFilters.course },
            { Metrica: 'Alumno (filtro global)', Valor: globalFilters.student },
            { Metrica: 'Busqueda libre', Valor: globalFilters.search || 'N/A' },
            { Metrica: 'Rango global', Valor: globalFilters.range },
            { Metrica: 'Total matrículas exportadas', Valor: exportRows.length },
            { Metrica: 'Estudiantes únicos', Valor: new Set(exportRows.map(e => e.students?.rut || e.student_id)).size },
            { Metrica: 'Cursos activos', Valor: new Set(exportRows.map(e => e.course_id)).size },
            { Metrica: 'Completadas', Valor: exportRows.filter(e => e.status === 'completed').length },
            { Metrica: 'En progreso', Valor: exportRows.filter(e => e.status === 'in_progress').length },
            {
                Metrica: 'Promedio global (%)',
                Valor: exportRows.length > 0 ? Math.round(exportRows.reduce((acc, e) => acc + Number(e.best_score || 0), 0) / exportRows.length) : 0
            },
            {
                Metrica: 'Tasa completitud (%)',
                Valor: exportRows.length > 0 ? Math.round((exportRows.filter(e => e.status === 'completed').length / exportRows.length) * 100) : 0
            }
        ];

        const courseMap = new Map<string, { code: string; rows: Enrollment[] }>();
        exportRows.forEach((e) => {
            const key = e.courses?.name || 'Sin nombre';
            if (!courseMap.has(key)) courseMap.set(key, { code: e.courses?.code || '', rows: [] });
            courseMap.get(key)!.rows.push(e);
        });

        const courseRows = Array.from(courseMap.entries()).map(([name, payload]) => {
            const rows = payload.rows;
            const scores = rows.map((e) => Number(e.best_score || 0));
            const sortedScores = [...scores].sort((a, b) => a - b);
            const median = sortedScores.length > 0
                ? sortedScores.length % 2 === 0
                    ? (sortedScores[sortedScores.length / 2 - 1] + sortedScores[sortedScores.length / 2]) / 2
                    : sortedScores[Math.floor(sortedScores.length / 2)]
                : 0;

            return {
                Curso: name,
                Codigo: payload.code,
                Inscritos: rows.length,
                En_Progreso: rows.filter(e => e.status === 'in_progress').length,
                Completados: rows.filter(e => e.status === 'completed').length,
                Promedio: rows.length > 0 ? Math.round(scores.reduce((a, b) => a + b, 0) / rows.length) : 0,
                Mediana: Math.round(median)
            };
        });

        const enrollmentRows = exportRows.map((e: any) => {
            const track = getTrackProgressFields(e);
            return ({
            Fecha_Inscripcion: new Date(e.created_at).toLocaleDateString(),
            Estado: formatExportStatus(e.status, e.best_score),
            Puntaje: Number(e.best_score || 0),
            Fecha_Completado: e.completed_at ? new Date(e.completed_at).toLocaleDateString() : '',
            Estudiante_RUT: e.students?.rut || '',
            Estudiante_Nombre: `${e.students?.first_name || ''} ${e.students?.last_name || ''}`.trim(),
            Correo: e.students?.email || '',
            Cargo: e.students?.position || '',
            Empresa: e.students?.company_name || companyName,
            Curso: e.courses?.name || '',
            Codigo_Curso: e.courses?.code || '',
            Intentos: Number(e.current_attempt || e.attempt_number || 1),
            'Tiempo(HH:MM:SS)': formatSeconds(enrollmentTimeById.get(e.id) || 0),
            Certificado: (e.status === 'completed' && Number(e.best_score || 0) >= 70) ? 'SI' : 'NO',
            Avance_General_Porcentaje: Number(e.partial_progress ?? e.progress ?? 0),
            Modulo_Actual: Number(e.current_module_index ?? 0),
            Modulos_Totales: Number(e.total_modules ?? 0),
            'Avance T1': track.avanceT1,
            'Completo T1': track.completoT1,
            'Avance T2': track.avanceT2,
            'Completo T2': track.completoT2
            });
        });

        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(summaryRows), 'Resumen');
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(courseRows), 'Cursos');
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(enrollmentRows), 'Matriculas');
        XLSX.writeFile(workbook, `dashboard-${companyName}-${rangeLabel}-${filterScope}-${today}.xlsx`);
    };

    const formatSeconds = (seconds: number) => {
        const safe = Math.max(0, Math.floor(seconds || 0));
        const h = Math.floor(safe / 3600).toString().padStart(2, '0');
        const m = Math.floor((safe % 3600) / 60).toString().padStart(2, '0');
        const s = Math.floor(safe % 60).toString().padStart(2, '0');
        return `${h}:${m}:${s}`;
    };

    const parseDurationSeconds = (value: any): number => {
        if (!value) return 0;
        if (typeof value === 'number' && Number.isFinite(value)) return value;
        if (typeof value !== 'string') return 0;

        const cleaned = value.trim();
        if (!cleaned.includes(':')) return Number(cleaned) || 0;

        const parts = cleaned.split(':').map(p => Number(p));
        if (parts.some(n => Number.isNaN(n))) return 0;
        if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
        if (parts.length === 2) return parts[0] * 60 + parts[1];
        return 0;
    };

    const normalizeFilterToken = (value: string) => {
        const normalized = (value || 'all')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        return normalized || 'all';
    };

    const formatExportStatus = (status?: string, bestScore?: string | number | null) => {
        const score = Number(bestScore || 0);
        if (status === 'completed') return score >= 70 ? 'aprobado' : 'reprobado';
        if (status === 'failed') return 'reprobado';
        if (status === 'in_progress') return 'en_progreso';
        return 'pendiente';
    };

    const getTrackProgressFields = (enrollment: any) => {
        const totalModules = Math.max(0, Number(enrollment?.total_modules ?? 0));
        const currentModule = Math.max(0, Number(enrollment?.current_module_index ?? 0));
        const progressPercent = Math.max(0, Math.min(100, Number(enrollment?.partial_progress ?? enrollment?.progress ?? 0)));

        const t1Total = totalModules > 0 ? Math.ceil(totalModules / 2) : 0;
        const t2Total = totalModules > 0 ? totalModules - t1Total : 0;
        const t1Current = t1Total > 0 ? Math.min(currentModule, t1Total) : 0;
        const t2Current = t2Total > 0 ? Math.max(0, Math.min(currentModule - t1Total, t2Total)) : 0;

        return {
            avanceT1: `${t1Current}/${t1Total}`,
            completoT1: t1Total === 0 ? 'N/A' : (progressPercent >= 50 || t1Current >= t1Total ? 'SI' : 'NO'),
            avanceT2: `${t2Current}/${t2Total}`,
            completoT2: t2Total === 0 ? 'N/A' : (progressPercent >= 100 || t2Current >= t2Total ? 'SI' : 'NO')
        };
    };

    const enrollmentMap = useMemo(() => {
        const map = new Map<string, Enrollment>();
        enrollments.forEach(e => map.set(e.id, e));
        return map;
    }, [enrollments]);

    const enrollmentTimeById = useMemo(() => {
        const map = new Map<string, number>();
        activityLogs.forEach(log => {
            const seconds =
                parseDurationSeconds(log?.raw_data?.session_time) ||
                parseDurationSeconds(log?.raw_data?.['cmi.core.session_time']) ||
                0;

            if (!log.enrollment_id) return;
            map.set(log.enrollment_id, (map.get(log.enrollment_id) || 0) + seconds);
        });
        return map;
    }, [activityLogs]);

    const globalFilteredEnrollments = useMemo(() => {
        const now = new Date();
        const base = [...filteredEnrollments];

        return base.filter((e) => {
            const created = new Date(e.created_at);
            if (globalFilters.range !== 'all') {
                const days = globalFilters.range === '30d' ? 30 : globalFilters.range === '90d' ? 90 : 365;
                const since = new Date(now);
                since.setDate(now.getDate() - days);
                if (created < since) return false;
            }

            if (globalFilters.course !== 'Todos') {
                if ((e.courses?.name || '') !== globalFilters.course) return false;
            }

            if (globalFilters.student !== 'Todos') {
                const full = `${e.students?.first_name || ''} ${e.students?.last_name || ''}`.trim();
                if (full !== globalFilters.student) return false;
            }

            if (globalFilters.search.trim()) {
                const term = globalFilters.search.toLowerCase();
                const full = `${e.students?.first_name || ''} ${e.students?.last_name || ''}`.toLowerCase();
                const rut = (e.students?.rut || '').toLowerCase();
                const email = (e.students as any)?.email?.toLowerCase?.() || '';
                if (!full.includes(term) && !rut.includes(term) && !email.includes(term)) return false;
            }

            return true;
        });
    }, [filteredEnrollments, globalFilters]);

    const globalFilteredEnrollmentIds = useMemo(() => {
        return new Set(globalFilteredEnrollments.map(e => e.id));
    }, [globalFilteredEnrollments]);

    const globalCourses = useMemo(() => ['Todos', ...Array.from(new Set(enrollments.map(e => e.courses?.name).filter(Boolean)))], [enrollments]);
    const globalStudents = useMemo(() => ['Todos', ...Array.from(new Set(enrollments.map(e => `${e.students?.first_name || ''} ${e.students?.last_name || ''}`.trim()).filter(Boolean)))], [enrollments]);

    const globalMetrics = useMemo(() => {
        const total = globalFilteredEnrollments.length;
        const approved = globalFilteredEnrollments.filter(e => e.status === 'completed' && Number(e.best_score || 0) >= 70).length;
        const failed = globalFilteredEnrollments.filter(e => e.status === 'failed' || (e.status === 'completed' && Number(e.best_score || 0) < 70)).length;
        const withResult = approved + failed;
        const approvalRate = withResult > 0 ? Math.round((approved / withResult) * 100) : 0;

        const attempts = globalFilteredEnrollments.map((e: any) => Number(e.current_attempt || e.attempt_number || 1));
        const avgAttempts = attempts.length > 0 ? attempts.reduce((a, b) => a + b, 0) / attempts.length : 0;

        const totalSeconds = globalFilteredEnrollments.reduce((acc, e) => acc + (enrollmentTimeById.get(e.id) || 0), 0);
        const avgSeconds = total > 0 ? Math.round(totalSeconds / total) : 0;

        return {
            total,
            approved,
            failed,
            approvalRate,
            totalSeconds,
            avgAttempts,
            avgSeconds
        };
    }, [globalFilteredEnrollments, enrollmentTimeById]);

    const barsByCourse = useMemo(() => {
        const courseMap = new Map<string, Map<string, number>>();

        globalFilteredEnrollments.forEach((e) => {
            const course = e.courses?.name || 'Sin curso';
            const date = new Date(e.created_at).toISOString().split('T')[0];
            if (!courseMap.has(course)) courseMap.set(course, new Map<string, number>());
            const dayMap = courseMap.get(course)!;
            dayMap.set(date, (dayMap.get(date) || 0) + 1);
        });

        return Array.from(courseMap.entries()).map(([course, dayMap]) => {
            const dates = Array.from(dayMap.keys()).sort();
            return {
                course,
                labels: dates,
                values: dates.map(d => dayMap.get(d) || 0)
            };
        });
    }, [globalFilteredEnrollments]);

    const difficultQuestions = useMemo(() => {
        const questionMap = new Map<string, { course: string; hits: number; misses: number }>();

        activityLogs.forEach(log => {
            if (!globalFilteredEnrollmentIds.has(log.enrollment_id)) return;
            const perQuestion = log?.raw_data?.perQuestion;
            if (!Array.isArray(perQuestion) || !log.enrollment_id) return;

            const enrollment = enrollmentMap.get(log.enrollment_id);
            const course = enrollment?.courses?.name || 'Curso';

            perQuestion.forEach((q: any) => {
                const qId = String(q?.id || q?.question_id || 'Sin ID');
                const key = `${course}::${qId}`;
                const current = questionMap.get(key) || { course, hits: 0, misses: 0 };

                if (q?.correct === true) current.hits += 1;
                else current.misses += 1;

                questionMap.set(key, current);
            });
        });

        return Array.from(questionMap.entries())
            .map(([key, value]) => {
                const [, questionId] = key.split('::');
                const total = value.hits + value.misses;
                const accuracy = total > 0 ? Math.round((value.hits / total) * 100) : 0;
                return {
                    course: value.course,
                    question: `Pregunta ${questionId}`,
                    accuracy,
                    hits: value.hits,
                    total
                };
            })
            .sort((a, b) => a.accuracy - b.accuracy)
            .slice(0, 12);
    }, [activityLogs, enrollmentMap, globalFilteredEnrollmentIds]);

    const difficultByCourse = useMemo(() => {
        const map = new Map<string, typeof difficultQuestions>();
        difficultQuestions.forEach((q) => {
            if (!map.has(q.course)) map.set(q.course, [] as any);
            (map.get(q.course) as any).push(q);
        });

        return Array.from(map.entries()).map(([course, questions]) => ({
            course,
            questions: [...questions].sort((a, b) => a.accuracy - b.accuracy).slice(0, 3)
        }));
    }, [difficultQuestions]);

    const activeSessions = useMemo(() => {
        const threshold = Date.now() - (30 * 60 * 1000);
        const latestPerEnrollment = new Map<string, ActivityLog>();

        activityLogs.forEach(log => {
            if (!globalFilteredEnrollmentIds.has(log.enrollment_id)) return;
            if (!log.enrollment_id) return;
            const ts = new Date(log.created_at).getTime();
            if (ts < threshold) return;
            const prev = latestPerEnrollment.get(log.enrollment_id);
            if (!prev || new Date(prev.created_at).getTime() < ts) {
                latestPerEnrollment.set(log.enrollment_id, log);
            }
        });

        return Array.from(latestPerEnrollment.values()).map(log => {
            const enrollment = enrollmentMap.get(log.enrollment_id);
            return {
                log,
                enrollment
            };
        }).filter(x => !!x.enrollment);
    }, [activityLogs, enrollmentMap, globalFilteredEnrollmentIds]);

    const exportGlobalView = () => {
        const workbook = XLSX.utils.book_new();
        const today = new Date().toISOString().split('T')[0];
        const hasDateFilters = Boolean(dateFilter.startDate || dateFilter.endDate);
        const hasGlobalFilters = globalFilters.course !== 'Todos' || globalFilters.student !== 'Todos' || Boolean(globalFilters.search.trim()) || globalFilters.range !== 'all';

        const enrollmentsByCourse = new Map<string, Enrollment[]>();
        globalFilteredEnrollments.forEach((enrollment) => {
            const key = enrollment.courses?.name || 'Sin curso';
            if (!enrollmentsByCourse.has(key)) enrollmentsByCourse.set(key, []);
            enrollmentsByCourse.get(key)!.push(enrollment);
        });

        const filtersSheet = [
            { Filtro: 'Fecha inicio', Valor: dateFilter.startDate || 'N/A' },
            { Filtro: 'Fecha fin', Valor: dateFilter.endDate || 'N/A' },
            { Filtro: 'Curso', Valor: globalFilters.course },
            { Filtro: 'Alumno', Valor: globalFilters.student },
            { Filtro: 'Buscar libre', Valor: globalFilters.search || 'N/A' },
            { Filtro: 'Rango', Valor: globalFilters.range },
            { Filtro: 'Registros', Valor: globalFilteredEnrollments.length }
        ];

        const courseSummarySheet = Array.from(enrollmentsByCourse.entries()).map(([courseName, rows]) => {
            const approved = rows.filter((e) => formatExportStatus(e.status, e.best_score) === 'aprobado').length;
            const failed = rows.filter((e) => formatExportStatus(e.status, e.best_score) === 'reprobado').length;
            const inProgress = rows.filter((e) => e.status === 'in_progress').length;
            const avgScore = rows.length > 0
                ? Math.round(rows.reduce((acc, e) => acc + Number(e.best_score || 0), 0) / rows.length)
                : 0;

            return {
                Curso: courseName,
                Registros: rows.length,
                Aprobados: approved,
                Reprobados: failed,
                En_Progreso: inProgress,
                Promedio: avgScore
            };
        });

        const listSheet = globalFilteredEnrollments.map((e: any) => {
            const track = getTrackProgressFields(e);
            return ({
            Fecha: new Date(e.created_at).toLocaleDateString(),
            Curso: e.courses?.name || '',
            Codigo_Curso: e.courses?.code || '',
            Nombre: `${e.students?.first_name || ''} ${e.students?.last_name || ''}`.trim(),
            Correo: e.students?.email || '',
            RUT: e.students?.rut || '',
            Cargo: e.students?.position || '',
            Empresa: e.students?.company_name || companyName,
            Porcentaje: Number(e.best_score || 0),
            Intentos: Number(e.current_attempt || e.attempt_number || 1),
            'Tiempo(HH:MM:SS)': formatSeconds(enrollmentTimeById.get(e.id) || 0),
            Estado: formatExportStatus(e.status, e.best_score),
            Certificado: (e.status === 'completed' && Number(e.best_score || 0) >= 70) ? 'SI' : 'NO',
            Avance_General_Porcentaje: Number(e.partial_progress ?? e.progress ?? 0),
            Modulo_Actual: Number(e.current_module_index ?? 0),
            Modulos_Totales: Number(e.total_modules ?? 0),
            'Avance T1': track.avanceT1,
            'Completo T1': track.completoT1,
            'Avance T2': track.avanceT2,
            'Completo T2': track.completoT2,
            Fecha_Completado: e.completed_at ? new Date(e.completed_at).toLocaleDateString() : '',
            Fecha_Inscripcion_ISO: e.created_at || ''
            });
        });

        const questionsSheet = difficultQuestions.map(q => ({
            Curso: q.course,
            Pregunta: q.question,
            Acierto_Porcentaje: q.accuracy,
            Aciertos: q.hits,
            Respuestas: q.total
        }));

        const sessionsSheet = activeSessions.map(({ log, enrollment }: any) => ({
            Hace: `${Math.max(0, Math.round((Date.now() - new Date(log.created_at).getTime()) / 60000))} min`,
            Fecha: new Date(log.created_at).toLocaleString(),
            Curso: enrollment?.courses?.name || '',
            Nombre: `${enrollment?.students?.first_name || ''} ${enrollment?.students?.last_name || ''}`.trim(),
            Correo: enrollment?.students?.email || '',
            RUT: enrollment?.students?.rut || '',
            Cargo: enrollment?.students?.position || '',
            Empresa: enrollment?.students?.company_name || companyName
        }));

        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(filtersSheet), 'Filtros');
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(courseSummarySheet), 'Resumen_por_curso');
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(listSheet), 'Listado');
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(questionsSheet), 'Preguntas_dificiles');
        XLSX.utils.book_append_sheet(workbook, XLSX.utils.json_to_sheet(sessionsSheet), 'Sesiones_30min');

        const finalScope = hasDateFilters || hasGlobalFilters ? 'filtrado' : 'completo';
        const fileCourse = normalizeFilterToken(globalFilters.course === 'Todos' ? 'all' : globalFilters.course);
        XLSX.writeFile(workbook, `listado_global_${companyName}_${finalScope}_${fileCourse}_${today}.xlsx`);
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
                <div className="flex items-center gap-3 flex-wrap">
                    <button
                        onClick={() => setShowParticipantsView((prev) => !prev)}
                        className="px-4 py-2 rounded-xl border bg-white/5 border-white/10 hover:border-brand/40 hover:bg-brand/10 transition-all flex items-center gap-2"
                    >
                        <Users className="w-4 h-4" />
                        {showParticipantsView ? 'Volver a vista global' : 'Ver listado de participantes'}
                    </button>
                </div>

                {!showParticipantsView ? (
                    <div className="flex items-center gap-3 flex-wrap justify-end">
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
                                    value={dateFilter.startDate}
                                    onChange={(e) => setDateFilter(prev => ({ ...prev, startDate: e.target.value }))}
                                    className="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-brand/40"
                                />
                                <input
                                    type="date"
                                    value={dateFilter.endDate}
                                    onChange={(e) => setDateFilter(prev => ({ ...prev, endDate: e.target.value }))}
                                    className="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-brand/40"
                                />
                                {(dateFilter.startDate || dateFilter.endDate) && (
                                    <button
                                        onClick={() => setDateFilter({ startDate: "", endDate: "" })}
                                        className="p-2 hover:bg-white/5 rounded-lg transition-all"
                                        title="Limpiar filtro"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                )}
                            </motion.div>
                        )}

                        <button
                            onClick={exportData}
                            className="px-4 py-2 bg-brand/20 border border-brand/40 rounded-xl hover:bg-brand/30 transition-all flex items-center gap-2"
                        >
                            <Download className="w-4 h-4" />
                            Exportar Datos
                        </button>
                    </div>
                ) : null}
            </div>

            {showParticipantsView && (
            <section className="glass p-5 rounded-3xl border-white/5 space-y-5">
                <div className="flex items-start justify-between flex-wrap gap-4">
                    <h2 className="text-2xl md:text-3xl font-black tracking-tight">Listado Global de Participantes</h2>
                    <div className="text-[11px] text-white/40 font-bold uppercase tracking-wide">Actualizado {lastUpdatedAt.toLocaleTimeString()}</div>
                </div>

                <div className="flex items-center justify-end gap-2">
                    <button
                        onClick={() => setShowGlobalFilters((prev) => !prev)}
                        className={`px-3.5 py-1.5 border rounded-xl transition-all flex items-center gap-2 text-sm ${showGlobalFilters ? 'bg-white/10 border-white/20 text-white' : 'bg-white/5 border-white/10 text-white/70 hover:bg-white/10'}`}
                    >
                        <Filter className="w-4 h-4" /> Filtros
                    </button>
                    <button
                        onClick={exportGlobalView}
                        className="px-3.5 py-1.5 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all flex items-center gap-2 text-sm"
                    >
                        <Download className="w-4 h-4" /> Exportar Excel (Filtros Activos)
                    </button>
                </div>

                {showGlobalFilters && (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    <div className="space-y-1">
                        <label className="text-[10px] font-black uppercase tracking-widest text-white/40">Curso</label>
                        <select value={globalFilters.course} onChange={(e) => setGlobalFilters(prev => ({ ...prev, course: e.target.value }))} className="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white [&>option]:bg-slate-100 [&>option]:text-slate-900">
                            {globalCourses.map(c => <option className="bg-slate-100 text-slate-900" key={c} value={c}>{c}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <label className="text-[10px] font-black uppercase tracking-widest text-white/40">Alumno (Nombre y Apellido)</label>
                        <select value={globalFilters.student} onChange={(e) => setGlobalFilters(prev => ({ ...prev, student: e.target.value }))} className="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white [&>option]:bg-slate-100 [&>option]:text-slate-900">
                            {globalStudents.map(s => <option className="bg-slate-100 text-slate-900" key={s} value={s}>{s}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <label className="text-[10px] font-black uppercase tracking-widest text-white/40">Buscar libre</label>
                        <input value={globalFilters.search} onChange={(e) => setGlobalFilters(prev => ({ ...prev, search: e.target.value }))} placeholder="Correo, RUT..." className="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white" />
                    </div>
                    <div className="space-y-1">
                        <label className="text-[10px] font-black uppercase tracking-widest text-white/40">Rango temporal</label>
                        <select value={globalFilters.range} onChange={(e) => setGlobalFilters(prev => ({ ...prev, range: e.target.value }))} className="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white [&>option]:bg-slate-100 [&>option]:text-slate-900">
                            <option className="bg-slate-100 text-slate-900" value="30d">Ultimos 30 dias</option>
                            <option className="bg-slate-100 text-slate-900" value="90d">Ultimos 90 dias</option>
                            <option className="bg-slate-100 text-slate-900" value="365d">Ultimo ano</option>
                            <option className="bg-slate-100 text-slate-900" value="all">Todo</option>
                        </select>
                    </div>
                </div>
                )}

                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2.5">
                    <div className="bg-white/[0.03] border border-white/10 rounded-2xl p-2.5"><div className="text-[10px] uppercase tracking-widest text-white/40">Total</div><div className="text-2xl font-black mt-1">{globalMetrics.total}</div></div>
                    <div className="bg-white/[0.03] border border-white/10 rounded-2xl p-2.5"><div className="text-[10px] uppercase tracking-widest text-white/40">Aprobados</div><div className="text-2xl font-black text-brand mt-1">{globalMetrics.approved}</div><div className="text-[10px] text-white/30">{globalMetrics.total ? Math.round((globalMetrics.approved / globalMetrics.total) * 100) : 0}% del total</div></div>
                    <div className="bg-white/[0.03] border border-white/10 rounded-2xl p-2.5"><div className="text-[10px] uppercase tracking-widest text-white/40">Reprobados</div><div className="text-2xl font-black text-red-400 mt-1">{globalMetrics.failed}</div><div className="text-[10px] text-white/30">{globalMetrics.total ? Math.round((globalMetrics.failed / globalMetrics.total) * 100) : 0}% del total</div></div>
                    <div className="bg-white/[0.03] border border-white/10 rounded-2xl p-2.5"><div className="text-[10px] uppercase tracking-widest text-white/40">% Aprobacion</div><div className="text-2xl font-black mt-1">{globalMetrics.approvalRate}%</div><div className="text-[10px] text-white/30">sobre registros con % &gt; 0</div></div>
                    <div className="bg-white/[0.03] border border-white/10 rounded-2xl p-2.5"><div className="text-[10px] uppercase tracking-widest text-white/40">Tiempo total</div><div className="text-xl font-black mt-1.5">{formatSeconds(globalMetrics.totalSeconds)}</div><div className="text-[10px] text-white/30">HH:MM:SS acumulado</div></div>
                    <div className="bg-white/[0.03] border border-white/10 rounded-2xl p-2.5"><div className="text-[10px] uppercase tracking-widest text-white/40">Intentos (prom.)</div><div className="text-2xl font-black mt-1">{globalMetrics.avgAttempts.toFixed(2)}</div><div className="text-[10px] text-white/30">en registros filtrados</div></div>
                    <div className="bg-white/[0.03] border border-white/10 rounded-2xl p-2.5"><div className="text-[10px] uppercase tracking-widest text-white/40">Tiempo prom.</div><div className="text-xl font-black mt-1.5">{formatSeconds(globalMetrics.avgSeconds)}</div><div className="text-[10px] text-white/30">en registros filtrados</div></div>
                </div>

                <div className="flex items-end justify-start gap-3 flex-wrap">
                    <div className="flex items-center gap-1 border-b border-white/10 overflow-x-auto">
                        {[
                            { id: 'listado', label: 'Listado', icon: ClipboardList, iconClass: 'text-brand' },
                            { id: 'barras', label: 'Barras por curso', icon: BarChart3, iconClass: 'text-brand' },
                            { id: 'preguntas', label: 'Preguntas dificiles', icon: HelpCircle, iconClass: 'text-brand' },
                            { id: 'sesiones', label: 'Sesiones (30 min)', icon: Clock, iconClass: 'text-brand' }
                        ].map((tab: any) => (
                            <button key={tab.id} onClick={() => setActiveTab(tab.id)} className={`px-3.5 py-1.5 rounded-t-xl border border-b-0 text-sm font-semibold flex items-center gap-2 whitespace-nowrap ${activeTab === tab.id ? 'bg-white/10 border-white/20 text-white' : 'bg-transparent border-transparent text-white/50 hover:text-white'}`}>
                                <tab.icon className={`w-4 h-4 ${tab.iconClass}`} /> {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                {activeTab === 'listado' && (
                    <div className="overflow-x-auto bg-white/[0.02] border border-white/10 rounded-xl">
                        <div className="px-3 py-1.5 text-xs text-white/40 border-b border-white/10">{globalFilteredEnrollments.length} registros filtrados</div>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-white/40 border-b border-white/10 bg-white/[0.03]">
                                    <th className="py-1.5 px-3">Fecha</th><th className="px-3">Curso</th><th className="px-3">Nombre</th><th className="px-3">Correo</th><th className="px-3">RUT</th><th className="px-3">%</th><th className="px-3">Intentos</th><th className="px-3">Tiempo</th><th className="px-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                {globalFilteredEnrollments.map((e) => (
                                    <tr key={e.id} className="border-b border-white/5">
                                        <td className="py-1.5 px-3">{new Date(e.created_at).toLocaleDateString()}</td>
                                        <td className="px-3">{e.courses?.name || '-'}</td>
                                        <td className="px-3">{`${e.students?.first_name || ''} ${e.students?.last_name || ''}`.trim()}</td>
                                        <td className="px-3">{(e.students as any)?.email || '-'}</td>
                                        <td className="px-3">{e.students?.rut || '-'}</td>
                                        <td className="px-3">{Number(e.best_score || 0)}%</td>
                                        <td className="px-3">{Number((e as any).current_attempt || (e as any).attempt_number || 1)}</td>
                                        <td className="px-3">{formatSeconds(enrollmentTimeById.get(e.id) || 0)}</td>
                                        <td className="px-3">
                                            <span className={`px-2 py-1 rounded-full text-xs font-bold ${e.status === 'completed' ? 'bg-brand/20 text-brand' : e.status === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-white/10 text-white/70'}`}>
                                                {e.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {activeTab === 'barras' && (
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        <div className="md:col-span-2 xl:col-span-3 flex justify-end">
                            <button className="px-3 py-1.5 border border-white/10 rounded-lg text-xs text-white/60 hover:bg-white/10">Reset zoom</button>
                        </div>
                        {barsByCourse.length === 0 && <div className="text-sm text-white/40">Sin datos para el rango seleccionado.</div>}
                        {barsByCourse.map((item) => (
                            <div key={item.course} className="bg-white/[0.02] border border-white/10 rounded-xl p-3">
                                <div className="text-xs font-semibold text-white/60 mb-2">{item.course}</div>
                                <div className="h-36">
                                    <Bar
                                        options={{
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: { legend: { display: false } },
                                            scales: {
                                                x: { ticks: { color: '#94a3b8', maxTicksLimit: 5 }, grid: { color: 'rgba(148,163,184,0.18)' } },
                                                y: { ticks: { color: '#94a3b8' }, beginAtZero: true, grid: { color: 'rgba(148,163,184,0.18)' } }
                                            }
                                        }}
                                        data={{
                                            labels: item.labels,
                                            datasets: [{ label: 'Registros', data: item.values, backgroundColor: '#34d399' }]
                                        }}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {activeTab === 'preguntas' && (
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div className="bg-white/[0.02] border border-white/10 rounded-xl p-3"><div className="text-xs text-white/40">Cursos con Top 3</div><div className="text-3xl font-black mt-1">{new Set(difficultQuestions.map(q => q.course)).size}</div></div>
                            <div className="bg-white/[0.02] border border-white/10 rounded-xl p-3"><div className="text-xs text-white/40">Preguntas (Top 3)</div><div className="text-3xl font-black mt-1">{Math.min(3, difficultQuestions.length)}</div></div>
                            <div className="bg-white/[0.02] border border-white/10 rounded-xl p-3"><div className="text-xs text-white/40">% Acierto prom.</div><div className="text-3xl font-black mt-1">{difficultQuestions.length ? Math.round(difficultQuestions.reduce((a, q) => a + q.accuracy, 0) / difficultQuestions.length) : 0}%</div></div>
                            <div className="bg-white/[0.02] border border-white/10 rounded-xl p-3"><div className="text-xs text-white/40">Mas dificil</div><div className="text-sm font-bold mt-1 line-clamp-2">{difficultQuestions[0]?.question || 'Sin datos'}</div></div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {difficultQuestions.length === 0 ? (
                            <p className="text-white/40 text-sm">No hay datos de preguntas aun. Se alimenta con registros de actividad del quiz.</p>
                        ) : difficultByCourse.map((courseBlock) => (
                            <div key={courseBlock.course} className="bg-white/[0.02] border border-white/10 rounded-xl p-4">
                                <h4 className="font-bold mb-2">{courseBlock.course}</h4>
                                <div className="space-y-3">
                                    {courseBlock.questions.map((q, idx) => (
                                        <div key={`${q.course}-${q.question}-${idx}`}>
                                            <p className="text-sm">{idx + 1}. {q.question}</p>
                                            <div className="mt-1 flex items-center gap-2">
                                                <div className="h-3 bg-white/10 rounded-full w-full overflow-hidden">
                                                    <div className={`h-full ${q.accuracy < 70 ? 'bg-yellow-400' : 'bg-green-500'}`} style={{ width: `${q.accuracy}%` }} />
                                                </div>
                                                <span className="text-xs font-bold whitespace-nowrap">{q.accuracy}% {q.hits} / {q.total}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                        </div>
                    </div>
                )}

                {activeTab === 'sesiones' && (
                    <div className="overflow-x-auto bg-white/[0.02] border border-white/10 rounded-xl p-3">
                        <div className="flex items-center justify-between mb-3">
                            <div>
                                <h4 className="text-lg font-bold">Sesiones activas en los ultimos 30 minutos</h4>
                                <p className="text-sm text-white/40">Se purgan automaticamente las sesiones con mas de 30 minutos.</p>
                            </div>
                        </div>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-white/40 border-b border-white/10">
                                    <th className="py-2">Hace</th><th>Fecha</th><th>Curso</th><th>Nombre</th><th>Correo</th><th>RUT</th><th>Cargo</th><th>Empresa</th>
                                </tr>
                            </thead>
                            <tbody>
                                {activeSessions.length === 0 ? (
                                    <tr><td colSpan={8} className="py-4 text-white/40">0 sesiones activas</td></tr>
                                ) : activeSessions.map(({ log, enrollment }: any) => (
                                    <tr key={log.id} className="border-b border-white/5">
                                        <td className="py-2">{Math.max(0, Math.round((Date.now() - new Date(log.created_at).getTime()) / 60000))} min</td>
                                        <td>{new Date(log.created_at).toLocaleString()}</td>
                                        <td>{enrollment?.courses?.name || '-'}</td>
                                        <td>{`${enrollment?.students?.first_name || ''} ${enrollment?.students?.last_name || ''}`.trim()}</td>
                                        <td>{enrollment?.students?.email || '-'}</td>
                                        <td>{enrollment?.students?.rut || '-'}</td>
                                        <td>{enrollment?.students?.position || '-'}</td>
                                        <td>{enrollment?.students?.company_name || companyName}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="pt-3 text-right text-xs text-white/40">Actualiza junto con el resto cada 20 s.</div>
                    </div>
                )}
            </section>
            )}

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
