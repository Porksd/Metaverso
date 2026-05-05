import React, { useState, useEffect } from 'react';

interface GeniallyEmbedProps {
    src: string;
    onInteract?: () => void;
    hideNativeControls?: boolean;
}

function normalizeGeniallyUrl(rawSrc: string): string | null {
    const raw = (rawSrc || '').trim();
    if (!raw) return null;

    const iframeSrcMatch = raw.match(/src=["']([^"']+)["']/i);
    const candidate = (iframeSrcMatch?.[1] || raw).replace(/&amp;/g, '&').trim();

    if (
        candidate.startsWith('/') ||
        candidate.startsWith('./') ||
        candidate.startsWith('../') ||
        candidate.startsWith('data:')
    ) {
        return candidate;
    }

    const withProtocol = /^[a-zA-Z][a-zA-Z\d+\-.]*:\/\//.test(candidate)
        ? candidate
        : candidate.startsWith('//')
            ? `https:${candidate}`
            : `https://${candidate}`;

    try {
        const url = new URL(withProtocol);
        const host = url.hostname.toLowerCase();
        const isGeniallyHost =
            host === 'genially.com' ||
            host.endsWith('.genially.com') ||
            host === 'genial.ly' ||
            host.endsWith('.genial.ly');

        if (isGeniallyHost) {
            const idMatch = url.pathname.match(/[0-9a-f]{24}/i);
            if (idMatch?.[0]) {
                url.hostname = 'view.genially.com';
                url.pathname = `/${idMatch[0]}`;
            }
        }

        url.protocol = 'https:';
        return url.toString();
    } catch {
        return null;
    }
}

export default function GeniallyEmbed({ src, onInteract, hideNativeControls = true }: GeniallyEmbedProps) {
    const [loaded, setLoaded] = useState(false);
    const [completed, setCompleted] = useState(false);
    const [interacted, setInteracted] = useState(false);
    const completedRef = React.useRef(false);
    const normalizedSrc = normalizeGeniallyUrl(src);

    useEffect(() => {
        setLoaded(false);
        setCompleted(false);
        setInteracted(false);
        completedRef.current = false;
    }, [normalizedSrc]);

    const handleComplete = React.useCallback(() => {
        if (completedRef.current) return;
        completedRef.current = true;
        setCompleted(true);
        if (onInteract) onInteract();
    }, [onInteract]);

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
                    console.log('✅ Genially: Detectada palabra clave de fin - auto-completando');
                    handleComplete();
                    return;
                }

                // 2. Detección de última slide por contador
                if (typeof data === 'string' && data.includes('slide')) {
                    const parts = data.split(':');
                    if (parts.length >= 3) {
                        const current = parseInt(parts[parts.length - 2]);
                        const total = parseInt(parts[parts.length - 1]);
                        if (!isNaN(current) && !isNaN(total) && current > 0 && current >= total) {
                            console.log('✅ Genially: Detectada última slide - auto-completando');
                            handleComplete();
                        }
                    }
                }
            } catch {}
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
    }, [interacted, handleComplete, completed]);

    // Si hubo interacción y han pasado 15 segundos, auto-completar
    useEffect(() => {
        if (interacted && !completed) {
            const timer = setTimeout(() => {
                console.log('✅ Genially: Auto-completando por interacción prolongada (15s fallback)');
                handleComplete();
            }, 15000);
            return () => clearTimeout(timer);
        }
    }, [interacted, completed, handleComplete]);
    
    return (
        <div className="w-full h-full relative flex flex-col bg-black">
            {!normalizedSrc && (
                <div className="absolute inset-0 z-30 flex items-center justify-center bg-black/85 p-4">
                    <div className="max-w-md rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-center text-red-100">
                        <p className="text-sm font-bold uppercase tracking-wide">No se pudo cargar Genially</p>
                        <p className="mt-2 text-xs text-red-200/90">La URL es invalida o no corresponde a un enlace publico de Genially.</p>
                    </div>
                </div>
            )}

            {!loaded && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/50 z-10">
                    <div className="w-8 h-8 border-2 border-brand border-t-transparent rounded-full animate-spin" />
                </div>
            )}
            
            <iframe
                src={normalizedSrc || ''}
                className="w-full h-full border-0 flex-1"
                allowFullScreen
                onLoad={() => setLoaded(true)}
            />

            {hideNativeControls && (
                <div className="absolute bottom-0 right-0 h-11 w-28 max-[430px]:w-24 sm:h-12 sm:w-40 [@media(max-height:430px)]:h-10 [@media(max-height:430px)]:w-32 bg-black z-20 pointer-events-none" />
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
