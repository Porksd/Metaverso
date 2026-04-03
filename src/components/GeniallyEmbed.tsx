import React, { useState, useEffect } from 'react';

interface GeniallyEmbedProps {
    src: string;
    onInteract?: () => void;
}

export default function GeniallyEmbed({ src, onInteract }: GeniallyEmbedProps) {
    const [loaded, setLoaded] = useState(false);
    const [showButton, setShowButton] = useState(false);
    const [completed, setCompleted] = useState(false);
    const [interacted, setInteracted] = useState(false);

    useEffect(() => {
        const handleMessage = (event: MessageEvent) => {
            // Log de depuración detallado
            console.log('[Genially Debug] Mensaje:', event.data);

            try {
                let data = event.data;
                const rawString = typeof data === 'string' ? data : JSON.stringify(data);
                
                // 1. Detección por palabras clave (prioridad máxima)
                const completionKeywords = ['FIN', 'CERRAR', 'FINISHED', 'COMPLETED', 'END_SCENE', 'FINALIZAR', 'TERMINAR', 'EXIT', 'CLOSE', 'LAST_SLIDE'];
                if (completionKeywords.some(key => rawString.toUpperCase().includes(key))) {
                    console.log('✅ Genially: Detectada palabra clave de fin');
                    setShowButton(true);
                    return;
                }

                // 2. Detección de última slide por contador
                if (typeof data === 'string' && data.includes('slide')) {
                    const parts = data.split(':');
                    if (parts.length >= 3) {
                        const current = parseInt(parts[parts.length - 2]);
                        const total = parseInt(parts[parts.length - 1]);
                        if (!isNaN(current) && !isNaN(total) && current > 0 && current >= total) {
                            console.log('✅ Genially: Detectada última slide por contador');
                            setShowButton(true);
                        }
                    }
                }
            } catch (e) {}
        };

        // 3. SECCIÓN CRÍTICA: Detección de interacción por foco (Fallback infalible)
        const checkFocus = () => {
            if (document.activeElement instanceof HTMLIFrameElement) {
                // El usuario ha hecho clic dentro del Genially
                if (!interacted) {
                    setInteracted(true);
                    console.log('🖱️ Genially: Interacción detectada (clic en iframe)');
                }
            }
        };

        window.addEventListener('message', handleMessage);
        const focusInterval = setInterval(checkFocus, 1000);
        
        return () => {
            window.removeEventListener('message', handleMessage);
            clearInterval(focusInterval);
        };
    }, [interacted]);

    // Si hubo interacción y han pasado 15 segundos de uso, habilitar el botón como fallback
    useEffect(() => {
        if (interacted && !showButton && !completed) {
            const timer = setTimeout(() => {
                console.log('✅ Genially: Habilitando botón por interacción prolongada (fallback)');
                setShowButton(true);
            }, 15000); // 15 segundos después del primer clic
            return () => clearTimeout(timer);
        }
    }, [interacted, showButton, completed]);

    const handleComplete = () => {
        setCompleted(true);
        setShowButton(false);
        if (onInteract) onInteract();
    };
    
    return (
        <div className="w-full h-full relative flex flex-col bg-black">
            {!loaded && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/50 z-10">
                    <div className="w-8 h-8 border-2 border-brand border-t-transparent rounded-full animate-spin" />
                </div>
            )}
            
            <iframe
                src={src}
                className="w-full h-full border-0 flex-1"
                allowFullScreen
                onLoad={() => setLoaded(true)}
            />

            {showButton && !completed && (
                <div className="absolute bottom-10 inset-x-0 flex justify-center z-20 animate-in fade-in zoom-in duration-500">
                    <button
                        onClick={handleComplete}
                        className="flex items-center gap-2 px-5 py-2.5 bg-brand text-black font-black text-[11px] uppercase tracking-wider rounded-full shadow-[0_10px_30px_rgba(49,210,45,0.5)] hover:bg-white hover:scale-105 transition-all active:scale-95 border-2 border-brand"
                    >
                        <span>Finalizar actividad interactiva</span>
                    </button>
                </div>
            )}

            {completed && (
                <div className="absolute top-4 right-4 z-20 bg-brand/90 text-black px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 shadow-lg border border-black/10">
                    <div className="w-1.5 h-1.5 bg-black rounded-full animate-pulse" />
                    Actividad Completada
                </div>
            )}
        </div>
    );
}
