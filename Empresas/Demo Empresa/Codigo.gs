/*******************************
 * CONFIG (Script Properties)
 * EMPRESAS_ROOT_FOLDER_ID = <folder id de EMPRESAS>
 * EMPRESAS_DB_SHEET_ID    = <sheet id de EMPRESAS DB>
 *
 * Plantillas por curso (por código):
 * TEMPLATE_ALTURA_SHEET_ID = <sheet id plantilla ALTURA>
 * TEMPLATE_ANDAMIOS_SHEET_ID = ...
 *******************************/

function getCfg_() {
  const p = PropertiesService.getScriptProperties();
  return {
    rootFolderId: p.getProperty('EMPRESAS_ROOT_FOLDER_ID'),
    dbId: p.getProperty('EMPRESAS_DB_SHEET_ID'),
  };
}

function db_() {
  const { dbId } = getCfg_();
  if (!dbId) throw new Error('Falta Script Property EMPRESAS_DB_SHEET_ID');
  return SpreadsheetApp.openById(dbId);
}

function ensureDb_() {
  const ss = db_();

  const tabs = [
    {
      name: 'Empresas',
      headers: ['empresaId','nombre','rut','direccion','fono','mail','folderId','createdAt','activo']
    },
    {
      name: 'Usuarios',
      // ✅ headers finales con token
      headers: ['userId','empresaId','nombre','email','rol','token','tokenExpiraAt','activo','createdAt']
    },
    {
      name: 'Cursos',
      headers: ['cursoId','empresaId','nombreCurso','codigoCurso','sheetIdCurso','folderIdCurso','activo','createdAt']
    },
    {
      name: 'Alumnos',
      headers: [
        'empresaId','cursoId',
        'Fecha','Nombre','Apellido','Correo','RUT','Género','Edad','Empresa','Cargo',
        'Correctas','Totales','Porcentaje',
        'Tiempo Total (seg)','Tiempo Total (HH:MM:SS)',
        'Firma (base64)',
        'certificadoFileId','certificadoUrl',
        'createdAt'
      ]
    }
  ];

  tabs.forEach(t => {
    let sh = ss.getSheetByName(t.name);
    if (!sh) sh = ss.insertSheet(t.name);
    if (sh.getLastRow() === 0) sh.appendRow(t.headers);
  });

  return { ok: true };
}

function uid_(prefix) {
  return (prefix || 'id') + '_' + Utilities.getUuid().slice(0, 8);
}

/***********************
 * WEB APP ENTRY
 ***********************/
