import jsPDF from "jspdf";
import type { SacyrIrlFormData } from "./sacyrIrlData";

export interface SacyrIrlPdfInput {
  form: SacyrIrlFormData;
  studentName: string;
  studentRut: string;
  jobName?: string;
  companyName?: string;
  fecha?: string;
  motivo: "nueva_incorporacion" | "cambio_proceso" | "nuevas_actividades";
  respuestas_parte1: Record<number, number>;      // {q_index: selected_option}
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
const HEADER_TITLE = "INFORMACIÓN Y FORMACIÓN PREVENTIVA - INFORMAR LOS RIESGOS LABORALES (IRL)";
const FOOTER_LINE1 = "DIRECCIÓN DE SEGURIDAD Y SALUD";
const FOOTER_LINE2 = "La Dirección de Seguridad y Salud del Grupo Sacyr no garantiza que la copia impresa de este documento sea la Edición vigente.";
const SYSTEM_LABEL = "SISTEMA DE GESTIÓN DE SEGURIDAD Y SALUD";

const MOTIVO_LABELS: Record<string, string> = {
  nueva_incorporacion: "Nueva incorporación de Persona Trabajadora",
  cambio_proceso: "Cambios en el proceso de trabajo o puesto de trabajo",
  nuevas_actividades: "Nuevas actividades",
};

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

  // ── Header box ──────────────────────────────────────────────────────────
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
  pdf.text(`Pág. ${pageNum} de ${totalPages}`, W - 38, 19);

  // ── Footer ──────────────────────────────────────────────────────────────
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
  pdf.text(text, 13, y + 4.5);
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
  pdf.text(label, 12, y + rowH - 2.5);
  pdf.setFont("helvetica", "normal");
  pdf.setTextColor(0, 0, 0);
  const maxW = lineW - labelW - 4;
  const wrapped = pdf.splitTextToSize(value, maxW);
  pdf.text(wrapped[0] || "", 12 + labelW, y + rowH - 2.5);
  return y + rowH;
}

