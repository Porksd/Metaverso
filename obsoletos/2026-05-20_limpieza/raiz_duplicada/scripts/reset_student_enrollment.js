/**
 * Script para reiniciar correctamente un enrollment de un estudiante
 * Resetea todos los campos relacionados con el progreso del curso
 * 
 * Uso:
 * node scripts/reset_student_enrollment.js <student_id> <course_id>
 */

require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function resetEnrollment(studentId, courseId) {
    console.log('\n🔄 REINICIANDO ENROLLMENT...');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

    // 1. Verificar que el enrollment existe
    const { data: enrollment, error: fetchError } = await supabase
        .from('enrollments')
        .select('*, students(first_name, last_name, rut), courses(name)')
        .eq('student_id', studentId)
        .eq('course_id', courseId)
        .single();

    if (fetchError || !enrollment) {
        console.error('❌ Error: Enrollment no encontrado');
        console.error(fetchError);
        return;
    }

    console.log('\n📋 Enrollment encontrado:');
    console.log(`   Estudiante: ${enrollment.students.first_name} ${enrollment.students.last_name}`);
    console.log(`   RUT: ${enrollment.students.rut}`);
    console.log(`   Curso: ${enrollment.courses.name}`);
    console.log(`   Estado actual: ${enrollment.status}`);
    console.log(`   Quiz Score: ${enrollment.quiz_score || 0}%`);
    console.log(`   SCORM Score: ${enrollment.scorm_score || 0}%`);
    console.log(`   Best Score: ${enrollment.best_score || 0}%`);
    console.log(`   Completed At: ${enrollment.completed_at || 'N/A'}`);

    console.log('\n⚠️  REINICIANDO EN 3 SEGUNDOS...');
    await new Promise(resolve => setTimeout(resolve, 3000));

    // 2. Resetear el enrollment
    const { error: updateError } = await supabase
        .from('enrollments')
        .update({
            status: 'not_started',
            current_module_index: 0,
            quiz_score: 0,
            scorm_score: 0,
            best_score: 0,
            completed_at: null,
            certificate_url: null,
            certificate_id: null
        })
        .eq('id', enrollment.id);

    if (updateError) {
        console.error('❌ Error al actualizar enrollment:', updateError);
        return;
    }

    // 3. Limpiar la firma digital del estudiante (opcional)
    console.log('\n🔍 ¿Deseas eliminar también la firma digital del estudiante? (Presiona Ctrl+C para cancelar)');
    await new Promise(resolve => setTimeout(resolve, 2000));

    const { error: sigError } = await supabase
        .from('students')
        .update({ digital_signature_url: null })
        .eq('id', studentId);

    if (sigError) {
        console.warn('⚠️  No se pudo limpiar la firma digital:', sigError);
    } else {
        console.log('✅ Firma digital eliminada');
    }

    // 4. Eliminar registros de progreso (course_progress)
    const { error: progressError } = await supabase
        .from('course_progress')
        .delete()
        .eq('enrollment_id', enrollment.id);

    if (progressError) {
        console.warn('⚠️  No se pudo eliminar el progreso:', progressError);
    } else {
        console.log('✅ Progreso eliminado de course_progress');
    }

    // 5. Eliminar logs de actividad (opcional, para limpieza completa)
    const { error: logsError } = await supabase
        .from('activity_logs')
        .delete()
        .eq('enrollment_id', enrollment.id);

    if (logsError) {
        console.warn('⚠️  No se pudo eliminar activity logs:', logsError);
    } else {
        console.log('✅ Activity logs eliminados');
    }

    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('✅ ENROLLMENT REINICIADO EXITOSAMENTE');
    console.log('\n📝 Estado final:');
    console.log('   Status: not_started');
    console.log('   Module Index: 0');
    console.log('   Quiz Score: 0');
    console.log('   SCORM Score: 0');
    console.log('   Best Score: 0');
    console.log('   Firma Digital: Eliminada');
    console.log('   Progreso: Limpio');
    console.log('\n🚀 El estudiante puede comenzar el curso desde cero.');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');
}

// Main
const args = process.argv.slice(2);

if (args.length < 2) {
    console.error('\n❌ Error: Parámetros insuficientes');
    console.log('\nUso:');
    console.log('  node scripts/reset_student_enrollment.js <student_id> <course_id>');
    console.log('\nEjemplo:');
    console.log('  node scripts/reset_student_enrollment.js 947ec667-7b28-46d5-8a57-8a441605550b 123e4567-e89b-12d3-a456-426614174000\n');
    process.exit(1);
}

const [studentId, courseId] = args;

resetEnrollment(studentId, courseId)
    .then(() => process.exit(0))
    .catch(err => {
        console.error('\n💥 ERROR CRÍTICO:', err);
        process.exit(1);
    });
