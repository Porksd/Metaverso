// ==============================
// Code.gs — COMPLETO (Tiempo Total + MM:SS para listado)
// ==============================

// IDs y constantes — AJÚSTALOS SI LO NECESITAS
const TEMPLATE_ID = '11fEjDGTg0w3crCIEYLgWzsWEyuxBgMTvebf3Spgy_pQ';
const FOLDER_ID   = '1NckcDrgf5E2e2Kvoacmi0wijOA7_Lbuu';
const ADMIN_EMAIL = 'jaullaca@gmail.com';

// Hoja de cálculo principal
const SPREADSHEET_ID = '1PnnmabIwwvlNX4lqD1jUqazepDZfyGccoZ_opfMLqEc';

// % mínimo para considerar "aprobado"
const APPROVAL_THRESHOLD = 60;

// Total de intentos permitidos (por si el front cambia)
const MAX_INTENTOS = 3;

/**
 * Mini HTML que emite una señal al padre (tu index) para cerrar el popup
 * y opcionalmente indicar destino (next, slide7, etc.).
 */
function signalHtml_(dest) {
  var d = dest || '';
  var html =
    '<!DOCTYPE html><meta charset="utf-8">' +
    '<style>body{font-family:system-ui;margin:16px}</style>' +
    '<p>Cerrando…</p>' +
    '<script>' +
    ' (function(){' +
    '   var msg = "fin:" + ' + JSON.stringify(d) + ';' +
    '   function send(){' +
    '     try{ if(window.parent)  window.parent.postMessage(msg,"*"); }catch(e){}' +
    '     try{ if(window.top)     window.top.postMessage(msg,"*"); }catch(e){}' +
    '     try{ if(window.opener)  window.opener.postMessage(msg,"*"); }catch(e){}' +
    '   }' +
    '   var n=0, id=setInterval(function(){ send(); if(++n>20){ clearInterval(id); try{ window.close(); }catch(e){} } }, 150);' +
    '   send();' +
    ' })();' +
    '<\/script>';
  return HtmlService.createHtmlOutput(html)
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

// ==============================
// MOSTRAR index.html, admin.html o listado.html + endpoint de señal
// ==============================
function doGet(e) {
  var pagina = e && e.parameter && e.parameter.page;
  var view   = e && e.parameter && e.parameter.view;

  if (view === 'signal' || view === 'cerrar') {
    var dest = (e.parameter && e.parameter.goto) || '';
    return signalHtml_(dest);
  }

  if (pagina === "admin") {
    return HtmlService.createHtmlOutputFromFile("admin")
      .setTitle("Administrador de Curso")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
  }

  if (pagina === "listado") {
    return HtmlService.createHtmlOutputFromFile("listado")
      .setTitle("Listado de Participantes")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
  }

  return HtmlService.createHtmlOutputFromFile("index")
    .setTitle("TRABAJO EN ALTURA")
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

// ==============================
// GUARDAR CONTENIDO EDITABLE
// ==============================
function guardarContenido(data) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  let hoja = ss.getSheetByName("Contenido");

  if (!hoja) hoja = ss.insertSheet("Contenido");
  if (hoja.getLastRow() === 0) hoja.appendRow(["Clave", "Valor"]);

  const ultimaFila = hoja.getLastRow();
  const clavesExistentes = ultimaFila > 1
    ? hoja.getRange(2, 1, ultimaFila - 1, 1).getValues().flat()
    : [];

  for (const clave in data) {
    const valor = data[clave];
    const i = clavesExistentes.indexOf(clave);
    if (i !== -1) hoja.getRange(i + 2, 2).setValue(valor);
    else hoja.appendRow([clave, valor]);
  }
}

// ==============================
// ENTREGAR CONTENIDO COMO JSON
// ==============================
function obtenerContenido() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const hoja = ss.getSheetByName("Contenido");
  if (!hoja) return {};

  const ultimaFila = hoja.getLastRow();
  if (ultimaFila < 2) return {};

  const datos = hoja.getRange(2, 1, ultimaFila - 1, 2).getValues();
  const out = {};
  datos.forEach(([k, v]) => out[k] = v);
  return out;
}

