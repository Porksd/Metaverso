import jsPDF from "jspdf";

/**
 * Convierte cualquier RUT almacenado (con o sin puntos/guión) al formato
 * chileno estándar: 12.345.678-9  o  12345678-K
 */
function formatRutCl(raw: string): string {
  const clean = raw.replace(/[^0-9kK]/g, "").toUpperCase();
  if (clean.length < 2) return raw;
  const body = clean.slice(0, -1);
  const dv = clean.slice(-1);
  const withDots = body.replace(/(\d)(?=(\d{3})+$)/g, "$1.");
  return `${withDots}-${dv}`;
}

export interface GenerateIrlCertInput {
  studentName: string;
  rut: string;
  age?: number | string | null;
  jobName: string;
  date?: string;
  companyName?: string | null;
  studentSignatureUrl?: string | null;
  relatorSignatureUrl?: string | null;
  relatorName?: string | null;
  relatorRole?: string | null;
  signatureUrl?: string | null;
}

// ─── Inline-bold paragraph renderer ──────────────────────────────────────────

type MixedSeg = { text: string; bold: boolean };

function _mW(pdf: jsPDF, text: string, bold: boolean, size: number): number {
  pdf.setFont("helvetica", bold ? "bold" : "normal");
  pdf.setFontSize(size);
  return (pdf.getStringUnitWidth(text) * size) / pdf.internal.scaleFactor;
}

/**
 * Renders a left-aligned paragraph with mixed bold/normal inline segments,
 * word-wrapped to maxW. Returns Y after the last rendered line.
 */
function drawMixedParagraph(
  pdf: jsPDF,
  segs: MixedSeg[],
  x: number,
  startY: number,
  maxW: number,
  fontSize: number,
  lineH: number
): number {
  const spW = _mW(pdf, " ", false, fontSize);

  // Tokenise all segments into words
  const words: { w: string; bold: boolean }[] = [];
  for (const seg of segs) {
    for (const token of seg.text.split(/\s+/)) {
      if (token) words.push({ w: token, bold: seg.bold });
    }
  }
  if (!words.length) return startY;

  // Greedy line-wrap (no space before punctuation to keep commas tight)
  const lines: { w: string; bold: boolean }[][] = [];
  let cur: { w: string; bold: boolean }[] = [];
  let usedW = 0;
  for (const word of words) {
    const wW = _mW(pdf, word.w, word.bold, fontSize);
    const startsPunct = /^[,.:;!?]/.test(word.w);
    const gap = cur.length && !startsPunct ? spW : 0;
    if (usedW + gap + wW > maxW + 0.01 && cur.length) {
      lines.push(cur); cur = [word]; usedW = wW;
    } else {
      usedW += gap + wW; cur.push(word);
    }
  }
  if (cur.length) lines.push(cur);

  // Draw each line
  let cy = startY;
  for (const line of lines) {
    let cx = x;
    for (let i = 0; i < line.length; i++) {
      const { w, bold } = line[i];
      const startsPunct = i > 0 && /^[,.:;!?]/.test(w);
      if (i > 0 && !startsPunct) { cx += spW; }
      pdf.setFont("helvetica", bold ? "bold" : "normal");
      pdf.setFontSize(fontSize);
      pdf.setTextColor(20, 20, 20);
      pdf.text(w, cx, cy);
      cx += _mW(pdf, w, bold, fontSize);
    }
    cy += lineH;
  }
  return cy;
}

// ──────────────────────────────────────────────────────────────────────────────

async function urlToDataUrl(url: string): Promise<string | null> {
  try {
    if (url.startsWith("data:image/")) return url;

    const fullUrl = url.startsWith("http") ? url : `${window.location.origin}${url}`;
    const res = await fetch(fullUrl);
    if (!res.ok) return null;
    const blob = await res.blob();

    const objectUrl = URL.createObjectURL(blob);
    const image = await new Promise<HTMLImageElement>((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error("No se pudo cargar la firma"));
      img.src = objectUrl;
    });

    const canvas = document.createElement("canvas");
    canvas.width = image.naturalWidth || image.width;
    canvas.height = image.naturalHeight || image.height;
    const ctx = canvas.getContext("2d");
    if (!ctx) return null;
    ctx.drawImage(image, 0, 0);

    URL.revokeObjectURL(objectUrl);
    return canvas.toDataURL("image/png");
  } catch {
    return null;
  }
}

