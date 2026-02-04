/**
 * Script para probar la funcionalidad de firma digital
 * 1. Crea una firma de prueba en base64
 * 2. La guarda en el perfil del estudiante
 * 3. Verifica que el certificado se pueda generar
 */

const { createClient } = require('@supabase/supabase-js');
const path = require('path');
const fs = require('fs');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY
);

const STUDENT_ID = '46911462-3853-4c0c-963e-7383726f2f9a';
const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

// Firma digital de prueba (imagen SVG en base64)
const TEST_SIGNATURE = `data:image/svg+xml;base64,${Buffer.from(`
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200" viewBox="0 0 400 200">
  <style>
    .signature { 
      font-family: 'Brush Script MT', cursive; 
      font-size: 48px; 
      fill: #1a1a1a; 
    }
  </style>
  <text x="50" y="120" class="signature">Nicolás Cavieres</text>
  <path d="M 50 130 Q 200 135 350 130" stroke="#1a1a1a" stroke-width="2" fill="none"/>
</svg>
`).toString('base64')}`;

async function main() {
    console.log('🖊️  PROBANDO FUNCIONALIDAD DE FIRMA DIGITAL\n');

    // 1. Get student
    const { data: student, error: studentError } = await supabase
        .from('students')
        .select('*')
        .eq('id', STUDENT_ID)
        .single();

    if (studentError || !student) {
        console.error('❌ No se encontró el estudiante');
        return;
    }

    console.log(`👤 Estudiante: ${student.first_name} ${student.last_name}`);
    console.log(`   RUT: ${student.rut}`);
    console.log(`   Email: ${student.email}`);
    console.log(`   Firma actual: ${student.digital_signature_url ? 'Presente ✅' : 'Ausente ❌'}`);

    // 2. Update with test signature
    console.log('\n🖊️  Guardando firma de prueba...');
    const { error: updateError } = await supabase
        .from('students')
        .update({ digital_signature_url: TEST_SIGNATURE })
        .eq('id', STUDENT_ID);

    if (updateError) {
        console.error('❌ Error al guardar firma:', updateError.message);
        return;
    }

    console.log('✅ Firma guardada correctamente');

    // 3. Verify signature is saved
    const { data: updatedStudent } = await supabase
        .from('students')
        .select('digital_signature_url')
        .eq('id', STUDENT_ID)
        .single();

    if (updatedStudent?.digital_signature_url) {
        const signatureLength = updatedStudent.digital_signature_url.length;
        console.log(`   Tamaño de firma: ${signatureLength} caracteres`);
        console.log(`   Formato: ${updatedStudent.digital_signature_url.substring(0, 30)}...`);
    }

    // 4. Get enrollment status
    console.log('\n📋 Verificando requisitos para certificado...');
    const { data: enrollment } = await supabase
        .from('enrollments')
        .select('*')
        .eq('student_id', STUDENT_ID)
        .eq('course_id', COURSE_ID)
        .single();

    if (!enrollment) {
        console.error('❌ No se encontró enrollment');
        return;
    }

    console.log(`   Estado: ${enrollment.status}`);
    console.log(`   Mejor nota: ${enrollment.best_score || 'N/A'}%`);

    // 5. Get course config
    const { data: course } = await supabase
        .from('courses')
        .select('config, name')
        .eq('id', COURSE_ID)
        .single();

    const passingScore = course?.config?.passing_score || 60;

    // 6. Check all requirements
    console.log('\n📜 REQUISITOS PARA CERTIFICADO:');
    
    const hasScore = enrollment.best_score !== null && enrollment.best_score !== undefined;
    const hasPassed = enrollment.best_score >= passingScore;
    const hasSignature = updatedStudent?.digital_signature_url !== null;

    console.log(`   ${hasScore ? '✅' : '❌'} Tiene nota registrada: ${hasScore ? enrollment.best_score + '%' : 'No'}`);
    console.log(`   ${hasPassed ? '✅' : '❌'} Nota aprobatoria: ${hasPassed ? `${enrollment.best_score}% >= ${passingScore}%` : 'No cumple'}`);
    console.log(`   ${hasSignature ? '✅' : '❌'} Firma digital presente`);

    const canGenerateCertificate = hasScore && hasPassed && hasSignature;

    if (canGenerateCertificate) {
        console.log('\n🎓 ✅ PUEDE GENERAR CERTIFICADO');
        
        // Try to update status to completed if not already
        if (enrollment.status !== 'completed') {
            console.log('\n🔄 Actualizando estado a "completed"...');
            const { error: completeError } = await supabase
                .from('enrollments')
                .update({
                    status: 'completed',
                    completed_at: new Date().toISOString()
                })
                .eq('id', enrollment.id);

            if (completeError) {
                console.error('❌ Error al completar:', completeError.message);
            } else {
                console.log('✅ Estado actualizado a "completed"');
            }
        }

        // Simulate certificate data
        console.log('\n📄 DATOS PARA CERTIFICADO:');
        console.log(`   Estudiante: ${student.first_name} ${student.last_name}`);
        console.log(`   RUT: ${student.rut}`);
        console.log(`   Curso: ${course.name}`);
        console.log(`   Nota: ${enrollment.best_score}%`);
        console.log(`   Fecha: ${new Date().toLocaleDateString('es-CL')}`);
        console.log(`   Firma: ${updatedStudent.digital_signature_url.substring(0, 50)}...`);

    } else {
        console.log('\n❌ NO PUEDE GENERAR CERTIFICADO');
        if (!hasScore) console.log('   Falta: Nota registrada');
        if (!hasPassed) console.log('   Falta: Nota aprobatoria');
        if (!hasSignature) console.log('   Falta: Firma digital');
    }

    // 7. Test certificate generation preview
    if (canGenerateCertificate) {
        console.log('\n🖼️  VISTA PREVIA DEL CERTIFICADO:');
        console.log('   ╔════════════════════════════════════════════════════╗');
        console.log('   ║                                                    ║');
        console.log('   ║              CERTIFICADO DE APROBACIÓN             ║');
        console.log('   ║                                                    ║');
        console.log(`   ║         ${student.first_name} ${student.last_name}`.padEnd(57) + '║');
        console.log(`   ║         RUT: ${student.rut}`.padEnd(57) + '║');
        console.log('   ║                                                    ║');
        console.log(`   ║         ${course.name}`.padEnd(57) + '║');
        console.log(`   ║         Nota: ${enrollment.best_score}%`.padEnd(57) + '║');
        console.log('   ║                                                    ║');
        console.log('   ║         ____________________________               ║');
        console.log('   ║         Firma del Alumno                           ║');
        console.log('   ║                                                    ║');
        console.log('   ╚════════════════════════════════════════════════════╝');
    }

    console.log('\n' + '='.repeat(60));
    console.log('📊 RESUMEN FINAL');
    console.log('='.repeat(60));
    console.log(`Estudiante: ${student.first_name} ${student.last_name} (${student.rut})`);
    console.log(`Firma digital: ${hasSignature ? 'SÍ ✅' : 'NO ❌'}`);
    console.log(`Nota: ${enrollment.best_score}%`);
    console.log(`Estado: ${enrollment.status}`);
    console.log(`Puede generar certificado: ${canGenerateCertificate ? 'SÍ ✅' : 'NO ❌'}`);
    console.log('='.repeat(60));
}

main().catch(console.error);