// ==============================
// UTILIDADES
// ==============================
function _keyCorreoCurso_(correo, curso) {
  const c = (correo || '').trim().toLowerCase();
  const kCurso = (curso || '').trim();
  return `${c}|${kCurso}`;
}

function _elegirMejorIntento_(arr) {
  if (!Array.isArray(arr) || !arr.length) return null;
  return arr.reduce((mejor, cur) => {
    if (!mejor) return cur;
    const pm = Number(mejor.porcentaje) || 0;
    const pc = Number(cur.porcentaje) || 0;
    if (pc > pm) return cur;
    if (pc < pm) return mejor;
    const tm = Number(mejor.tiempo) || 999999;
    const tc = Number(cur.tiempo) || 999999;
    if (tc < tm) return cur;
    return cur; // empate final: más reciente
  }, null);
}

function _n(x, d) {
  const v = Number(x);
  return isNaN(v) ? d : v;
}

function safeStr(v) { return (v === null || v === undefined) ? '' : String(v); }
function toISO_(d) {
  try { return (d && d.toISOString) ? d.toISOString() : (d ? new Date(d).toISOString() : ''); }
  catch(e){ return ''; }
}

// ==============================
// GUARDAR RESPUESTAS (Resumen + Detalle + Envío único)
// ==============================
function guardarDatos(data) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);

  // ===== 1) Guardar DETALLE del intento (una fila por pregunta)
  try {
    const arr = Array.isArray(data.intentos) ? data.intentos : [];
    const ultimo = arr.length ? arr[arr.length - 1] : null;

    const detalle = Array.isArray(data.detalle) ? data.detalle :
                    (ultimo && Array.isArray(ultimo.detalle) ? ultimo.detalle : []);

    if (detalle && detalle.length) {
      const intentoActual = (ultimo && _n(ultimo.intento, arr.length)) || _n(data.intento, 1) || 1;
      const payload = {
        intento: intentoActual,
        maxIntentos: _n(data.maxIntentos, MAX_INTENTOS),
        correo: (data.correo || ''),
        nombre: (data.nombre || ''),
        apellido: (data.apellido || ''),
        empresa: (data.empresa || ''),
        cargo: (data.cargo || ''),
        curso: (data.curso || ''),
        correctas: _n((ultimo && ultimo.correctas) || data.correctas, 0),
        totales: _n((ultimo && ultimo.totales) || data.totales, 0),
        porcentaje: _n((ultimo && ultimo.porcentaje) || data.porcentaje, 0),
        tiempo: _n((ultimo && ultimo.tiempo) || data.tiempo, 0),
        detalle: detalle
      };
      registrarDetallePorFila_(ss, payload);
    }
  } catch (e) {
    Logger.log('Detalle error: ' + e);
  }

  // ===== 2) Elegir el MEJOR intento (según % y tiempo de quiz)
  let mejor = null;
  if (Array.isArray(data.intentos) && data.intentos.length) {
    mejor = _elegirMejorIntento_(data.intentos);
  } else {
    mejor = {
      intento: _n(data.intento, 1),
      maxIntentos: _n(data.maxIntentos, MAX_INTENTOS),
      correctas: _n(data.correctas, 0),
      totales: _n(data.totales, 0),
      porcentaje: _n(data.porcentaje, 0),
      tiempo: _n(data.tiempo, 0),
      detalle: Array.isArray(data.detalle) ? data.detalle : []
    };
  }
  const aprobadoMejor = (_n(mejor.porcentaje, 0) >= APPROVAL_THRESHOLD);

  // ===== 3) Upsert en "Respuestas" con el MEJOR intento (guarda Tiempo Total)
  try {
    upsertMejorEnRespuestas_(ss, data, mejor);
  } catch (e) {
    Logger.log('Respuestas error: ' + e);
  }

  // ===== 4) Envío de correo (uno por correo|curso) y generación de certificado
  const props = PropertiesService.getScriptProperties();
  const keySent = 'sent:' + _keyCorreoCurso_(data.correo, data.curso);
  const yaEnviado = props.getProperty(keySent) === '1';

  const totalIntentosRecibidos = Array.isArray(data.intentos) ? data.intentos.length : _n(data.intento, 1);
  const terminado = !!data.esFinal || aprobadoMejor || (totalIntentosRecibidos >= MAX_INTENTOS);

  let url = null, enviado = false;

  if (!yaEnviado && terminado) {
    // Ajustamos 'data' con el mejor intento antes de generar/enviar
    data.correctas  = _n(mejor.correctas, 0);
    data.totales    = _n(mejor.totales, 0);
    data.porcentaje = _n(mejor.porcentaje, 0);
    data.tiempo     = _n(mejor.tiempo, 0);
    data.aprobado   = aprobadoMejor;

    try {
      url = generarCertificado(data);  // genera PDF y envía correo
      PropertiesService.getScriptProperties().setProperty(keySent, '1'); // marca como enviado
      enviado = true;

      // Actualizar la columna "Certificado" en Respuestas
      if (url) {
        try { updateRespuestasCertUrl_(ss, data.correo, url); } catch (e2) { Logger.log('Update Cert URL error: ' + e2); }
      }
    } catch (e) {
      Logger.log('Correo/Certificado error: ' + e);
    }
  }

  return {
    ok: true,
    enviado,
    url,
    mejor: {
      intento: _n(mejor.intento, totalIntentosRecibidos),
      correctas: _n(mejor.correctas, 0),
      totales: _n(mejor.totales, 0),
      porcentaje: _n(mejor.porcentaje, 0),
      tiempo: _n(mejor.tiempo, 0),
      aprobado: aprobadoMejor
    },
    threshold: APPROVAL_THRESHOLD,
    intentosRecibidos: totalIntentosRecibidos
  };
}

