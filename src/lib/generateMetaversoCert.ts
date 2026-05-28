/**
 * generateMetaversoCert.ts
 *
 * Generates the Metaverso certificate PDF using jsPDF.
 * Loads the configured background image and overlays all dynamic
 * text fields at the correct positions to match the reference design.
 */

import jsPDF from "jspdf";

// ─── Types ────────────────────────────────────────────────────────────────────

/** Per-block layout config stored in diploma_config.fields_config.layout */
export interface LayoutConfig {
  student_name_y: number;    student_name_size: number;
  rut_y: number;             rut_size: number;
  company_name_y: number;    company_name_size: number;
  company_rut_y: number;     company_rut_size: number;
  ha_realizado_y: number;    ha_realizado_size: number;
  course_name_y: number;     course_name_size: number;
  hours_gap: number;         hours_size: number;
  date_gap: number;          date_gap_no_hours: number;  date_size: number;
  course_code_gap: number;   course_code_size: number;
  expiration_date_gap: number; expiration_date_size: number;
}

export const DEFAULT_LAYOUT: LayoutConfig = {
  student_name_y: 112,   student_name_size: 17,
  rut_y: 121,            rut_size: 11,
  company_name_y: 138,   company_name_size: 11,
  company_rut_y: 145,    company_rut_size: 11,
  ha_realizado_y: 157,   ha_realizado_size: 11,
  course_name_y: 168,    course_name_size: 20,
  hours_gap: 16,         hours_size: 11,
  date_gap: 24,          date_gap_no_hours: 16, date_size: 11,
  course_code_gap: 9,    course_code_size: 11,
  expiration_date_gap: 10, expiration_date_size: 11,
};

export interface MetaversoCertData {
  studentName: string;
  rut: string;
  companyName: string;
  companyRut: string;
  courseName: string;
  courseCode?: string;
  hours?: string | number;
  date: string;
  expirationDate?: string;
  backgroundUrl?: string;
  layoutConfig?: Partial<LayoutConfig>;
  fieldsConfig?: {
    student_name?: boolean;
    rut?: boolean;
    company_name?: boolean;
    company_rut?: boolean;
    course_name?: boolean;
    hours?: boolean;
    date?: boolean;
    course_code?: boolean;
    expiration_date?: boolean;
  };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Fetch an image URL and return it as a base64 data string with type.
 * Works both for Supabase storage URLs (CORS-enabled) and local /public paths.
 */
async function urlToBase64(
  url: string
): Promise<{ data: string; type: "JPEG" | "PNG" } | null> {
  try {
    // For relative paths, prefix with window.location.origin
    const fullUrl =
      url.startsWith("http") ? url : `${window.location.origin}${url}`;
    const res = await fetch(fullUrl);
    if (!res.ok) return null;
    const blob = await res.blob();
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onloadend = () => {
        const result = reader.result as string;
        const b64 = result.split(",")[1];
        resolve({
          data: b64,
          type: blob.type.includes("png") ? "PNG" : "JPEG",
        });
      };
      reader.readAsDataURL(blob);
    });
  } catch {
    return null;
  }
}

/**
 * Returns the width of a text string in mm at a given font size.
 */
function strWidthMm(pdf: jsPDF, text: string, fontSize: number): number {
  pdf.setFontSize(fontSize);
  return (
    (pdf.getStringUnitWidth(text) * fontSize) / pdf.internal.scaleFactor
  );
}

/**
 * Draws a line of text composed of mixed-weight parts, horizontally centered.
 */
function drawMixedCentered(
  pdf: jsPDF,
  parts: Array<{
    text: string;
    bold: boolean;
    size: number;
    color?: [number, number, number];
  }>,
  y: number,
  pageWidth: number
) {
  // Calculate total width
  const totalWidth = parts.reduce((acc, p) => {
    pdf.setFont("helvetica", p.bold ? "bold" : "normal");
    return acc + strWidthMm(pdf, p.text, p.size);
  }, 0);

  let x = (pageWidth - totalWidth) / 2;
  for (const p of parts) {
    pdf.setFont("helvetica", p.bold ? "bold" : "normal");
    pdf.setFontSize(p.size);
    if (p.color) pdf.setTextColor(...p.color);
    else pdf.setTextColor(60, 60, 60);
    pdf.text(p.text, x, y);
    x += strWidthMm(pdf, p.text, p.size);
  }
}

function getMixedWidthMm(
  pdf: jsPDF,
  parts: Array<{ text: string; bold: boolean; size: number }>
): number {
  return parts.reduce((acc, p) => {
    pdf.setFont("helvetica", p.bold ? "bold" : "normal");
    return acc + strWidthMm(pdf, p.text, p.size);
  }, 0);
}

// ─── Main export ──────────────────────────────────────────────────────────────

/**
 * Generate and save a Metaverso certificate PDF.
 * The background image must already contain all static decorative elements
 * (logo, "CERTIFICADO DE APROBACIÓN", signature, seal, QR, CERTHIA logo, etc.).
 * This function only overlays the dynamic text fields.
 */
