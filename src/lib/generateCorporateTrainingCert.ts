import jsPDF from "jspdf";
import { supabase } from "@/lib/supabase";

interface CorporateTrainingCertInput {
  companyId: string;
  companyName: string;
  companyRut?: string | null;
  issueDate?: Date;
}

interface ApprovedEnrollmentRow {
  completed_at?: string | null;
  best_score?: number | null;
  status?: string | null;
  students?: {
    first_name?: string | null;
    last_name?: string | null;
    rut?: string | null;
    company_roles?: { name?: string | null } | null;
  } | null;
  courses?: {
    name?: string | null;
  } | null;
}

interface CourseGroupRow {
  fullName: string;
  rut: string;
  role: string;
  completedDate: string;
}

const PAGE_W = 210;
const PAGE_H = 297;
const MARGIN_X = 10;
const CONTENT_W = PAGE_W - MARGIN_X * 2;

const BG_COVER = "/cert-assets/Certificado Capacitaciones-01.jpg";
const BG_INNER = "/cert-assets/Certificado Capacitaciones-02.jpg";
const SIGNATURE_FOOTER = "/cert-assets/FirmaMeta.png";
const SIGNATURE_RATIO = 367 / 570;
const SIGNATURE_W_MM = 98;
const SIGNATURE_H_MM = SIGNATURE_W_MM * SIGNATURE_RATIO;
const SIGNATURE_X_MM = (PAGE_W - SIGNATURE_W_MM) / 2;
const SIGNATURE_Y_MM = 297 - SIGNATURE_H_MM - 18;
const COURSE_TITLE_Y = 30;
const COURSE_LINE_Y = 33;
const COURSE_TABLE_HEADER_Y = 38;
const COURSE_TABLE_BODY_Y = COURSE_TABLE_HEADER_Y + 8;
const COURSE_TABLE_BOTTOM_Y = 270;

// Cover page text sits below the "CERTIFICADO" title baked into the background.
const COVER_DATE_Y = 70;
const COVER_EMPRESA_Y = 86;
const COVER_RUT_Y = 94;
const COVER_INTRO_Y = 108;
const COVER_FIRST_COURSE_TITLE_Y = 134;
const COVER_FIRST_COURSE_LINE_Y = 137;
const COVER_FIRST_COURSE_TABLE_HEADER_Y = 142;
const COVER_FIRST_COURSE_TABLE_BODY_Y = COVER_FIRST_COURSE_TABLE_HEADER_Y + 8;

