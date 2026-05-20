/**
 * Script final para completar el curso del estudiante
 * Actualiza el estado a 'completed' y genera el certificado
 */

const { createClient } = require('@supabase/supabase-js');
const path = require('path');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
const supabase = createClient(process.env.NEXT_PUBLIC_SUPABASE_URL, supabaseKey);

const STUDENT_ID = '46911462-3853-4c0c-963e-7383726f2f9a';
const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function main() {
    console.log('🎓 COMPLETANDO CURSO DEL ESTUDIANTE\n');

    // 1. Get student
    const { data: student } = await supabase
        .from('students')
        .select('*')
        .eq('id', STUDENT_ID)
        .single();

    if (!student) {
        console.error('❌ Estudiante no encontrado');
        return;
    }

    console.log(`👤 Estudiante: ${student.first_name} ${student.last_name}`);
    console.log(`   RUT: ${student.rut}`);
    console.log(`   Email: ${student.email}`);
    console.log(`   Firma: ${student.digital_signature_url ? '✅' : '❌'}`);

    // 2. Get enrollment
    const { data: enrollment } = await supabase
        .from('enrollments')
        .select('*')
        .eq('student_id', STUDENT_ID)
        .eq('course_id', COURSE_ID)
        .single();

    if (!enrollment) {
        console.error('❌ Enrollment no encontrado');
        return;
    }

    console.log(`\n📋 Enrollment actual:`);
    console.log(`   Estado: ${enrollment.status}`);
    console.log(`   Nota: ${enrollment.best_score}%`);
    console.log(`   Certificado: ${enrollment.certificate_url || 'No generado'}`);

    // 3. Get course
    const { data: course } = await supabase
        .from('courses')
        .select('*')
        .eq('id', COURSE_ID)
        .single();

    const passingScore = course?.config?.passing_score || 60;
    const passed = enrollment.best_score >= passingScore;

    console.log(`\n✅ VERIFICACIÓN:`);
    console.log(`   Nota requerida: ${passingScore}%`);
    console.log(`   Nota obtenida: ${enrollment.best_score}%`);
    console.log(`   ¿Aprobó?: ${passed ? 'SÍ ✅' : 'NO ❌'}`);
    console.log(`   Firma digital: ${student.digital_signature_url ? 'SÍ ✅' : 'NO ❌'}`);

    if (!passed) {
        console.error('\n❌ El estudiante no ha aprobado el curso');
        return;
    }

    if (!student.digital_signature_url) {
        console.error('\n❌ Falta firma digital');
        return;
    }

    // 4. Update to completed
    if (enrollment.status !== 'completed') {
        console.log('\n🔄 Actualizando estado a "completed"...');
        
        const { error: updateError } = await supabase
            .from('enrollments')
            .update({
                status: 'completed',
                completed_at: new Date().toISOString()
            })
            .eq('id', enrollment.id);

        if (updateError) {
            console.error('❌ Error:', updateError.message);
            return;
        }

        console.log('✅ Estado actualizado a "completed"');
    } else {
        console.log('\n✅ El curso ya está marcado como "completed"');
    }

    // 5. Simulate certificate generation
    console.log('\n📜 GENERANDO CERTIFICADO...');
    
    const certificateData = {
        studentName: `${student.first_name} ${student.last_name}`,
        rut: student.rut,
        courseName: course.name,
        score: enrollment.best_score,
        date: new Date().toLocaleDateString('es-CL'),
        hours: 8,
        studentSignature: student.digital_signature_url,
        companyLogo: '/uploads/courses/ALTURA/media/sacyr.jpg'
    };

    console.log('\n📄 DATOS DEL CERTIFICADO:');
    console.log(`   Estudiante: ${certificateData.studentName}`);
    console.log(`   RUT: ${certificateData.rut}`);
    console.log(`   Curso: ${certificateData.courseName}`);
    console.log(`   Nota: ${certificateData.score}%`);
    console.log(`   Fecha: ${certificateData.date}`);
    console.log(`   Horas: ${certificateData.hours}`);
    console.log(`   Firma: ${certificateData.studentSignature.substring(0, 50)}...`);

    // Note: In real scenario, CertificateCanvas would be called from frontend
    // to generate the actual PDF using canvas API
    const mockCertUrl = `/certificates/${enrollment.id}.pdf`;
    
    console.log(`\n📥 URL del certificado (mock): ${mockCertUrl}`);
    console.log('   (En producción, esto se genera con CertificateCanvas)');

    // 6. Final summary
    console.log('\n' + '='.repeat(60));
    console.log('🎉 ¡CURSO COMPLETADO CON ÉXITO!');
    console.log('='.repeat(60));
    console.log(`Estudiante: ${student.first_name} ${student.last_name}`);
    console.log(`RUT: ${student.rut}`);
    console.log(`Curso: ${course.name}`);
    console.log(`Nota Final: ${enrollment.best_score}%`);
    console.log(`Estado: completed ✅`);
    console.log(`Firma Digital: Presente ✅`);
    console.log(`Certificado: Listo para generar ✅`);
    console.log('='.repeat(60));

    console.log('\n💡 PRÓXIMOS PASOS:');
    console.log('   1. El estudiante puede ver el curso en /courses');
    console.log('   2. Al entrar a /courses/' + COURSE_ID);
    console.log('   3. CoursePlayer detectará que aprobó (100%)');
    console.log('   4. Mostrará el botón "Generar Certificado"');
    console.log('   5. CertificateCanvas creará el PDF con la firma');
}

main().catch(console.error);
