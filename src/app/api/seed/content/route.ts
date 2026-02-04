import { supabase } from "@/lib/supabase";
import * as xlsx from 'xlsx';
import path from 'path';

export async function POST(req: Request) {
    try {
        // En un caso real, esto vendría de un archivo subido o una ruta configurada
        // Por ahora, para el demo, vamos a insertar los contenidos "hardcoded" que conocemos del análisis
        // simulando que leímos el Excel.

        const courseId = "1a8b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p"; // ID del curso Trabajo en Altura

        const content = [
            { key: "tituloCurso", value: "Curso de Trabajo en Altura" },
            { key: "tituloSlide1", value: "Introducción al curso" },
            { key: "videoIntro_url", value: "/uploads/courses/ALTURA/media/intro.mp4" },
            { key: "texto2", value: "Reconoce la señalización y comprende su significado." },
            { key: "audio2_url", value: "/uploads/courses/ALTURA/media/slide2.mp3" },
            { key: "imagen2_url", value: "/uploads/courses/ALTURA/media/slide2.jpg" },
            { key: "tituloJuego", value: "Actividad Interactiva" },
            { key: "juegoUrl", value: "https://view.genial.ly/..." }, // Placeholder, user will replace
            { key: "tituloSlide4", value: "Medidas preventivas en obra" },
            { key: "videoSlide4_url", value: "/uploads/courses/ALTURA/media/slide4.mp4" },
            { key: "tituloJuegoSlide5", value: "Actividad Práctica" },
            { key: "juegoUrlSlide5", value: "https://view.genial.ly/..." },
            { key: "texto6", value: "Recuerda verificar el estado de los elementos de seguridad diariamente." },
            { key: "audio6_url", value: "/uploads/courses/ALTURA/media/slide6.mp3" },
            { key: "tituloQuiz", value: "Evaluación Final" },
            { key: "tituloFirma", value: "Firma Digital" }
        ];

        // Insertar contenido
        for (const item of content) {
            const { error } = await supabase
                .from('course_content')
                .upsert({
                    course_id: courseId,
                    key: item.key,
                    value: item.value,
                    updated_at: new Date().toISOString()
                }, { onConflict: 'course_id, key' });

            if (error) console.error("Error creating content:", error);
        }

        // Crear preguntas del quiz si no existen en config del curso
        // (Esto normalmente iría en course.config, pero course_content puede tener overrides)

        return new Response(JSON.stringify({ success: true, message: "Contenido inicial cargado" }), {
            headers: { "Content-Type": "application/json" },
        });
    } catch (error) {
        return new Response(JSON.stringify({ error: "Error seeding content" }), { status: 500 });
    }
}