function bulletList(pdf: jsPDF, items: string[], x: number, y: number, maxW: number): number {
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.setTextColor(20, 20, 20);
  for (const item of items) {
    const lines = pdf.splitTextToSize(`• ${item}`, maxW - 4);
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
    pdf.text("✓", x + 0.5, y + 0.3);
  }
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.text(label, x + 5.5, y);
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

  // ── PAGE 1 ───────────────────────────────────────────────────────────────
  let y = bodyTop;

  // ── Info header table ──────────────────────────────────────────────────
  const halfW = contentW / 2;
  const infoRows1: [string, string][] = [
    ["Centro de Trabajo:", "Hospital Provincia Cordillera"],
    ["Empresa:", input.companyName || "Sacyr"],
    ["Fecha:", fecha],
  ];
  const infoRows2: [string, string][] = [
    ["País:", "Chile"],
    ["Línea de Negocio:", "Infraestructura"],
    ["Duración de IRL:", "8 horas"],
  ];

  infoRows1.forEach(([label, value], i) => {
    tableRow(pdf, label, value, y + i * 7, 40, 7, halfW);
  });
  infoRows2.forEach(([label, value], i) => {
    tableRow(pdf, label, value, y + i * 7, 40, 7, contentW);
    // Adjust x for right column
  });

  // Draw right column
  y = bodyTop;
  for (let i = 0; i < infoRows2.length; i++) {
    const [label, value] = infoRows2[i];
    pdf.setDrawColor(180, 180, 180);
    pdf.setLineWidth(0.2);
    pdf.rect(M + halfW, y + i * 7, 40, 7);
    pdf.rect(M + halfW + 40, y + i * 7, halfW - 40, 7);
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(7.5);
    pdf.text(label, M + halfW + 2, y + i * 7 + 4.5);
    pdf.setFont("helvetica", "normal");
    pdf.text(value, M + halfW + 42, y + i * 7 + 4.5);
  }

  y += 24;

  // ── Worker info ────────────────────────────────────────────────────────
  const workerRows: [string, string][] = [
    ["Nombres y Apellidos:", input.studentName],
    ["RUT:", input.studentRut],
    ["Área o sección de trabajo:", input.form.area],
    ["Cargo o puesto de Trabajo:", input.form.cargo_name],
  ];
  for (const [label, value] of workerRows) {
    y = tableRow(pdf, label, value, y, 55, 7, contentW);
  }
  y += 3;

  // ── Job description ────────────────────────────────────────────────────
  y = sectionTitle(pdf, "Descripción breve del puesto de trabajo", y, W);
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.setTextColor(20, 20, 20);
  const descLines = pdf.splitTextToSize(input.form.descripcion_puesto, contentW - 4);
  pdf.text(descLines, M + 2, y + 5);
  y += descLines.length * 4.5 + 8;

  // ── Tasks ──────────────────────────────────────────────────────────────
  if (input.form.tareas.length > 0) {
    y = sectionTitle(pdf, "Tareas que realizan", y, W);
    y += 3;
    y = bulletList(pdf, input.form.tareas, M, y, contentW);
    y += 4;
  }

  // ── Work locations ─────────────────────────────────────────────────────
  if (input.form.lugares_trabajo.length > 0) {
    y = sectionTitle(pdf, "Lugares de Trabajo", y, W);
    y += 3;
    y = bulletList(pdf, input.form.lugares_trabajo, M, y, contentW);
    y += 4;
  }

  // ── Tools ──────────────────────────────────────────────────────────────
  if (input.form.herramientas.length > 0) {
    y = sectionTitle(pdf, "Herramientas y Equipos", y, W);
    y += 3;
    y = bulletList(pdf, input.form.herramientas, M, y, contentW);
    y += 4;
  }

  // ── Order/cleanliness ──────────────────────────────────────────────────
  if (input.form.orden_aseo.length > 0) {
    y = sectionTitle(pdf, "Condiciones de orden y aseo exigidas en el puesto de trabajo", y, W);
    y += 3;
    y = bulletList(pdf, input.form.orden_aseo, M, y, contentW);
    y += 4;
  }

  // ── IRL Reason ────────────────────────────────────────────────────────
  // Check if we need a new page
  if (y > 230) {
    addHeaderFooter(pdf, 1, 2, sacyrLogo);
    pdf.addPage();
    y = bodyTop;
  }

  y = sectionTitle(pdf, "Motivo de información de riesgos laborales", y, W);
  y += 6;
  const motivos = [
    { key: "nueva_incorporacion", label: "Nueva incorporación de Persona Trabajadora." },
    { key: "cambio_proceso", label: "Cambios en el proceso de trabajo o puesto de trabajo." },
    { key: "nuevas_actividades", label: "Nuevas actividades." },
  ];
  for (const m of motivos) {
    checkbox(pdf, M + 4, y, input.motivo === m.key, m.label);
    y += 7;
  }
  y += 3;

  // ── Legal declaration ──────────────────────────────────────────────────
  const legalText = `En cumplimiento a lo dispuesto en el Decreto N° 44, título II, párrafo 4, artículo 15 en "INFORMAR LOS RIESGOS LABORALES (IRL)". Por tanto, el abajo firmante; declara conocer los riesgos que conllevan las labores que ejecuta, las medidas preventivas que debe respetar y cumplir de manera inmediata, ejecutando sus labores por medio de métodos de trabajos correctos y seguros. Por lo tanto, el abajo firmante; se compromete a que cuando se presenten condiciones de riesgo en los lugares de trabajo, deberá informarlos de manera inmediata y oportuna a su jefatura directa y/o personal de SST y/o Comité Paritario de Higiene y Seguridad, con la finalidad que estas condiciones sean analizadas y se establezcan los métodos y medidas de control que deberá adoptar para ejecutar en forma segura sus labores.`;
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7);
  const legalLines = pdf.splitTextToSize(legalText, contentW - 4);
  pdf.text(legalLines, M + 2, y);
  y += legalLines.length * 4 + 6;

  addHeaderFooter(pdf, 1, 2, sacyrLogo);

  // ── PAGE 2 ────────────────────────────────────────────────────────────
  pdf.addPage();
  y = bodyTop;

  // ── Part 1: Multiple-choice questions ─────────────────────────────────
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
      addHeaderFooter(pdf, 2, 2, sacyrLogo);
      pdf.addPage();
      y = bodyTop;
    }
  }

  y += 4;

  // ── Part 2: Workshop ──────────────────────────────────────────────────
  if (y > 200) {
    addHeaderFooter(pdf, 2, 2, sacyrLogo);
    pdf.addPage();
    y = bodyTop;
  }

  y = sectionTitle(pdf, "Segunda Parte: Taller de Aplicación", y, W);
  y += 4;

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(7.5);
  pdf.text(
    "Según la Matriz IPERO identifique y describa 5 posibles riesgos y sus medidas de control a los cuales se encuentra expuesto en sus labores:",
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

  // ── Image analysis section ─────────────────────────────────────────────
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

  // ── Signatures ────────────────────────────────────────────────────────
  if (y > 245) { y = 245; }

  const sigW = (contentW - 20) / 2;
  const sigX1 = M + 5;
  const sigX2 = M + contentW / 2 + 5;
  const sigLineY = y + 22;

  // Relator signature
  if (relatorSig) {
    try { pdf.addImage(relatorSig, "PNG", sigX1, y, sigW, 18); } catch { /* skip */ }
  }
  pdf.setDrawColor(0, 0, 0);
  pdf.line(sigX1, sigLineY, sigX1 + sigW, sigLineY);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(7.5);
  pdf.text(input.relatorName || "Aplicada por", sigX1 + sigW / 2, sigLineY + 5, { align: "center" });
  pdf.setFont("helvetica", "normal");
  pdf.text(input.relatorRole || "", sigX1 + sigW / 2, sigLineY + 10, { align: "center" });

  // Student signature
  if (studentSig) {
    try { pdf.addImage(studentSig, "PNG", sigX2, y, sigW, 18); } catch { /* skip */ }
  }
  pdf.line(sigX2, sigLineY, sigX2 + sigW, sigLineY);
  pdf.setFont("helvetica", "bold");
  pdf.text(input.studentName, sigX2 + sigW / 2, sigLineY + 5, { align: "center" });
  pdf.setFont("helvetica", "normal");
  pdf.text(`Nombre del Trabajador`, sigX2 + sigW / 2, sigLineY + 10, { align: "center" });

  addHeaderFooter(pdf, 2, 2, sacyrLogo);

  const safeName = (input.studentName || "trabajador").replace(/[^a-zA-Z0-9_-]+/g, "_");
  pdf.save(`IRL_Sacyr_${input.form.cargo_name.replace(/\s+/g, "_")}_${safeName}.pdf`);
}