async function urlToBase64(
  url: string
): Promise<{ data: string; type: "JPEG" | "PNG" } | null> {
  try {
    const fullUrl = url.startsWith("http") ? url : `${window.location.origin}${url}`;
    const res = await fetch(fullUrl);
    if (!res.ok) return null;
    const blob = await res.blob();

    return await new Promise((resolve) => {
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

function formatDateLongEs(date: Date) {
  return date.toLocaleDateString("es-CL", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

function formatDateShortEs(value?: string | null) {
  if (!value) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "-";
  return d.toLocaleDateString("es-CL");
}

function drawBackground(
  pdf: jsPDF,
  bg: { data: string; type: "JPEG" | "PNG" } | null
) {
  if (bg) {
    pdf.addImage(bg.data, bg.type, 0, 0, PAGE_W, PAGE_H);
  }
}

function drawJustifiedParagraph(
  pdf: jsPDF,
  text: string,
  x: number,
  y: number,
  width: number,
  lineHeight: number
) {
  const lines = pdf.splitTextToSize(text, width) as string[];

  lines.forEach((line, idx) => {
    const isLast = idx === lines.length - 1;
    const words = line.trim().split(/\s+/);

    if (isLast || words.length <= 1) {
      pdf.text(line, x, y + idx * lineHeight);
      return;
    }

    const wordsWidth = words.reduce((acc, word) => {
      return acc + pdf.getTextWidth(word);
    }, 0);

    const gaps = words.length - 1;
    const extraSpace = (width - wordsWidth) / gaps;

    let cx = x;
    words.forEach((word, i) => {
      pdf.text(word, cx, y + idx * lineHeight);
      cx += pdf.getTextWidth(word);
      if (i < gaps) cx += extraSpace;
    });
  });

  return y + lines.length * lineHeight;
}

function sanitizeFilenamePart(value: string) {
  return value
    .replace(/[^a-zA-Z0-9A-Za-z\u00C0-\u017F\s_-]/g, "")
    .trim()
    .replace(/\s+/g, "_")
    .slice(0, 40);
}

export async function generateCorporateTrainingCert(
  input: CorporateTrainingCertInput
): Promise<{ courseCount: number; studentCount: number }> {
  const issueDate = input.issueDate || new Date();
  const issueDateLabel = `Santiago, ${formatDateLongEs(issueDate)}`;
  const issueYear = issueDate.getFullYear();

  const [coverBg, innerBg, signatureImg] = await Promise.all([
    urlToBase64(BG_COVER),
    urlToBase64(BG_INNER),
    urlToBase64(SIGNATURE_FOOTER),
  ]);

  if (!coverBg) {
    throw new Error("Falta fondo oficial hoja 1 en /public/cert-assets/Certificado Capacitaciones-01.jpg");
  }

  if (!innerBg) {
    throw new Error("Falta fondo oficial hoja 2+ en /public/cert-assets/Certificado Capacitaciones-02.jpg");
  }

  if (!signatureImg) {
    throw new Error("Falta firma oficial con sello en /public/cert-assets/FirmaMeta.png");
  }

  const { data, error } = await supabase
    .from("enrollments")
    .select(`
      status,
      best_score,
      completed_at,
      students!inner (
        first_name,
        last_name,
        rut,
        company_roles(name),
        client_id
      ),
      courses!inner (
        name
      )
    `)
    .eq("students.client_id", input.companyId)
    .eq("status", "completed");

  if (error) throw error;

  const approved = ((data || []) as ApprovedEnrollmentRow[]).filter((row) => {
    if (row.status !== "completed") return false;
    if (row.best_score == null) return true;
    return Number(row.best_score) >= 70;
  });

  const groups = new Map<string, CourseGroupRow[]>();

  approved.forEach((row) => {
    const courseName = (row.courses?.name || "Curso sin nombre").trim();
    const fullName = `${row.students?.first_name || ""} ${row.students?.last_name || ""}`
      .trim()
      .replace(/\s+/g, " ");
    const role = row.students?.company_roles?.name?.trim() || "-";
    const rut = row.students?.rut?.trim() || "-";

    const item: CourseGroupRow = {
      fullName: fullName || "Sin nombre",
      rut,
      role,
      completedDate: formatDateShortEs(row.completed_at),
    };

    const arr = groups.get(courseName) || [];
    arr.push(item);
    groups.set(courseName, arr);
  });

  const sortedCourses = Array.from(groups.entries())
    .sort((a, b) => a[0].localeCompare(b[0], "es", { sensitivity: "base" }))
    .map(([courseName, rows]) => ({
      courseName,
      rows: rows.sort((a, b) => a.fullName.localeCompare(b.fullName, "es", { sensitivity: "base" })),
    }));

  const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });

  // Page 1: cover + intro content.
  drawBackground(pdf, coverBg);

  pdf.setFont("helvetica", "normal");
  pdf.setTextColor(60, 60, 60);
  pdf.setFontSize(11);
  pdf.text(issueDateLabel, PAGE_W - MARGIN_X, COVER_DATE_Y, { align: "right" });

  pdf.setFontSize(11.5);
  pdf.setFont("helvetica", "bold");
  pdf.text("EMPRESA:", MARGIN_X, COVER_EMPRESA_Y);
  pdf.setFont("helvetica", "normal");
  pdf.text(input.companyName || "-", 35, COVER_EMPRESA_Y);

  pdf.setFont("helvetica", "bold");
  pdf.text("RUT:", MARGIN_X, COVER_RUT_Y);
  pdf.setFont("helvetica", "normal");
  pdf.text(input.companyRut?.trim() || "-", 20, COVER_RUT_Y);

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(11.2);
  const intro = `MetaversOtec Spa, certifica que el (los) trabajador(es) indicado(s) a continuación, ha(n) participado en la(s) siguiente(s) actividad(es) de capacitación, durante el año ${issueYear}:`;
  drawJustifiedParagraph(pdf, intro, MARGIN_X, COVER_INTRO_Y, CONTENT_W, 6.2);

  const colX = {
    num: MARGIN_X,
    name: MARGIN_X + 12,
    rut: MARGIN_X + 80,
    role: MARGIN_X + 113,
    date: MARGIN_X + 163,
  };
  const colW = {
    num: 12,
    name: 68,
    rut: 33,
    role: 50,
    date: 27,
  };

  const drawCourseHeader = (courseName: string, titleY: number = COURSE_TITLE_Y, lineY: number = COURSE_LINE_Y) => {
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(13);
    pdf.setTextColor(38, 38, 38);

    pdf.text(`ACTIVIDAD DE CAPACITACION: ${courseName.toUpperCase()}`, MARGIN_X, titleY);

    pdf.setDrawColor(40, 180, 95);
    pdf.setLineWidth(0.6);
    pdf.line(MARGIN_X, lineY, PAGE_W - MARGIN_X, lineY);
  };

  const drawTableHeader = (y: number) => {
    pdf.setFillColor(242, 246, 243);
    pdf.rect(MARGIN_X, y, CONTENT_W, 8, "F");

    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(9.2);
    pdf.setTextColor(56, 56, 56);
    pdf.text("N°", colX.num + 2, y + 5.2);
    pdf.text("NOMBRE COMPLETO", colX.name + 1, y + 5.2);
    pdf.text("RUT", colX.rut + 1, y + 5.2);
    pdf.text("CARGO", colX.role + 1, y + 5.2);
    pdf.text("FECHA", colX.date + 1, y + 5.2);

    pdf.setDrawColor(210, 214, 211);
    pdf.setLineWidth(0.25);
    pdf.line(MARGIN_X, y + 8, PAGE_W - MARGIN_X, y + 8);
  };

  const addInnerPage = () => {
    pdf.addPage();
    drawBackground(pdf, innerBg);
  };

  const startCourseListPage = (courseName: string) => {
    // Hard reset to enforce each course list starts at the top of a new page.
    addInnerPage();
    drawCourseHeader(courseName);
    drawTableHeader(COURSE_TABLE_HEADER_Y);
    return COURSE_TABLE_BODY_Y;
  };

  // The first course list continues directly below the cover intro paragraph (same page).
  const startFirstCourseOnCoverPage = (courseName: string) => {
    drawCourseHeader(courseName, COVER_FIRST_COURSE_TITLE_Y, COVER_FIRST_COURSE_LINE_Y);
    drawTableHeader(COVER_FIRST_COURSE_TABLE_HEADER_Y);
    return COVER_FIRST_COURSE_TABLE_BODY_Y;
  };

  let totalStudents = 0;

  if (sortedCourses.length === 0) {
    addInnerPage();
    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(14);
    pdf.setTextColor(40, 40, 40);
    pdf.text("SIN REGISTROS DE CAPACITACIONES APROBADAS", MARGIN_X, 36);

    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(11);
    pdf.text("No existen alumnos aprobados para el periodo seleccionado.", MARGIN_X, 46);
  } else {
    sortedCourses.forEach((course, courseIdx) => {
      let y = courseIdx === 0
        ? startFirstCourseOnCoverPage(course.courseName)
        : startCourseListPage(course.courseName);

      course.rows.forEach((row, idx) => {
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(8.8);
        pdf.setTextColor(45, 45, 45);

        const nameLines = pdf.splitTextToSize(row.fullName || "-", colW.name - 2) as string[];
        const rutLines = pdf.splitTextToSize(row.rut || "-", colW.rut - 2) as string[];
        const roleLines = pdf.splitTextToSize(row.role || "-", colW.role - 2) as string[];
        const dateLines = pdf.splitTextToSize(row.completedDate || "-", colW.date - 2) as string[];

        const maxLines = Math.max(nameLines.length, rutLines.length, roleLines.length, dateLines.length, 1);
        const lineH = 4.3;
        const rowH = Math.max(8.5, maxLines * lineH + 1.5);

        if (y + rowH > COURSE_TABLE_BOTTOM_Y) {
          y = startCourseListPage(course.courseName);
        }

        if (idx % 2 === 1) {
          pdf.setFillColor(250, 251, 250);
          pdf.rect(MARGIN_X, y, CONTENT_W, rowH, "F");
        }

        pdf.text(String(idx + 1), colX.num + 2, y + 4.8);

        nameLines.forEach((line, li) => pdf.text(line, colX.name + 1, y + 4.8 + li * lineH));
        rutLines.forEach((line, li) => pdf.text(line, colX.rut + 1, y + 4.8 + li * lineH));
        roleLines.forEach((line, li) => pdf.text(line, colX.role + 1, y + 4.8 + li * lineH));
        dateLines.forEach((line, li) => pdf.text(line, colX.date + 1, y + 4.8 + li * lineH));

        pdf.setDrawColor(226, 230, 227);
        pdf.setLineWidth(0.2);
        pdf.line(MARGIN_X, y + rowH, PAGE_W - MARGIN_X, y + rowH);

        y += rowH;
        totalStudents += 1;
      });

      // Signature only on final course page.
      if (courseIdx === sortedCourses.length - 1) {
        const requiredY = 255;
        if (y > requiredY) {
          addInnerPage();
          y = 30;
        }

        pdf.addImage(
          signatureImg.data,
          signatureImg.type,
          SIGNATURE_X_MM,
          SIGNATURE_Y_MM,
          SIGNATURE_W_MM,
          SIGNATURE_H_MM
        );
      }
    });
  }

  const safeCompany = sanitizeFilenamePart(input.companyName || "Empresa");
  pdf.save(`Certificado_Capacitaciones_${safeCompany}.pdf`);

  return {
    courseCount: sortedCourses.length,
    studentCount: totalStudents,
  };
}
