import { createClient } from '@supabase/supabase-js';
import fs from 'fs';
import jsPDF from 'jspdf';
import nodemailer from 'nodemailer';
import path from 'path';

type ReportFrequency = 'daily' | 'weekly' | 'biweekly' | 'monthly';

type CompanyReportConfig = {
  id: string;
  name: string;
  email: string | null;
  tax_id?: string | null;
  branch_zone?: string | null;
  address?: string | null;
  phone?: string | null;
  logo_url?: string | null;
  primary_color?: string | null;
  secondary_color?: string | null;
  report_auto_enabled: boolean;
  report_frequency: ReportFrequency;
  report_include_dashboard_body: boolean;
  report_include_pdf_attachment: boolean;
  report_copy_emails?: string | null;
  report_last_sent_at: string | null;
};

type EnrollmentRow = {
  id: string;
  student_id: string;
  course_id: string;
  status: string | null;
  best_score: number | string | null;
  created_at: string;
  completed_at: string | null;
  students?: {
    rut?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    email?: string | null;
    client_id?: string | null;
  } | null;
  courses?: {
    name?: string | null;
    code?: string | null;
  } | null;
};

type CourseAggregation = {
  courseName: string;
  courseCode: string;
  enrolled: number;
  inProgress: number;
  completed: number;
  avgScore: number;
};

type DailyPoint = {
  date: string;
  enrollments: number;
  completions: number;
};

type ReportData = {
  company: CompanyReportConfig;
  generatedAt: Date;
  totals: {
    uniqueStudents: number;
    uniqueCourses: number;
    totalEnrollments: number;
    completedEnrollments: number;
    inProgressEnrollments: number;
    pendingEnrollments: number;
    failedEnrollments: number;
    completionRate: number;
    avgScoreCompleted: number;
  };
  topCourses: CourseAggregation[];
  dailyActivity: DailyPoint[];
  recentEnrollments: EnrollmentRow[];
  scoreBands: Array<{ label: string; value: number }>;
  studentSummary: Array<{
    key: string;
    fullName: string;
    email: string;
    enrollments: number;
    completed: number;
    inProgress: number;
    avgScore: number;
  }>;
};

type SendSingleOptions = {
  force?: boolean;
  overrides?: ReportOverrides;
};

type DispatchOptions = {
  force?: boolean;
  companyId?: string;
};

type ReportOverrides = {
  includeStudents?: boolean;
  includePdfAttachment?: boolean;
  copyEmails?: string | null;
};

const ONE_DAY_MS = 24 * 60 * 60 * 1000;

function getSupabaseAdminClient() {
  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const serviceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

  if (!supabaseUrl || !serviceRoleKey) {
    throw new Error('Faltan credenciales Supabase para generar reportes.');
  }

  return createClient(supabaseUrl, serviceRoleKey, {
    auth: { autoRefreshToken: false, persistSession: false }
  });
}

function normalizeFrequency(value: string | null | undefined): ReportFrequency {
  if (value === 'daily' || value === 'weekly' || value === 'biweekly' || value === 'monthly') return value;
  return 'weekly';
}

function shouldSendNow(company: CompanyReportConfig, now: Date, force: boolean): boolean {
  if (force) return true;
  if (!company.report_auto_enabled) return false;

  if (!company.report_last_sent_at) return true;

  const lastSent = new Date(company.report_last_sent_at);
  if (Number.isNaN(lastSent.getTime())) return true;

  const elapsed = now.getTime() - lastSent.getTime();
  const intervalByFrequency: Record<ReportFrequency, number> = {
    daily: ONE_DAY_MS,
    weekly: ONE_DAY_MS * 7,
    biweekly: ONE_DAY_MS * 15,
    monthly: ONE_DAY_MS * 30
  };

  return elapsed >= intervalByFrequency[company.report_frequency];
}

function formatDateLabel(dateISO: string) {
  const parsed = new Date(`${dateISO}T00:00:00`);
  if (Number.isNaN(parsed.getTime())) return dateISO;
  return parsed.toLocaleDateString('es-CL', { day: '2-digit', month: 'short' });
}