export async function generateMetaversoCert(
  data: MetaversoCertData
): Promise<void> {
  const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
  const W = 210;
  const cfg = data.fieldsConfig ?? {};
  const lc: LayoutConfig = { ...DEFAULT_LAYOUT, ...(data.layoutConfig ?? {}) };

  // ── 1. Background image ──────────────────────────────────────────────────
  const bgUrl =
    data.backgroundUrl && data.backgroundUrl.trim()
      ? data.backgroundUrl
      : "/cert-assets/metaverso-cert-bg.jpg";

  const bgImg = await urlToBase64(bgUrl);
  if (bgImg) {
    pdf.addImage(bgImg.data, bgImg.type, 0, 0, W, 297);
  }

  // ── 2. Student name (large bold, in the white box) ───────────────────────
  if (cfg.student_name !== false) {
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(lc.student_name_size);
    pdf.setTextColor(30, 30, 30);
    pdf.text(data.studentName.toUpperCase(), W / 2, lc.student_name_y, { align: "center" });
  }

  // ── 3. RUT ───────────────────────────────────────────────────────────────
  if (cfg.rut !== false) {
    drawMixedCentered(
      pdf,
      [
        { text: "RUT: ", bold: false, size: lc.rut_size },
        { text: data.rut, bold: true, size: lc.rut_size, color: [30, 30, 30] },
      ],
      lc.rut_y,
      W
    );
  }

  // ── 4. Company name ──────────────────────────────────────────────────────
  if (cfg.company_name !== false) {
    drawMixedCentered(
      pdf,
      [
        { text: "De la empresa ", bold: false, size: lc.company_name_size },
        {
          text: data.companyName.toUpperCase(),
          bold: true,
          size: lc.company_name_size,
          color: [30, 30, 30],
        },
      ],
      lc.company_name_y,
      W
    );
  }

  // ── 5. Company RUT ───────────────────────────────────────────────────────
  if (cfg.company_rut !== false && data.companyRut) {
    drawMixedCentered(
      pdf,
      [
        { text: "con RUT: ", bold: false, size: lc.company_rut_size },
        { text: data.companyRut, bold: true, size: lc.company_rut_size, color: [30, 30, 30] },
      ],
      lc.company_rut_y,
      W
    );
  }

  // ── 6. "Ha realizado y APROBADO el Curso de:" ────────────────────────────
  drawMixedCentered(
    pdf,
    [
      { text: "Ha realizado y ", bold: false, size: lc.ha_realizado_size },
      { text: "APROBADO", bold: true, size: lc.ha_realizado_size, color: [30, 30, 30] },
      { text: " el Curso de:", bold: false, size: lc.ha_realizado_size },
    ],
    lc.ha_realizado_y,
    W
  );

  // ── 7. Course name (large) ───────────────────────────────────────────────
  if (cfg.course_name !== false) {
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(lc.course_name_size);
    pdf.setTextColor(30, 30, 30);
    const lineH = lc.course_name_size * 0.45;
    const lines: string[] = pdf.splitTextToSize(data.courseName, 165);
    lines.forEach((line: string, i: number) => {
      pdf.text(line, W / 2, lc.course_name_y + i * lineH, { align: "center" });
    });
  }

  // Compute Y after course name to position hours/date below it
  pdf.setFontSize(lc.course_name_size);
  const courseLines: string[] = pdf.splitTextToSize(
    cfg.course_name !== false ? data.courseName : "",
    165
  );
  const lineH = lc.course_name_size * 0.45;
  const afterCourseY = lc.course_name_y + Math.max(0, courseLines.length - 1) * lineH;

  // ── 8. Hours ─────────────────────────────────────────────────────────────
  if (cfg.hours !== false && data.hours) {
    drawMixedCentered(
      pdf,
      [
        { text: "Con un total de ", bold: false, size: lc.hours_size },
        {
          text: `${data.hours} horas cronológicas`,
          bold: true,
          size: lc.hours_size,
          color: [30, 30, 30],
        },
      ],
      afterCourseY + lc.hours_gap,
      W
    );
  }

  // ── 9. Date ───────────────────────────────────────────────────────────────
  const dateY = afterCourseY + (cfg.hours !== false && data.hours ? lc.date_gap : lc.date_gap_no_hours);
  if (cfg.date !== false) {
    drawMixedCentered(
      pdf,
      [
        { text: "Fecha de realización: ", bold: false, size: lc.date_size },
        { text: data.date, bold: true, size: lc.date_size, color: [30, 30, 30] },
      ],
      dateY,
      W
    );
  }

  // ── 10. Course code ───────────────────────────────────────────────────────
  const hasCourseCode = cfg.course_code !== false && !!data.courseCode;
  if (hasCourseCode) {
    // Auto-fit for long course codes to keep a single line inside page width.
    let codeSize = lc.course_code_size;
    while (codeSize > 7) {
      const testParts = [
        { text: "Código del Curso: ", bold: true, size: codeSize },
        { text: data.courseCode || "", bold: false, size: codeSize },
      ];
      if (getMixedWidthMm(pdf, testParts) <= W - 20) break;
      codeSize -= 0.5;
    }

    drawMixedCentered(
      pdf,
      [
        { text: "Código del Curso: ", bold: true, size: codeSize, color: [30, 30, 30] },
        { text: data.courseCode || "", bold: false, size: codeSize, color: [60, 60, 60] },
      ],
      dateY + lc.course_code_gap,
      W
    );
  }

  // ── 11. Expiration date ───────────────────────────────────────────────────
  if (cfg.expiration_date !== false && data.expirationDate) {
    drawMixedCentered(
      pdf,
      [
        { text: "Fecha de expiración: ", bold: false, size: lc.expiration_date_size },
        { text: data.expirationDate, bold: true, size: lc.expiration_date_size, color: [30, 30, 30] },
      ],
      dateY + (hasCourseCode ? lc.course_code_gap : 0) + lc.expiration_date_gap,
      W
    );
  }

  // ── Save ──────────────────────────────────────────────────────────────────
  const safeName = data.studentName.replace(/[^a-zA-Z0-9ÁÉÍÓÚáéíóúÑñ ]/g, "").replace(/\s+/g, "_");
  const safeCourse = data.courseName.replace(/\s+/g, "_").substring(0, 25);
  pdf.save(`Certificado_${safeName}_${safeCourse}.pdf`);
}
