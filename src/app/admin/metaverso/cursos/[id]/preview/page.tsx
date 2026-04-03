"use client";

import { useParams, useRouter } from "next/navigation";
import { X } from "lucide-react";
import CoursePlayer from "@/components/CoursePlayer";

export default function CoursePreviewPage() {
    const params = useParams();
    const router = useRouter();
    const courseId = params.id as string;

    return (
        <div className="relative">
            {/* Close Preview Button */}
            <button
                onClick={() => router.back()}
                className="fixed top-4 right-4 z-[100] bg-black/80 backdrop-blur-md border border-white/10 text-white rounded-full p-2 hover:bg-white/20 transition-all group"
                title="Cerrar Vista Previa"
            >
                <X className="w-6 h-6 group-hover:scale-110 transition-transform" />
            </button>
            <div className="fixed top-4 right-16 z-[100] px-4 py-2 bg-black/80 backdrop-blur-md border border-white/10 rounded-full text-xs font-bold uppercase text-white/60 pointer-events-none">
                Vista Previa de Administrador
            </div>

            <CoursePlayer
                courseId={courseId}
                studentId="preview-admin"
                mode="preview"
                onComplete={() => alert("Curso finalizado en modo vista previa.")}
                className="min-h-screen"
            />
        </div>
    );
}