function esc(text: string | null | undefined): string {
  return (text || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function toUpperDisplayName(input: string): string {
  return input
    .trim()
    .replace(/\s+/g, ' ')
    .toLocaleUpperCase('es-CL');
}

function parseReportCopyEmails(input: string | null | undefined): string[] {
  return (input || '')
    .split(',')
    .map((value) => value.trim())
    .filter((value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value));
}

function buildSupabaseImageTransformUrl(source: string | null | undefined, width: number, height: number): string | null {
  if (!source) return null;
  if (!source.includes('/storage/v1/object/public/')) return source;

  const transformSource = source.replace('/storage/v1/object/public/', '/storage/v1/render/image/public/');
  const separator = transformSource.includes('?') ? '&' : '?';
  return `${transformSource}${separator}width=${width}&height=${height}&resize=contain&quality=90`;
}

async function sourceToDataUri(source: string | null | undefined): Promise<string | null> {
  if (!source) return null;

  if (source.startsWith('data:image/')) return source;

  try {
    if (source.startsWith('http://') || source.startsWith('https://')) {
      const response = await fetch(source);
      if (!response.ok) return null;
      const contentType = response.headers.get('content-type') || 'image/png';
      const buffer = Buffer.from(await response.arrayBuffer());
      return `data:${contentType};base64,${buffer.toString('base64')}`;
    }

    const normalized = source.startsWith('/') ? source.slice(1) : source;
    const localPath = path.join(process.cwd(), 'public', normalized);
    if (!fs.existsSync(localPath)) return null;

    const ext = path.extname(localPath).toLowerCase();
    const contentType = ext === '.jpg' || ext === '.jpeg'
      ? 'image/jpeg'
      : ext === '.svg'
        ? 'image/svg+xml'
        : 'image/png';

    const buffer = fs.readFileSync(localPath);
    return `data:${contentType};base64,${buffer.toString('base64')}`;
  } catch {
    return null;
  }
}

function toSafeScore(raw: number | string | null | undefined): number {
  const parsed = Number(raw ?? 0);
  return Number.isFinite(parsed) ? parsed : 0;
}

async function fetchEnrollmentsByCompany(companyId: string): Promise<EnrollmentRow[]> {
  const supabaseAdmin = getSupabaseAdminClient();
  const enrollmentSelect = `
    id,
    student_id,
    course_id,
    status,
    best_score,
    created_at,
    completed_at,
    students!inner(rut, first_name, last_name, email, client_id),
    courses(name, code)
  `;

  const pageSize = 1000;
  const rows: EnrollmentRow[] = [];

  for (let from = 0; ; from += pageSize) {
    const { data, error } = await supabaseAdmin
      .from('enrollments')
      .select(enrollmentSelect)
      .eq('students.client_id', companyId)
      .order('created_at', { ascending: false })
      .range(from, from + pageSize - 1);

    if (error) {
      throw new Error(`No se pudieron cargar matrículas para reporte: ${error.message}`);
    }

    const pageRows = (data || []) as EnrollmentRow[];
    rows.push(...pageRows);

    if (pageRows.length < pageSize) {
      break;
    }
  }

  return rows;
}

async function fetchCompany(companyId: string): Promise<CompanyReportConfig | null> {
  const supabaseAdmin = getSupabaseAdminClient();
  const { data, error } = await supabaseAdmin
    .from('companies')
    .select('*')
    .eq('id', companyId)
    .maybeSingle();

  if (error) {
    throw new Error(`No se pudo leer la empresa: ${error.message}`);
  }

  if (!data) return null;

  return {
    id: data.id,
    name: data.name,
    email: data.email,
    tax_id: data.tax_id || null,
    branch_zone: data.branch_zone || null,
    address: data.address || null,
    phone: data.phone || null,
    logo_url: data.logo_url || null,
    primary_color: data.primary_color || null,
    secondary_color: data.secondary_color || null,
    report_auto_enabled: data.report_auto_enabled === true,
    report_frequency: normalizeFrequency(data.report_frequency),
    report_include_dashboard_body: data.report_include_dashboard_body !== false,
    report_include_pdf_attachment: data.report_include_pdf_attachment !== false,
    report_copy_emails: data.report_copy_emails || null,
    report_last_sent_at: data.report_last_sent_at || null
  };
}

async function buildCompanyReportPayload(companyId: string, overrides: ReportOverrides = {}): Promise<ReportData | null> {
  const company = await fetchCompany(companyId);
  if (!company) return null;

  if (typeof overrides.includeStudents === 'boolean') {
    company.report_include_dashboard_body = overrides.includeStudents;
  }

  if (typeof overrides.includePdfAttachment === 'boolean') {
    company.report_include_pdf_attachment = overrides.includePdfAttachment;
  }

  if (Object.prototype.hasOwnProperty.call(overrides, 'copyEmails')) {
    const normalized = (overrides.copyEmails || '').trim();
    company.report_copy_emails = normalized.length > 0 ? normalized : null;
  }

  const enrollments = await fetchEnrollmentsByCompany(company.id);
  return buildReportData(company, enrollments);
}

function buildReportData(company: CompanyReportConfig, enrollments: EnrollmentRow[]): ReportData {
  const uniqueStudents = new Set(enrollments.map((row) => row.students?.rut || row.student_id)).size;
  const uniqueCourses = new Set(enrollments.map((row) => row.course_id)).size;
  const completedRows = enrollments.filter((row) => row.status === 'completed');
  const inProgressRows = enrollments.filter((row) => row.status === 'in_progress');
  const failedRows = enrollments.filter((row) => row.status === 'failed');
  const pendingRows = enrollments.filter((row) => row.status !== 'completed' && row.status !== 'in_progress');

  const completedScores = completedRows.map((row) => toSafeScore(row.best_score));
  const avgScoreCompleted = completedScores.length > 0
    ? Math.round(completedScores.reduce((acc, score) => acc + score, 0) / completedScores.length)
    : 0;

  const completionRate = enrollments.length > 0
    ? Math.round((completedRows.length / enrollments.length) * 100)
    : 0;

  const byCourse = new Map<string, {
    courseName: string;
    courseCode: string;
    enrolled: number;
    inProgress: number;
    completed: number;
    scoreAcc: number;
    scoreCount: number;
  }>();

  for (const row of enrollments) {
    const key = row.course_id;
    const courseName = row.courses?.name || 'Curso sin nombre';
    const courseCode = row.courses?.code || '';

    if (!byCourse.has(key)) {
      byCourse.set(key, {
        courseName,
        courseCode,
        enrolled: 0,
        inProgress: 0,
        completed: 0,
        scoreAcc: 0,
        scoreCount: 0
      });
    }

    const current = byCourse.get(key)!;
    current.enrolled += 1;
    if (row.status === 'in_progress') current.inProgress += 1;
    if (row.status === 'completed') current.completed += 1;
    if (row.status === 'completed') {
      current.scoreAcc += toSafeScore(row.best_score);
      current.scoreCount += 1;
    }
  }

  const topCourses = Array.from(byCourse.values())
    .map((item) => ({
      courseName: item.courseName,
      courseCode: item.courseCode,
      enrolled: item.enrolled,
      inProgress: item.inProgress,
      completed: item.completed,
      avgScore: item.scoreCount > 0 ? Math.round(item.scoreAcc / item.scoreCount) : 0
    }))
    .sort((a, b) => b.enrolled - a.enrolled)
    .slice(0, 8);

  const scoreBands = [
    { label: '0-59', min: 0, max: 59 },
    { label: '60-69', min: 60, max: 69 },
    { label: '70-79', min: 70, max: 79 },
    { label: '80-89', min: 80, max: 89 },
    { label: '90-100', min: 90, max: 100 }
  ].map((band) => ({
    label: band.label,
    value: completedScores.filter((score) => score >= band.min && score <= band.max).length
  }));

  const days = 14;
  const dayMap = new Map<string, DailyPoint>();
  for (let i = days - 1; i >= 0; i--) {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    dayMap.set(key, { date: key, enrollments: 0, completions: 0 });
  }

  for (const row of enrollments) {
    const createdKey = new Date(row.created_at).toISOString().slice(0, 10);
    const createdEntry = dayMap.get(createdKey);
    if (createdEntry) createdEntry.enrollments += 1;

    if (row.completed_at) {
      const completedKey = new Date(row.completed_at).toISOString().slice(0, 10);
      const completedEntry = dayMap.get(completedKey);
      if (completedEntry) completedEntry.completions += 1;
    }
  }

  const studentMap = new Map<string, {
    fullName: string;
    email: string;
    enrollments: number;
    completed: number;
    inProgress: number;
    scoreAcc: number;
    scoreCount: number;
  }>();

  for (const row of enrollments) {
    const key = row.students?.rut || row.students?.email || row.student_id;
    const fullName = `${row.students?.first_name || ''} ${row.students?.last_name || ''}`.trim() || key;
    const email = row.students?.email || '';
    if (!studentMap.has(key)) {
      studentMap.set(key, {
        fullName,
        email,
        enrollments: 0,
        completed: 0,
        inProgress: 0,
        scoreAcc: 0,
        scoreCount: 0
      });
    }

    const current = studentMap.get(key)!;
    current.enrollments += 1;
    if (row.status === 'completed') {
      current.completed += 1;
      current.scoreAcc += toSafeScore(row.best_score);
      current.scoreCount += 1;
    }
    if (row.status === 'in_progress') current.inProgress += 1;
  }

  const studentSummary = Array.from(studentMap.entries()).map(([key, row]) => ({
    key,
    fullName: toUpperDisplayName(row.fullName),
    email: row.email,
    enrollments: row.enrollments,
    completed: row.completed,
    inProgress: row.inProgress,
    avgScore: row.scoreCount > 0 ? Math.round(row.scoreAcc / row.scoreCount) : 0
  })).sort((a, b) => a.fullName.localeCompare(b.fullName, 'es', { sensitivity: 'base' }));

  return {
    company,
    generatedAt: new Date(),
    totals: {
      uniqueStudents,
      uniqueCourses,
      totalEnrollments: enrollments.length,
      completedEnrollments: completedRows.length,
      inProgressEnrollments: inProgressRows.length,
      pendingEnrollments: pendingRows.length,
      failedEnrollments: failedRows.length,
      completionRate,
      avgScoreCompleted
    },
    topCourses,
    dailyActivity: Array.from(dayMap.values()),
    recentEnrollments: enrollments.slice(0, 60),
    scoreBands,
    studentSummary
  };
}

function quickChartUrl(config: Record<string, unknown>, width = 700, height = 360) {
  const encoded = encodeURIComponent(JSON.stringify(config));
  return `https://quickchart.io/chart?c=${encoded}&w=${width}&h=${height}&devicePixelRatio=1&format=png&backgroundColor=white`;
}

function buildCharts(report: ReportData) {
  const activityLabels = report.dailyActivity.map((point) => formatDateLabel(point.date));
  const activityEnrollments = report.dailyActivity.map((point) => point.enrollments);
  const activityCompletions = report.dailyActivity.map((point) => point.completions);

  const courseLabels = report.topCourses.map((course) => course.courseName);
  const coursesEnrollmentSeries = report.topCourses.map((course) => course.enrolled);
  const coursesCompletionSeries = report.topCourses.map((course) => course.completed);

  const lineChart = quickChartUrl({
    type: 'line',
    data: {
      labels: activityLabels,
      datasets: [
        {
          label: 'Inscripciones',
          data: activityEnrollments,
          borderColor: '#22d3ee',
          backgroundColor: 'rgba(34,211,238,0.2)',
          pointRadius: 2,
          tension: 0.35
        },
        {
          label: 'Completados',
          data: activityCompletions,
          borderColor: '#31D22D',
          backgroundColor: 'rgba(49,210,45,0.2)',
          pointRadius: 2,
          tension: 0.35
        }
      ]
    },
    options: {
      plugins: {
        legend: { position: 'bottom', labels: { color: '#0f172a' } }
      },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#334155' } },
        x: { ticks: { color: '#334155', maxRotation: 35, minRotation: 35 } }
      }
    }
  }, 760, 320);

  const statusDonut = quickChartUrl({
    type: 'doughnut',
    data: {
      labels: ['Completados', 'En progreso', 'Pendientes', 'Reprobados'],
      datasets: [{
        data: [
          report.totals.completedEnrollments,
          report.totals.inProgressEnrollments,
          report.totals.pendingEnrollments,
          report.totals.failedEnrollments
        ],
        backgroundColor: ['#31D22D', '#0ea5e9', '#f59e0b', '#ef4444']
      }]
    },
    options: {
      plugins: { legend: { position: 'bottom', labels: { color: '#0f172a' } } }
    }
  }, 560, 320);

  const topCoursesChart = quickChartUrl({
    type: 'bar',
    data: {
      labels: courseLabels,
      datasets: [
        {
          label: 'Inscritos',
          data: coursesEnrollmentSeries,
          backgroundColor: '#0ea5e9'
        },
        {
          label: 'Completados',
          data: coursesCompletionSeries,
          backgroundColor: '#31D22D'
        }
      ]
    },
    options: {
      plugins: { legend: { position: 'bottom', labels: { color: '#0f172a' } } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#334155' } },
        x: { ticks: { color: '#334155', maxRotation: 35, minRotation: 35 } }
      }
    }
  }, 760, 320);

  const scoreBands = quickChartUrl({
    type: 'bar',
    data: {
      labels: report.scoreBands.map((row) => row.label),
      datasets: [{
        label: 'Participantes',
        data: report.scoreBands.map((row) => row.value),
        backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a']
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#334155' } },
        x: { ticks: { color: '#334155' } }
      }
    }
  }, 620, 320);

  return { lineChart, statusDonut, topCoursesChart, scoreBands };
}

// Replica las reglas del panel "Insights y Recomendaciones" (EnhancedManagerDashboard.tsx) para incluirlas textualmente en el correo.
function buildInsightsList(report: ReportData): string[] {
  const { totals, studentSummary } = report;
  const insights: string[] = [];

  if (totals.completionRate < 50) {
    insights.push(`Tasa de completitud baja (${totals.completionRate}%): considera enviar recordatorios a los colaboradores para completar sus cursos.`);
  }

  if (totals.avgScoreCompleted < 70 && totals.completedEnrollments > 0) {
    insights.push(`Promedio general bajo (${totals.avgScoreCompleted}%): considera revisar el nivel de dificultad o proporcionar material de apoyo adicional.`);
  }

  if (totals.inProgressEnrollments > totals.completedEnrollments * 2 && totals.completedEnrollments > 0) {
    insights.push(`Muchos cursos en progreso: hay ${totals.inProgressEnrollments} cursos iniciados vs ${totals.completedEnrollments} completados. Motiva a los estudiantes a finalizar.`);
  }

  const students8Plus = studentSummary.filter((student) => student.enrollments >= 8).length;
  if (students8Plus > 0) {
    insights.push(`¡Excelente compromiso! ${students8Plus} estudiante${students8Plus > 1 ? 's han' : ' ha'} completado 8 o más cursos. Considera reconocer su esfuerzo.`);
  }

  if (totals.totalEnrollments === 0) {
    insights.push('Comienza asignando cursos: no hay inscripciones aún. Dirígete a la sección de administración para asignar cursos.');
  }

  if (insights.length === 0) {
    insights.push('El desempeño de la organización se mantiene dentro de parámetros esperados, sin alertas relevantes en este periodo.');
  }

  return insights;
}

function buildEmailHtml(report: ReportData): string {
  const { company, generatedAt } = report;
  const insightsHtml = buildInsightsList(report)
    .map((line) => `<li style="margin-bottom:8px;">${esc(line)}</li>`)
    .join('');
  const sentDateLabel = generatedAt.toLocaleDateString('es-CL', { day: '2-digit', month: 'long', year: 'numeric' });

  return `
    <div style="font-family:Segoe UI,Arial,sans-serif;background:#ffffff;padding:24px 12px;color:#0f172a;">
      <div style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:16px;border:1px solid #e2e8f0;padding:32px 36px;">
        <p style="font-size:16px;font-weight:700;margin:0 0 20px 0;color:#0f172a;">${esc(company.name)}</p>

        <p style="font-size:14px;line-height:1.6;margin:0 0 16px 0;color:#1f2937;">
          Por medio del presente adjuntamos el informe periódico de capacitación de su organización, el cual presenta
          el estado y avance de los cursos, junto con el listado de colaboradores capacitados.
        </p>

        <p style="font-size:14px;line-height:1.6;margin:0 0 12px 0;color:#1f2937;">
          Adicionalmente, ponemos a su consideración Insights y recomendaciones basados en la información del dashboard:
        </p>

        <ul style="font-size:14px;line-height:1.6;margin:0 0 20px 0;padding-left:20px;color:#1f2937;">
          ${insightsHtml}
        </ul>

        <p style="font-size:14px;line-height:1.6;margin:0 0 24px 0;color:#1f2937;">
          Esperamos que la información adjunta, junto con los insights y recomendaciones presentadas, sean de utilidad
          para fortalecer la gestión de la capacitación y apoyar la toma de decisiones orientadas a la mejora continua.
        </p>

        <p style="font-size:14px;line-height:1.6;margin:0;color:#1f2937;">Saludos cordiales,</p>
        <p style="font-size:14px;line-height:1.6;margin:0 0 20px 0;font-weight:700;color:#0f172a;">MetaversOtec</p>

        <p style="font-size:13px;color:#64748b;margin:0;">Fecha: ${esc(sentDateLabel)}</p>
      </div>
    </div>
  `;
}

async function buildReportPdf(report: ReportData): Promise<Buffer> {
  const companyLogo = await sourceToDataUri(buildSupabaseImageTransformUrl(report.company.logo_url, 280, 100));
  const { lineChart, statusDonut, topCoursesChart, scoreBands } = buildCharts(report);
  const [lineChartDataUri, statusDonutDataUri, topCoursesChartDataUri, scoreBandsDataUri] = await Promise.all([
    sourceToDataUri(lineChart),
    sourceToDataUri(statusDonut),
    sourceToDataUri(topCoursesChart),
    sourceToDataUri(scoreBands)
  ]);

  const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
  const pageW = 210;
  const pageH = 297;

  const drawFooter = () => {
    doc.setDrawColor(220, 227, 236);
    doc.line(14, 282.4, 196, 282.4);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10.5);
    doc.setTextColor(31, 41, 55);
    doc.text('Metaverso', 14, 289.2);
    doc.setTextColor(49, 210, 45);
    doc.text('Otec', 31.6, 289.2);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(100, 116, 139);
    doc.text('Informe corporativo de aprendizaje', pageW - 14, 289.6, { align: 'right' });
  };

  doc.setFillColor(255, 255, 255);
  doc.rect(0, 0, pageW, pageH, 'F');
  doc.setFillColor(16, 34, 30);
  doc.rect(0, 0, pageW, 7, 'F');

  if (companyLogo) {
    try {
      doc.setFillColor(255, 255, 255);
      doc.setDrawColor(226, 232, 240);
      doc.roundedRect(pageW - 48, 11, 34, 13, 2, 2, 'FD');
      doc.addImage(companyLogo, 'PNG', pageW - 46, 13, 30, 8.4);
    } catch {
      // ignore invalid logo image
    }
  }

  doc.setTextColor(15, 23, 42);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(15.5);
  doc.text('INFORME DE AVANCES Y', 14, 35.5);
  doc.text('DESARROLLO DE CURSOS', 14, 43.2);
  doc.setFontSize(8.6);
  doc.setFont('helvetica', 'normal');
  doc.setDrawColor(203, 213, 225);
  doc.setFillColor(255, 255, 255);
  doc.roundedRect(14, 50, 100, 24, 3, 3, 'FD');
  doc.roundedRect(118, 50, 78, 24, 3, 3, 'FD');
  doc.setTextColor(15, 23, 42);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9.6);
  doc.text(report.company.name.slice(0, 44), 18, 59.2);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.2);
  doc.setTextColor(71, 85, 105);
  doc.text(`Generado: ${report.generatedAt.toLocaleString('es-CL')}`, 18, 66.2);

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.4);
  doc.setTextColor(15, 23, 42);
  doc.text(`RUT: ${report.company.tax_id || '-'}`, 122, 58.2);
  doc.text(`Zona: ${report.company.branch_zone || '-'}`, 122, 64.0);
  doc.text(`Contacto: ${(report.company.email || report.company.phone || '-').slice(0, 30)}`, 122, 69.8);

  const kpi = [
    ['Participantes', String(report.totals.uniqueStudents)],
    ['Cursos', String(report.totals.uniqueCourses)],
    ['Matriculas', String(report.totals.totalEnrollments)],
    ['Completitud', `${report.totals.completionRate}%`],
    ['Promedio', `${report.totals.avgScoreCompleted}%`]
  ];

  let kx = 14;
  const ky = 81;
  const kw = 35;
  const kh = 15;
  kpi.forEach(([label, value]) => {
    doc.setFillColor(245, 248, 252);
    doc.setDrawColor(220, 227, 236);
    doc.roundedRect(kx, ky, kw, kh, 2, 2, 'FD');
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.8);
    doc.setTextColor(90, 102, 117);
    doc.text(label.toUpperCase(), kx + 2.2, ky + 5.0);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11.2);
    doc.setTextColor(15, 23, 42);
    doc.text(value, kx + 2.2, ky + 12.5);
    kx += kw + 2.5;
  });

  const panel = (x: number, y: number, w: number, h: number, title: string) => {
    doc.setFillColor(255, 255, 255);
    doc.setDrawColor(220, 227, 236);
    doc.roundedRect(x, y, w, h, 2, 2, 'FD');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(15, 23, 42);
    doc.text(title, x + 3, y + 5.2);
  };

  const drawChartInPanel = (x: number, y: number, w: number, h: number, title: string, imageDataUri: string | null) => {
    panel(x, y, w, h, title);
    if (imageDataUri) {
      try {
        doc.addImage(imageDataUri, 'PNG', x + 3, y + 7.5, w - 6, h - 10.5);
      } catch {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.2);
        doc.setTextColor(100, 116, 139);
        doc.text('No se pudo cargar el grafico.', x + 3, y + 14);
      }
    } else {
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(7.2);
      doc.setTextColor(100, 116, 139);
      doc.text('No se pudo cargar el grafico.', x + 3, y + 14);
    }
  };

  drawChartInPanel(14, 100, 88, 48, 'Actividad ultimos 14 dias', lineChartDataUri);
  drawChartInPanel(108, 100, 88, 48, 'Estado de matriculas', statusDonutDataUri);
  drawChartInPanel(14, 152, 88, 48, 'Top cursos por volumen', topCoursesChartDataUri);
  drawChartInPanel(108, 152, 88, 48, 'Distribucion de puntajes', scoreBandsDataUri);

  doc.setFillColor(255, 255, 255);
  doc.setDrawColor(220, 227, 236);
  doc.roundedRect(14, 201, 182, 67, 2, 2, 'FD');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(15, 23, 42);
  doc.text('Detalle por curso', 17, 208);

  const startY = 216;
  doc.setFontSize(7.4);
  doc.setFont('helvetica', 'bold');
  doc.text('Curso', 17, startY);
  doc.text('Inscritos', 142, startY, { align: 'right' });
  doc.text('Progreso', 160, startY, { align: 'right' });
  doc.text('Complet.', 178, startY, { align: 'right' });
  doc.text('Prom.', 194, startY, { align: 'right' });

  doc.setFont('helvetica', 'normal');
  let rowY = startY + 4.8;
  report.topCourses.slice(0, 5).forEach((course) => {
    doc.setDrawColor(236, 240, 245);
    doc.line(16, rowY + 1.5, 194, rowY + 1.5);
    doc.setTextColor(31, 41, 55);
    doc.setFontSize(7.0);
    doc.text(course.courseName.slice(0, 54), 17, rowY);
    doc.setFontSize(6.4);
    doc.setTextColor(100, 116, 139);
    doc.text(`Cod: ${course.courseCode || '-'}`, 17, rowY + 3.6);
    doc.setFontSize(7.1);
    doc.setTextColor(31, 41, 55);
    doc.text(String(course.enrolled), 142, rowY, { align: 'right' });
    doc.text(String(course.inProgress), 160, rowY, { align: 'right' });
    doc.text(String(course.completed), 178, rowY, { align: 'right' });
    doc.text(`${course.avgScore}%`, 194, rowY, { align: 'right' });
    rowY += 6.9;
  });

  drawFooter();

  if (report.company.report_include_dashboard_body) {
    doc.addPage();
    doc.setFillColor(255, 255, 255);
    doc.rect(0, 0, pageW, pageH, 'F');
    doc.setFillColor(15, 23, 42);
    doc.rect(0, 0, pageW, 18, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.6);
    doc.text('Listado de alumnos y estado de avance', 14, 11.8);
    let y = 28;
    doc.setTextColor(15, 23, 42);
    doc.setFontSize(8.5);
    doc.setFont('helvetica', 'bold');
    doc.text('#', 14, y);
    doc.text('Alumno', 22, y);
    doc.text('Correo', 88, y);
    doc.text('Matric.', 150, y, { align: 'right' });
    doc.text('Compl.', 167, y, { align: 'right' });
    doc.text('En prog.', 183, y, { align: 'right' });
    doc.text('Prom.', 198, y, { align: 'right' });
    y += 5;

    doc.setFont('helvetica', 'normal');
    report.studentSummary.forEach((row, index) => {
      if (y > 279) {
        drawFooter();
        doc.addPage();
        y = 20;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.text('#', 14, y);
        doc.text('Alumno', 22, y);
        doc.text('Correo', 88, y);
        doc.text('Matric.', 150, y, { align: 'right' });
        doc.text('Compl.', 167, y, { align: 'right' });
        doc.text('En prog.', 183, y, { align: 'right' });
        doc.text('Prom.', 198, y, { align: 'right' });
        y += 5;
        doc.setFont('helvetica', 'normal');
      }

      doc.setDrawColor(236, 240, 245);
      doc.line(14, y + 1.2, 198, y + 1.2);
      doc.text(String(index + 1), 14, y);
      doc.text(row.fullName.slice(0, 35), 22, y);
      doc.text((row.email || '-').slice(0, 36), 88, y);
      doc.text(String(row.enrollments), 150, y, { align: 'right' });
      doc.text(String(row.completed), 167, y, { align: 'right' });
      doc.text(String(row.inProgress), 183, y, { align: 'right' });
      doc.text(`${row.avgScore}%`, 198, y, { align: 'right' });
      y += 6.2;
    });

    drawFooter();
  }

  const output = doc.output('arraybuffer');
  return Buffer.from(output);
}

