/**
 * Verifica que el sistema de evaluación funcione correctamente:
 * 1. Quiz scoring
 * 2. SCORM tracking
 * 3. Certificado con firma digital
 */

const { createClient } = require('@supabase/supabase-js');
const path = require('path');

// Load env from .env.local
require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

if (!supabaseUrl || !supabaseKey) {
    console.error('❌ Falta configuración de Supabase en .env.local');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

// Test student RUT
const TEST_RUT = '20.207.790-0';
const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function main() {
    console.log('🔍 VERIFICACIÓN DEL SISTEMA DE EVALUACIÓN\n');

    // 1. Find student
    console.log('1️⃣ Buscando estudiante...');
    const { data: student, error: studentError } = await supabase
        .from('students')
        .select('*')
        .eq('rut', TEST_RUT)
        .single();

    if (studentError || !student) {
        console.error('❌ Estudiante no encontrado:', studentError?.message);
        return;
    }

    console.log(`✅ Estudiante: ${student.first_name} ${student.last_name}`);
    console.log(`   ID: ${student.id}`);
    console.log(`   Email: ${student.email}`);
    console.log(`   Firma Digital: ${student.digital_signature_url ? '✅ Presente' : '❌ Falta'}`);

    // 2. Check enrollment
    console.log('\n2️⃣ Verificando inscripción...');
    const { data: enrollment, error: enrollError } = await supabase
        .from('enrollments')
        .select('*')
        .eq('student_id', student.id)
        .eq('course_id', COURSE_ID)
        .single();

    if (enrollError || !enrollment) {
        console.error('❌ Inscripción no encontrada');
        return;
    }

    console.log(`✅ Inscripción encontrada`);
    console.log(`   Estado: ${enrollment.status}`);
    console.log(`   Mejor nota: ${enrollment.best_score || 'Sin nota'}`);
    console.log(`   Intentos: ${enrollment.current_attempt}/${enrollment.max_attempts}`);
    console.log(`   Certificado: ${enrollment.certificate_url || 'No generado'}`);

    // 3. Check course structure for evaluation
    console.log('\n3️⃣ Verificando estructura del curso...');
    const { data: course, error: courseError } = await supabase
        .from('courses')
        .select('*')
        .eq('id', COURSE_ID)
        .single();

    if (courseError || !course) {
        console.error('❌ Curso no encontrado');
        return;
    }

    console.log(`✅ Curso: ${course.name}`);

    // Parse modules
    const modules = course.modules || [];
    console.log(`   Total módulos: ${modules.length}`);

    // Find evaluation module
    const evalModule = modules.find(m => m.type === 'evaluation');
    if (evalModule) {
        console.log(`\n   📋 MÓDULO DE EVALUACIÓN ENCONTRADO:`);
        console.log(`      Título: ${evalModule.title}`);
        console.log(`      Items: ${evalModule.items?.length || 0}`);
        
        // Check quiz
        const quiz = evalModule.items?.find(i => i.type === 'quiz');
        if (quiz) {
            console.log(`      ✅ Quiz encontrado: ${quiz.content.questions?.length || 0} preguntas`);
        } else {
            console.log(`      ⚠️  No hay quiz`);
        }

        // Check SCORM
        const scorm = evalModule.items?.find(i => i.type === 'scorm');
        if (scorm) {
            console.log(`      ✅ SCORM encontrado: ${scorm.content.package_path}`);
        } else {
            console.log(`      ⚠️  No hay SCORM`);
        }

        // Check signature requirement
        const signature = evalModule.items?.find(i => i.type === 'signature');
        if (signature) {
            console.log(`      ✅ Firma digital requerida`);
        } else {
            console.log(`      ⚠️  No requiere firma`);
        }

        // Settings
        if (evalModule.settings) {
            console.log(`\n      ⚙️  CONFIGURACIÓN:`);
            console.log(`         Nota mínima: ${evalModule.settings.min_score}%`);
            console.log(`         Ponderación Quiz: ${evalModule.settings.quiz_percentage || 100}%`);
            console.log(`         Ponderación SCORM: ${evalModule.settings.scorm_percentage || 0}%`);
        }
    } else {
        console.log(`   ⚠️  No se encontró módulo de evaluación`);
    }

    // 4. Check activity logs for this enrollment
    console.log('\n4️⃣ Revisando historial de actividad...');
    const { data: logs, error: logsError } = await supabase
        .from('activity_logs')
        .select('*')
        .eq('enrollment_id', enrollment.id)
        .order('created_at', { ascending: false })
        .limit(10);

    if (logsError) {
        console.warn('⚠️  Error al obtener logs:', logsError.message);
    } else if (logs && logs.length > 0) {
        console.log(`✅ ${logs.length} actividades registradas:`);
        logs.forEach((log, i) => {
            console.log(`   ${i + 1}. ${log.interaction_type} - Score: ${log.score || 'N/A'} - ${new Date(log.created_at).toLocaleString()}`);
        });
    } else {
        console.log(`   ℹ️  Sin actividades registradas aún`);
    }

    // 5. Validate certificate generation readiness
    console.log('\n5️⃣ Validando requisitos para certificado...');
    const canGenerateCert = 
        enrollment.status === 'completed' &&
        enrollment.best_score >= (evalModule?.settings?.min_score || 60) &&
        student.digital_signature_url;

    if (canGenerateCert) {
        console.log('✅ LISTO PARA GENERAR CERTIFICADO');
        console.log(`   • Estado: completado`);
        console.log(`   • Nota aprobatoria: ${enrollment.best_score}%`);
        console.log(`   • Firma digital: presente`);
    } else {
        console.log('⚠️  FALTA PARA GENERAR CERTIFICADO:');
        if (enrollment.status !== 'completed') console.log(`   ❌ Estado actual: ${enrollment.status}`);
        if (!enrollment.best_score || enrollment.best_score < (evalModule?.settings?.min_score || 60)) {
            console.log(`   ❌ Nota insuficiente: ${enrollment.best_score || 0}%`);
        }
        if (!student.digital_signature_url) console.log(`   ❌ Falta firma digital`);
    }

    // Summary
    console.log('\n' + '='.repeat(60));
    console.log('📊 RESUMEN');
    console.log('='.repeat(60));
    console.log(`Estudiante: ${student.first_name} ${student.last_name}`);
    console.log(`RUT: ${TEST_RUT}`);
    console.log(`Firma Digital: ${student.digital_signature_url ? 'SÍ' : 'NO'}`);
    console.log(`Inscrito: SÍ`);
    console.log(`Estado: ${enrollment.status}`);
    console.log(`Nota: ${enrollment.best_score || 'Pendiente'}`);
    console.log(`Certificado: ${enrollment.certificate_url ? 'Generado' : 'Pendiente'}`);
    console.log(`Quiz configurado: ${evalModule?.items?.some(i => i.type === 'quiz') ? 'SÍ' : 'NO'}`);
    console.log(`SCORM configurado: ${evalModule?.items?.some(i => i.type === 'scorm') ? 'SÍ' : 'NO'}`);
    console.log(`Puede generar certificado: ${canGenerateCert ? 'SÍ ✅' : 'NO ❌'}`);
    console.log('='.repeat(60));
}

main().catch(console.error);
