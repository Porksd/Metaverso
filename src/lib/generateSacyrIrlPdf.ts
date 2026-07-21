import jsPDF from "jspdf";
import type { SacyrIrlFormData } from "./sacyrIrlData";

export interface InduccionData {
  politicas: Record<string, boolean>;
  contenidos: Record<string, boolean | string>;
  productos_quimicos: { tipo: string; riesgos: string; medidas: string }[];
  equipos_maquinarias: { nombre: string; marca: string; modelo: string }[];
  epp_tipo: string;
  capacitacion: { riesgo: string; accion: string }[];
  comprension: Record<string, boolean>;
}

export interface SacyrIrlPdfInput {
  form: SacyrIrlFormData;
  studentName: string;
  studentRut: string;
  jobName?: string;
  companyName?: string;
  fecha?: string;
  motivo: "nueva_incorporacion" | "cambio_proceso" | "nuevas_actividades";
  induccion?: InduccionData;
  respuestas_parte1: Record<number, number>;
  riesgos_identificados: { riesgo: string; medidas: string }[];
  imagen_riesgo_1?: string;
  imagen_medidas_1?: string;
  imagen_riesgo_2?: string;
  imagen_medidas_2?: string;
  studentSignatureUrl?: string | null;
  relatorSignatureUrl?: string | null;
  relatorName?: string | null;
  relatorRole?: string | null;
}

const HEADER_CODE = "PG.10.06.CL-F03 Ed 05";
const HEADER_TITLE = "INFORMACI\u00d3N Y FORMACI\u00d3N PREVENTIVA - INFORMAR LOS RIESGOS LABORALES (IRL)";
const FOOTER_LINE1 = "DIRECCI\u00d3N DE SEGURIDAD Y SALUD";
const FOOTER_LINE2 = "La Direcci\u00f3n de Seguridad y Salud del Grupo Sacyr no garantiza que la copia impresa de este documento sea la Edici\u00f3n vigente.";
const SYSTEM_LABEL = "SISTEMA DE GESTI\u00d3N DE SEGURIDAD Y SALUD";

/**
 * Reverses the UTF-8 → Windows-1252 corruption introduced when PowerShell
 * edited this file using the wrong encoding.
 */
function fixenc(s: string | null | undefined): string {
  if (!s) return s ?? '';
  return s
    .replace(/\u00c3\u00a1/g, '\u00e1') // \u00e1 = \xc3\xa1
    .replace(/\u00c3\u00a9/g, '\u00e9') // \u00e9
    .replace(/\u00c3\u00ad/g, '\u00ed') // \u00ed
    .replace(/\u00c3\u00b3/g, '\u00f3') // \u00f3
    .replace(/\u00c3\u00ba/g, '\u00fa') // \u00fa
    .replace(/\u00c3\u00b1/g, '\u00f1') // \u00f1
    .replace(/\u00c3\u00bc/g, '\u00fc') // \u00fc
    .replace(/\u00c3\u00a7/g, '\u00e7') // \u00e7
    .replace(/\u00c3\u00a0/g, '\u00e0') // \u00e0
    .replace(/\u00c3\u00b2/g, '\u00f2') // \u00f2
    .replace(/\u00c3\u00b9/g, '\u00f9') // \u00f9
    .replace(/\u00c3\u201c/g, '\u00d3') // \u00d3 (0x93 in cp1252 = U+201C)
    .replace(/\u00c3\u2018/g, '\u00d1') // \u00d1 (0x91 = U+2018)
    .replace(/\u00c3\u2030/g, '\u00c9') // \u00c9 (0x89 = U+2030)
    .replace(/\u00c3\u0161/g, '\u00da') // \u00da (0x9A = U+0161)
    .replace(/\u00c3\u0153/g, '\u00dc') // \u00dc (0x9C = U+0153)
    .replace(/\u00c3\u0081/g, '\u00c1') // \u00c1 (0x81 undefined)
    .replace(/\u00c2\u00b0/g, '\u00b0') // degree sign
    .replace(/\u00c2\u00ba/g, '\u00ba') // masculine ordinal
    .replace(/\u00c2\u00a1/g, '\u00a1') // inverted !
    .replace(/\u00e2\u20ac\u00a2/g, '\u2022') // bullet
    .replace(/\u00c3\u00af/g, '\u00ef') // \u00ef
    .replace(/\u00c3\u00ae/g, '\u00ee') // \u00ee
    .replace(/\u00c3\u00b4/g, '\u00f4') // \u00f4
    .replace(/\u00c3\u201d/g, '\u00d4') // \u00d4
    .replace(/\u00c3\u2019/g, '\u00d2') // \u00d2
    ;
}

