"use client";

import React, { useRef, useEffect, useState } from 'react';
import { 
    Bold, Italic, List, ListOrdered, Heading1, Heading2, Heading3, 
    Image as ImageIcon, Undo, Redo, Code, Eye, Upload
} from 'lucide-react';

type RichTextEditorProps = {
    content: string;
    onChange: (html: string) => void;
};

export default function RichTextEditor({ content, onChange }: RichTextEditorProps) {
    const editorRef = useRef<HTMLDivElement>(null);
    const [viewMode, setViewMode] = useState<'visual' | 'code'>('visual');
    const [htmlContent, setHtmlContent] = useState(content || '');

    // Sincronizar contenido cuando cambia la prop externa (ej: al editar o limpiar)
    useEffect(() => {
        if (editorRef.current && content !== editorRef.current.innerHTML) {
            editorRef.current.innerHTML = content || '<p><br></p>';
            setHtmlContent(content || '');
        }
    }, [content]);

    const execCommand = (command: string, value: string = '') => {
        document.execCommand(command, false, value);
        if (editorRef.current) {
            onChange(editorRef.current.innerHTML);
        }
    };

    const handleInput = () => {
        if (editorRef.current) {
            const newHtml = editorRef.current.innerHTML;
            setHtmlContent(newHtml);
            onChange(newHtml);
        }
    };

    const addImageFromURL = () => {
        const url = window.prompt('🖼️ Ingresa la URL de la imagen:');
        if (url) {
            execCommand('insertHTML', `<img src="${url}" style="max-width: 100%; height: auto; border-radius: 12px; margin: 20px auto; display: block; box-shadow: 0 4px 20px rgba(0,0,0,0.3);" />`);
        }
    };

    const addImageFromPC = () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = async (e: any) => {
            const file = e.target?.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (event) => {
                const base64 = event.target?.result as string;
                execCommand('insertHTML', `<img src="${base64}" style="max-width: 100%; height: auto; border-radius: 12px; margin: 20px auto; display: block; box-shadow: 0 4px 20px rgba(0,0,0,0.3);" />`);
            };
            reader.readAsDataURL(file);
        };
        input.click();
    };

    return (
        <div className="border border-white/10 rounded-xl overflow-hidden bg-black/20" onClick={(e) => e.stopPropagation()}>
            {/* Toolbar */}
            <div className="flex flex-wrap gap-1 p-2 border-b border-white/10 bg-white/5 sticky top-0 z-10">
                <button
                    type="button"
                    onClick={() => execCommand('bold')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Negrita"
                >
                    <Bold className="w-4 h-4" />
                </button>
                <button
                    type="button"
                    onClick={() => execCommand('italic')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Cursiva"
                >
                    <Italic className="w-4 h-4" />
                </button>
                <div className="w-px h-4 bg-white/10 mx-1 self-center" />
                <button
                    type="button"
                    onClick={() => execCommand('formatBlock', '<h1>')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Título 1"
                >
                    <Heading1 className="w-4 h-4" />
                </button>
                <button
                    type="button"
                    onClick={() => execCommand('formatBlock', '<h2>')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Título 2"
                >
                    <Heading2 className="w-4 h-4" />
                </button>
                <button
                    type="button"
                    onClick={() => execCommand('formatBlock', '<h3>')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Título 3"
                >
                    <Heading3 className="w-4 h-4" />
                </button>
                <div className="w-px h-4 bg-white/10 mx-1 self-center" />
                <button
                    type="button"
                    onClick={() => execCommand('insertUnorderedList')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Lista"
                >
                    <List className="w-4 h-4" />
                </button>
                <button
                    type="button"
                    onClick={() => execCommand('insertOrderedList')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Lista Numerada"
                >
                    <ListOrdered className="w-4 h-4" />
                </button>
                <div className="w-px h-4 bg-white/10 mx-1 self-center" />
                <button
                    type="button"
                    onClick={addImageFromURL}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Imagen desde URL"
                >
                    <ImageIcon className="w-4 h-4" />
                </button>
                <button
                    type="button"
                    onClick={addImageFromPC}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60 hover:text-brand"
                    title="Subir Imagen desde PC"
                >
                    <Upload className="w-4 h-4" />
                </button>
                <div className="flex-1" />
                <button
                    type="button"
                    onClick={() => setViewMode(viewMode === 'visual' ? 'code' : 'visual')}
                    className={`p-1.5 rounded hover:bg-white/10 ${viewMode === 'code' ? 'text-brand bg-brand/10' : 'text-white/60'}`}
                    title="Ver Código HTML"
                >
                    {viewMode === 'visual' ? <Code className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
                <div className="w-px h-4 bg-white/10 mx-1 self-center" />
                <button
                    type="button"
                    onClick={() => execCommand('undo')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60"
                    title="Deshacer"
                >
                    <Undo className="w-4 h-4" />
                </button>
                <button
                    type="button"
                    onClick={() => execCommand('redo')}
                    className="p-1.5 rounded hover:bg-white/10 text-white/60"
                    title="Rehacer"
                >
                    <Redo className="w-4 h-4" />
                </button>
            </div>

            {/* Editor Area */}
            {viewMode === 'visual' ? (
                <div
                    ref={editorRef}
                    contentEditable
                    onInput={handleInput}
                    className="prose prose-invert prose-brand max-w-none p-6 min-h-[250px] outline-none bg-black/10 text-white"
                    data-placeholder="Comienza a escribir..."
                />
            ) : (
                <textarea
                    value={htmlContent}
                    onChange={(e) => {
                        setHtmlContent(e.target.value);
                        onChange(e.target.value);
                        if (editorRef.current) {
                            editorRef.current.innerHTML = e.target.value;
                        }
                    }}
                    className="w-full h-[250px] bg-zinc-950 text-brand font-mono text-xs p-6 outline-none border-none resize-none"
                    spellCheck={false}
                />
            )}

            <style jsx global>{`
                [contenteditable]:empty:before {
                    content: attr(placeholder);
                    color: rgba(255,255,255,0.2);
                    cursor: text;
                }
            `}</style>
        </div>
    );
}