// ==============================
// HOJA "Respuestas": upsert con el MEJOR intento
// (UNA FILA por correo) — Guarda Tiempo Total (seg) y (HH:MM:SS)
// ==============================
function upsertMejorEnRespuestas_(ss, data, mejor) {
  let hoja = ss.getSheetByName("Respuestas");
  if (!hoja) hoja = ss.insertSheet("Respuestas");

  // Encabezados ACTUALIZADOS
  const headers = [
    "Fecha","Nombre","Apellido","Correo","RUT","Género","Edad",
    "Empresa","Cargo","Correctas","Totales","Porcentaje",
    "Tiempo Total (seg)","Tiempo Total (HH:MM:SS)",
    "Firma (base64)","Certificado"
  ];

  // Asegurar encabezados
  if (hoja.getLastRow() === 0) {
    hoja.getRange(1,1,1,headers.length).setValues([headers]);
  } else {
    const current = hoja.getRange(1,1,1,Math.max(headers.length, hoja.getLastColumn())).getValues()[0];
    let mustRewrite = false;
    headers.forEach((h, i) => { if (current[i] !== h) mustRewrite = true; });
    if (mustRewrite) hoja.getRange(1,1,1,headers.length).setValues([headers]);
  }

  const hdr = hoja.getRange(1,1,1,hoja.getLastColumn()).getValues()[0];
  const idx = {
    fecha:        hdr.indexOf("Fecha")+1,
    nombre:       hdr.indexOf("Nombre")+1,
    apellido:     hdr.indexOf("Apellido")+1,
    correo:       hdr.indexOf("Correo")+1,
    rut:          hdr.indexOf("RUT")+1,
    genero:       hdr.indexOf("Género")+1,
    edad:         hdr.indexOf("Edad")+1,
    empresa:      hdr.indexOf("Empresa")+1,
    cargo:        hdr.indexOf("Cargo")+1,
    correctas:    hdr.indexOf("Correctas")+1,
    totales:      hdr.indexOf("Totales")+1,
    porcentaje:   hdr.indexOf("Porcentaje")+1,
    tiempoSeg:    hdr.indexOf("Tiempo Total (seg)")+1,
    tiempoFmt:    hdr.indexOf("Tiempo Total (HH:MM:SS)")+1,
    firma:        hdr.indexOf("Firma (base64)")+1,
    certificado:  hdr.indexOf("Certificado")+1
  };

  const correoKey = String(data.correo || '').trim().toLowerCase();
  const lastRow = hoja.getLastRow();

  let filaEncontrada = -1;
  if (lastRow >= 2) {
    const colCorreoPos = idx.correo;
    const correos = hoja.getRange(2, colCorreoPos, lastRow-1, 1).getValues().map(r => String(r[0]||'').toLowerCase());
    for (let i=0; i<correos.length; i++) {
      if (correos[i] === correoKey) { filaEncontrada = i + 2; break; }
    }
  }

  // Compatibilidad: si llegara el nombre viejo "tiempoTotal"
  const tiempoTotalSegundos = _n((data.tiempoTotalSegundos != null ? data.tiempoTotalSegundos : data.tiempoTotal), 0);
  const tiempoTotalHHMMSS   = safeStr(data.tiempoTotalHHMMSS || '');

  const fila = [
    new Date(),
    data.nombre || "",
    data.apellido || "",
    data.correo || "",
    data.rut || "",
    data.genero || "",
    data.edad || "",
    data.empresa || "",
    data.cargo || "",
    _n(mejor.correctas, 0),
    _n(mejor.totales, 0),
    _n(mejor.porcentaje, 0),
    tiempoTotalSegundos,           // seg
    tiempoTotalHHMMSS,             // HH:MM:SS
    data.firma || "",
    ""
  ];

  if (filaEncontrada === -1) hoja.appendRow(fila);
  else hoja.getRange(filaEncontrada, 1, 1, fila.length).setValues([fila]);
}

