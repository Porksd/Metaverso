"use client";

import { useEffect, useRef } from "react";

interface CertificateCanvasProps {
    studentName: string;
    rut: string;
    courseName: string;
    date: string;
    score?: number;
    signatures: { url: string; name: string; role: string }[];
    studentSignature?: string; 
    companyLogo?: string; 
    companyName?: string;
    jobPosition?: string;
    age?: string | number;
    gender?: string;
    onReady: (blob: Blob) => void;
}

// ── Helpers ──────────────────────────────────────────────
function loadImage(src: string): Promise<HTMLImageElement | null> {
    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = "anonymous";
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = src;
    });
}

function wrapText(ctx: CanvasRenderingContext2D, text: string, maxWidth: number): string[] {
    const words = text.split(" ");
    const lines: string[] = [];
    let current = "";
    for (const word of words) {
        const test = current ? `${current} ${word}` : word;
        if (ctx.measureText(test).width > maxWidth) {
            if (current) lines.push(current);
            current = word;
        } else {
            current = test;
        }
    }
    if (current) lines.push(current);
    return lines;
}

// ── Component ────────────────────────────────────────────
export default function CertificateCanvas({
    studentName,
    rut,
    courseName,
    date,
    score,
    signatures,
    studentSignature, 
    companyLogo,
    companyName,
    jobPosition,
    age,
    gender,
    onReady
}: CertificateCanvasProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        const W = canvas.width;   // 1414
        const H = canvas.height;  // 2000
        const CX = W / 2;
        const ML = 140;           // margen izquierdo
        const MR = W - 140;       // margen derecho
        const contentW = MR - ML; // ancho útil

        // ── Paleta ──
        const BLACK   = "#1A1A1A";
        const DARK    = "#333333";
        const MID     = "#555555";
        const LIGHT   = "#888888";
        const ACCENT  = "#C8A951"; // dorado corporativo
        const BG_DATA = "#F7F7F5";
        const LINE_LT = "#E0E0E0";

        const draw = async () => {
            // ── Fondo blanco ──
            ctx.fillStyle = "#FFFFFF";
            ctx.fillRect(0, 0, W, H);

            // ── Borde superior decorativo (línea dorada fina) ──
            ctx.fillStyle = ACCENT;
            ctx.fillRect(0, 0, W, 6);

            let Y = 60;

            // ── 1. Logo de empresa ──
            if (companyLogo) {
                const logoImg = await loadImage(companyLogo);
                if (logoImg) {
                    const maxLogoH = 120;
                    const maxLogoW = 400;
                    const ratio = Math.min(maxLogoW / logoImg.width, maxLogoH / logoImg.height);
                    const lw = logoImg.width * ratio;
                    const lh = logoImg.height * ratio;
                    ctx.drawImage(logoImg, CX - lw / 2, Y, lw, lh);
                    Y += lh + 30;
                } else {
                    Y += 140;
                }
            } else {
                Y += 80;
            }

            // ── 2. Línea decorativa superior ──
            ctx.save();
            const gradTop = ctx.createLinearGradient(ML + 100, 0, MR - 100, 0);
            gradTop.addColorStop(0, "transparent");
            gradTop.addColorStop(0.2, ACCENT);
            gradTop.addColorStop(0.8, ACCENT);
            gradTop.addColorStop(1, "transparent");
            ctx.strokeStyle = gradTop;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(ML + 100, Y);
            ctx.lineTo(MR - 100, Y);
            ctx.stroke();
            ctx.restore();
            Y += 50;

            // ── 3. Título ──
            ctx.textAlign = "center";
            ctx.fillStyle = BLACK;
            ctx.font = "600 54px 'Georgia', 'Times New Roman', serif";
            ctx.fillText("CERTIFICADO DE PARTICIPACIÓN", CX, Y);
            Y += 25;

            // Línea decorativa bajo título
            ctx.save();
            const gradBot = ctx.createLinearGradient(ML + 100, 0, MR - 100, 0);
            gradBot.addColorStop(0, "transparent");
            gradBot.addColorStop(0.2, ACCENT);
            gradBot.addColorStop(0.8, ACCENT);
            gradBot.addColorStop(1, "transparent");
            ctx.strokeStyle = gradBot;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(ML + 100, Y);
            ctx.lineTo(MR - 100, Y);
            ctx.stroke();
            ctx.restore();
            Y += 60;

            // ── 4. Párrafo introductorio ──
            ctx.textAlign = "left";
            ctx.font = "22px 'Georgia', serif";
            ctx.fillStyle = DARK;
            const cargoText = jobPosition || "";
            const introText = jobPosition
                ? `Se certifica que ${studentName}, de la empresa ${companyName || 'la empresa'}, con el cargo de ${cargoText}, ha completado satisfactoriamente el contenido del curso:`
                : `Se certifica que ${studentName}, de la empresa ${companyName || 'la empresa'}, ha completado satisfactoriamente el contenido del curso:`;
            const introLines = wrapText(ctx, introText, contentW);
            for (const line of introLines) {
                ctx.fillText(line, ML, Y);
                Y += 34;
            }
            Y += 25;

            // ── 5. Nombre del curso ──
            ctx.textAlign = "center";
            ctx.font = "bold 38px 'Georgia', serif";
            ctx.fillStyle = BLACK;
            const courseLines = wrapText(ctx, courseName, contentW - 60);
            for (const line of courseLines) {
                ctx.fillText(line, CX, Y);
                Y += 50;
            }
            Y += 30;

            // ── 6. Encabezado datos ──
            ctx.textAlign = "left";
            ctx.font = "italic 20px 'Georgia', serif";
            ctx.fillStyle = LIGHT;
            ctx.fillText("Los siguientes son los datos obtenidos en su participación:", ML, Y);
            Y += 35;

            // ── 7. Tabla de datos (solo campos con valor) ──
            const allRows: [string, string | undefined | null][] = [
                ["Nombre", studentName],
                ["RUT", rut],
                ["Empresa", companyName],
                ["Cargo", jobPosition],
                ["Puntaje obtenido", score != null ? `${score}%` : "100%"],
                ["Género", gender],
                ["Edad", age ? `${age} años` : null],
                ["Fecha de emisión", date],
            ];
            // Filter out rows with no data
            const rows = allRows.filter(([, v]) => v != null && v !== '') as [string, string][];
            const rowH = 44;
            const tableH = rows.length * rowH + 24;
            const labelColW = 280;

            // Fondo tabla
            ctx.fillStyle = BG_DATA;
            ctx.fillRect(ML, Y, contentW, tableH);

            // Borde izquierdo dorado decorativo
            ctx.fillStyle = ACCENT;
            ctx.fillRect(ML, Y, 4, tableH);

            Y += 28; // padding top
            for (let i = 0; i < rows.length; i++) {
                const [label, value] = rows[i];

                // línea separadora suave entre filas
                if (i > 0) {
                    ctx.beginPath();
                    ctx.moveTo(ML + 20, Y - 14);
                    ctx.lineTo(MR - 20, Y - 14);
                    ctx.strokeStyle = LINE_LT;
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                }

                ctx.font = "20px 'Arial', sans-serif";
                ctx.fillStyle = MID;
                ctx.fillText(label, ML + 24, Y);

                ctx.font = "bold 20px 'Arial', sans-serif";
                ctx.fillStyle = BLACK;
                ctx.fillText(value, ML + 24 + labelColW, Y);

                Y += rowH;
            }
            Y += 30; // padding bottom extra

            // ── 8. Consentimiento ──
            Y += 20;
            ctx.font = "bold 22px 'Arial', sans-serif";
            ctx.fillStyle = BLACK;
            ctx.fillText("CONSENTIMIENTO", ML, Y);
            Y += 35;

            ctx.font = "17px 'Arial', sans-serif";
            ctx.fillStyle = MID;
            const consentText = "Declaro que he sido informado/a de la finalidad y condiciones bajo las cuales se tratarán mis datos personales, y autorizo expresamente a MetaversOtec a utilizar mi RUT y demás información señalada para los fines descritos.";
            const consentLines = wrapText(ctx, consentText, contentW);
            for (const line of consentLines) {
                ctx.fillText(line, ML, Y);
                Y += 26;
            }
            Y += 30;

            // ── 9. Firma del alumno (centrada) ──
            if (studentSignature) {
                const stuSigImg = await loadImage(studentSignature);
                if (stuSigImg) {
                    const sigW = 280;
                    const sigH = 140;
                    ctx.drawImage(stuSigImg, CX - sigW / 2, Y, sigW, sigH);
                    Y += sigH + 8;

                    // Línea
                    ctx.beginPath();
                    ctx.moveTo(CX - 160, Y);
                    ctx.lineTo(CX + 160, Y);
                    ctx.strokeStyle = BLACK;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                    Y += 22;

                    // Nombre y cargo
                    ctx.textAlign = "center";
                    ctx.font = "bold 18px 'Arial', sans-serif";
                    ctx.fillStyle = BLACK;
                    ctx.fillText(studentName, CX, Y);
                    Y += 22;
                    ctx.font = "16px 'Arial', sans-serif";
                    ctx.fillStyle = LIGHT;
                    ctx.fillText(jobPosition || "", CX, Y);
                    Y += 18;

                    // Checkmark consentimiento
                    ctx.font = "italic 13px 'Arial', sans-serif";
                    ctx.fillStyle = LIGHT;
                    ctx.fillText("✓ Acepto el tratamiento de mis datos personales", CX, Y);
                    ctx.textAlign = "left";
                    Y += 50;
                }
            }

            // ── 10. Firmas empresa (abajo) ──
            const sigAreaTop = Math.max(Y + 30, H - 300);
            const validSigs = signatures.filter(s => s.url || s.name);
            if (validSigs.length > 0) {
                const spacing = contentW / validSigs.length;
                for (let i = 0; i < validSigs.length; i++) {
                    const sig = validSigs[i];
                    const xCenter = ML + spacing * i + spacing / 2;
                    let sY = sigAreaTop;

                    if (sig.url) {
                        const sigImg = await loadImage(sig.url);
                        if (sigImg) {
                            const sw = 200;
                            const sh = 90;
                            ctx.drawImage(sigImg, xCenter - sw / 2, sY, sw, sh);
                            sY += sh + 10;
                        } else {
                            sY += 100;
                        }
                    } else {
                        sY += 100;
                    }

                    // Línea
                    ctx.beginPath();
                    ctx.moveTo(xCenter - 120, sY);
                    ctx.lineTo(xCenter + 120, sY);
                    ctx.strokeStyle = LINE_LT;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                    sY += 24;

                    ctx.textAlign = "center";
                    ctx.font = "bold 18px 'Arial', sans-serif";
                    ctx.fillStyle = BLACK;
                    ctx.fillText(sig.name || "", xCenter, sY);
                    sY += 20;
                    ctx.font = "15px 'Arial', sans-serif";
                    ctx.fillStyle = LIGHT;
                    ctx.fillText(sig.role || "", xCenter, sY);
                }
            }

            // ── Borde inferior decorativo ──
            ctx.fillStyle = ACCENT;
            ctx.fillRect(0, H - 6, W, 6);

            // ── Generar blob ──
            canvas.toBlob((blob) => {
                if (blob) onReady(blob);
            }, "image/png");
        };

        draw().catch(console.error);
    }, [studentName, rut, courseName, date, score, signatures, studentSignature, companyLogo, companyName, jobPosition, age, gender, onReady]);

    return (
        <canvas
            ref={canvasRef}
            width={1414}
            height={2000}
            className="hidden"
        />
    );
}