async function urlToDataUrl(url: string): Promise<string | null> {
  try {
    if (url.startsWith("data:image/")) return url;
    const fullUrl = url.startsWith("http") ? url : `${window.location.origin}${url}`;
    const res = await fetch(fullUrl);
    if (!res.ok) return null;
    const blob = await res.blob();
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(reader.result as string);
      reader.readAsDataURL(blob);
    });
  } catch {
    return null;
  }
}

function addHeaderFooter(
  pdf: jsPDF,
  pageNum: number,
  totalPages: number,
  sacyrLogoData: string | null
) {
  const W = 210;
  const headerH = 20;

  // â”€â”€ Header box â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  pdf.setDrawColor(0, 51, 102);
  pdf.setLineWidth(0.5);
  pdf.rect(10, 8, W - 20, headerH);

  // Sacyr logo area (left cell)
  if (sacyrLogoData) {
    try { pdf.addImage(sacyrLogoData, "PNG", 12, 9, 30, 16); } catch { /* ignore */ }
  }
  // Divider
  pdf.line(44, 8, 44, 28);
  // Center cell: system title + form title
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(6);
  pdf.setTextColor(0, 51, 102);
  pdf.text(SYSTEM_LABEL, 47, 14);
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(5.5);
  pdf.text(HEADER_TITLE, 47, 18);
  // Right cell: code + page
  pdf.line(W - 40, 8, W - 40, 28);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(5.5);
  pdf.text(HEADER_CODE, W - 38, 14);
  pdf.setFont("helvetica", "normal");
  // Page number is updated in the post-render pass at the end of generateSacyrIrlPdf
  pdf.text(`Pág. ${pageNum}`, W - 38, 19);

  // â”€â”€ Footer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  const footerY = 285;
  pdf.setDrawColor(0, 51, 102);
  pdf.line(10, footerY, W - 10, footerY);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(5.5);
  pdf.setTextColor(0, 51, 102);
  pdf.text(FOOTER_LINE1, W / 2, footerY + 4, { align: "center" });
  pdf.setFont("helvetica", "normal");
  pdf.setTextColor(100, 100, 100);
  pdf.setFontSize(4.5);
  pdf.text(FOOTER_LINE2, W / 2, footerY + 8, { align: "center" });
}

function sectionTitle(pdf: jsPDF, text: string, y: number, pageW: number) {
  pdf.setFillColor(0, 51, 102);
  pdf.rect(10, y, pageW - 20, 6, "F");
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(8);
  pdf.setTextColor(255, 255, 255);
  pdf.text(fixenc(text), 13, y + 4.5);
  pdf.setTextColor(0, 0, 0);
  return y + 6;
}

function tableRow(
  pdf: jsPDF,
  label: string,
  value: string,
  y: number,
  labelW: number,
  rowH: number,
  lineW: number
) {
  pdf.setDrawColor(180, 180, 180);
  pdf.setLineWidth(0.2);
  pdf.rect(10, y, labelW, rowH);
  pdf.rect(10 + labelW, y, lineW - labelW, rowH);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(7.5);
  pdf.setTextColor(50, 50, 50);
  pdf.text(fixenc(label), 12, y + rowH - 2.5);
  pdf.setFont("helvetica", "normal");
  pdf.setTextColor(0, 0, 0);
  const maxW = lineW - labelW - 4;
  const wrapped = pdf.splitTextToSize(fixenc(value), maxW);
  pdf.text(wrapped[0] || "", 12 + labelW, y + rowH - 2.5);
  return y + rowH;
}

function bulletList(pdf: jsPDF, items: string[], x: number, y: number, maxW: number): number {
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.setTextColor(20, 20, 20);
  for (const item of items) {
    const lines = pdf.splitTextToSize(`• ${fixenc(item)}`, maxW - 4);
    pdf.text(lines, x + 2, y);
    y += lines.length * 4 + 1;
  }
  return y;
}

function checkbox(pdf: jsPDF, x: number, y: number, checked: boolean, label: string) {
  pdf.setDrawColor(0, 0, 0);
  pdf.setLineWidth(0.3);
  pdf.rect(x, y - 3, 4, 4);
  if (checked) {
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(9);
    pdf.text("x", x + 0.5, y + 0.3);
  }
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.text(fixenc(label), x + 5.5, y);
}

