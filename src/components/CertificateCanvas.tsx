"use client";

import { useEffect, useRef } from "react";

interface CertificateCanvasProps {
    studentName: string;
    rut: string;
    courseName: string;
    date: string;
    signatures: { url: string; name: string; role: string }[];
    studentSignature?: string; 
    companyLogo?: string; 
    companyName?: string;
    jobPosition?: string;
    age?: string | number;
    gender?: string;
    onReady: (blob: Blob) => void;
}

export default function CertificateCanvas({
    studentName,
    rut,
    courseName,
    date,
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

        // Fonts
        const fontBold = "bold 60px 'Inter', sans-serif";
        const fontRegular = "40px 'Inter', sans-serif";
        const fontSmall = "30px 'Inter', sans-serif";
        const fontTitle = "bold 90px 'Inter', sans-serif";

        // Load images
        const bgImage = new Image();
        bgImage.src = "/certificate-bg-white.jpg"; // We need a clean background or generate one

        bgImage.onload = async () => {
            // Fondo blanco simple
            ctx.fillStyle = "#FFFFFF";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Sin bordes
            const marginLeft = 150;
            const startY = 200;
            let currentY = startY;
            
            // 0. LOGO DE LA EMPRESA (CENTRADO ARRIBA - MÁS GRANDE)
            if (companyLogo) {
                try {
                    const logoImg = new Image();
                    logoImg.crossOrigin = "anonymous";
                    logoImg.src = companyLogo;
                    await new Promise((resolve) => {
                        logoImg.onload = resolve;
                        logoImg.onerror = resolve;
                    });
                    
                    const logoWidth = 450;
                    const logoHeight = 180;
                    ctx.drawImage(logoImg, (canvas.width / 2) - (logoWidth / 2), 40, logoWidth, logoHeight);
                } catch (e) {
                    console.error("Error loading company logo", e);
                }
            }

            currentY = 260;

            // 1. TÍTULO: CERTIFICADO DE PARTICIPACIÓN (MÁS PEQUEÑO Y REFINADO)
            ctx.fillStyle = "#000000";
            ctx.font = "bold 50px 'Arial', sans-serif";
            ctx.textAlign = "center";
            ctx.fillText("CERTIFICADO DE PARTICIPACIÓN", canvas.width / 2, currentY);
            currentY += 30;
            
            // Línea decorativa bajo el título
            ctx.beginPath();
            ctx.moveTo(canvas.width / 2 - 400, currentY);
            ctx.lineTo(canvas.width / 2 + 400, currentY);
            ctx.strokeStyle = "#EEEEEE";
            ctx.lineWidth = 2;
            ctx.stroke();
            currentY += 100;

            // 2. Párrafo de certificación
            ctx.font = "24px 'Arial', sans-serif";
            ctx.textAlign = "left";
            ctx.fillStyle = "#333333";
            const text1 = `Se certifica que ${studentName}, de la empresa ${companyName || '[Empresa]'}, con el cargo de`;
            ctx.fillText(text1, marginLeft, currentY);
            currentY += 45;
            const text2 = `${jobPosition || '[Cargo]'}, ha completado el contenido del curso:`;
            ctx.fillText(text2, marginLeft, currentY);
            currentY += 60;
            
            ctx.font = "bold 34px 'Arial', sans-serif";
            ctx.fillStyle = "#000000";
            ctx.fillText(courseName, marginLeft, currentY);
            currentY += 100;

            // 3. "Los siguientes son los datos obtenidos..."
            ctx.font = "italic 22px 'Arial', sans-serif";
            ctx.fillStyle = "#666666";
            ctx.fillText("Los siguientes son los datos obtenidos en su participación:", marginLeft, currentY);
            currentY += 50;

            // 4. Datos tabulados (MÁS ORDENADOS CON FONDO TENUE)
            ctx.fillStyle = "#F9F9F9";
            ctx.fillRect(marginLeft - 20, currentY - 30, canvas.width - (marginLeft * 2) + 40, 360);
            
            ctx.font = "24px 'Arial', sans-serif";
            ctx.fillStyle = "#000000";
            const dataLeft = marginLeft + 40;
            const dataValueX = dataLeft + 250;
            
            const drawDataRow = (label: string, value: string) => {
                ctx.fillStyle = "#666666";
                ctx.font = "22px 'Arial', sans-serif";
                ctx.fillText(label, dataLeft, currentY);
                ctx.fillStyle = "#000000";
                ctx.font = "bold 22px 'Arial', sans-serif";
                ctx.fillText(`: ${value}`, dataValueX, currentY);
                currentY += 45;
            };

            drawDataRow("Nombre", studentName);
            drawDataRow("RUT", rut);
            drawDataRow("Cargo", jobPosition || 'No especificado');
            drawDataRow("% Obtenido", "100%");
            drawDataRow("Género", gender || 'No especificado');
            drawDataRow("Edad", age ? `${age} años` : 'No especificada');
            drawDataRow("Fecha de emisión", date);
            
            currentY += 80;

            // 5. CONSENTIMIENTO
            ctx.font = "bold 26px 'Arial', sans-serif";
            ctx.fillText("CONSENTIMIENTO", marginLeft, currentY);
            currentY += 45;

            // 6. Texto de consentimiento
            ctx.font = "18px 'Arial', sans-serif";
            ctx.fillStyle = "#444444";
            const consentLines = [
                "Declaro que he sido informado/a de la finalidad y condiciones bajo las cuales se tratarán mis datos",
                "personales, y autorizo expresamente a MetaversOtec a utilizar mi RUT y demás información señalada",
                "para los fines descritos."
            ];
            consentLines.forEach(line => {
                ctx.fillText(line, marginLeft, currentY);
                currentY += 30;
            });
            currentY += 60;

            // 7. Firma del alumno (CENTRAD)
            if (studentSignature) {
                try {
                    const stuSigImg = new Image();
                    stuSigImg.crossOrigin = "anonymous";
                    stuSigImg.src = studentSignature;
                    await new Promise((resolve) => {
                        stuSigImg.onload = resolve;
                        stuSigImg.onerror = resolve;
                    });

                    const sigWidth = 350;
                    const sigHeight = 175;
                    const centerX = canvas.width / 2;
                    
                    ctx.drawImage(stuSigImg, centerX - sigWidth/2, currentY, sigWidth, sigHeight);
                    currentY += sigHeight + 10;

                    // Línea de firma (centrada)
                    ctx.beginPath();
                    ctx.moveTo(centerX - 200, currentY);
                    ctx.lineTo(centerX + 200, currentY);
                    ctx.strokeStyle = "#000000";
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    currentY += 30;

                    // Etiqueta "Firma:" (centrada)
                    ctx.font = "bold 16px Arial";
                    ctx.fillStyle = "#000000";
                    ctx.textAlign = "center";
                    ctx.fillText("Firma:", centerX, currentY);
                    currentY += 35;
                    
                    // Confirmación de consentimiento (centrado)
                    ctx.font = "italic 14px Arial";
                    ctx.fillText("✓ Acepto el tratamiento de mis datos personales según lo indicado anteriormente", centerX, currentY);
                    currentY += 80; // Espacio reducido
                } catch (e) {
                    console.error("Error drawing student signature", e);
                }
            }

            // 9. Firmas de la empresa (abajo - POSICIÓN DINÁMICA)
            const signatureStartY = Math.max(currentY + 60, 1600);
            currentY = signatureStartY;
            
            const sigSpacing = canvas.width / (signatures.length + 1);
            
            for (let i = 0; i < signatures.length; i++) {
                const sig = signatures[i];
                if (!sig.name && !sig.url) continue;

                const xPos = sigSpacing * (i + 1);

                if (sig.url) {
                    try {
                        const img = new Image();
                        img.crossOrigin = "anonymous";
                        img.src = sig.url;
                        await new Promise((resolve) => {
                            img.onload = resolve;
                            img.onerror = resolve;
                        });

                        const w = 220;
                        const h = 100;
                        ctx.drawImage(img, xPos - w/2, currentY, w, h);
                    } catch (e) {
                        console.error("Error drawing signature", e);
                    }
                }

                // Línea y nombre
                const lineY = currentY + 120;
                ctx.beginPath();
                ctx.moveTo(xPos - 130, lineY);
                ctx.lineTo(xPos + 130, lineY);
                ctx.strokeStyle = "#DDDDDD";
                ctx.lineWidth = 2;
                ctx.stroke();

                ctx.font = "bold 20px 'Arial', sans-serif";
                ctx.textAlign = "center";
                ctx.fillStyle = "#000000";
                ctx.fillText(sig.name || "", xPos, lineY + 30);
                ctx.font = "16px 'Arial', sans-serif";
                ctx.fillStyle = "#666666";
                ctx.fillText(sig.role || "", xPos, lineY + 50);
            }

            // Generate Blob
            canvas.toBlob((blob) => {
                if (blob) onReady(blob);
            }, 'image/png');
        };

        // Fallback execution if no bg image checks
        // For now, we are creating content programmatically so we trigger it immediately 
        // if we don't strictly wait for bgImage.onload. 
        // But since we put logic inside onload, we need to ensure src is set.
        // If image is missing, it won't trigger. so we'll handle error on bgImage
        bgImage.onerror = () => {
            // If BG fails, just fill white and run logic
            const ctx = canvas.getContext("2d");
            if (ctx) {
                ctx.fillStyle = "#FFFFFF";
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                onReady(new Blob()); // Fail gracefully or run similar drawing logic without bg
            }
        };

        // Trigger load (using a base64 white pixel or actual path if exists)
        // Using a data URI for a white background to ensure onload fires immediately
        bgImage.src = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==";

    }, [studentName, rut, courseName, date, signatures, studentSignature, companyLogo, onReady]);

    return (
        <canvas
            ref={canvasRef}
            width={1414}
            height={2000}
            className="hidden" // Keep hidden, we only need the blob
        />
    );
}