/**
 * Actualiza la columna "Certificado" de la fila en Respuestas que
 * corresponde al correo indicado. Si hay varias filas, actualiza la más reciente.
 */
function updateRespuestasCertUrl_(ss, correo, urlCert) {
  if (!correo || !urlCert) return;
  const hoja = ss.getSheetByName("Respuestas");
  if (!hoja || hoja.getLastRow() < 2) return;

  const hdr = hoja.getRange(1,1,1,hoja.getLastColumn()).getValues()[0];
  const colCorreo = hdr.indexOf("Correo")+1;
  const colCert   = hdr.indexOf("Certificado")+1;
  const colFecha  = hdr.indexOf("Fecha")+1;

  if (colCorreo < 1 || colCert < 1) return;

  const lastRow = hoja.getLastRow();
  const rng = hoja.getRange(2, 1, lastRow-1, hoja.getLastColumn()).getValues();

  let bestRowIndex = -1;
  let bestDateValue = -1;
  for (let i=0;i<rng.length;i++) {
    const row = rng[i];
    const mail = String(row[colCorreo-1] || '').toLowerCase();
    if (mail === String(correo).toLowerCase()) {
      const d = row[colFecha-1];
      const ts = (d && d.getTime) ? d.getTime() : Date.parse(d || '') || 0;
      if (ts >= bestDateValue) {
        bestDateValue = ts;
        bestRowIndex = i + 2;
      }
    }
  }
  if (bestRowIndex !== -1) {
    hoja.getRange(bestRowIndex, colCert).setValue(urlCert);
  }
}

