const { createClient } = require('@supabase/supabase-js');
const path = require('path');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const SUPABASE_URL = process.env.NEXT_PUBLIC_SUPABASE_URL;
const SUPABASE_SERVICE_ROLE_KEY = process.env.SUPABASE_SERVICE_ROLE_KEY;
const SUPABASE_ANON_KEY = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

if (!SUPABASE_URL || (!SUPABASE_SERVICE_ROLE_KEY && !SUPABASE_ANON_KEY)) {
    console.error('Faltan variables de entorno. Revisa .env.local');
    process.exit(1);
}

const supabase = createClient(
    SUPABASE_URL,
    SUPABASE_SERVICE_ROLE_KEY || SUPABASE_ANON_KEY,
    { auth: { persistSession: false, autoRefreshToken: false } }
);

function isMissingSignature(value) {
    if (value == null) return true;
    if (typeof value !== 'string') return true;
    return value.trim().length === 0;
}

async function fetchAllEnrollments() {
    const pageSize = 1000;
    let from = 0;
    const rows = [];

    while (true) {
        const { data, error } = await supabase
            .from('enrollments')
            .select('id, student_id, course_id, status, completed_at, best_score')
            .range(from, from + pageSize - 1);

        if (error) throw error;
        if (!data || data.length === 0) break;

        rows.push(...data);
        if (data.length < pageSize) break;
        from += pageSize;
    }

    return rows;
}

async function fetchStudentsByIds(studentIds) {
    if (studentIds.length === 0) return [];

    const chunkSize = 500;
    const students = [];

    for (let i = 0; i < studentIds.length; i += chunkSize) {
        const chunk = studentIds.slice(i, i + chunkSize);
        const { data, error } = await supabase
            .from('students')
            .select('id, first_name, last_name, rut, email, digital_signature_url')
            .in('id', chunk);

        if (error) throw error;
        students.push(...(data || []));
    }

    return students;
}

async function fetchCoursesMap(courseIds) {
    if (courseIds.length === 0) return new Map();

    const chunkSize = 500;
    const map = new Map();

    for (let i = 0; i < courseIds.length; i += chunkSize) {
        const chunk = courseIds.slice(i, i + chunkSize);
        const { data, error } = await supabase
            .from('courses')
            .select('id, name')
            .in('id', chunk);

        if (error) throw error;
        for (const c of data || []) {
            map.set(c.id, c.name || '(sin nombre)');
        }
    }

    return map;
}

function formatName(student) {
    const fullName = `${student.first_name || ''} ${student.last_name || ''}`.trim();
    return fullName || '(sin nombre)';
}

async function main() {
    const includeAllStatuses = process.argv.includes('--all');

    console.log('Audit de firmas digitales en alumnos');
    console.log('Modo:', includeAllStatuses ? 'TODOS los enrollments' : 'Solo enrollments completados');
    console.log('');

    const enrollments = await fetchAllEnrollments();

    if (!enrollments.length) {
        console.log('No hay enrollments para revisar.');
        return;
    }

    const scopedEnrollments = includeAllStatuses
        ? enrollments
        : enrollments.filter((e) => e.status === 'completed');

    if (!scopedEnrollments.length) {
        console.log('No hay enrollments en el alcance seleccionado.');
        return;
    }

    const studentIds = [...new Set(scopedEnrollments.map((e) => e.student_id).filter(Boolean))];
    const courseIds = [...new Set(scopedEnrollments.map((e) => e.course_id).filter(Boolean))];

    const [students, coursesMap] = await Promise.all([
        fetchStudentsByIds(studentIds),
        fetchCoursesMap(courseIds)
    ]);

    const studentMap = new Map(students.map((s) => [s.id, s]));

    const missingRows = [];
    for (const e of scopedEnrollments) {
        const student = studentMap.get(e.student_id);
        if (!student) {
            missingRows.push({
                enrollment_id: e.id,
                student_id: e.student_id,
                student_name: '(no encontrado en students)',
                rut: '',
                email: '',
                course: coursesMap.get(e.course_id) || '(curso no encontrado)',
                status: e.status,
                completed_at: e.completed_at || '',
                best_score: e.best_score ?? ''
            });
            continue;
        }

        if (isMissingSignature(student.digital_signature_url)) {
            missingRows.push({
                enrollment_id: e.id,
                student_id: student.id,
                student_name: formatName(student),
                rut: student.rut || '',
                email: student.email || '',
                course: coursesMap.get(e.course_id) || '(curso no encontrado)',
                status: e.status,
                completed_at: e.completed_at || '',
                best_score: e.best_score ?? ''
            });
        }
    }

    const missingUniqueStudents = new Set(missingRows.map((r) => r.student_id)).size;

    console.log('Resumen');
    console.log('- Enrollments analizados:', scopedEnrollments.length);
    console.log('- Alumnos unicos analizados:', studentIds.length);
    console.log('- Enrollments sin firma:', missingRows.length);
    console.log('- Alumnos unicos sin firma:', missingUniqueStudents);
    console.log('');

    if (!missingRows.length) {
        console.log('OK: no se detectaron alumnos sin firma en el alcance seleccionado.');
        return;
    }

    console.log('Detalle (max 100 filas)');
    console.table(
        missingRows.slice(0, 100).map((r) => ({
            student_name: r.student_name,
            rut: r.rut,
            email: r.email,
            course: r.course,
            status: r.status,
            completed_at: r.completed_at,
            best_score: r.best_score,
            enrollment_id: r.enrollment_id
        }))
    );

    if (missingRows.length > 100) {
        console.log(`Se omitieron ${missingRows.length - 100} filas adicionales.`);
    }

    process.exitCode = 2;
}

main().catch((err) => {
    console.error('Error ejecutando audit:', err.message || err);
    process.exit(1);
});
