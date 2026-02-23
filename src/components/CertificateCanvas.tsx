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

            let Y = 80;

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
                    Y += lh + 40;
                } else {
                    Y += 160;
                }
            } else {
                Y += 100;
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
            Y += 70;

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
            Y += 80;

            // ── 4. Párrafo introductorio ──
            ctx.textAlign = "left";
            ctx.font = "24px 'Georgia', serif";
            ctx.fillStyle = DARK;
            const cargoText = jobPosition || "";
            const introText = jobPosition
                ? `Se certifica que ${studentName}, de la empresa ${companyName || 'la empresa'}, con el cargo de ${cargoText}, ha completado satisfactoriamente el contenido del curso:`
                : `Se certifica que ${studentName}, de la empresa ${companyName || 'la empresa'}, ha completado satisfactoriamente el contenido del curso:`;
            const introLines = wrapText(ctx, introText, contentW);
            for (const line of introLines) {
                ctx.fillText(line, ML, Y);
                Y += 40;
            }
            Y += 40;

            // ── 5. Nombre del curso ──
            ctx.textAlign = "center";
            ctx.font = "bold 42px 'Georgia', serif";
            ctx.fillStyle = BLACK;
            const courseLines = wrapText(ctx, courseName, contentW - 60);
            for (const line of courseLines) {
                ctx.fillText(line, CX, Y);
                Y += 55;
            }
            Y += 60;

            // ── 6. Encabezado datos ──
            ctx.textAlign = "left";
            ctx.font = "italic 20px 'Georgia', serif";
            ctx.fillStyle = LIGHT;
            ctx.fillText("Los siguientes son los datos obtenidos en su participación:", ML, Y);
            Y += 40;

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
            const rowH = 48;
            const tableH = rows.length * rowH + 24;
            const labelColW = 280;

            // Fondo tabla
            ctx.fillStyle = BG_DATA;
            ctx.fillRect(ML, Y, contentW, tableH);

            // Borde izquierdo dorado decorativo
            ctx.fillStyle = ACCENT;
            ctx.fillRect(ML, Y, 4, tableH);

            Y += rowH - 15; // padding top
            for (let i = 0; i < rows.length; i++) {
                const [label, value] = rows[i];

                // NO dibujamos líneas separadoras horizontales para evitar superposición visual si Y no es exacto
                /*
                if (i > 0) {
                    ctx.beginPath(); ...
                }
                */

                ctx.font = "20px 'Arial', sans-serif";
                ctx.fillStyle = MID;
                ctx.fillText(label, ML + 24, Y);

                ctx.font = "bold 20px 'Arial', sans-serif";
                ctx.fillStyle = BLACK;
                ctx.fillText(value, ML + 24 + labelColW, Y);

                Y += rowH;
            }
            Y += 60; // Separación tras tabla

            // ── 8. Consentimiento ──
            ctx.font = "bold 22px 'Arial', sans-serif";
            ctx.fillStyle = BLACK;
            ctx.fillText("CONSENTIMIENTO", ML, Y);
            Y += 40;

            ctx.font = "17px 'Arial', sans-serif";
            ctx.fillStyle = MID;
            const consentText = "Declaro que he sido informado/a de la finalidad y condiciones bajo las cuales se tratarán mis datos personales, y autorizo expresamente a MetaversOtec a utilizar mi RUT y demás información señalada para los fines descritos.";
            const consentLines = wrapText(ctx, consentText, contentW);
            for (const line of consentLines) {
                ctx.fillText(line, ML, Y);
                Y += 30;
            }
            Y += 80; // Espacio mayor antes de la firma del alumno para que no quede pegada

            // ── 9. Firma del alumno (CENTRADDA Y SUBIDA) ──
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
                    Y += 100; // Espacio notable antes de las firmas de la empresa
                }
            }

            // ── 10. Firmas empresa (abajo) ──
            const sigAreaTop = H - 280; // Posición fija abajo para las firmas de la empresa
            const validSigs = signatures.filter(s => s.url || s.name);
            if (validSigs.length > 0) {
                const spacing = contentW / validSigs.length;
                for (let i = 0; i < validSigs.length; i++) {
                    const sig = validSigs[i];
                    const sigX = ML + (spacing * i) + (spacing / 2);
                    let sigY = sigAreaTop;

                    if (sig.url) {
                        const sigImg = await loadImage(sig.url);
                        if (sigImg) {
                            const sw = 220;
                            const sh = 100;
                            ctx.drawImage(sigImg, sigX - sw / 2, sigY - sh - 10, sw, sh);
                        }
                    }

                    // Línea firma empresa
                    ctx.beginPath();
                    ctx.moveTo(sigX - 100, sigY);
                    ctx.lineTo(sigX + 100, sigY);
                    ctx.strokeStyle = BLACK;
                    ctx.lineWidth = 1;
                    ctx.stroke();

                    // Nombre y rol
                    ctx.textAlign = "center";
                    ctx.fillStyle = BLACK;
                    ctx.font = "bold 17px 'Arial', sans-serif";
                    ctx.fillText(sig.name, sigX, sigY + 25);
                    ctx.fillStyle = LIGHT;
                    ctx.font = "14px 'Arial', sans-serif";
                    ctx.fillText(sig.role, sigX, sigY + 45);
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
