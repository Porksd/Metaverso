/**
 * Script para encontrar IDs de estudiante y curso para reiniciar enrollment
 * 
 * Uso:
 * node scripts/find_enrollment_ids.js <rut_o_email_del_estudiante>
 */

require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function findEnrollmentIds(searchTerm) {
    console.log('\n🔍 BUSCANDO ENROLLMENTS...');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

    // Buscar estudiante por RUT o email
    const { data: students, error: studentError } = await supabase
        .from('students')
        .select('*')
        .or(`rut.ilike.%${searchTerm}%,email.ilike.%${searchTerm}%`);

    if (studentError) {
        console.error('❌ Error buscando estudiante:', studentError);
        return;
    }

    if (!students || students.length === 0) {
        console.log('❌ No se encontraron estudiantes con ese RUT o email');
        return;
    }

    console.log(`\n✅ Encontrado(s) ${students.length} estudiante(s):\n`);

    for (const student of students) {
        console.log(`👤 ${student.first_name} ${student.last_name}`);
        console.log(`   RUT: ${student.rut}`);
        console.log(`   Email: ${student.email}`);
        console.log(`   ID: ${student.id}`);
        console.log(`   Firma Digital: ${student.digital_signature_url ? '✅ Presente' : '❌ No guardada'}`);

        // Buscar enrollments para este estudiante
        const { data: enrollments, error: enrollError } = await supabase
            .from('enrollments')
            .select('*, courses(name)')
            .eq('student_id', student.id);

        if (enrollError) {
            console.error('   ❌ Error cargando enrollments:', enrollError);
            continue;
        }

        if (!enrollments || enrollments.length === 0) {
            console.log('   📚 No tiene cursos asignados\n');
            continue;
        }

        console.log(`\n   📚 CURSOS (${enrollments.length}):`);
        enrollments.forEach((enr, idx) => {
            console.log(`\n   ${idx + 1}. ${enr.courses.name}`);
            console.log(`      Course ID: ${enr.course_id}`);
            console.log(`      Status: ${enr.status}`);
            console.log(`      Quiz: ${enr.quiz_score || 0}% | SCORM: ${enr.scorm_score || 0}% | Total: ${enr.best_score || 0}%`);
            console.log(`      Completado: ${enr.completed_at || 'N/A'}`);
            
            if (enr.status !== 'not_started') {
                console.log(`\n      🔄 Para reiniciar este enrollment:`);
                console.log(`      node scripts/reset_student_enrollment.js ${student.id} ${enr.course_id}`);
            }
        });

        console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    console.log('\n');
}

// Main
const args = process.argv.slice(2);

if (args.length < 1) {
    console.error('\n❌ Error: Debes proporcionar un RUT o email');
    console.log('\nUso:');
    console.log('  node scripts/find_enrollment_ids.js <rut_o_email>');
    console.log('\nEjemplos:');
    console.log('  node scripts/find_enrollment_ids.js 12345678-9');
    console.log('  node scripts/find_enrollment_ids.js usuario@ejemplo.com\n');
    process.exit(1);
}

const searchTerm = args[0];

findEnrollmentIds(searchTerm)
    .then(() => process.exit(0))
    .catch(err => {
        console.error('\n💥 ERROR CRÍTICO:', err);
        process.exit(1);
    });
