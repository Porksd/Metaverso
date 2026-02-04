"use client";

import { useEffect, useRef } from "react";

interface CertificateCanvasProps {
    studentName: string;
    rut: string;
    courseName: string;
    date: string;
    signatures: { url: string; name: string; role: string }[];
    studentSignature?: string; // New prop for student signature
    companyLogo?: string; // New prop for company logo
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
    studentSignature, // Should be passed from parent
    companyLogo,
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
            
            // 0. LOGO DE LA EMPRESA (CENTRADO ARRIBA)
            if (companyLogo) {
                try {
                    const logoImg = new Image();
                    logoImg.crossOrigin = "anonymous";
                    logoImg.src = companyLogo;
                    await new Promise((resolve) => {
                        logoImg.onload = resolve;
                        logoImg.onerror = resolve;
                    });
                    
                    const logoWidth = 250;
                    const logoHeight = 120;
                    ctx.drawImage(logoImg, (canvas.width / 2) - (logoWidth / 2), 50, logoWidth, logoHeight);
                } catch (e) {
                    console.error("Error loading company logo", e);
                }
            }

            // 1. TÍTULO: CERTIFICADO DE PARTICIPACIÓN
            ctx.fillStyle = "#000000";
            ctx.font = "bold 70px 'Arial', sans-serif";
            ctx.textAlign = "center";
            ctx.fillText("CERTIFICADO DE PARTICIPACIÓN", canvas.width / 2, currentY);
            currentY += 100;

            // 2. Párrafo de certificación
            ctx.font = "28px 'Arial', sans-serif";
            ctx.textAlign = "left";
            const text1 = `Se certifica que a ${studentName}, de la empresa [Empresa], con el cargo de`;
            ctx.fillText(text1, marginLeft, currentY);
            currentY += 50;
            const text2 = `[Cargo], ha completado el contenido ${courseName}.`;
            ctx.fillText(text2, marginLeft, currentY);
            currentY += 80;

            // 3. "Los siguientes son los datos obtenidos..."
            ctx.fillText("Los siguientes son los datos obtenidos en su participación:", marginLeft, currentY);
            currentY += 60;

            // 4. Datos tabulados
            ctx.font = "24px 'Arial', sans-serif";
            const dataLeft = marginLeft + 50;
            ctx.fillText(`Nombre          : ${studentName}`, dataLeft, currentY);
            currentY += 45;
            ctx.fillText(`RUT             : ${rut}`, dataLeft, currentY);
            currentY += 45;
            ctx.fillText(`% Obtenido      : 100%`, dataLeft, currentY);
            currentY += 45;
            ctx.fillText(`Género          : ${gender || ''}`, dataLeft, currentY);
            currentY += 45;
            ctx.fillText(`Edad            : ${age || ''}`, dataLeft, currentY);
            currentY += 45;
            ctx.fillText(`Fecha de emisión : ${date}`, dataLeft, currentY);
            currentY += 80;

            // 5. CONSENTIMIENTO
            ctx.font = "bold 30px 'Arial', sans-serif";
            ctx.fillText("CONSENTIMIENTO", marginLeft, currentY);
            currentY += 50;

            // 6. Texto de consentimiento
            ctx.font = "22px 'Arial', sans-serif";
            const consentText1 = "Declaro que he sido informado/a de la finalidad y condiciones bajo las cuales se tratarán mis datos";
            ctx.fillText(consentText1, marginLeft, currentY);
            currentY += 35;
            const consentText2 = "personales, y autorizo expresamente a MetaversOtec a utilizar mi RUT y demás información señalada";
            ctx.fillText(consentText2, marginLeft, currentY);
            currentY += 35;
            const consentText3 = "para los fines descritos.";
            ctx.fillText(consentText3, marginLeft, currentY);
            currentY += 80;

            // 7. Firma del alumno (CENTRADA)
            if (studentSignature) {
                try {
                    const stuSigImg = new Image();
                    stuSigImg.crossOrigin = "anonymous";
                    stuSigImg.src = studentSignature;
                    await new Promise((resolve) => {
                        stuSigImg.onload = resolve;
                        stuSigImg.onerror = resolve;
                    });

                    const sigWidth = 300;
                    const sigHeight = 150;
                    const centerX = canvas.width / 2;
                    
                    // Dibujar firma centrada
                    ctx.drawImage(stuSigImg, centerX - sigWidth/2, currentY, sigWidth, sigHeight);
                    currentY += sigHeight + 20;

                    // Línea de firma (centrada)
                    ctx.beginPath();
                    ctx.moveTo(centerX - 175, currentY);
                    ctx.lineTo(centerX + 175, currentY);
                    ctx.strokeStyle = "#000000";
                    ctx.lineWidth = 2;
                    ctx.stroke();
                    currentY += 35;

                    // Etiqueta "Firma:" (centrada)
                    ctx.font = "bold 16px Arial";
                    ctx.fillStyle = "#000000";
                    ctx.textAlign = "center";
                    ctx.fillText("Firma:", centerX, currentY);
                    currentY += 40;
                    
                    // Confirmación de consentimiento (centrado)
                    ctx.font = "18px Arial";
                    const checkMark = "✓";
                    ctx.fillText(checkMark, centerX - 120, currentY);
                    ctx.font = "16px Arial";
                    ctx.fillText("Acepto el tratamiento de datos personales", centerX - 90, currentY);
                    currentY += 80;
                } catch (e) {
                    console.error("Error drawing signature", e);
                }
            }

            // 9. Firmas de la empresa (abajo)
            currentY = 1600;
            const sigSpacing = canvas.width / (signatures.length + 1);
            
            for (let i = 0; i < signatures.length; i++) {
                const sig = signatures[i];
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

                        const w = 250;
                        const h = 120;
                        ctx.drawImage(img, xPos - w/2, currentY, w, h);
                    } catch (e) {
                        console.error("Error drawing signature", e);
                    }
                }

                // Línea y nombre
                const lineY = currentY + 140;
                ctx.beginPath();
                ctx.moveTo(xPos - 120, lineY);
                ctx.lineTo(xPos + 120, lineY);
                ctx.strokeStyle = "#000000";
                ctx.lineWidth = 2;
                ctx.stroke();

                ctx.font = "22px 'Arial', sans-serif";
                ctx.textAlign = "center";
                ctx.fillText(sig.name, xPos, lineY + 30);
                ctx.font = "18px 'Arial', sans-serif";
                ctx.fillText(sig.role, xPos, lineY + 55);
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
