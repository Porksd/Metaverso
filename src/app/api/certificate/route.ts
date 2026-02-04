import { NextRequest, NextResponse } from "next/server";
import jsPDF from "jspdf";
import fs from 'fs';
import path from 'path';

export async function GET(req: NextRequest) {
    const { searchParams } = new URL(req.url);
    const fullName = searchParams.get("name") || "Participante";
    const rut = searchParams.get("rut") || "-";
    const course = searchParams.get("course") || "Capacitación";
    const score = searchParams.get("score") || "100";
    const date = searchParams.get("date") || new Date().toLocaleDateString('es-CL');
    const company = searchParams.get("company") || "SACYR";

    // Cargar imágenes base64 para el PDF
    const loadImg = (name: string) => {
        try {
            const filePath = path.join(process.cwd(), 'public', 'cert-assets', name);
            const buffer = fs.readFileSync(filePath);
            return `data:image/png;base64,${buffer.toString('base64')}`;
        } catch (e) { return null; }
    };

    const logoSacyr = loadImg('logo_sacyr.png');
    const logoMetaCert = loadImg('logo-footer-cert.png');
    const firma1 = loadImg('firma1.png');
    const firma2 = loadImg('firma2.png');
    const firma3 = loadImg('firma3.png');

    const doc = new jsPDF({
        orientation: "portrait",
        unit: "mm",
        format: "a4",
    });

    // Margen decorativo sutil
    doc.setDrawColor(49, 210, 45); // Verde
    doc.setLineWidth(0.5);
    doc.rect(5, 5, 200, 287);

    doc.setDrawColor(0, 242, 255); // Cyan
    doc.setLineWidth(0.1);
    doc.rect(7, 7, 196, 283);

    // Logo Empresa Superior Central
    if (logoSacyr) doc.addImage(logoSacyr, 'PNG', 75, 20, 60, 20);

    // Título
    doc.setTextColor(20, 40, 80);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(28);
    doc.text("CERTIFICADO DE PARTICIPACIÓN", 105, 60, { align: "center" });

    // Cuerpo de texto refinado
    doc.setTextColor(50, 50, 50);
    doc.setFontSize(14);
    doc.setFont("helvetica", "normal");
    doc.text("Se otorga el presente reconocimiento oficial a:", 105, 80, { align: "center" });

    // Nombre del Alumno (Destacado)
    doc.setTextColor(49, 210, 45);
    doc.setFontSize(32);
    doc.setFont("helvetica", "bold");
    doc.text(fullName.toUpperCase(), 105, 100, { align: "center" });

    // RUT y Empresa
    doc.setTextColor(100, 100, 100);
    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.text(`RUT: ${rut}   |   Empresa: ${company}`, 105, 110, { align: "center" });

    // Detalle del curso
    doc.setTextColor(50, 50, 50);
    doc.setFontSize(14);
    doc.text("Por haber completado satisfactoriamente el contenido técnico de:", 105, 130, { align: "center" });

    doc.setTextColor(0, 150, 200);
    doc.setFontSize(22);
    doc.setFont("helvetica", "bold");
    doc.text(course.toUpperCase(), 105, 145, { align: "center" });

    // Tabla informativa
    doc.setDrawColor(200, 200, 200);
    doc.line(30, 160, 180, 160);

    doc.setTextColor(80, 80, 80);
    doc.setFontSize(10);
    doc.text(`Puntaje obtenido: ${score}%`, 35, 170);
    doc.text(`Fecha de emisión: ${date}`, 105, 170, { align: "center" });
    doc.text(`Código Verificación: ${Math.random().toString(36).substr(2, 9).toUpperCase()}`, 175, 170, { align: "right" });

    // Consentimiento
    doc.setFontSize(7);
    doc.setTextColor(160, 160, 160);
    const consent = "CONSENTIMIENTO: Declaro haber sido informado/a de la finalidad y condiciones bajo las cuales se tratarán mis datos personales, y autorizo expresamente a MetaversoOtec a utilizar mi RUT y demás información señalada para fines de certificación y gestión académica.";
    doc.text(doc.splitTextToSize(consent, 150), 105, 185, { align: "center" });

    // --- FIRMAS ---
    // Firma 1: Centrada
    if (firma1) {
        doc.addImage(firma1, 'PNG', 85, 210, 40, 20);
        doc.setFontSize(9);
        doc.setTextColor(50, 50, 50);
        doc.text("Firma Autorizada", 105, 235, { align: "center" });
        doc.line(85, 232, 125, 232);
    }

    // Firmas 2 y 3: Abajo, misma altura
    const ySignatures = 250;
    if (firma2) {
        doc.addImage(firma2, 'PNG', 40, ySignatures, 40, 20);
        doc.text("Líder Dpto. SST", 60, ySignatures + 25, { align: "center" });
        doc.line(40, ySignatures + 22, 80, ySignatures + 22);
    }

    if (firma3) {
        doc.addImage(firma3, 'PNG', 130, ySignatures, 40, 20);
        doc.text("Gerente General", 150, ySignatures + 25, { align: "center" });
        doc.line(130, ySignatures + 22, 170, ySignatures + 22);
    }

    // Footer / Logo Metaverso Certificado (NUEVO: Más pequeño y discreto)
    if (logoMetaCert) {
        doc.addImage(logoMetaCert, 'PNG', 92, 282, 26, 6);
    }

    const pdfOutput = doc.output("arraybuffer");
    return new NextResponse(pdfOutput, {
        headers: {
            "Content-Type": "application/pdf",
            "Content-Disposition": `attachment; filename=Certificado_${rut}.pdf`,
        },
    });
}