// ==============================
// HOJA "Detalle": una fila por pregunta del intento actual
// ==============================
function registrarDetallePorFila_(ss, data) {
  let sh = ss.getSheetByName('Detalle');
  if (!sh) sh = ss.insertSheet('Detalle');

  const headers = [
    "Fecha","Intento","MaxIntentos","Correo","Nombre","Apellido","Empresa","Cargo",
    "Pregunta #","Pregunta","Marcada (letra)","Marcada (texto)",
    "Correcta (letra)","Correcta (texto)","OK",
    "Correctas totales","Totales","Porcentaje","Tiempo (seg)","Curso"
  ];
  if (sh.getLastRow() === 0) sh.appendRow(headers);

  const detalle = Array.isArray(data.detalle) ? data.detalle : [];
  if (!detalle.length) return;

  const fecha       = new Date();
  const intento     = _n(data.intento, 1);
  const maxIntentos = _n(data.maxIntentos, MAX_INTENTOS);

  const correctas   = _n(data.correctas, 0);
  const totales     = _n(data.totales, 0);
  const porcentaje  = _n(data.porcentaje, 0);
  const tiempo      = _n(data.tiempo, 0);

  const rows = detalle.map((d, idx) => ([
    fecha,
    intento,
    maxIntentos,
    data.correo || "",
    data.nombre || "",
    data.apellido || "",
    data.empresa || "",
    data.cargo || "",
    (d && d.numero != null ? d.numero : idx + 1),
    (d && d.pregunta) || "",
    (d && d.opcionMarcada) || "",
    (d && d.textoMarcada) || "",
    (d && d.opcionCorrecta) || "",
    (d && d.textoCorrecta) || "",
    (d && d.esCorrecta) ? 1 : 0,
    correctas,
    totales,
    porcentaje,
    tiempo,
    data.curso || ""
  ]));

  const start = sh.getLastRow() + 1;
  sh.getRange(start, 1, rows.length, headers.length).setValues(rows);
}