async function sendMail(report: ReportData): Promise<void> {
  const smtpHost = process.env.SMTP_HOST;
  const smtpPort = Number(process.env.SMTP_PORT || 587);
  const smtpUser = process.env.SMTP_USER;
  const smtpPass = process.env.SMTP_PASS;
  const smtpSecure = process.env.SMTP_SECURE === 'true' || smtpPort === 465;
  const smtpFrom = process.env.SMTP_FROM || smtpUser;

  if (!smtpHost || !smtpFrom) {
    throw new Error('Faltan variables SMTP_HOST y/o SMTP_FROM para enviar reportes.');
  }

  if (!report.company.email) {
    throw new Error('La empresa no tiene email configurado para recibir informes.');
  }

  const transporter = nodemailer.createTransport({
    host: smtpHost,
    port: smtpPort,
    secure: smtpSecure,
    auth: smtpUser ? { user: smtpUser, pass: smtpPass } : undefined
  });

  const attachments = [] as Array<{
    filename: string;
    content: Buffer;
    contentType: string;
    cid?: string;
    contentDisposition?: 'inline' | 'attachment';
  }>;

  if (report.company.report_include_pdf_attachment) {
    attachments.push({
      filename: `informe-${report.company.name.replace(/\s+/g, '-').toLowerCase()}-${new Date().toISOString().slice(0, 10)}.pdf`,
      content: await buildReportPdf(report),
      contentType: 'application/pdf',
      contentDisposition: 'attachment'
    });
  }

  const html = buildEmailHtml(report);
  const copyRecipients = parseReportCopyEmails(report.company.report_copy_emails);

  await transporter.sendMail({
    from: smtpFrom,
    to: report.company.email,
    cc: copyRecipients.length > 0 ? copyRecipients : undefined,
    subject: `Informe de avance de cursos - ${report.company.name}`,
    html,
    attachments
  });
}

