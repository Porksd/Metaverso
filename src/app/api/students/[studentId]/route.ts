import { NextResponse } from 'next/server';
import { supabaseAdmin } from '@/lib/supabase';

type RouteParams = { studentId: string };

const getStudentId = async (context: { params: RouteParams | Promise<RouteParams> }) => {
    const params = await Promise.resolve(context.params);
    return params.studentId;
};

export async function DELETE(_request: Request, context: { params: RouteParams | Promise<RouteParams> }) {
    try {
        if (!supabaseAdmin) {
            return NextResponse.json({ error: 'Configuracion invalida: supabaseAdmin no disponible' }, { status: 500 });
        }

        const studentId = await getStudentId(context);
        if (!studentId) {
            return NextResponse.json({ error: 'studentId es requerido' }, { status: 400 });
        }

        const { data: student, error: studentLookupError } = await supabaseAdmin
            .from('students')
            .select('id, auth_user_id, rut, client_id')
            .eq('id', studentId)
            .maybeSingle();

        if (studentLookupError) {
            console.error('Error buscando alumno para eliminar:', studentLookupError);
            return NextResponse.json({ error: 'No se pudo buscar el alumno' }, { status: 500 });
        }

        if (!student) {
            return NextResponse.json({ error: 'Alumno no encontrado' }, { status: 404 });
        }

        const { data: enrollments, error: enrollmentsError } = await supabaseAdmin
            .from('enrollments')
            .select('id')
            .eq('student_id', student.id);

        if (enrollmentsError) {
            console.error('Error buscando enrollments del alumno:', enrollmentsError);
            return NextResponse.json({ error: 'No se pudo buscar progreso del alumno' }, { status: 500 });
        }

        const enrollmentIds = (enrollments || []).map((row) => row.id);

        if (enrollmentIds.length > 0) {
            const { error: courseProgressError } = await supabaseAdmin
                .from('course_progress')
                .delete()
                .in('enrollment_id', enrollmentIds);

            if (courseProgressError) {
                console.error('Error eliminando course_progress:', courseProgressError);
                return NextResponse.json({ error: 'No se pudo eliminar avance del curso' }, { status: 500 });
            }

            const { error: activityLogsError } = await supabaseAdmin
                .from('activity_logs')
                .delete()
                .in('enrollment_id', enrollmentIds);

            if (activityLogsError) {
                console.error('Error eliminando activity_logs:', activityLogsError);
                return NextResponse.json({ error: 'No se pudieron eliminar logs de actividad' }, { status: 500 });
            }
        }

        const { error: enrollmentsDeleteError } = await supabaseAdmin
            .from('enrollments')
            .delete()
            .eq('student_id', student.id);

        if (enrollmentsDeleteError) {
            console.error('Error eliminando enrollments:', enrollmentsDeleteError);
            return NextResponse.json({ error: 'No se pudo eliminar la inscripcion del alumno' }, { status: 500 });
        }

        const { error: studentDeleteError } = await supabaseAdmin
            .from('students')
            .delete()
            .eq('id', student.id);

        if (studentDeleteError) {
            console.error('Error eliminando alumno:', studentDeleteError);
            return NextResponse.json({ error: 'No se pudo eliminar el alumno' }, { status: 500 });
        }

        if (student.auth_user_id) {
            const { error: authDeleteError } = await supabaseAdmin.auth.admin.deleteUser(student.auth_user_id);
            if (authDeleteError) {
                console.error('Error eliminando usuario de auth:', authDeleteError);
                // No revertimos porque el alumno ya fue eliminado de negocio.
            }
        }

        return NextResponse.json({
            success: true,
            message: 'Alumno eliminado completamente',
            studentId: student.id,
            rut: student.rut,
            companyId: student.client_id,
            removedEnrollments: enrollmentIds.length
        });
    } catch (error) {
        console.error('DELETE /api/students/[studentId] error:', error);
        return NextResponse.json({ error: 'Error interno del servidor' }, { status: 500 });
    }
}