export async function generateSacyrIrlPdf(input: SacyrIrlPdfInput): Promise<void> {
  const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
  const W = 210;
  const M = 10;
  const contentW = W - 2 * M;
  const bodyTop = 32;

  const fecha = input.fecha || new Date().toLocaleDateString("es-CL");

  // Pre-load images
  const [sacyrLogo, studentSig, relatorSig] = await Promise.all([
    urlToDataUrl("/cert-assets/logo_sacyr.png"),
    input.studentSignatureUrl ? urlToDataUrl(input.studentSignatureUrl) : Promise.resolve(null),
    input.relatorSignatureUrl ? urlToDataUrl(input.relatorSignatureUrl) : Promise.resolve(null),
  ]);

  // â”€â”€ PAGE 1 â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  let y = bodyTop;

  // â”€â”€ Info header table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  const halfW = contentW / 2;
  const infoRows1: [string, string][] = [
    ["Centro de Trabajo:", "Hospital Provincia Cordillera"],
    ["Empresa:", input.companyName || "Sacyr"],
    ["Fecha:", fecha],
  ];
  const infoRows2: [string, string][] = [
    ["PaÃ­s:", "Chile"],
    ["LÃ­nea de Negocio:", "Infraestructura"],
    ["DuraciÃ³n de IRL:", "8 horas"],
  ];

  infoRows1.forEach(([label, value], i) => {
    tableRow(pdf, label, value, y + i * 7, 40, 7, halfW);
  });
  // Right column only (no duplicate tableRow)
  for (let i = 0; i < infoRows2.length; i++) {
    const [label, value] = infoRows2[i];
    pdf.setDrawColor(180, 180, 180);
    pdf.setLineWidth(0.2);
    pdf.rect(M + halfW, y + i * 7, 40, 7);
    pdf.rect(M + halfW + 40, y + i * 7, halfW - 40, 7);
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(7.5);
    pdf.setTextColor(50, 50, 50);
    pdf.text(fixenc(label), M + halfW + 2, y + i * 7 + 4.5);
    pdf.setFont("helvetica", "normal");
    pdf.setTextColor(0, 0, 0);
    pdf.text(fixenc(value), M + halfW + 42, y + i * 7 + 4.5);
  }

  y += 24;

  // â”€â”€ Worker info â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  const workerRows: [string, string][] = [
    ["Nombres y Apellidos:", input.studentName],
    ["RUT:", input.studentRut],
    ["Ãrea o secciÃ³n de trabajo:", input.form.area],
    ["Cargo o puesto de Trabajo:", input.form.cargo_name],
  ];
  for (const [label, value] of workerRows) {
    y = tableRow(pdf, label, value, y, 55, 7, contentW);
  }
  y += 3;

  // â”€â”€ Job description â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  y = sectionTitle(pdf, "DescripciÃ³n breve del puesto de trabajo", y, W);
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.setTextColor(20, 20, 20);
  const descLines = pdf.splitTextToSize(input.form.descripcion_puesto, contentW - 4);
  pdf.text(descLines, M + 2, y + 5);
  y += descLines.length * 4.5 + 8;

  // â”€â”€ Tasks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (input.form.tareas.length > 0) {
    y = sectionTitle(pdf, "Tareas que realizan", y, W);
    y += 3;
    y = bulletList(pdf, input.form.tareas, M, y, contentW);
    y += 4;
  }

  // â”€â”€ Work locations â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (input.form.lugares_trabajo.length > 0) {
    y = sectionTitle(pdf, "Lugares de Trabajo", y, W);
    y += 3;
    y = bulletList(pdf, input.form.lugares_trabajo, M, y, contentW);
    y += 4;
  }

  // â”€â”€ Tools â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (input.form.herramientas.length > 0) {
    y = sectionTitle(pdf, "Herramientas y Equipos", y, W);
    y += 3;
    y = bulletList(pdf, input.form.herramientas, M, y, contentW);
    y += 4;
  }

  // â”€â”€ Order/cleanliness â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (input.form.orden_aseo.length > 0) {
    y = sectionTitle(pdf, "Condiciones de orden y aseo exigidas en el puesto de trabajo", y, W);
    y += 3;
    y = bulletList(pdf, input.form.orden_aseo, M, y, contentW);
    y += 4;
  }

  // â”€â”€ IRL Reason â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Check if we need a new page
  if (y > 230) {
    addHeaderFooter(pdf, 1, 4, sacyrLogo);
    pdf.addPage();
    y = bodyTop;
  }

  y = sectionTitle(pdf, "Motivo de informaciÃ³n de riesgos laborales", y, W);
  y += 6;
  const motivos = [
    { key: "nueva_incorporacion", label: "Nueva incorporaciÃ³n de Persona Trabajadora." },
    { key: "cambio_proceso", label: "Cambios en el proceso de trabajo o puesto de trabajo." },
    { key: "nuevas_actividades", label: "Nuevas actividades." },
  ];
  for (const m of motivos) {
    checkbox(pdf, M + 4, y, input.motivo === m.key, m.label);
    y += 7;
  }
  y += 3;

  // â”€â”€ Legal declaration â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  const legalText = `En cumplimiento a lo dispuesto en el Decreto NÂ° 44, tÃ­tulo II, pÃ¡rrafo 4, artÃ­culo 15 en "INFORMAR LOS RIESGOS LABORALES (IRL)". Por tanto, el abajo firmante; declara conocer los riesgos que conllevan las labores que ejecuta, las medidas preventivas que debe respetar y cumplir de manera inmediata, ejecutando sus labores por medio de mÃ©todos de trabajos correctos y seguros. Por lo tanto, el abajo firmante; se compromete a que cuando se presenten condiciones de riesgo en los lugares de trabajo, deberÃ¡ informarlos de manera inmediata y oportuna a su jefatura directa y/o personal de SST y/o ComitÃ© Paritario de Higiene y Seguridad, con la finalidad que estas condiciones sean analizadas y se establezcan los mÃ©todos y medidas de control que deberÃ¡ adoptar para ejecutar en forma segura sus labores.`;
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7);
  const legalLines = pdf.splitTextToSize(fixenc(legalText), contentW - 4);
  pdf.text(legalLines, M + 2, y);
  y += legalLines.length * 4 + 6;

  addHeaderFooter(pdf, 1, 4, sacyrLogo);

  // â”€â”€ PAGE 2: InducciÃ³n sections â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  pdf.addPage();
  y = bodyTop;

  const ind = input.induccion;

  // â”€â”€ PolÃ­ticas recibidas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  y = sectionTitle(pdf, "Recibe la InducciÃ³n por parte del Dpto. SST Sacyr Chile S.A.", y, W);
  y += 4;
  const politicasItems = [
    { key: "prevencion", label: "PolÃ­tica de PrevenciÃ³n" },
    { key: "alcohol",    label: "PolÃ­tica Alcohol, tabaco y drogas" },
    { key: "vial",       label: "PolÃ­tica Seguridad Vial" },
    { key: "inclusion",  label: "PolÃ­tica InclusiÃ³n" },
  ];
  const halfPol = contentW / 2;
  for (let i = 0; i < politicasItems.length; i++) {
    const pol = politicasItems[i];
    const bx = i % 2 === 0 ? M : M + halfPol;
    if (i % 2 === 0 && i > 0) y += 6;
    checkbox(pdf, bx + 2, i % 2 === 0 ? y : y - 0, !!ind?.politicas?.[pol.key], pol.label);
    if (i % 2 === 1) y += 6;
  }
  y += 6;

  // â”€â”€ Contenidos recibidos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  y = sectionTitle(pdf, "Contenidos y materias informadas", y, W);
  y += 4;

  const contenidoItems = [
    { key: "mipero",          label: "Matriz de IdentificaciÃ³n de peligros (MIPERO):", desc: "mipero_desc" },
    { key: "erpt",            label: "EvaluaciÃ³n de Riesgos de Puesto y Lugar (ERPT y ERLT):", desc: "erpt_desc" },
    { key: "restriccion",     label: "RestricciÃ³n mÃ©dica:", desc: "restriccion_desc" },
    { key: "sensible",        label: "Persona especialmente sensible o vulnerable:", desc: "sensible_desc" },
    { key: "plan_gestion",    label: "Plan de GestiÃ³n de PrevenciÃ³n y actividades asociadas." },
    { key: "salud",           label: "Salud ocupacional: Protocolos MINSAL." },
    { key: "ptos",            label: "Procedimiento PTOS relativo a su cargo." },
    { key: "plan_emergencias",label: "Medidas contenidas en el Plan de emergencias y contingencias." },
    { key: "ind_vial",        label: "InducciÃ³n de Seguridad Vial." },
    { key: "epp_uso",         label: "Elementos de protecciÃ³n personal (cuidado y uso correcto)." },
    { key: "comite",          label: "ComitÃ© Paritario de Higiene y Seguridad." },
    { key: "req_legales",     label: "Requisitos Legales / Otros:", desc: "req_otros_desc" },
    { key: "prod_quimicos",   label: "Productos quÃ­micos." },
  ];

  pdf.setFontSize(7.5);
  for (const item of contenidoItems) {
    checkbox(pdf, M + 2, y, !!ind?.contenidos?.[item.key], item.label);
    y += 5.5;
    if (item.desc) {
      const descVal = ind?.contenidos?.[item.desc as string] as string || "";
      if (descVal) {
        pdf.setFont("helvetica", "italic");
        pdf.setFontSize(7);
        const dLines = pdf.splitTextToSize(`  â†’ ${descVal}`, contentW - 10);
        pdf.text(dLines, M + 8, y);
        y += dLines.length * 4 + 1;
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(7.5);
      }
    }
    if (y > 268) {
      addHeaderFooter(pdf, 2, 4, sacyrLogo);
      pdf.addPage();
      y = bodyTop;
    }
  }
  y += 3;

  // Productos quÃ­micos table (if present)
  if (ind?.productos_quimicos?.length) {
    pdf.setFontSize(7.5);
    pdf.setFont("helvetica", "bold");
    pdf.text("Productos químicos:", M + 2, y); y += 5;
    const colW = contentW / 3;
    ["Tipo de producto químico", "Riesgos asociados", "Medidas de control"].forEach((h, i) => {
      pdf.setFillColor(220, 230, 242);
      pdf.rect(M + i * colW, y, colW, 6, "F");
      pdf.setDrawColor(150, 150, 150); pdf.rect(M + i * colW, y, colW, 6);
      pdf.setFont("helvetica", "bold"); pdf.setFontSize(6.5);
      pdf.text(h, M + i * colW + 2, y + 4);
    });
    y += 6;
    for (const row of ind.productos_quimicos) {
      [row.tipo, row.riesgos, row.medidas].forEach((val, i) => {
        pdf.setDrawColor(150, 150, 150); pdf.rect(M + i * colW, y, colW, 7);
        pdf.setFont("helvetica", "normal"); pdf.setFontSize(6.5);
        pdf.text(pdf.splitTextToSize(val || "", colW - 4)[0] || "", M + i * colW + 2, y + 4);
      });
      y += 7;
    }
    y += 3;
  }

  if (y > 240) { addHeaderFooter(pdf, 2, 4, sacyrLogo); pdf.addPage(); y = bodyTop; }

  // â”€â”€ Equipos y maquinarias â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  y = sectionTitle(pdf, "Equipos y/o maquinarias", y, W);
  y += 3;
  const eqColW = contentW / 3;
  ["Nombre", "Marca", "Modelo"].forEach((h, i) => {
    pdf.setFillColor(220, 230, 242);
    pdf.rect(M + i * eqColW, y, eqColW, 6, "F");
    pdf.setDrawColor(150, 150, 150); pdf.rect(M + i * eqColW, y, eqColW, 6);
    pdf.setFont("helvetica", "bold"); pdf.setFontSize(7);
    pdf.text(h, M + i * eqColW + 2, y + 4);
  });
  y += 6;
  const eqRows = ind?.equipos_maquinarias?.length ? ind.equipos_maquinarias : Array(4).fill({ nombre: "", marca: "", modelo: "" });
  for (const row of eqRows.slice(0, 4)) {
    [row.nombre, row.marca, row.modelo].forEach((val, i) => {
      pdf.setDrawColor(150, 150, 150); pdf.rect(M + i * eqColW, y, eqColW, 7);
      pdf.setFont("helvetica", "normal"); pdf.setFontSize(7);
      pdf.text(val || "", M + i * eqColW + 2, y + 4.5);
    });
    y += 7;
  }
  y += 4;

  // â”€â”€ Tipo EPP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  y = sectionTitle(pdf, "Tipo de EPPs (Describir EPPs a utilizar)", y, W);
  y += 3;
  pdf.setDrawColor(150, 150, 150);
  pdf.rect(M, y, contentW, 12);
  if (ind?.epp_tipo) {
    pdf.setFont("helvetica", "normal"); pdf.setFontSize(7.5);
    pdf.text(pdf.splitTextToSize(ind.epp_tipo, contentW - 4), M + 2, y + 5);
  }
  y += 16;

  // â”€â”€ CapacitaciÃ³n â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  y = sectionTitle(pdf, "CapacitaciÃ³n y formaciÃ³n recibida", y, W);
  y += 3;
  const capColW = contentW / 2;
  ["Riesgo", "Nombre acción formativa"].forEach((h, i) => {
    pdf.setFillColor(220, 230, 242);
    pdf.rect(M + i * capColW, y, capColW, 6, "F");
    pdf.setDrawColor(150, 150, 150); pdf.rect(M + i * capColW, y, capColW, 6);
    pdf.setFont("helvetica", "bold"); pdf.setFontSize(7);
    pdf.text(h, M + i * capColW + 2, y + 4);
  });
  y += 6;
  const capRows = ind?.capacitacion?.length ? ind.capacitacion : Array(5).fill({ riesgo: "", accion: "" });
  for (const row of capRows.slice(0, 5)) {
    [row.riesgo, row.accion].forEach((val, i) => {
      pdf.setDrawColor(150, 150, 150); pdf.rect(M + i * capColW, y, capColW, 7);
      pdf.setFont("helvetica", "normal"); pdf.setFontSize(7);
      pdf.text(val || "", M + i * capColW + 2, y + 4.5);
    });
    y += 7;
  }
  pdf.setFont("helvetica", "italic"); pdf.setFontSize(6);
  pdf.setTextColor(100, 100, 100);
  pdf.text("Consideración: capacitaciones que permitan a la persona trabajadora reconocer y gestionar los riesgos presentes en su entorno de trabajo.", M, y + 4);
  pdf.setTextColor(20, 20, 20);
  y += 10;

  if (y > 220) { addHeaderFooter(pdf, 2, 4, sacyrLogo); pdf.addPage(); y = bodyTop; }

  // â”€â”€ ComprensiÃ³n del trabajador â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  y = sectionTitle(pdf, "El trabajador declara haber comprendido la inducciÃ³n y documentaciÃ³n al respecto:", y, W);
  y += 4;
  pdf.setDrawColor(180, 180, 180);
  const compItems = [
    { key: "plan_emergencias", label: "Plan de Emergencias, contingencias y/o Desastres" },
    { key: "plan_gestion",     label: "Plan de GestiÃ³n de la PrevenciÃ³n" },
    { key: "mipero",           label: "Matriz de IdentificaciÃ³n de Peligros (MIPERO)" },
    { key: "erpt",             label: "EvaluaciÃ³n de Riesgos (ERPT y ERLT) *" },
    { key: "riohs",            label: "RIOHS" },
    { key: "protocolos",       label: "Protocolos del Cliente" },
    { key: "ptos",             label: "Procedimiento Teletrabajo (PTOS)" },
    { key: "calor",            label: "EstÃ¡ndar de Calor Extremo y Altas Temperatura" },
  ];
  const halfComp = contentW / 2;
  for (let i = 0; i < compItems.length; i++) {
    const ci = compItems[i];
    const bx = i % 2 === 0 ? M : M + halfComp;
    checkbox(pdf, bx + 2, y, !!ind?.comprension?.[ci.key], ci.label);
    if (i % 2 === 1) y += 6;
  }
  if (compItems.length % 2 !== 0) y += 6;
  pdf.setFont("helvetica", "italic"); pdf.setFontSize(6);
  pdf.setTextColor(100, 100, 100);
  pdf.text("* Herramientas que derivan del análisis MIPERO en las cuales se expresan los riesgos en el puesto de trabajo y lugar de trabajo.", M, y + 3);
  pdf.setTextColor(20, 20, 20);
  y += 8;

  addHeaderFooter(pdf, 2, 4, sacyrLogo);

  // â”€â”€ PAGE 3: Test â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  pdf.addPage();
  y = bodyTop;

  y = sectionTitle(pdf, "TEST EVALUACION INDUCCION", y, W);
  y += 3;
  pdf.setFillColor(255, 160, 0);
  pdf.rect(M, y, contentW, 7, "F");
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(7.5);
  pdf.setTextColor(255, 255, 255);
  pdf.text("Cada Pregunta vale 1 punto. Para la aprobacion se necesita una calificacion de 80%", M + 2, y + 5);
  pdf.setTextColor(20, 20, 20);
  y += 10;

  y = sectionTitle(pdf, "Primera Parte: Preguntas de alternativas (marque con una X la respuesta correcta)", y, W);
  y += 4;

  for (let qi = 0; qi < input.form.preguntas.length; qi++) {
    const q = input.form.preguntas[qi];
    const selected = input.respuestas_parte1[qi];

    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(7.5);
    pdf.setTextColor(20, 20, 20);
    const qLines = pdf.splitTextToSize(`${qi + 1}. ${q.pregunta}`, contentW - 4);
    pdf.text(qLines, M + 2, y);
    y += qLines.length * 4.5 + 2;

    const labels = ["a)", "b)", "c)", "d)"];
    for (let oi = 0; oi < q.opciones.length; oi++) {
      const isSelected = selected === oi;
      checkbox(pdf, M + 8, y, isSelected, `${labels[oi]} ${q.opciones[oi]}`);
      y += 6;
    }
    y += 3;

    if (y > 250 && qi < input.form.preguntas.length - 1) {
      addHeaderFooter(pdf, 3, 4, sacyrLogo);
      pdf.addPage();
      y = bodyTop;
    }
  }

  y += 4;

  // â”€â”€ Part 2: Workshop â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  if (y > 200) {
    addHeaderFooter(pdf, 3, 4, sacyrLogo);
    pdf.addPage();
    y = bodyTop;
  }

  y = sectionTitle(pdf, "Segunda Parte: Taller de AplicaciÃ³n", y, W);
  y += 4;

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.text(
    fixenc("Seg\u00fan la Matriz IPERO identifique y describa 5 posibles riesgos y sus medidas de control a los cuales se encuentra expuesto en sus labores:"),
    M + 2,
    y
  );
  y += 7;

  // Table header
  const colRiesgo = contentW * 0.5;
  const colMedidas = contentW * 0.5;
  pdf.setFillColor(220, 230, 242);
  pdf.rect(M, y, colRiesgo, 7, "F");
  pdf.rect(M + colRiesgo, y, colMedidas, 7, "F");
  pdf.setDrawColor(100, 100, 100);
  pdf.rect(M, y, contentW, 7);
  pdf.line(M + colRiesgo, y, M + colRiesgo, y + 7);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(7.5);
  pdf.text("Riesgos", M + colRiesgo / 2, y + 4.5, { align: "center" });
  pdf.text("Medidas de control", M + colRiesgo + colMedidas / 2, y + 4.5, { align: "center" });
  y += 7;

  const riesgos = [...(input.riesgos_identificados || [])];
  while (riesgos.length < 5) riesgos.push({ riesgo: "", medidas: "" });

  for (let ri = 0; ri < 5; ri++) {
    const rowH = 10;
    pdf.setDrawColor(150, 150, 150);
    pdf.rect(M, y, colRiesgo, rowH);
    pdf.rect(M + colRiesgo, y, colMedidas, rowH);
    pdf.line(M + colRiesgo, y, M + colRiesgo, y + rowH);
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(7);
    pdf.text(`${ri + 1})`, M + 2, y + 4);
    pdf.setFont("helvetica", "normal");
    const rLines = pdf.splitTextToSize(riesgos[ri].riesgo || "", colRiesgo - 10);
    pdf.text(rLines, M + 8, y + 4);
    const mLines = pdf.splitTextToSize(riesgos[ri].medidas || "", colMedidas - 4);
    pdf.text(mLines, M + colRiesgo + 2, y + 4);
    y += rowH;
  }

  y += 6;

  // Image analysis + signatures always on fresh page
  addHeaderFooter(pdf, (pdf as any).internal.getCurrentPageInfo().pageNumber, 4, sacyrLogo);
  pdf.addPage();
  y = bodyTop;

  // Image analysis section â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  pdf.setFont("helvetica", "italic");
  pdf.setFontSize(7.5);
  pdf.text(
    "Observe la imagen y analice situaciones de riesgo. Indique 2 con al menos 1 medida de control por cada una:",
    M + 2,
    y
  );
  y += 6;

  // Load the construction scene image
  const analysisImg = await urlToDataUrl("/cert-assets/sacyr-irl-header.png");
  if (analysisImg) {
    try {
      pdf.addImage(analysisImg, "PNG", M + 30, y, 90, 50);
    } catch { /* skip */ }
    y += 54;
  }

  // Riesgo 1 / Riesgo 2 response boxes
  const halfC = contentW / 2;
  const boxH = 24;
  ["Riesgo 1", "Riesgo 2"].forEach((label, idx) => {
    const bx = M + idx * halfC;
    pdf.setDrawColor(100, 100, 100);
    pdf.rect(bx, y, halfC, 7);
    pdf.setFillColor(220, 230, 242);
    pdf.rect(bx, y, halfC, 7, "F");
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(7.5);
    pdf.text(label, bx + halfC / 2, y + 4.5, { align: "center" });
  });
  y += 7;

  ["Riesgo 1", "Riesgo 2"].forEach((_, idx) => {
    const bx = M + idx * halfC;
    const val = idx === 0 ? (input.imagen_riesgo_1 || "") : (input.imagen_riesgo_2 || "");
    pdf.setDrawColor(150, 150, 150);
    pdf.rect(bx, y, halfC, boxH);
    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(7.5);
    const rLines = pdf.splitTextToSize(val, halfC - 4);
    pdf.text(rLines, bx + 2, y + 5);
  });
  y += boxH;

  ["Medidas de control", "Medidas de control"].forEach((label, idx) => {
    const bx = M + idx * halfC;
    pdf.setDrawColor(100, 100, 100);
    pdf.rect(bx, y, halfC, 7);
    pdf.setFillColor(220, 230, 242);
    pdf.rect(bx, y, halfC, 7, "F");
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(7.5);
    pdf.text(label, bx + halfC / 2, y + 4.5, { align: "center" });
  });
  y += 7;

  ["Medidas de control", "Medidas de control"].forEach((_, idx) => {
    const bx = M + idx * halfC;
    const val = idx === 0 ? (input.imagen_medidas_1 || "") : (input.imagen_medidas_2 || "");
    pdf.setDrawColor(150, 150, 150);
    pdf.rect(bx, y, halfC, boxH);
    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(7.5);
    const mLines = pdf.splitTextToSize(val, halfC - 4);
    pdf.text(mLines, bx + 2, y + 5);
  });
  y += boxH + 8;

  // â”€â”€ Signatures â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Pin signatures to bottom of last content page
  const sigBottomPin = 232; // signatures start y-position for bottom alignment
  const _curPage = (pdf as any).internal.getCurrentPageInfo().pageNumber;
  if (y > sigBottomPin) {
    // Content too long: need a new page for signatures
    addHeaderFooter(pdf, _curPage, 4, sacyrLogo);
    pdf.addPage();
    y = bodyTop;
  } else {
    y = sigBottomPin; // push to bottom
  }

  const sigW = (contentW - 20) / 2;
  const sigX1 = M + 5;
  const sigX2 = M + contentW / 2 + 5;
  const sigLineY = y + 26;

  // Relator signature
  if (relatorSig) {
    try { pdf.addImage(relatorSig, "PNG", sigX1, y, sigW, 22); } catch { /* skip */ }
  }
  pdf.setDrawColor(0, 0, 0);
  pdf.line(sigX1, sigLineY, sigX1 + sigW, sigLineY);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(8);
  pdf.text(fixenc(input.relatorName || "Aplicada por"), sigX1 + sigW / 2, sigLineY + 6, { align: "center" });
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.text(fixenc(input.relatorRole || ""), sigX1 + sigW / 2, sigLineY + 12, { align: "center" });

  // Student signature
  if (studentSig) {
    try { pdf.addImage(studentSig, "PNG", sigX2, y, sigW, 22); } catch { /* skip */ }
  }
  pdf.line(sigX2, sigLineY, sigX2 + sigW, sigLineY);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(8);
  pdf.text(input.studentName, sigX2 + sigW / 2, sigLineY + 6, { align: "center" });
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.text("Nombre del Trabajador", sigX2 + sigW / 2, sigLineY + 12, { align: "center" });

  addHeaderFooter(pdf, (pdf as any).internal.getCurrentPageInfo().pageNumber, 0, sacyrLogo);

  // Post-render: now that we know the total page count, go back and update each header
  const totalPages = (pdf as any).internal.getNumberOfPages();
  for (let p = 1; p <= totalPages; p++) {
    pdf.setPage(p);
    // White rect to cover the old "Pág. X" text
    pdf.setFillColor(255, 255, 255);
    pdf.rect(210 - 40 + 1, 16, 36, 5, "F");
    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(5.5);
    pdf.setTextColor(0, 51, 102);
    pdf.text(`Pág. ${p} de ${totalPages}`, 210 - 38, 19);
    pdf.setTextColor(0, 0, 0);
  }
  // Return to last page before saving
  pdf.setPage(totalPages);

  const safeName = (input.studentName || "trabajador").replace(/[^a-zA-Z0-9_-]+/g, "_");
  pdf.save(`IRL_Sacyr_${input.form.cargo_name.replace(/\s+/g, "_")}_${safeName}.pdf`);
}