async function markAsSent(companyId: string): Promise<void> {
  const supabaseAdmin = getSupabaseAdminClient();
  const { error } = await supabaseAdmin
    .from('companies')
    .update({ report_last_sent_at: new Date().toISOString() })
    .eq('id', companyId);

  if (error) {
    throw new Error(`No se pudo actualizar report_last_sent_at: ${error.message}`);
  }
}

export async function sendCompanyProgressReport(companyId: string, options: SendSingleOptions = {}) {
  const force = options.force === true;
  const company = await fetchCompany(companyId);

  if (!company) {
    return { sent: false, reason: 'company_not_found' as const };
  }

  if (!company.email) {
    return { sent: false, reason: 'missing_email' as const };
  }

  if (!shouldSendNow(company, new Date(), force)) {
    return { sent: false, reason: 'not_due' as const };
  }

  const report = await buildCompanyReportPayload(company.id, options.overrides || {});
  if (!report) {
    return { sent: false, reason: 'company_not_found' as const };
  }

  await sendMail(report);
  await markAsSent(company.id);

  return { sent: true as const, companyId: company.id, recipient: company.email };
}

export async function getCompanyProgressReportPreview(companyId: string, overrides: ReportOverrides = {}) {
  const report = await buildCompanyReportPayload(companyId, overrides);
  if (!report) {
    return null;
  }

  return {
    report,
    html: buildEmailHtml(report)
  };
}

