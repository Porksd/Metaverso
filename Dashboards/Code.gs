/**
 * Code.gs completo para el Dashboard (RUT único, fechas robustas y distribución por cursos únicos)
 * Encabezados esperados (exactos), pero con tolerancia:
 * Fecha | Nombre | Apellido | Correo | RUT | Género | Edad | Empresa | Cargo |
 * Correctas | Totales | Porcentaje | Tiempo Total (seg) | Tiempo Total (HH:MM:SS) |
 * Firma (base64) | Certificado | Cursos
 */

/* ========== CONFIG ========== */
const SHEET_ID = '1jnIPE6d0fyHZTGxpu0RiYDFwMfii-1Rb8DckRB1mBJA';
const RESPONSES_SHEET_NAME = 'Respuestas';

/* ========== Helpers ========== */
function normCourseName_(c){ if(c==null) return ''; return String(c).normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,' ').trim(); }

function toNumberOrNull_(v){ const n=Number(v); return (isFinite(n)&&!isNaN(n))?n:null; }
function indexByExact_(hdr, name){ return hdr.findIndex(h => String(h??'').trim()===name); }
/** Busca una columna por coincidencia parcial (case-insensitive) del término dado. */
function indexByContains_(hdr, term){
  const t = term.toLowerCase();
  return hdr.findIndex(h => String(h??'').toLowerCase().includes(t));
}

/**
 * Parser de fechas robusto:
 * - Date nativo
 * - Strings ISO
 * - Strings dd/MM/yyyy o dd-MM-yyyy (con o sin HH:mm:ss)
 * - Números seriales de Sheets/Excel
 * Devuelve YYYY-MM-DD o null si no puede parsear.
 */
