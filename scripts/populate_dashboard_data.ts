/**
 * Script para poblar datos de prueba en el dashboard
 * Crea enrollments adicionales con variedad de:
 * - Fechas (últimos 30 días)
 * - Estados (in_progress, completed)
 * - Scores variados
 * - Diferentes estudiantes
 */

import { createClient } from '@supabase/supabase-js';
import * as dotenv from 'dotenv';

dotenv.config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function populateDashboardData() {
    console.log('🚀 Iniciando población de datos de prueba...\n');

    // 1. Obtener estudiantes de Sacyr Chile S.A.
    const { data: students, error: studentsError } = await supabase
        .from('students')
        .select('id, rut, first_name, last_name, company_name')
        .eq('company_name', 'Sacyr Chile S.A.')
        .limit(10);

    if (studentsError || !students || students.length === 0) {
        console.error('❌ Error obteniendo estudiantes:', studentsError);
        return;
    }

    console.log(`✅ Encontrados ${students.length} estudiantes de Sacyr Chile S.A.`);

    // 2. Obtener cursos disponibles
    const { data: courses, error: coursesError } = await supabase
        .from('courses')
        .select('id, name, code')
        .limit(5);

    if (coursesError || !courses || courses.length === 0) {
        console.error('❌ Error obteniendo cursos:', coursesError);
        return;
    }

    console.log(`✅ Encontrados ${courses.length} cursos disponibles`);
    console.log(`   Cursos: ${courses.map(c => c.name).join(', ')}\n`);

    // 3. Generar enrollments de prueba
    const enrollments = [];
    const today = new Date();
    const statuses = ['in_progress', 'completed', 'completed', 'completed']; // Más completados
    const scores = [65, 70, 75, 80, 85, 90, 95, 100]; // Variedad de scores

    // Por cada estudiante, crear entre 2 y 8 enrollments
    for (const student of students) {
        const numEnrollments = Math.floor(Math.random() * 7) + 2; // 2-8 cursos
        const selectedCourses = shuffleArray([...courses]).slice(0, Math.min(numEnrollments, courses.length));

        for (let i = 0; i < selectedCourses.length; i++) {
            const course = selectedCourses[i];
            const daysAgo = Math.floor(Math.random() * 30); // Últimos 30 días
            const createdAt = new Date(today);
            createdAt.setDate(createdAt.getDate() - daysAgo);

            const status = statuses[Math.floor(Math.random() * statuses.length)];
            const score = status === 'completed' ? scores[Math.floor(Math.random() * scores.length)] : null;

            enrollments.push({
                student_id: student.id,
                course_id: course.id,
                status: status,
                best_score: score ? score.toString() : null,
                current_attempt: status === 'completed' ? 1 : 0,
                max_attempts: 3,
                completed_at: status === 'completed' ? createdAt.toISOString() : null,
                created_at: createdAt.toISOString()
            });
        }
    }

    console.log(`📊 Generados ${enrollments.length} enrollments de prueba\n`);

    // 4. Verificar duplicados antes de insertar
    console.log('🔍 Verificando enrollments existentes...');

    const existingEnrollments = new Set();
    for (const enrollment of enrollments) {
        const { data: existing } = await supabase
            .from('enrollments')
            .select('id')
            .eq('student_id', enrollment.student_id)
            .eq('course_id', enrollment.course_id)
            .single();

        if (existing) {
            existingEnrollments.add(`${enrollment.student_id}-${enrollment.course_id}`);
        }
    }

    const newEnrollments = enrollments.filter(e =>
        !existingEnrollments.has(`${e.student_id}-${e.course_id}`)
    );

    console.log(`   📌 ${enrollments.length - newEnrollments.length} enrollments ya existen`);
    console.log(`   ➕ ${newEnrollments.length} nuevos enrollments a insertar\n`);

    if (newEnrollments.length === 0) {
        console.log('✨ No hay nuevos enrollments para insertar. Base de datos ya poblada.\n');
        return;
    }

    // 5. Insertar enrollments en lotes de 10
    const batchSize = 10;
    let inserted = 0;

    for (let i = 0; i < newEnrollments.length; i += batchSize) {
        const batch = newEnrollments.slice(i, i + batchSize);

        const { data, error } = await supabase
            .from('enrollments')
            .insert(batch)
            .select();

        if (error) {
            console.error(`❌ Error insertando lote ${Math.floor(i / batchSize) + 1}:`, error.message);
        } else {
            inserted += data.length;
            console.log(`   ✅ Lote ${Math.floor(i / batchSize) + 1}: ${data.length} enrollments insertados`);
        }
    }

    console.log(`\n🎉 ¡Población de datos completada!`);
    console.log(`   Total insertado: ${inserted} enrollments`);
    console.log(`   Estudiantes afectados: ${students.length}`);
    console.log(`   Cursos utilizados: ${courses.length}\n`);

    // 6. Crear activity_logs para los enrollments completados
    console.log('📝 Generando activity logs para cursos completados...\n');

    const completedEnrollments = newEnrollments.filter(e => e.status === 'completed');
    const activityLogs = [];

    for (const enrollment of completedEnrollments) {
        // Crear 1-3 intentos de quiz
        const numAttempts = Math.floor(Math.random() * 3) + 1;

        for (let attempt = 1; attempt <= numAttempts; attempt++) {
            const score = attempt === numAttempts
                ? parseInt(enrollment.best_score!)
                : Math.floor(Math.random() * 60) + 40; // Intentos previos con menor score

            const logDate = new Date(enrollment.created_at!);
            logDate.setHours(logDate.getHours() + attempt);

            activityLogs.push({
                student_id: enrollment.student_id,
                course_id: enrollment.course_id,
                interaction_type: 'final_quiz',
                score: score,
                raw_data: JSON.stringify({
                    attempt: attempt,
                    correct: Math.floor((score / 100) * 7), // Asumiendo 7 preguntas
                    total: 7,
                    timestamp: logDate.toISOString()
                }),
                created_at: logDate.toISOString()
            });
        }
    }

    console.log(`   📊 Generados ${activityLogs.length} activity logs`);

    if (activityLogs.length > 0) {
        const { data: logsData, error: logsError } = await supabase
            .from('activity_logs')
            .insert(activityLogs)
            .select();

        if (logsError) {
            console.error('❌ Error insertando activity logs:', logsError.message);
        } else {
            console.log(`   ✅ ${logsData.length} activity logs insertados\n`);
        }
    }

    // 7. Resumen final
    console.log('═══════════════════════════════════════════');
    console.log('📊 RESUMEN DE DATOS POBLADOS');
    console.log('═══════════════════════════════════════════');
    console.log(`Total enrollments nuevos: ${inserted}`);
    console.log(`Total activity logs: ${activityLogs.length}`);
    console.log(`Estudiantes: ${students.length}`);
    console.log(`Cursos: ${courses.length}`);
    console.log('\nDistribución de estados:');
    console.log(`  - En progreso: ${newEnrollments.filter(e => e.status === 'in_progress').length}`);
    console.log(`  - Completados: ${newEnrollments.filter(e => e.status === 'completed').length}`);
    console.log('\nRango de fechas:');
    const dates = newEnrollments.map(e => new Date(e.created_at!));
    console.log(`  - Desde: ${new Date(Math.min(...dates.map(d => d.getTime()))).toLocaleDateString()}`);
    console.log(`  - Hasta: ${new Date(Math.max(...dates.map(d => d.getTime()))).toLocaleDateString()}`);
    console.log('═══════════════════════════════════════════\n');

    console.log('✅ Dashboard listo para visualizar datos enriquecidos!');
    console.log('🌐 Accede a: http://localhost:3001/admin/empresa (Vista Gerente)\n');
}

// Función auxiliar para mezclar array
function shuffleArray<T>(array: T[]): T[] {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
}

// Ejecutar
populateDashboardData()
    .then(() => {
        console.log('🏁 Script finalizado exitosamente');
        process.exit(0);
    })
    .catch(error => {
        console.error('❌ Error fatal:', error);
        process.exit(1);
    });
