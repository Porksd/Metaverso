/**
 * Script para desvincular un alumno de un curso específico
 * Uso: node scripts/desvincular_alumno_curso.js <student_id> <course_id>
 */

require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function desvincularAlumnoCurso(studentId, courseId) {
    try {
        console.log('\n=== DESVINCULAR ALUMNO DE CURSO ===\n');

        // 1. Verificar que exista el enrollment
        const { data: enrollment, error: enrollError } = await supabase
            .from('enrollments')
            .select('id, students(first_name, last_name), courses(name)')
            .eq('student_id', studentId)
            .eq('course_id', courseId)
            .single();

        if (enrollError || !enrollment) {
            console.error('❌ No se encontró la inscripción del alumno en este curso');
            console.error('Error:', enrollError?.message);
            return;
        }

        console.log(`📋 Alumno: ${enrollment.students.first_name} ${enrollment.students.last_name}`);
        console.log(`📚 Curso: ${enrollment.courses.name}`);
        console.log(`🔑 Enrollment ID: ${enrollment.id}\n`);

        // Confirmación
        console.log('⚠️  Esta acción eliminará:');
        console.log('   - La inscripción del alumno al curso');
        console.log('   - Todo su progreso en el curso');
        console.log('   - Todos sus registros de actividad');
        console.log('\nPresiona Ctrl+C para cancelar o espera 5 segundos para continuar...\n');
        
        await new Promise(resolve => setTimeout(resolve, 5000));

        // 2. Eliminar course_progress
        console.log('🗑️  Eliminando progreso del curso...');
        const { error: progressError } = await supabase
            .from('course_progress')
            .delete()
            .eq('enrollment_id', enrollment.id);

        if (progressError) {
            console.error('⚠️  Error eliminando progreso:', progressError.message);
        } else {
            console.log('✅ Progreso eliminado');
        }

        // 3. Eliminar activity_logs
        console.log('🗑️  Eliminando registros de actividad...');
        const { error: logsError } = await supabase
            .from('activity_logs')
            .delete()
            .eq('enrollment_id', enrollment.id);

        if (logsError) {
            console.error('⚠️  Error eliminando logs:', logsError.message);
        } else {
            console.log('✅ Logs eliminados');
        }

        // 4. Eliminar enrollment
        console.log('🗑️  Eliminando inscripción...');
        const { error: enrollmentError } = await supabase
            .from('enrollments')
            .delete()
            .eq('id', enrollment.id);

        if (enrollmentError) {
            console.error('❌ Error eliminando inscripción:', enrollmentError.message);
            return;
        }
        console.log('✅ Inscripción eliminada');

        // 5. Verificar si tiene otros cursos
        console.log('🔍 Verificando otros cursos del alumno...');
        const { data: otherEnrollments } = await supabase
            .from('enrollments')
            .select('id')
            .eq('student_id', studentId);

        if (!otherEnrollments || otherEnrollments.length === 0) {
            console.log('🗑️  No tiene más cursos asignados. Limpiando firma digital...');
            const { error: signatureError } = await supabase
                .from('students')
                .update({ digital_signature_url: null })
                .eq('id', studentId);

            if (signatureError) {
                console.error('⚠️  Error limpiando firma:', signatureError.message);
            } else {
                console.log('✅ Firma digital eliminada');
            }
        } else {
            console.log(`ℹ️  El alumno tiene ${otherEnrollments.length} curso(s) más asignado(s)`);
        }

        console.log('\n✅ DESVINCULACIÓN COMPLETADA EXITOSAMENTE\n');

    } catch (error) {
        console.error('❌ Error inesperado:', error);
    }
}

// Ejecutar
const studentId = process.argv[2];
const courseId = process.argv[3];

if (!studentId || !courseId) {
    console.log('❌ Uso: node scripts/desvincular_alumno_curso.js <student_id> <course_id>');
    console.log('\nPara encontrar los IDs, usa: node scripts/find_enrollment_ids.js <rut_alumno>');
    process.exit(1);
}

desvincularAlumnoCurso(studentId, courseId).then(() => process.exit(0));
