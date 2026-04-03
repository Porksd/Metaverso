import React, { useRef, useState, useEffect } from 'react';
import { Eraser, Save } from 'lucide-react';

interface SignatureCanvasProps {
    onSave: (signatureUrl: string) => void;
    isLight?: boolean;
}

export default function SignatureCanvas({ onSave, isLight }: SignatureCanvasProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [isDrawing, setIsDrawing] = useState(false);
    const [hasSignature, setHasSignature] = useState(false);
    const [consentAccepted, setConsentAccepted] = useState(false);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (canvas) {
            canvas.width = canvas.parentElement?.clientWidth || 500;
            canvas.height = 200;
            const ctx = canvas.getContext('2d');
            if (ctx) {
                // Fill with white background for certificate compatibility
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                ctx.lineWidth = 3;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#000000'; // Always black for visibility on certificates
            }
        }
    }, [isLight]);

    const getCoordinates = (e: React.MouseEvent | React.TouchEvent, canvas: HTMLCanvasElement) => {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;

        if ('touches' in e) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = (e as React.MouseEvent).clientX;
            clientY = (e as React.MouseEvent).clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    };

    const startDrawing = (e: React.MouseEvent | React.TouchEvent) => {
        e.preventDefault(); // Prevent scrolling on touch devices
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        setIsDrawing(true);
        const { x, y } = getCoordinates(e, canvas);
        ctx.beginPath();
        ctx.moveTo(x, y);
    };

    const draw = (e: React.MouseEvent | React.TouchEvent) => {
        e.preventDefault(); // Prevent scrolling on touch devices
        if (!isDrawing) return;
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const { x, y } = getCoordinates(e, canvas);
        ctx.lineTo(x, y);
        ctx.stroke();
        setHasSignature(true);
    };

    const stopDrawing = () => {
        setIsDrawing(false);
    };

    const clear = () => {
        const canvas = canvasRef.current;
        if (canvas) {
            const ctx = canvas.getContext('2d');
            if (ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                // Restore white background after clear
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
            setHasSignature(false);
        }
    };

    const save = () => {
        if (!hasSignature) {
            alert('Por favor, firma en el recuadro antes de guardar.');
            return;
        }
        if (!consentAccepted) {
            alert('Debes aceptar el tratamiento de datos personales para continuar.');
            return;
        }
        const canvas = canvasRef.current;
        if (canvas) {
            // Canvas already has white background from initialization, so PNG will be opaque
            const dataUrl = canvas.toDataURL("image/png");
            console.log("[SignatureCanvas] Signature saved in BLACK on WHITE background");
            console.log("[SignatureCanvas] Data length:", dataUrl.length, "chars");
            console.log("[SignatureCanvas] Consent accepted: YES");
            onSave(dataUrl);
        }
    };

    return (
        <div className="flex flex-col items-center gap-4">
            <div className="border border-white/20 rounded-xl overflow-hidden bg-white/5 relative" style={{ width: 500, height: 200 }}>
                <canvas
                    ref={canvasRef}
                    className="w-full h-full cursor-crosshair touch-none"
                    onMouseDown={startDrawing}
                    onMouseMove={draw}
                    onMouseUp={stopDrawing}
                    onMouseLeave={stopDrawing}
                    onTouchStart={startDrawing}
                    onTouchMove={draw}
                    onTouchEnd={stopDrawing}
                />
                {!hasSignature && (
                    <div className="absolute inset-0 pointer-events-none flex items-center justify-center text-white/20 text-xl font-bold uppercase select-none">
                        Firma Aquí
                    </div>
                )}
            </div>
            
            {/* Checkbox de Consentimiento */}
            <label className="flex items-start gap-3 cursor-pointer max-w-xl text-left group">
                <input 
                    type="checkbox" 
                    checked={consentAccepted}
                    onChange={(e) => setConsentAccepted(e.target.checked)}
                    className="mt-1 w-5 h-5 rounded border-2 border-white/20 bg-white/5 checked:bg-brand checked:border-brand focus:ring-2 focus:ring-brand/50 cursor-pointer transition-all"
                />
                <span className="text-sm text-white/70 group-hover:text-white/90 transition-colors leading-relaxed">
                    Acepto el tratamiento de mis datos personales para fines de <strong className="text-white">certificación y gestión académica</strong>.
                </span>
            </label>
            
            <div className="flex gap-4">
                <button onClick={clear} className="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/60 text-xs uppercase font-bold transition-colors">
                    <Eraser className="w-4 h-4" /> Borrar
                </button>
                <button
                    onClick={save}
                    disabled={!hasSignature || !consentAccepted}
                    className={`
                        flex items-center gap-2 px-6 py-2 rounded-lg text-xs uppercase font-bold shadow-lg transition-all
                        ${hasSignature && consentAccepted ? 'bg-brand text-black hover:bg-white hover:scale-105 shadow-brand/20' : 'bg-white/5 text-white/20 cursor-not-allowed'}
                    `}
                >
                    <Save className="w-4 h-4" /> Guardar Firma
                </button>
            </div>
        </div>
    );
}
