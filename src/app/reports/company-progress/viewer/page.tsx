import { getCompanyProgressReportData } from '@/lib/server/companyProgressReport';

type PageProps = {
  searchParams?: Promise<{ companyId?: string; includeStudents?: string }> | { companyId?: string; includeStudents?: string };
};

export default async function CompanyProgressPdfViewerPage({ searchParams }: PageProps) {
  const params = await Promise.resolve(searchParams || {});
  const companyId = params.companyId || '';
  const includeStudentsParam = (params.includeStudents || '').trim().toLowerCase();
  const includeStudents = includeStudentsParam
    ? ['1', 'true', 'yes', 'on'].includes(includeStudentsParam)
    : undefined;

  if (!companyId) {
    return <div className="min-h-screen bg-[#05070b] text-white flex items-center justify-center px-6">Falta companyId en la URL.</div>;
  }

  const report = await getCompanyProgressReportData(companyId, { includeStudents });
  if (!report) {
    return <div className="min-h-screen bg-[#05070b] text-white flex items-center justify-center px-6">Empresa no encontrada.</div>;
  }

  const dateLabel = new Date(report.generatedAt).toLocaleString('es-CL');
  const generatedAtMs = new Date(report.generatedAt).getTime();
  const reportVersion = Number.isFinite(generatedAtMs) ? generatedAtMs : 0;

  return (
    <div className="min-h-screen bg-[#0b1220] text-[#0f172a] py-8 px-4 md:px-8">
      <div className="max-w-[1180px] mx-auto space-y-8">
        <div className="sticky top-0 z-20 bg-[#111827]/95 backdrop-blur border border-white/10 rounded-2xl px-4 md:px-6 py-4 text-white flex items-center justify-between gap-4 shadow-2xl shadow-black/20">
          <div>
            <p className="text-[10px] uppercase tracking-[0.3em] text-brand font-black">Vista previa PDF</p>
            <h1 className="text-lg md:text-xl font-black text-white">Informe corporativo listo para impresión</h1>
          </div>
          <a className="px-4 py-2 rounded-xl bg-brand text-black text-xs font-black uppercase tracking-widest" href={`/api/reports/company-progress/preview-pdf?companyId=${encodeURIComponent(companyId)}${typeof includeStudents === 'boolean' ? `&includeStudents=${includeStudents}` : ''}&v=${encodeURIComponent(String(reportVersion))}`}>
            Descargar versión PDF
          </a>
        </div>

        <section className="bg-white rounded-[22px] overflow-hidden border border-slate-200">
          <div className="p-6 md:p-8 bg-white">
            <div className="grid grid-cols-1 gap-5 min-h-[118px] pt-2 pb-1">
              <div>
                {report.company.logo_url ? (
                  <img src={report.company.logo_url} alt="Logo empresa" className="h-12 w-auto object-contain bg-white rounded-xl px-3 py-2 shadow-sm border border-slate-200" />
                ) : (
                  <div className="h-12 w-40 rounded-xl bg-white flex items-center justify-center font-black text-slate-900 border border-slate-200">{report.company.name}</div>
                )}
                <h2 className="mt-5 text-[30px] md:text-[34px] font-black text-slate-950 leading-[1.05] max-w-[420px] uppercase whitespace-pre-line">INFORME DE AVANCES Y{`\n`}DESARROLLO DE CURSOS</h2>
                <div className="mt-4 grid grid-cols-1 md:grid-cols-[minmax(0,1.4fr)_minmax(240px,0.9fr)] gap-3">
                  <div className="rounded-2xl border border-slate-300 bg-white px-4 py-4 text-slate-950">
                    <div className="text-[10px] uppercase tracking-[0.22em] text-slate-500">Empresa</div>
                    <div className="mt-1 font-bold text-lg">{report.company.name}</div>
                    <div className="mt-2 text-slate-600 text-sm">Generado: {dateLabel}</div>
                  </div>
                  <div className="rounded-2xl border border-slate-300 bg-white px-4 py-4 text-slate-950">
                    <div className="text-[10px] uppercase tracking-[0.22em] text-slate-500">Datos empresa</div>
                    <div className="mt-1 text-sm font-semibold">RUT: {report.company.tax_id || '-'}</div>
                    <div className="text-sm font-semibold">Zona: {report.company.branch_zone || '-'}</div>
                    <div className="text-sm font-semibold break-all">Contacto: {report.company.email || report.company.phone || '-'}</div>
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-4 grid grid-cols-2 md:grid-cols-5 gap-3">
              {[
                ['Participantes', report.totals.uniqueStudents],
                ['Cursos', report.totals.uniqueCourses],
                ['Matrículas', report.totals.totalEnrollments],
                ['Completitud', `${report.totals.completionRate}%`],
                ['Promedio', `${report.totals.avgScoreCompleted}%`],
              ].map(([label, value]) => (
                <div key={String(label)} className="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                  <div className="text-[10px] uppercase tracking-widest text-slate-500">{label}</div>
                  <div className="mt-1 text-3xl font-black text-slate-950">{value}</div>
                </div>
              ))}
            </div>

            <div className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
              <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Actividad últimos 14 días</div>
                <img src={report.charts.lineChart} alt="Actividad últimos 14 días" className="w-full rounded-lg border border-slate-100" />
              </div>
              <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Estado de matrículas</div>
                <img src={report.charts.statusDonut} alt="Estado de matrículas" className="w-full rounded-lg border border-slate-100" />
              </div>
              <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Top cursos por volumen</div>
                <img src={report.charts.topCoursesChart} alt="Top cursos por volumen" className="w-full rounded-lg border border-slate-100" />
              </div>
              <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Distribución de puntajes</div>
                <img src={report.charts.scoreBands} alt="Distribución de puntajes" className="w-full rounded-lg border border-slate-100" />
              </div>
            </div>

            <div className="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              <div className="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Top cursos por volumen</div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-slate-500 uppercase text-[10px] tracking-widest">
                      <th className="py-2 pr-3">Curso</th>
                      <th className="py-2 px-3 text-right">Inscritos</th>
                      <th className="py-2 px-3 text-right">En progreso</th>
                      <th className="py-2 px-3 text-right">Completados</th>
                      <th className="py-2 pl-3 text-right">Promedio</th>
                    </tr>
                  </thead>
                  <tbody>
                    {report.topCourses.map((course) => (
                      <tr key={course.courseCode || course.courseName} className="border-t border-slate-100">
                        <td className="py-3 pr-3 align-top">
                          <div className="font-bold text-slate-950">{course.courseName}</div>
                          <div className="text-[11px] text-slate-500">Codigo: {course.courseCode || '-'}</div>
                        </td>
                        <td className="py-3 px-3 text-right align-top">{course.enrolled}</td>
                        <td className="py-3 px-3 text-right align-top">{course.inProgress}</td>
                        <td className="py-3 px-3 text-right align-top">{course.completed}</td>
                        <td className="py-3 pl-3 text-right align-top">{course.avgScore}%</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        {report.company.report_include_dashboard_body !== false && (
        <section className="bg-white rounded-[22px] overflow-hidden shadow-2xl shadow-black/30 border border-black/10">
          <div className="bg-[#06101b] text-white px-6 py-4 flex items-center justify-between">
            <h3 className="font-black uppercase tracking-widest text-sm">Listado de participantes</h3>
            <span className="text-xs text-white/60">Página 2</span>
          </div>
          <div className="p-4 md:p-6 overflow-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-slate-500 uppercase text-[10px] tracking-widest">
                  <th className="py-2 pr-2">#</th>
                  <th className="py-2 pr-3">Alumno</th>
                  <th className="py-2 px-3">Correo</th>
                  <th className="py-2 px-3 text-right">Matric.</th>
                  <th className="py-2 px-3 text-right">Compl.</th>
                  <th className="py-2 px-3 text-right">En prog.</th>
                  <th className="py-2 pl-3 text-right">Prom.</th>
                </tr>
              </thead>
              <tbody>
                {report.studentSummary.map((student, index) => (
                  <tr key={student.key} className="border-t border-slate-100">
                    <td className="py-3 pr-2 align-top text-slate-500">{index + 1}</td>
                    <td className="py-3 pr-3 align-top">
                      <div className="font-bold text-slate-950">{student.fullName}</div>
                    </td>
                    <td className="py-3 px-3 align-top text-slate-600 break-all">{student.email || '-'}</td>
                    <td className="py-3 px-3 text-right align-top">{student.enrollments}</td>
                    <td className="py-3 px-3 text-right align-top">{student.completed}</td>
                    <td className="py-3 px-3 text-right align-top">{student.inProgress}</td>
                    <td className="py-3 pl-3 text-right align-top">{student.avgScore}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="border-t border-slate-200 text-slate-950 px-6 py-4 flex items-center justify-between gap-4 bg-white">
            <div className="flex items-baseline gap-0 text-[16px] md:text-[18px] font-medium tracking-[-0.12em] leading-none">
              <span className="text-slate-900">Metaverso</span><span className="text-brand -ml-[0.08em]">Otec</span>
            </div>
            <span className="text-[11px] text-slate-500">Informe corporativo de aprendizaje</span>
          </div>
        </section>
        )}
      </div>
    </div>
  );
}
