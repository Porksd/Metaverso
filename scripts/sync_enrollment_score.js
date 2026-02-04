/**
 * Actualiza el enrollment con el mejor score registrado en activity_logs
 */

const { createClient } = require('@supabase/supabase-js');
const path = require('path');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY
);

const STUDENT_ID = '46911462-3853-4c0c-963e-7383726f2f9a';
const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function main() {
    console.log('🔄 ACTUALIZANDO ENROLLMENT CON SCORE DE LOS LOGS\n');

    // 1. Get enrollment
    const { data: enrollment, error: enrollError } = await supabase
        .from('enrollments')
        .select('*')
        .eq('student_id', STUDENT_ID)
        .eq('course_id', COURSE_ID)
        .single();

    if (enrollError || !enrollment) {
        console.error('❌ No se encontró enrollment');
        return;
    }

    console.log(`📋 Enrollment actual:`);
    console.log(`   Estado: ${enrollment.status}`);
    console.log(`   Mejor nota: ${enrollment.best_score || 'null'}`);
    console.log(`   Intentos: ${enrollment.current_attempt}/${enrollment.max_attempts}`);

    // 2. Get activity logs for quizzes and scorm
    const { data: logs, error: logsError } = await supabase
        .from('activity_logs')
        .select('*')
        .eq('enrollment_id', enrollment.id)
        .not('score', 'is', null)
        .order('created_at', { ascending: false });

    if (logsError) {
        console.error('❌ Error al obtener logs:', logsError.message);
        return;
    }

    if (!logs || logs.length === 0) {
        console.log('\n⚠️  No hay scores registrados en los logs');
        return;
    }

    console.log(`\n📊 Scores encontrados en logs (últimos 20):`);
    logs.slice(0, 20).forEach((log, i) => {
        console.log(`   ${i + 1}. ${log.score}% - ${log.interaction_type} - ${new Date(log.created_at).toLocaleString()}`);
    });

    // Separate quiz and scorm scores
    const quizLogs = logs.filter(l => ['final_quiz', 'quiz_completed'].includes(l.interaction_type));
    const scormLogs = logs.filter(l => ['passed', 'scorm_completed', 'tracking'].includes(l.interaction_type) || (l.raw_data && l.raw_data['cmi.core.score.raw'] !== undefined));

    const bestQuiz = quizLogs.length ? Math.max(...quizLogs.map(l => Number(l.score))) : null;
    const bestScorm = scormLogs.length ? Math.max(...scormLogs.map(l => Number(l.score))) : null;

    console.log(`\n🔎 Mejor quiz: ${bestQuiz !== null ? bestQuiz + '%' : 'N/A'}`);
    console.log(`🔎 Mejor SCORM: ${bestScorm !== null ? bestScorm + '%' : 'N/A'}`);

    // 3. Get course config to check passing score and evaluation settings
    const { data: course } = await supabase
        .from('courses')
        .select('config, modules')
        .eq('id', COURSE_ID)
        .single();

    const passingScore = course?.config?.passing_score || 60;

    // Determine scorm percentage from evaluation module settings (default 0)
    let scormPercentage = 0;
    try {
        const evalModule = (course?.modules || []).find(m => m.type === 'evaluation');
        scormPercentage = evalModule?.settings?.scorm_percentage || 0;
    } catch (e) {
        scormPercentage = 0;
    }

    // Fallback: if courses.modules isn't populated, check course_modules table
    if (!scormPercentage) {
        const { data: cmods } = await supabase
            .from('course_modules')
            .select('settings')
            .eq('course_id', COURSE_ID)
            .eq('type', 'evaluation')
            .limit(1)
            .single();
        if (cmods && cmods.settings && cmods.settings.scorm_percentage) {
            scormPercentage = cmods.settings.scorm_percentage;
        }
    }

    console.log(`\n⚙️  Configuración de evaluación: SCORM ${scormPercentage}% / Quiz ${100 - scormPercentage}%`);

    // Compute combined score: quiz weight + scorm weight
    let combinedScore = null;
    if (scormPercentage > 0) {
        // If any side is missing, treat missing as 0
        const q = bestQuiz !== null ? bestQuiz : 0;
        const s = bestScorm !== null ? bestScorm : 0;
        combinedScore = Math.round((q * (100 - scormPercentage) + s * scormPercentage) / 100);
    } else {
        combinedScore = bestQuiz !== null ? Math.round(bestQuiz) : null;
    }

    if (combinedScore === null) {
        console.log('\n⚠️  No hay suficientes datos para calcular la nota combinada');
        return;
    }

    console.log(`\n🏆 Nota combinada calculada: ${combinedScore}%`);

    const passed = combinedScore >= passingScore;

    console.log(`   Nota mínima requerida: ${passingScore}%`);
    console.log(`   ¿Aprobó?: ${passed ? 'SÍ ✅' : 'NO ❌'}`);

    // 4. Determine new status
    let newStatus = enrollment.status;
    if (enrollment.status === 'not_started') {
        newStatus = 'in_progress'; // Started taking quizzes
    }
    if (passed && enrollment.status !== 'completed') {
        // Check if signature is required and present
        const { data: student } = await supabase
            .from('students')
            .select('digital_signature_url')
            .eq('id', STUDENT_ID)
            .single();

        if (student?.digital_signature_url) {
            newStatus = 'completed'; // Has signature, can complete
        } else {
            newStatus = 'in_progress'; // Needs signature before completion
        }
    }

    console.log(`\n🔄 Actualizando enrollment...`);
    console.log(`   Nuevo estado: ${newStatus}`);
    console.log(`   Mejor nota: ${combinedScore}%`);

    // 5. Update enrollment
    const { error: updateError } = await supabase
        .from('enrollments')
        .update({
            status: newStatus,
            best_score: combinedScore,
            current_attempt: Math.max(enrollment.current_attempt, logs.length),
            ...(newStatus === 'completed' ? { completed_at: new Date().toISOString() } : {})
        })
        .eq('id', enrollment.id);

    if (updateError) {
        console.error('❌ Error al actualizar:', updateError.message);
        return;
    }

    console.log('✅ Enrollment actualizado correctamente\n');

    // 6. Show requirements for certificate
    console.log('📜 REQUISITOS PARA CERTIFICADO:');
    console.log(`   ✅ Nota aprobatoria: ${combinedScore}% >= ${passingScore}%`);
    
    const { data: student } = await supabase
        .from('students')
        .select('digital_signature_url')
        .eq('id', STUDENT_ID)
        .single();

    if (student?.digital_signature_url) {
        console.log(`   ✅ Firma digital: presente`);
        console.log(`\n🎓 PUEDE GENERAR CERTIFICADO`);
    } else {
        console.log(`   ❌ Firma digital: falta`);
        console.log(`\n⚠️  FALTA FIRMA DIGITAL PARA GENERAR CERTIFICADO`);
        console.log(`   El estudiante debe firmar al completar el curso`);
    }
}

main().catch(console.error);