function doGet(e) {
  ensureDb_();
  const view = e?.parameter?.view || 'admin';

  if (view === 'cursoForm') {
    const empresaId = e?.parameter?.empresaId || '';
    const token = e?.parameter?.token || '';
    const v = validateToken_(empresaId, token);
    if (!v.ok) return HtmlService.createHtmlOutput(`Acceso denegado: ${v.reason}`);

    const t = HtmlService.createTemplateFromFile('curso_form');
    t.empresaId = empresaId;
    t.token = token;

    return t.evaluate()
      .setTitle('Ingreso de alumnos')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
  }

  return HtmlService.createHtmlOutputFromFile('admin')
    .setTitle('CORE - Empresas / Cursos / Alumnos')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

/***********************
 * EMPRESAS
 ***********************/
function normalizeEmpresa_(p) {
  const nombre = String(p?.nombre || '').trim();
  const rut = String(p?.rut || '').trim();
  if (!nombre) throw new Error('Nombre empresa es obligatorio');
  if (!rut) throw new Error('RUT empresa es obligatorio');

  return {
    nombre,
    rut,
    direccion: String(p?.direccion || '').trim(),
    fono: String(p?.fono || '').trim(),
    mail: String(p?.mail || '').trim(),
  };
}

function apiCreateEmpresa(payload) {
  ensureDb_();
  const cfg = getCfg_();
  if (!cfg.rootFolderId) throw new Error('Falta Script Property EMPRESAS_ROOT_FOLDER_ID');

  const data = normalizeEmpresa_(payload);

  const root = DriveApp.getFolderById(cfg.rootFolderId);
  const empresaId = uid_('emp');
  const folderName = `${data.rut} - ${data.nombre}`.trim();
  const empresaFolder = root.createFolder(folderName);

  const fDatos = empresaFolder.createFolder('01_DATOS');
  const fCursos = empresaFolder.createFolder('02_CURSOS');
  const fCerts = empresaFolder.createFolder('03_CERTIFICADOS');

  const sh = db_().getSheetByName('Empresas');
  sh.appendRow([
    empresaId, data.nombre, data.rut, data.direccion, data.fono, data.mail,
    empresaFolder.getId(), new Date(), 1
  ]);

  return {
    ok: true,
    empresaId,
    folderId: empresaFolder.getId(),
    folders: { datos: fDatos.getId(), cursos: fCursos.getId(), certificados: fCerts.getId() }
  };
}

function apiListEmpresas() {
  ensureDb_();
  const sh = db_().getSheetByName('Empresas');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  return values
    .filter(r => Number(r[idx.activo]) === 1)
    .map(r => ({
      empresaId: r[idx.empresaId],
      nombre: r[idx.nombre],
      rut: r[idx.rut],
      folderId: r[idx.folderId]
    }));
}

function apiGetEmpresa_(empresaId) {
  const sh = db_().getSheetByName('Empresas');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  const row = values.find(r => String(r[idx.empresaId]) === String(empresaId));
  if (!row) throw new Error('Empresa no existe');

  return {
    empresaId: row[idx.empresaId],
    nombre: row[idx.nombre],
    rut: row[idx.rut],
    folderId: row[idx.folderId]
  };
}

/***********************
 * TOKENS / AUTH (Opción A)
 ***********************/
function newToken_() {
  return Utilities.getUuid().replace(/-/g, ''); // 32 chars
}

function validateToken_(empresaId, token) {
  ensureDb_();
  if (!empresaId || !token) return { ok: false, reason: 'Falta empresaId o token' };

  const sh = db_().getSheetByName('Usuarios');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  const tokenCol = idx.token;
  if (tokenCol === undefined) return { ok: false, reason: 'DB Usuarios sin columna token' };

  const row = values.find(r =>
    String(r[idx.empresaId]) === String(empresaId) &&
    String(r[tokenCol]) === String(token) &&
    Number(r[idx.activo]) === 1
  );

  if (!row) return { ok: false, reason: 'Token inválido' };

  // Expiración (si existe)
  if (idx.tokenExpiraAt !== undefined) {
    const exp = row[idx.tokenExpiraAt];
    if (exp) {
      const expMs = new Date(exp).getTime();
      if (!isNaN(expMs) && expMs < Date.now()) return { ok: false, reason: 'Token expirado' };
    }
  }

  return {
    ok: true,
    rol: String(row[idx.rol] || ''),
    userId: String(row[idx.userId] || ''),
    email: String(row[idx.email] || '')
  };
}

/***********************
 * USUARIOS
 ***********************/
function apiCreateUsuario(payload) {
  ensureDb_();

  const empresaId = String(payload?.empresaId || '').trim();
  const nombre = String(payload?.nombre || '').trim();
  const email = String(payload?.email || '').trim();
  const rol = String(payload?.rol || '').trim().toUpperCase();

  if (!empresaId) throw new Error('empresaId obligatorio');
  if (!nombre) throw new Error('nombre obligatorio');
  if (!email) throw new Error('email obligatorio');
  if (!['ADMIN', 'LECTOR'].includes(rol)) throw new Error('rol inválido (ADMIN o LECTOR)');

  const userId = uid_('usr');
  const token = newToken_();
  const expira = new Date(Date.now() + 1000 * 60 * 60 * 24 * 365); // 1 año

  const sh = db_().getSheetByName('Usuarios');
  // headers: userId, empresaId, nombre, email, rol, token, tokenExpiraAt, activo, createdAt
  sh.appendRow([userId, empresaId, nombre, email, rol, token, expira, 1, new Date()]);

  return { ok: true, userId, token, tokenExpiraAt: expira };
}

function apiListUsuarios(empresaId) {
  ensureDb_();
  const sh = db_().getSheetByName('Usuarios');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  return values
    .filter(r => String(r[idx.empresaId]) === String(empresaId) && Number(r[idx.activo]) === 1)
    .map(r => ({
      userId: r[idx.userId],
      nombre: r[idx.nombre],
      email: r[idx.email],
      rol: r[idx.rol],
      token: r[idx.token],
      tokenExpiraAt: r[idx.tokenExpiraAt]
    }));
}

function apiRenewToken(payload) {
  ensureDb_();
  const userId = String(payload?.userId || '').trim();
  if (!userId) throw new Error('userId obligatorio');

  const sh = db_().getSheetByName('Usuarios');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  const rowIndex = values.findIndex(r => String(r[idx.userId]) === String(userId));
  if (rowIndex === -1) throw new Error('Usuario no existe');

  const newTok = newToken_();
  const expira = new Date(Date.now() + 1000 * 60 * 60 * 24 * 365);

  const targetRow = rowIndex + 2; // +1 header +1 base
  sh.getRange(targetRow, idx.token + 1).setValue(newTok);
  sh.getRange(targetRow, idx.tokenExpiraAt + 1).setValue(expira);

  return { ok: true, token: newTok, tokenExpiraAt: expira };
}

/***********************
 * CURSOS (Templates por código)
 ***********************/
function getTemplateIdByCodigo_(codigoCurso) {
  const p = PropertiesService.getScriptProperties();
  const key = `TEMPLATE_${String(codigoCurso || '').toUpperCase()}_SHEET_ID`;
  const id = p.getProperty(key);
  if (!id) throw new Error(`Falta Script Property ${key} (ID de la plantilla del curso)`);
  return id;
}

function apiCreateCurso(payload) {
  ensureDb_();

  const empresaId   = String(payload?.empresaId || '').trim();
  const nombreCurso = String(payload?.nombreCurso || '').trim();
  const codigoCurso = String(payload?.codigoCurso || '').trim().toUpperCase();

  if (!empresaId) throw new Error('empresaId obligatorio');
  if (!nombreCurso) throw new Error('nombreCurso obligatorio');
  if (!codigoCurso) throw new Error('codigoCurso obligatorio');

  // 1) Carpeta empresa y asegurar 02_CURSOS
  const empresa = apiGetEmpresa_(empresaId);
  const empresaFolder = DriveApp.getFolderById(empresa.folderId);

  const itCursos = empresaFolder.getFoldersByName('02_CURSOS');
  const fCursos = itCursos.hasNext() ? itCursos.next() : empresaFolder.createFolder('02_CURSOS');

  // 2) Crear carpeta del curso 02_CURSOS/<CODIGO>
  const folderCurso = fCursos.createFolder(codigoCurso);

  // 3) Copiar plantilla del curso a esa carpeta
  const templateId = getTemplateIdByCodigo_(codigoCurso);
  const templateFile = DriveApp.getFileById(templateId);

  const copyName = `${codigoCurso} - ${nombreCurso}`;
  const copiedFile = templateFile.makeCopy(copyName, folderCurso);
  const sheetIdCurso = copiedFile.getId();

  // 4) Registrar en DB
  const cursoId = uid_('crs');
  const sh = db_().getSheetByName('Cursos');
  sh.appendRow([cursoId, empresaId, nombreCurso, codigoCurso, sheetIdCurso, folderCurso.getId(), 1, new Date()]);

  return { ok: true, cursoId, sheetIdCurso, folderIdCurso: folderCurso.getId() };
}

function apiListCursos(empresaId) {
  ensureDb_();
  const sh = db_().getSheetByName('Cursos');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  return values
    .filter(r => String(r[idx.empresaId]) === String(empresaId) && Number(r[idx.activo]) === 1)
    .map(r => ({
      cursoId: r[idx.cursoId],
      nombreCurso: r[idx.nombreCurso],
      codigoCurso: r[idx.codigoCurso],
      sheetIdCurso: r[idx.sheetIdCurso],
      folderIdCurso: r[idx.folderIdCurso]
    }));
}

function apiGetCurso_(cursoId) {
  const sh = db_().getSheetByName('Cursos');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  const row = values.find(r => String(r[idx.cursoId]) === String(cursoId));
  if (!row) throw new Error('Curso no existe');

  return {
    cursoId: row[idx.cursoId],
    empresaId: row[idx.empresaId],
    nombreCurso: row[idx.nombreCurso],
    codigoCurso: row[idx.codigoCurso],
    sheetIdCurso: row[idx.sheetIdCurso],
    folderIdCurso: row[idx.folderIdCurso]
  };
}

// Opcional: por si alguna vez necesitas ajustar manualmente (no deberías)
function apiUpdateCursoSheetId(payload) {
  ensureDb_();
  const cursoId = String(payload?.cursoId || '').trim();
  const sheetIdCurso = String(payload?.sheetIdCurso || '').trim();
  if (!cursoId) throw new Error('cursoId obligatorio');
  if (!sheetIdCurso) throw new Error('sheetIdCurso obligatorio');

  const sh = db_().getSheetByName('Cursos');
  const values = sh.getDataRange().getValues();
  const head = values.shift();
  const idx = Object.fromEntries(head.map((h, i) => [h, i]));

  const rowIndex = values.findIndex(r => String(r[idx.cursoId]) === String(cursoId));
  if (rowIndex === -1) throw new Error('Curso no existe');

  const targetRow = rowIndex + 2;
  sh.getRange(targetRow, idx.sheetIdCurso + 1).setValue(sheetIdCurso);

  return { ok: true };
}

/***********************
 * ALUMNOS (consolidado + sheet curso)
 ***********************/
function apiCreateAlumno_(payload) {
  ensureDb_();

  const empresaId = String(payload?.empresaId || '').trim();
  const cursoId = String(payload?.cursoId || '').trim();
  if (!empresaId) throw new Error('empresaId obligatorio');
  if (!cursoId) throw new Error('cursoId obligatorio');

  const sh = db_().getSheetByName('Alumnos');

  const row = [
    empresaId, cursoId,
    payload?.Fecha || '',
    payload?.Nombre || '',
    payload?.Apellido || '',
    payload?.Correo || '',
    payload?.RUT || '',
    payload?.['Género'] || '',
    payload?.Edad || '',
    payload?.Empresa || '',
    payload?.Cargo || '',
    payload?.Correctas || '',
    payload?.Totales || '',
    payload?.Porcentaje || '',
    payload?.['Tiempo Total (seg)'] || '',
    payload?.['Tiempo Total (HH:MM:SS)'] || '',
    payload?.['Firma (base64)'] || '',
    payload?.certificadoFileId || '',
    payload?.certificadoUrl || '',
    new Date()
  ];

  sh.appendRow(row);
  return { ok: true };
}

function appendAlumnoEnSheetCurso_(cursoId, payload) {
  const curso = apiGetCurso_(cursoId);
  if (!curso.sheetIdCurso) throw new Error('El curso no tiene sheetIdCurso configurado');

  const ss = SpreadsheetApp.openById(curso.sheetIdCurso);
  const sh = ss.getSheetByName('Respuestas');
  if (!sh) throw new Error('No existe la hoja "Respuestas" en el sheet del curso');

  // Orden según columnas típicas de "Respuestas"
  const row = [
    payload?.Fecha || '',
    payload?.Nombre || '',
    payload?.Apellido || '',
    payload?.Correo || '',
    payload?.RUT || '',
    payload?.['Género'] || '',
    payload?.Edad || '',
    payload?.Empresa || '',
    payload?.Cargo || '',
    payload?.Correctas || '',
    payload?.Totales || '',
    payload?.Porcentaje || '',
    payload?.['Tiempo Total (seg)'] || '',
    payload?.['Tiempo Total (HH:MM:SS)'] || '',
    payload?.['Firma (base64)'] || '',
    payload?.Certificado || payload?.certificadoUrl || '' // usa lo que tengas disponible
  ];

  sh.appendRow(row);
}

/***********************
 * API SEGURA: Crear alumno con token (ADMIN)
 ***********************/
function apiCreateAlumnoSecure(payload) {
  ensureDb_();

  const empresaId = String(payload?.empresaId || '').trim();
  const cursoId = String(payload?.cursoId || '').trim();
  const token = String(payload?.token || '').trim();

  if (!empresaId || !cursoId || !token) throw new Error('Faltan empresaId/cursoId/token');

  const v = validateToken_(empresaId, token);
  if (!v.ok) throw new Error('Acceso denegado: ' + v.reason);
  if (String(v.rol || '').toUpperCase() !== 'ADMIN') throw new Error('Solo ADMIN puede ingresar alumnos');

  // 1) consolidado global
  apiCreateAlumno_(payload);

  // 2) sheet del curso (Respuestas)
  appendAlumnoEnSheetCurso_(cursoId, payload);

  return { ok: true };
}