export async function getCompanyProgressReportPdfPreview(companyId: string, overrides: ReportOverrides = {}) {
  const report = await buildCompanyReportPayload(companyId, overrides);
  if (!report) {
    return null;
  }

  const pdfBuffer = await buildReportPdf(report);
  return {
    report,
    pdfBuffer
  };
}

export async function getCompanyProgressReportData(companyId: string, overrides: ReportOverrides = {}) {
  const report = await buildCompanyReportPayload(companyId, overrides);
  if (!report) {
    return null;
  }

  const charts = buildCharts(report);

  return {
    ...report,
    generatedAt: report.generatedAt.toISOString(),
    charts
  };
}

export async function dispatchCompanyProgressReports(options: DispatchOptions = {}) {
  const supabaseAdmin = getSupabaseAdminClient();
  const force = options.force === true;

  let query = supabaseAdmin
    .from('companies')
    .select('*')
    .eq('is_active', true);

  if (options.companyId) {
    query = query.eq('id', options.companyId);
  }

  const { data, error } = await query.order('name');

  if (error) {
    throw new Error(`No se pudieron listar empresas para dispatch: ${error.message}`);
  }

  const companies = ((data || []) as CompanyReportConfig[])
    .map((company) => ({
      ...company,
      report_frequency: normalizeFrequency(company.report_frequency)
    }))
    .filter((company) => company.email);

  const results: Array<{ companyId: string; companyName: string; sent: boolean; reason?: string }> = [];

  for (const company of companies) {
    try {
      const result = await sendCompanyProgressReport(company.id, { force });
      results.push({
        companyId: company.id,
        companyName: company.name,
        sent: result.sent,
        reason: result.sent ? undefined : result.reason
      });
    } catch (err: unknown) {
      const reason = err instanceof Error ? err.message : 'unknown_error';
      results.push({
        companyId: company.id,
        companyName: company.name,
        sent: false,
        reason
      });
    }
  }

  return {
    total: results.length,
    sent: results.filter((item) => item.sent).length,
    skippedOrFailed: results.filter((item) => !item.sent).length,
    results
  };
}
