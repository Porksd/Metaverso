/**
 * Script para corregir la estructura del curso y agregar módulo de evaluación
 */

const { createClient } = require('@supabase/supabase-js');
const path = require('path');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

const supabase = createClient(supabaseUrl, supabaseKey);

const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function main() {
    console.log('🔧 ARREGLANDO ESTRUCTURA DEL CURSO\n');

    // 1. Get current course
    const { data: course, error } = await supabase
        .from('courses')
        .select('*')
        .eq('id', COURSE_ID)
        .single();

    if (error || !course) {
        console.error('❌ No se pudo obtener el curso:', error?.message);
        return;
    }

    console.log(`📚 Curso actual: ${course.name}`);
    console.log(`   Módulos actuales: ${course.modules?.length || 0}`);

    // 2. Create proper modules structure
    const modules = [
        {
            id: 0,
            title: "Introducción",
            type: "content",
            order: 0,
            items: [
                {
                    id: "intro-video",
                    type: "video",
                    order: 0,
                    content: {
                        url: "/uploads/courses/ALTURA/media/intro.mp4"
                    }
                }
            ]
        },
        {
            id: 1,
            title: "Señalética",
            type: "content",
            order: 1,
            items: [
                {
                    id: "senaletica-image",
                    type: "image",
                    order: 0,
                    content: {
                        url: "/uploads/courses/ALTURA/media/sacyr.jpg"
                    }
                },
                {
                    id: "senaletica-text",
                    type: "text",
                    order: 1,
                    content: {
                        text: "La señalética es fundamental para la seguridad en trabajos de altura."
                    }
                }
            ]
        },
        {
            id: 8,
            title: "Evaluación Final",
            type: "evaluation",
            order: 8,
            settings: {
                min_score: 60,
                quiz_percentage: 50,
                scorm_percentage: 50
            },
            items: [
                {
                    id: "quiz-final",
                    type: "quiz",
                    order: 0,
                    content: {
                        questions: [
                            {
                                id: 1,
                                text: "¿Cuál es la altura mínima para considerar trabajo en altura?",
                                options: [
                                    { id: "a", text: "1 metro" },
                                    { id: "b", text: "1.8 metros" },
                                    { id: "c", text: "2 metros" },
                                    { id: "d", text: "2.5 metros" }
                                ],
                                correctAnswer: "b"
                            },
                            {
                                id: 2,
                                text: "¿Qué equipo es esencial para trabajo en altura?",
                                options: [
                                    { id: "a", text: "Casco" },
                                    { id: "b", text: "Arnés de seguridad" },
                                    { id: "c", text: "Guantes" },
                                    { id: "d", text: "Lentes" }
                                ],
                                correctAnswer: "b"
                            },
                            {
                                id: 3,
                                text: "¿Cada cuánto se debe inspeccionar el arnés?",
                                options: [
                                    { id: "a", text: "Antes de cada uso" },
                                    { id: "b", text: "Una vez por semana" },
                                    { id: "c", text: "Una vez al mes" },
                                    { id: "d", text: "Una vez al año" }
                                ],
                                correctAnswer: "a"
                            },
                            {
                                id: 4,
                                text: "¿Qué significa la señalética amarilla con negro?",
                                options: [
                                    { id: "a", text: "Prohibición" },
                                    { id: "b", text: "Obligación" },
                                    { id: "c", text: "Advertencia" },
                                    { id: "d", text: "Información" }
                                ],
                                correctAnswer: "c"
                            },
                            {
                                id: 5,
                                text: "¿Qué debe hacer si encuentra un arnés dañado?",
                                options: [
                                    { id: "a", text: "Usarlo con cuidado" },
                                    { id: "b", text: "Reportarlo y no usarlo" },
                                    { id: "c", text: "Repararlo uno mismo" },
                                    { id: "d", text: "Seguir trabajando" }
                                ],
                                correctAnswer: "b"
                            }
                        ]
                    }
                },
                {
                    id: "scorm-altura",
                    type: "scorm",
                    order: 1,
                    content: {
                        package_path: "/uploads/courses/ALTURA/scorm/",
                        launch_file: "index.html"
                    }
                },
                {
                    id: "signature-final",
                    type: "signature",
                    order: 2,
                    content: {
                        required: true,
                        message: "Dibuja tu firma para completar el curso"
                    }
                }
            ]
        }
    ];

    // 3. Update course with new modules
    const { error: updateError } = await supabase
        .from('courses')
        .update({ modules })
        .eq('id', COURSE_ID);

    if (updateError) {
        console.error('❌ Error al actualizar curso:', updateError.message);
        return;
    }

    console.log('✅ Curso actualizado con nueva estructura de módulos');
    console.log(`   • ${modules.length} módulos creados`);
    console.log(`   • Módulo de evaluación con quiz de 5 preguntas`);
    console.log(`   • SCORM integrado`);
    console.log(`   • Firma digital requerida`);

    // 4. Update student enrollment status based on existing scores
    console.log('\n🔄 Actualizando estado del estudiante...');
    
    const { data: enrollment } = await supabase
        .from('enrollments')
        .select('*, activity_logs(*)')
        .eq('course_id', COURSE_ID)
        .eq('student_id', '46911462-3853-4c0c-963e-7383726f2f9a')
        .single();

    if (enrollment && enrollment.activity_logs) {
        // Find best quiz score
        const quizLogs = enrollment.activity_logs.filter(log => 
            log.interaction_type === 'final_quiz' || log.interaction_type === 'passed'
        );
        
        if (quizLogs.length > 0) {
            const bestScore = Math.max(...quizLogs.map(log => log.score || 0));
            console.log(`   Mejor nota encontrada en logs: ${bestScore}%`);

            const newStatus = bestScore >= 60 ? 'in_progress' : 'not_started';
            
            const { error: updateEnrollError } = await supabase
                .from('enrollments')
                .update({
                    status: newStatus,
                    best_score: bestScore,
                    current_attempt: 1
                })
                .eq('id', enrollment.id);

            if (updateEnrollError) {
                console.error('   ❌ Error al actualizar enrollment:', updateEnrollError.message);
            } else {
                console.log(`   ✅ Estado actualizado a: ${newStatus}`);
                console.log(`   ✅ Mejor nota: ${bestScore}%`);
            }
        }
    }

    console.log('\n✅ ACTUALIZACIÓN COMPLETADA');
}

main().catch(console.error);
