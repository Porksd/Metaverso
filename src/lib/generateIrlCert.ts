import jsPDF from "jspdf";

export interface GenerateIrlCertInput {
  studentName: string;
  rut: string;
  age?: number | string | null;
  jobName: string;
  date?: string;
  signatureUrl?: string | null;
}

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

  const today = input.date || new Date().toLocaleDateString("es-CL");

  pdf.setFillColor(255, 255, 255);
  pdf.rect(0, 0, 210, 297, "F");

  pdf.setDrawColor(0, 0, 0);
  pdf.setLineWidth(0.8);
  pdf.roundedRect(12, 12, 186, 273, 4, 4);

  pdf.setTextColor(0, 0, 0);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(18);
  pdf.text("METAVERSO OTEC", pageW / 2, 30, { align: "center" });

  pdf.setTextColor(0, 0, 0);
  pdf.setFontSize(20);
  pdf.text("CERTIFICADO IRL", pageW / 2, 44, { align: "center" });

  pdf.setFontSize(12);
  pdf.setTextColor(0, 0, 0);
  pdf.text("INFORMAR LOS RIESGOS LABORALES", pageW / 2, 52, { align: "center" });

  pdf.setTextColor(0, 0, 0);
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(12);
  const paragraph = [
    `Se certifica que ${input.studentName}, RUT ${input.rut}${input.age ? `, edad ${input.age}` : ""},`,
    `desempenando el cargo ${input.jobName},`,
    "declaro estar en conocimiento de los riesgos que involucra la ejecucion de mi cargo."
  ];

  let y = 86;
  paragraph.forEach((line) => {
    pdf.text(line, pageW / 2, y, { align: "center" });
    y += 10;
  });

  pdf.setTextColor(0, 0, 0);
  pdf.setFontSize(11);
  pdf.text(`Fecha de emisi\u00f3n: ${today}`, pageW / 2, 150, { align: "center" });

  pdf.setDrawColor(0, 0, 0);
  pdf.line(65, 220, 145, 220);
  pdf.setTextColor(0, 0, 0);
  pdf.setFontSize(10);
  pdf.text("Firma del Alumno", pageW / 2, 226, { align: "center" });

  if (input.signatureUrl) {
    const signatureData = await urlToDataUrl(input.signatureUrl);
    if (signatureData) {
      try {
        pdf.addImage(signatureData, "PNG", 75, 188, 60, 26, undefined, "FAST");
      } catch {
        // Keep certificate valid even if signature image cannot be rendered.
      }
    }
  }

  const safeName = (input.studentName || "alumno").replace(/[^a-zA-Z0-9_-]+/g, "_");
  pdf.save(`certificado_irl_${safeName}.pdf`);
}