function formatDateOnly_(d){
  if (!d && d !== 0) return null;

  // Date nativo
  if (Object.prototype.toString.call(d) === '[object Date]' && !isNaN(d.getTime())) {
    const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), da=String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${da}`;
  }

  // Serial numérico de Sheets/Excel (días desde 1899-12-30)
  if (typeof d === 'number' && isFinite(d)) {
    const ms = Math.round((d - 25569) * 86400 * 1000); // 25569 = 1970-01-01
    const dt = new Date(ms);
    if (!isNaN(dt.getTime())) {
      const y=dt.getFullYear(), m=String(dt.getMonth()+1).padStart(2,'0'), da=String(dt.getDate()).padStart(2,'0');
      return `${y}-${m}-${da}`;
    }
  }

  // String
  if (typeof d === 'string') {
    const s = d.trim();

    // dd/MM/yyyy o dd-MM-yyyy con o sin hora
    const m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/);
    if (m) {
      const dd = parseInt(m[1],10), MM = parseInt(m[2],10), yyyy = parseInt(m[3].length===2?('20'+m[3]):m[3],10);
      if (yyyy>=1900 && MM>=1 && MM<=12 && dd>=1 && dd<=31) {
        return `${yyyy}-${String(MM).padStart(2,'0')}-${String(dd).padStart(2,'0')}`;
      }
    }

    // Intento final con Date
    const tmp = new Date(s);
    if (!isNaN(tmp.getTime())) {
      const y=tmp.getFullYear(), m=String(tmp.getMonth()+1).padStart(2,'0'), da=String(tmp.getDate()).padStart(2,'0');
      return `${y}-${m}-${da}`;
    }
  }

  return null;
}

/**
 * Normaliza RUT: quita puntos/espacios, mayúsculas y devuelve XXXXXXXX-D (o K).
 * Si no hay RUT válido, retorna ''.
 */
function normalizeRut_(rut) {
  if (rut == null) return '';
  let s = String(rut).toUpperCase().replace(/\s+/g, '');
  s = s.replace(/\./g, '').replace(/-/g, '');
  s = s.replace(/[^0-9K]/g, '');
  if (s.length < 2) return '';
  const dv = s.slice(-1);
  let num = s.slice(0, -1).replace(/^0+/, '');
  if (!num) return '';
  return `${num}-${dv}`;
}

/* ========== HTML ========== */
function doGet(e){
  const t = HtmlService.createTemplateFromFile('index'); // index.html
  t.SHEET_ID = SHEET_ID;
  return t.evaluate().setTitle('Dashboard Sacyr Chile S.A.').setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

/* ========== DATA API ========== */
function getDashboardData(e){
  const params = (e&&e.parameter)?e.parameter:{};
  const startDateParam = params.startDate || null;
  const endDateParam   = params.endDate   || null;
  const onlyCompleted  = (params.onlyCompleted==='true' || params.onlyCompleted===true);

  const ss = SpreadsheetApp.openById(SHEET_ID);
  const sh = ss.getSheetByName(RESPONSES_SHEET_NAME);
  if(!sh) throw new Error('No se encontró la hoja "'+RESPONSES_SHEET_NAME+'". Actualiza RESPONSES_SHEET_NAME en Code.gs');

  const values = sh.getDataRange().getValues();
  if(!values || values.length<2){
    return { totals:{ totalResponses:0, totalStudents:0, uniqueStudents:0, students8Plus:0 },
             dailyActivity:[], coursesDetected:[], demographics:{}, students:[], coursesPerStudent:[] };
  }

  const header = values[0].map(h => (h==null?'':String(h).trim()));
  const rows   = values.slice(1);

  // Índices (exacto y fallback por contains)
  let idxFecha        = indexByExact_(header,'Fecha');        if (idxFecha < 0)        idxFecha = indexByContains_(header,'fecha');
  let idxRUT          = indexByExact_(header,'RUT');          if (idxRUT < 0)          idxRUT   = indexByContains_(header,'rut');
  let idxGenero       = indexByExact_(header,'Género');       if (idxGenero < 0)       idxGenero= indexByContains_(header,'género');
  let idxEdad         = indexByExact_(header,'Edad');         if (idxEdad < 0)         idxEdad  = indexByContains_(header,'edad');
  let idxCorrectas    = indexByExact_(header,'Correctas');    if (idxCorrectas < 0)    idxCorrectas = indexByContains_(header,'correctas');
  let idxTotales      = indexByExact_(header,'Totales');      if (idxTotales < 0)      idxTotales   = indexByContains_(header,'totales');
  let idxPorcentaje   = indexByExact_(header,'Porcentaje');   if (idxPorcentaje < 0)   idxPorcentaje= indexByContains_(header,'porcentaje');
  let idxCertificado  = indexByExact_(header,'Certificado');  if (idxCertificado < 0)  idxCertificado= indexByContains_(header,'cert');
  let idxCursos       = indexByExact_(header,'Cursos');       if (idxCursos < 0)       idxCursos    = indexByContains_(header,'curso');

  // Parse filas
  const parsedRows = rows.map(r=>{
    const ts   = idxFecha>=0 ? r[idxFecha] : null;
    const date = formatDateOnly_(ts);

    const rutRaw = idxRUT>=0 ? r[idxRUT] : '';
    const rutN   = normalizeRut_(rutRaw); // <- RUT normalizado

    const gender = idxGenero>=0 ? String(r[idxGenero]??'').trim() : '';
    const age    = idxEdad>=0 ? toNumberOrNull_(r[idxEdad]) : null;

    const correctas = idxCorrectas>=0 ? toNumberOrNull_(r[idxCorrectas]) : null;
    const totales   = idxTotales>=0   ? toNumberOrNull_(r[idxTotales])   : null;
    let nota        = idxPorcentaje>=0 ? toNumberOrNull_(r[idxPorcentaje]) : null;
    if(nota===null && correctas!==null && totales){ nota = (correctas/totales)*100; }

    const certificado = idxCertificado>=0 ? String(r[idxCertificado]??'').trim().toLowerCase() : '';
    const cursosRaw   = idxCursos>=0 ? String(r[idxCursos]??'') : '';

    return { rawTimestamp: ts, date, rutN, gender, age, nota, certificado, cursosRaw };
  });

  // Filtros por fecha (si vinieran parámetros)
  let filteredRows = parsedRows;
  if(startDateParam){
    const sd = new Date(startDateParam+'T00:00:00');
    filteredRows = filteredRows.filter(rr => {
      const dt = (rr.rawTimestamp && Object.prototype.toString.call(rr.rawTimestamp) === '[object Date]' && !isNaN(rr.rawTimestamp.getTime()))
        ? rr.rawTimestamp
        : (rr.date ? new Date(rr.date+'T00:00:00') : null);
      return dt ? dt >= sd : false;
    });
  }
  if(endDateParam){
    const ed = new Date(endDateParam+'T23:59:59');
    filteredRows = filteredRows.filter(rr => {
      const dt = (rr.rawTimestamp && Object.prototype.toString.call(rr.rawTimestamp) === '[object Date]' && !isNaN(rr.rawTimestamp.getTime()))
        ? rr.rawTimestamp
        : (rr.date ? new Date(rr.date+'T00:00:00') : null);
      return dt ? dt <= ed : false;
    });
  }

  if(onlyCompleted){
    filteredRows = filteredRows.filter(rr=>{
      const v=(rr.certificado||'').toLowerCase();
      return ['si','sí','yes','true','completed','complete','1','ok','emitido'].includes(v) || v.indexOf('cert')===0;
    });
  }

  // Agregaciones (RUT siempre requerido; 'date' solo para actividad diaria y students)
  const totalResponses = filteredRows.length;
  const studentSet = new Set();              // set de RUT normalizados
  const uniqueCoursesByRut = {};             // RUT -> Set(cursos únicos)
  const courseAgg = {};                      // curso -> {sum,count,responses}
  const dailyStudentSets = {};               // date -> Set(RUT)
  const studentDayAgg = {};                  // (RUT|date) -> {rut,date,sumNota,countNota,totalCursos,gender,age}

  filteredRows.forEach(rr=>{
    if(!rr.rutN) return;                     // ignorar registros sin RUT
    const rut = rr.rutN;
    studentSet.add(rut);

    const cursos = String(rr.cursosRaw||'')
      .split(/[,;|\/\n]+/)
      .map(s=>normCourseName_(s))
      .filter(Boolean);

    if(!uniqueCoursesByRut[rut]) uniqueCoursesByRut[rut] = new Set();
    cursos.forEach(c=> uniqueCoursesByRut[rut].add(c));

    cursos.forEach(c=>{
      if(!courseAgg[c]) courseAgg[c]={sum:0,count:0,responses:0};
      courseAgg[c].responses += 1;
      if(rr.nota!==null){ courseAgg[c].sum += rr.nota; courseAgg[c].count += 1; }
    });

    // Solo para agregados diarios si hay fecha válida
    if (rr.date) {
      const dateKey = rr.date;
      const key = rut+'|'+dateKey;
      if(!studentDayAgg[key]) studentDayAgg[key] = { rut, date:dateKey, sumNota:0, countNota:0, totalCursos:0, gender: rr.gender||'', age: rr.age };
      studentDayAgg[key].totalCursos += cursos.length;
      if(rr.nota!==null){ studentDayAgg[key].sumNota += rr.nota; studentDayAgg[key].countNota += 1; }

      if(!dailyStudentSets[dateKey]) dailyStudentSets[dateKey] = new Set();
      dailyStudentSets[dateKey].add(rut);
    }
  });

  const dailyActivity = Object.keys(dailyStudentSets)
    .filter(Boolean) // descarta null/undefined
    .sort((a,b)=> a.localeCompare(b))
    .map(dateKey=>{
      const ruts = dailyStudentSets[dateKey];
      let eight = 0;
      ruts.forEach(r => { if((uniqueCoursesByRut[r]?.size || 0) >= 8) eight += 1; });
      return { date: dateKey, uniqueStudents: ruts.size, students8Plus: eight };
    });

  const coursesDetected = Object.keys(courseAgg).map(name=>{
    const a = courseAgg[name];
    const avg = a.count>0 ? a.sum/a.count : null;
    return { course:name, avg: avg===null ? null : Number(avg.toFixed(4)), n: a.count, responses: a.responses };
  }).sort((x,y)=>{
    if(x.avg===null && y.avg===null) return 0;
    if(x.avg===null) return 1;
    if(y.avg===null) return -1;
    return y.avg - x.avg;
  });

  const students = Object.values(studentDayAgg).map(s=>({
    RUT: s.rut,
    Género: s.gender || 'No declarado',
    Edad: s.age,
    Fecha: s.date,
    TotalCursos: s.totalCursos,
    PromedioNota: s.countNota ? Number((s.sumNota/s.countNota).toFixed(2)) : null
  }));

  // NUEVO: total de cursos únicos por RUT (para distribución correcta)
  const coursesPerStudent = Object.entries(uniqueCoursesByRut).map(([rut, set]) => ({
    RUT: rut,
    TotalCursos: set.size
  }));

  const demographics = { gender:{}, ageBuckets:{} };
  students.forEach(s=>{
    const g = s.Género || 'No declarado';
    demographics.gender[g] = (demographics.gender[g]||0)+1;
    const a = toNumberOrNull_(s.Edad);
    let bucket='No declarado';
    if(a!==null) bucket = a<18?'<18':a<25?'18-24':a<35?'25-34':a<50?'35-49':'50+';
    demographics.ageBuckets[bucket] = (demographics.ageBuckets[bucket]||0)+1;
  });

  const students8Plus = coursesPerStudent.filter(x => x.TotalCursos >= 8).length;
  const totals = {
    totalResponses,
    totalStudents: studentSet.size,
    uniqueStudents: studentSet.size,
    students8Plus
  };

  return { totals, dailyActivity, coursesDetected, demographics, students, coursesPerStudent,
           meta: { filteredRowsCount: filteredRows.length, originalHeader: header } };
}