// ==============================
// GENERAR CERTIFICADO + ENVIAR CORREO (admin + usuario) + DEVOLVER URL
// ==============================
function generarCertificado(data) {
  const carpeta = DriveApp.getFolderById(FOLDER_ID);

  const pct = Number(data.porcentaje) || 0;
  const aprobado = (typeof data.aprobado === 'boolean') ? data.aprobado : (pct >= APPROVAL_THRESHOLD);

  const estadoTxt = aprobado ? 'APROBADO' : 'NO APROBADO (no cumple con el puntaje mínimo)';
  const nombreBase = `Certificado ${data.nombre || ''} ${data.apellido || ''}${aprobado ? '' : ' (No aprueba)'}`.trim();

  const copia = DriveApp.getFileById(TEMPLATE_ID).makeCopy(nombreBase, carpeta);
  const docId = copia.getId();
  const doc   = DocumentApp.openById(docId);
  const body  = doc.getBody();

  body.replaceText('{{Nombre}}', (data.nombre || '') + ' ' + (data.apellido || ''));
  body.replaceText('{{Fecha}}', Utilities.formatDate(new Date(), 'America/Santiago', 'dd/MM/yyyy'));
  body.replaceText('{{Empresa}}', data.empresa || '');
  body.replaceText('{{Cargo}}', data.cargo || '');
  body.replaceText('{{Porcentaje}}', pct + '%');
  body.replaceText('{{Genero}}', data.genero || '');
  body.replaceText('{{Edad}}', data.edad || '');
  body.replaceText('{{Curso}}', data.curso || '');
  body.replaceText('{{RUT}}', data.rut || '');

  // tiempo total si tu plantilla tiene {{TiempoTotal}}
  if (body.findText('{{TiempoTotal}}')) {
    const hhmmss = safeStr(data.tiempoTotalHHMMSS || '');
    body.replaceText('{{TiempoTotal}}', hhmmss);
  }

  const estadoFound = body.findText('{{Estado}}');
  if (estadoFound) {
    body.replaceText('{{Estado}}', estadoTxt);
  } else if (!aprobado) {
    const aviso = body.insertParagraph(0, '*** PARTICIPANTE NO APROBADO: no cumple con el puntaje mínimo exigido. ***');
    aviso.setBold(true).setForegroundColor('#B00020').setAlignment(DocumentApp.HorizontalAlignment.CENTER);
  }

  const firmaMatch = body.findText('{{Firma}}');
  if (firmaMatch) {
    const base64 = ((data.firma || '').split(',')[1]) || '';
    if (base64) {
      const blob = Utilities.newBlob(Utilities.base64Decode(base64), 'image/png', 'firma.png');
      const elemento = firmaMatch.getElement();
      const parent   = elemento.getParent();
      const idx      = parent.getChildIndex(elemento);
      parent.insertInlineImage(idx + 1, blob).setWidth(200);
    }
    body.replaceText('{{Firma}}', '');
  }

  doc.saveAndClose();

  const pdfBlob = DriveApp.getFileById(docId).getAs('application/pdf').setName(`${nombreBase}.pdf`);
  const pdfFile = carpeta.createFile(pdfBlob);

  const userEmail = (data.correo || '').trim();
  const to = ADMIN_EMAIL;
  const cc = userEmail ? userEmail : '';

  const sujetoPrefix = aprobado ? '' : '[NO APROBADO] ';
  const cuerpoExtra  = aprobado ? '' :
    `<br><b>Estado:</b> No aprobado (no cumple con el puntaje mínimo de ${APPROVAL_THRESHOLD}%).` +
    `<br><b>Porcentaje obtenido:</b> ${pct}%.`;

  const tiempoLine = (data.tiempoTotalHHMMSS || data.tiempoTotalSegundos != null)
    ? `<br><b>Tiempo total:</b> ${data.tiempoTotalHHMMSS || (Number(data.tiempoTotalSegundos) + ' s')}`
    : '';

  try {
    MailApp.sendEmail({
      to,
      cc,
      subject: `${sujetoPrefix}Nuevo certificado: ${data.nombre || ''} ${data.apellido || ''}`.trim(),
      htmlBody: `Se ha generado un certificado para <b>${(data.nombre || '')} ${(data.apellido || '')}</b> el ${Utilities.formatDate(new Date(), 'America/Santiago', 'dd/MM/yyyy')}.` + cuerpoExtra + tiempoLine,
      attachments: [ pdfBlob ],
      replyTo: ADMIN_EMAIL
    });
  } catch (e) {
    MailApp.sendEmail({
      to: ADMIN_EMAIL,
      subject: `${sujetoPrefix}Nuevo certificado: ${data.nombre || ''} ${data.apellido || ''}`.trim(),
      htmlBody: `Se ha generado un certificado para <b>${(data.nombre || '')} ${(data.apellido || '')}</b> el ${Utilities.formatDate(new Date(), 'America/Santiago', 'dd/MM/yyyy')}.` + cuerpoExtra + tiempoLine + (cc ? `<br><i>Nota:</i> Falló el envío al usuario (${cc}).` : ''),
      attachments: [ pdfBlob ],
      replyTo: ADMIN_EMAIL
    });
  }

  return `https://docs.google.com/uc?export=download&id=${pdfFile.getId()}`;
}

// ==============================
// FORMATEO: SOLO Minutos:Segundos para listado
// ==============================
function segundosAMMSS(seg) {
  seg = Math.max(0, Number(seg) || 0);
  const mm = Math.floor(seg / 60);      // minutos totales (acumula horas si las hay)
  const ss = seg % 60;
  return `${String(mm).padStart(2,'0')}:${String(ss).padStart(2,'0')}`;
}

