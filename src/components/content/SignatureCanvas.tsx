"use client";

import { useRef, useEffect, useState } from "react";
import { Trash2, Check } from "lucide-react";

interface SignatureCanvasProps {
    onSign: (dataUrl: string) => void;
}

export default function SignatureCanvas({ onSign }: SignatureCanvasProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [isDrawing, setIsDrawing] = useState(false);
    const [isEmpty, setIsEmpty] = useState(true);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        // Configuración inicial
        ctx.strokeStyle = "#000000";
        ctx.lineWidth = 2;
        ctx.lineJoin = "round";
        ctx.lineCap = "round";

        // Fondo blanco para asegurar transparencia correcta al exportar o guardar
        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }, []);

    const startDrawing = (e: React.MouseEvent | React.TouchEvent) => {
        setIsDrawing(true);
        draw(e);
    };

    const stopDrawing = () => {
        setIsDrawing(false);
        const canvas = canvasRef.current;
        if (canvas) {
            ctx = canvas.getContext("2d"); // Ensure context is available
            if (ctx) ctx.beginPath(); // Reset path

            // Check if actually empty (vs just white) - simplified check
            setIsEmpty(false);
            onSign(canvas.toDataURL("image/png"));
        }
    };

    // Fix context scope issues
    let ctx: CanvasRenderingContext2D | null = null;

    const draw = (e: React.MouseEvent | React.TouchEvent) => {
        if (!isDrawing) return;
        const canvas = canvasRef.current;
        if (!canvas) return;

        if (!ctx) ctx = canvas.getContext("2d");
        if (!ctx) return;

        let clientX, clientY;
        if ('touches' in e) {
            const touch = e.touches[0];
            clientX = touch.clientX;
            clientY = touch.clientY;
        } else {
            clientX = (e as React.MouseEvent).clientX;
            clientY = (e as React.MouseEvent).clientY;
        }

        const rect = canvas.getBoundingClientRect();
        const x = clientX - rect.left;
        const y = clientY - rect.top;

        ctx.lineTo(x, y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(x, y);
    };

    const clear = () => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.beginPath();
        setIsEmpty(true);
        onSign(""); // Clear signature in parent
    };

    return (
        <div className="flex flex-col gap-4 items-center">
            <div className="relative border-2 border-white/20 rounded-2xl overflow-hidden bg-white shadow-xl touch-none">
                <canvas
                    ref={canvasRef}
                    width={600}
                    height={300}
                    className="cursor-crosshair w-full h-full max-w-[600px] max-h-[300px]"
                    onMouseDown={startDrawing}
                    onMouseUp={stopDrawing}
                    onMouseMove={draw}
                    onMouseLeave={stopDrawing}
                    onTouchStart={startDrawing}
                    onTouchEnd={stopDrawing}
                    onTouchMove={draw}
                />
                <div className="absolute top-4 left-4 text-black/20 text-sm font-bold uppercase pointer-events-none select-none">
                    Espacio para firmar
                </div>
            </div>

            <div className="flex gap-4">
                <button
                    onClick={clear}
                    className="flex items-center gap-2 px-6 py-3 rounded-xl border border-white/10 text-white/60 hover:bg-white/5 hover:text-white transition-all font-bold uppercase text-sm"
                >
                    <Trash2 className="w-4 h-4" />
                    Borrar
                </button>
            </div>
        </div>
    );
}
