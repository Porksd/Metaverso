"use client";

import { useState } from "react";
import { Upload, FileVideo, FileAudio, FileBox, CheckCircle, Package } from "lucide-react";

interface ContentUploaderProps {
    courseId: string;
    sectionKey: string;
    label: string;
    accept: string;
    currentValue?: string;
    onUploadComplete: (url: string) => void;
    compact?: boolean;
}

export default function ContentUploader({
    courseId,
    sectionKey,
    label,
    accept,
    currentValue,
    onUploadComplete,
    compact = false
}: ContentUploaderProps) {
    const [uploading, setUploading] = useState(false);
    const [progress, setProgress] = useState(0);

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setUploading(true);
        setProgress(10); // Fake start progress

        const formData = new FormData();
        formData.append('file', file);
        formData.append('courseId', courseId);
        formData.append('sectionKey', sectionKey);

        try {
            const res = await fetch('/api/upload/course-content', {
                method: 'POST',
                body: formData
            });

            if (!res.ok) throw new Error('Upload failed');

            const data = await res.json();
            if (data.success) {
                setProgress(100);
                onUploadComplete(data.url);
            }
        } catch (error) {
            console.error(error);
            alert('Error al subir contenido');
        } finally {
            setUploading(false);
            setProgress(0);
        }
    };

    return (
        <div className="bg-white/5 border border-white/10 rounded-xl p-4">
            <div className="flex justify-between items-start mb-3">
                <label className="text-sm font-bold text-white/80 flex items-center gap-2">
                    {accept.includes('zip') ? <Package className="w-4 h-4 text-brand" /> :
                        accept.includes('video') ? <FileVideo className="w-4 h-4 text-blue-400" /> :
                            <FileBox className="w-4 h-4 text-white/60" />}
                    {label}
                </label>
                {currentValue && <CheckCircle className="w-4 h-4 text-green-500" />}
            </div>

            <div className="flex items-center gap-3">
                {currentValue && (
                    <div className="flex-1 overflow-hidden">
                        <p className="text-xs text-white/40 truncate bg-black/20 p-2 rounded border border-white/5">
                            {currentValue.split('/').pop()}
                        </p>
                    </div>
                )}

                <label className={`cursor-pointer bg-brand/10 hover:bg-brand/20 text-brand border border-brand/30 px-3 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 ${uploading ? 'opacity-50 cursor-not-allowed' : ''}`}>
                    <Upload className="w-3 h-3" />
                    {uploading ? 'Subiendo...' : (currentValue ? 'Cambiar' : 'Subir')}
                    <input
                        type="file"
                        accept={accept}
                        onChange={handleFileChange}
                        disabled={uploading}
                        className="hidden"
                    />
                </label>
            </div>

            {uploading && (
                <div className="w-full bg-white/10 h-1 mt-3 rounded-full overflow-hidden">
                    <div
                        className="bg-brand h-full transition-all duration-500"
                        style={{ width: `${progress > 10 ? progress : 30}%` }} // Fake progress visual
                    />
                </div>
            )}
        </div>
    );
}