export async function generateIrlCert(input: GenerateIrlCertInput): Promise<void> {
  const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
  const pageW = 210;
  const margin = 24;
  const contentW = pageW - margin * 2;

  const formatDateLongEs = (value?: string) => {
    const d = value ? new Date(value) : new Date();
    if (Number.isNaN(d.getTime())) return value || new Date().toLocaleDateString("es-CL");
    const months = [
      "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
      "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
    ];
    return `${String(d.getDate()).padStart(2, "0")} de ${months[d.getMonth()]} de ${d.getFullYear()}`;
  };

  const today = formatDateLongEs(input.date);
  const studentSignatureUrl = input.studentSignatureUrl ?? input.signatureUrl ?? null;
  const relatorSignatureUrl = input.relatorSignatureUrl ?? null;
  const relatorName = input.relatorName?.trim() || "Relator";
  const relatorRole = input.relatorRole?.trim() || "Relator";
  const companyName = input.companyName?.trim() || "la empresa";
  const formattedRut = input.rut ? formatRutCl(input.rut) : input.rut;

  pdf.setFillColor(255, 255, 255);
  pdf.rect(0, 0, 210, 297, "F");

  pdf.setDrawColor(100, 100, 100);
  pdf.setLineWidth(0.45);
  pdf.roundedRect(5, 6, 200, 285, 4, 4);

  pdf.setTextColor(20, 20, 20);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(26);
  pdf.text("IRL", pageW / 2, 26, { align: "center" });

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(14);
  pdf.text("Información de Riesgos Laborales", pageW / 2, 34, { align: "center" });

  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(13);
  pdf.text(companyName.toUpperCase(), pageW / 2, 42, { align: "center" });

  let y = 56;
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(9.5);
  const intro = "El Decreto Supremo Nº 44, en su párrafo 4, artículo Nº 15, dispone que:";  
  const introLines = pdf.splitTextToSize(intro, contentW);
  pdf.text(introLines, margin, y);
  y += introLines.length * 4.8 + 2;

  const quote = "\"La entidad empleadora deberá garantizar que cada persona trabajadora, previo al inicio de las labores, reciba de forma oportuna y adecuada información acerca de los riesgos que entrañan sus labores, de las medidas preventivas y los métodos o procedimientos de trabajo correctos, determinados conforme a la matriz de riesgos y el programa de trabajo preventivo\".";
  const quoteLines = pdf.splitTextToSize(quote, contentW - 8);
  pdf.setFont("helvetica", "italic");
  pdf.text(quoteLines, margin + 4, y);
  y += quoteLines.length * 4.7 + 4;

  const complianceSegs: MixedSeg[] = [
    { text: "En cumplimiento de lo dispuesto en la normativa vigente, el trabajador", bold: false },
    { text: input.studentName.toUpperCase(), bold: true },
    { text: ", RUT:", bold: false },
    { text: formattedRut, bold: true },
    { text: ", quien se desempeña en la empresa en el cargo de", bold: false },
    { text: input.jobName.toUpperCase(), bold: true },
    { text: ", declara haber recibido información suficiente, oportuna y adecuada respecto de los riesgos asociados a las labores que realiza, así como de las medidas preventivas, normas de higiene y seguridad, y procedimientos de trabajo seguro aplicables a sus funciones.", bold: false },
  ];
  pdf.setTextColor(20, 20, 20);
  y = drawMixedParagraph(pdf, complianceSegs, margin, y, contentW, 9.5, 5.0) + 4;

  const commitment = "Asimismo, declara comprender y comprometerse a cumplir las disposiciones de seguridad establecidas por la empresa, participando activamente en las actividades de capacitación y prevención de riesgos que se desarrollen. Del mismo modo, se compromete a:";
  const commitmentLines = pdf.splitTextToSize(commitment, contentW);
  pdf.text(commitmentLines, margin, y);
  y += commitmentLines.length * 5.0 + 3;

  const bullets = [
    "Utilizar correctamente los elementos de protección personal proporcionados.",
    "Operar únicamente equipos, herramientas o maquinarias para los cuales se encuentre debidamente autorizado y capacitado.",
    "Informar de manera inmediata a su jefatura directa cualquier accidente, incidente o condición insegura que le afecte a él o a otro trabajador.",
    "Cumplir los procedimientos de trabajo seguro y las normas internas de prevención de riesgos.",
    "Colaborar activamente en el mantenimiento de condiciones de trabajo seguras y saludables."
  ];

  pdf.setFontSize(9.5);
  for (const bullet of bullets) {
    const bulletLines = pdf.splitTextToSize(bullet, contentW - 8);
    pdf.text("•", margin + 6, y);
    pdf.text(bulletLines, margin + 11, y);
    y += bulletLines.length * 5.0 + 1.5;
  }

  const signStartY = 236;

  const relatorSignatureData = relatorSignatureUrl ? await urlToDataUrl(relatorSignatureUrl) : null;
  const studentSignatureData = studentSignatureUrl ? await urlToDataUrl(studentSignatureUrl) : null;

  const leftCenter = 58;
  const rightCenter = 152;
  const signatureTop = signStartY;
  const lineY = signStartY + 11;

  if (relatorSignatureData) {
    try {
      pdf.addImage(relatorSignatureData, "PNG", 32, signatureTop - 7, 52, 14, undefined, "FAST");
    } catch {
      // Keep certificate valid even if the image cannot be rendered.
    }
  }
  pdf.setDrawColor(35, 35, 35);
  pdf.line(30, lineY, 86, lineY);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(9.5);
  pdf.setTextColor(35, 35, 35);
  pdf.text(relatorName, leftCenter, lineY + 9, { align: "center" });
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(9.5);
  pdf.text(relatorRole, leftCenter, lineY + 15, { align: "center" });

  if (studentSignatureData) {
    try {
      pdf.addImage(studentSignatureData, "PNG", 126, signatureTop - 7, 52, 14, undefined, "FAST");
    } catch {
      // Keep certificate valid even if the image cannot be rendered.
    }
  }
  pdf.line(124, lineY, 180, lineY);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(9.5);
  pdf.text(input.studentName, rightCenter, lineY + 9, { align: "center" });
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(9.5);
  pdf.text(`RUT: ${formattedRut}`, rightCenter, lineY + 15, { align: "center" });

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(9.5);
  pdf.text(today, pageW / 2, 282, { align: "center" });

  const safeName = (input.studentName || "alumno").replace(/[^a-zA-Z0-9_-]+/g, "_");
  pdf.save(`certificado_irl_${safeName}.pdf`);
}