// ==============================
// LISTADO PARA listado.html (usa TIEMPO TOTAL)
// Devuelve: tiempo   -> segundos
//           tiempoFmt-> MM:SS (calculado aquí)
// ==============================
function listarAlumnos() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);

  const shR = ss.getSheetByName("Respuestas");
  if (!shR) return [];

  const valsR = shR.getDataRange().getValues();
  if (valsR.length < 2) return [];

  // Encabezados actuales:
  // 0 Fecha | 1 Nombre | 2 Apellido | 3 Correo | 4 RUT | 5 Género | 6 Edad
  // 7 Empresa | 8 Cargo | 9 Correctas | 10 Totales | 11 Porcentaje
  // 12 Tiempo Total (seg) | 13 Tiempo Total (HH:MM:SS) | 14 Firma (base64) | 15 Certificado
  const rowsR = valsR.slice(1).map(r => ({
    fechaRaw: r[0],
    fecha: toISO_(r[0]),
    nombre: safeStr(r[1]),
    apellido: safeStr(r[2]),
    correo: safeStr(r[3]).toLowerCase(),
    rut: safeStr(r[4]),
    cargo: safeStr(r[8]),
    porcentaje: Number(r[11] || 0),
    tiempoTotalSeg: Number(r[12] || 0),
    certificado: safeStr(r[15] || '')
  }));

  // Intentos desde Detalle
  const intentosPorCorreo = {};
  const shD = ss.getSheetByName("Detalle");
  if (shD && shD.getLastRow() > 1) {
    const valsD = shD.getDataRange().getValues();
    for (var i = 1; i < valsD.length; i++) {
      var d = valsD[i];
      var correo = safeStr(d[3]).toLowerCase();
      var intento = Number(d[1] || 0);
      if (!correo) continue;
      if (!intentosPorCorreo[correo] || intento > intentosPorCorreo[correo]) {
        intentosPorCorreo[correo] = intento;
      }
    }
  }

  const out = rowsR.map(r => {
    const estado = (r.porcentaje >= APPROVAL_THRESHOLD) ? 'aprobado' :
                   (r.porcentaje > 0) ? 'reprobado' : 'proceso';
    const intentos = intentosPorCorreo[r.correo] || 1;
    const seg = r.tiempoTotalSeg;
    return {
      fecha: r.fecha,
      nombre: r.nombre,
      apellido: r.apellido,
      rut: r.rut,
      correo: r.correo,
      cargo: r.cargo,
      porcentaje: r.porcentaje,
      intentos: intentos,
      tiempo: seg,                // segundos
      tiempoFmt: segundosAMMSS(seg), // MM:SS para mostrar
      estado: estado,
      certificado: r.certificado
    };
  });

  out.sort((a,b) => {
    const tA = Date.parse(a.fecha || '') || 0;
    const tB = Date.parse(b.fecha || '') || 0;
    return tB - tA;
  });

  return out;
}

// ==============================
// Helpers de pruebas (opcionales)
// ==============================
function testMailDirect(){
  const to = ADMIN_EMAIL;
  MailApp.sendEmail({ to, subject: 'Test MailApp desde Code.gs', htmlBody: 'Si recibes esto, MailApp está OK.' });
}

function verCuota(){
  Logger.log('MailApp remaining quota: ' + MailApp.getRemainingDailyQuota());
}

function resetEnvio(correo, curso){
  const key = 'sent:' + _keyCorreoCurso_(correo, curso);
  PropertiesService.getScriptProperties().deleteProperty(key);
}

function resetTodasLasBanderasEnvio(){
  const props = PropertiesService.getScriptProperties();
  const all = props.getProperties();
  Object.keys(all).forEach(k => { if (k.startsWith('sent:')) { props.deleteProperty(k); } });
}

function verBanderasEnvio(){
  const props = PropertiesService.getScriptProperties().getProperties();
  Object.keys(props).forEach(k => { if (k.startsWith('sent:')) Logger.log(k + ' = ' + props[k]); });
}