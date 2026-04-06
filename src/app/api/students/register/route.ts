import { NextRequest, NextResponse } from 'next/server';
import { supabaseAdmin } from '@/lib/supabase';

const DUPLICATE_EMAIL_MESSAGE = 'Ya existe una cuenta registrada con este correo. Inicia sesion o recupera tu contrasena.';
const DUPLICATE_DOCUMENT_MESSAGE = 'Ya existe un alumno registrado con este RUT o pasaporte.';

const isMissingRowError = (message?: string) => {
    if (!message) {
        return false;
    }

    const normalizedMessage = message.toLowerCase();
    return normalizedMessage.includes('0 rows') || normalizedMessage.includes('no rows');
};

export async function POST(request: NextRequest) {
    try {
        const body = await request.json();
        const {
            email,
            password,
            rut,
            passport,
            first_name,
            last_name,
            gender,
            age,
            company_name,
            client_id, // Add support for direct company linking
            role_id,   // Support for company-specific roles
            position,
            digital_signature_url,
            language = 'es'
        } = body;

        const normalizedEmail = typeof email === 'string' ? email.trim().toLowerCase() : '';
        const normalizedRut = typeof rut === 'string' ? rut.trim() : '';
        const normalizedPassport = typeof passport === 'string' ? passport.trim() : '';

        // Validate required fields
        if (!normalizedEmail || !password || !first_name || !last_name) {
            return NextResponse.json(
                { error: 'Campos requeridos: email, password, first_name, last_name' },
                { status: 400 }
            );
        }

        // Must have either RUT or Passport
        if (!normalizedRut && !normalizedPassport) {
            return NextResponse.json(
                { error: 'Debe proporcionar RUT o Pasaporte' },
                { status: 400 }
            );
        }

        // 1. Create or Update Supabase Auth User
        // Usamos supabaseAdmin para crear el usuario y confirmar el email inmediatamente.
        if (!supabaseAdmin) {
            return NextResponse.json(
                { error: 'Error de configuración: supabaseAdmin no disponible' },
                { status: 500 }
            );
        }

        const { data: existingStudentByEmail, error: existingStudentByEmailError } = await supabaseAdmin
            .from('students')
            .select('id')
            .eq('email', normalizedEmail)
            .maybeSingle();

        if (existingStudentByEmailError && !isMissingRowError(existingStudentByEmailError.message)) {
            console.error('Error checking existing student by email:', existingStudentByEmailError);
            return NextResponse.json(
                { error: 'No se pudo validar el correo antes del registro' },
                { status: 500 }
            );
        }

        if (existingStudentByEmail) {
            return NextResponse.json({ error: DUPLICATE_EMAIL_MESSAGE }, { status: 409 });
        }

        if (normalizedRut) {
            const { data: existingStudentByRut, error: existingStudentByRutError } = await supabaseAdmin
                .from('students')
                .select('id')
                .eq('rut', normalizedRut)
                .maybeSingle();

            if (existingStudentByRutError && !isMissingRowError(existingStudentByRutError.message)) {
                console.error('Error checking existing student by RUT:', existingStudentByRutError);
                return NextResponse.json(
                    { error: 'No se pudo validar el RUT antes del registro' },
                    { status: 500 }
                );
            }

            if (existingStudentByRut) {
                return NextResponse.json({ error: DUPLICATE_DOCUMENT_MESSAGE }, { status: 409 });
            }
        }

        if (normalizedPassport) {
            const { data: existingStudentByPassport, error: existingStudentByPassportError } = await supabaseAdmin
                .from('students')
                .select('id')
                .eq('passport', normalizedPassport)
                .maybeSingle();

            if (existingStudentByPassportError && !isMissingRowError(existingStudentByPassportError.message)) {
                console.error('Error checking existing student by passport:', existingStudentByPassportError);
                return NextResponse.json(
                    { error: 'No se pudo validar el pasaporte antes del registro' },
                    { status: 500 }
                );
            }

            if (existingStudentByPassport) {
                return NextResponse.json({ error: DUPLICATE_DOCUMENT_MESSAGE }, { status: 409 });
            }
        }

        const { data: authUsersData, error: authUsersError } = await supabaseAdmin.auth.admin.listUsers({
            page: 1,
            perPage: 1000
        });

        if (authUsersError) {
            console.error('Error checking existing auth users:', authUsersError);
            return NextResponse.json(
                { error: 'No se pudo validar el correo en autenticacion' },
                { status: 500 }
            );
        }

        const existingAuthUser = authUsersData.users.find((user) => user.email?.toLowerCase() === normalizedEmail);
        if (existingAuthUser) {
            return NextResponse.json({ error: DUPLICATE_EMAIL_MESSAGE }, { status: 409 });
        }

        let authUser;
        let createdAuthUser = false;
        const { data: createData, error: createError } = await supabaseAdmin.auth.admin.createUser({
            email: normalizedEmail,
            password,
            email_confirm: true,
            user_metadata: {
                full_name: `${first_name} ${last_name}`,
                language
            }
        });

        if (createError) {
            if (createError.message.includes('already registered') || createError.message.includes('already exists')) {
                return NextResponse.json({ error: DUPLICATE_EMAIL_MESSAGE }, { status: 409 });
            }

            return NextResponse.json({ error: createError.message }, { status: 400 });
        } else {
            authUser = createData.user;
            createdAuthUser = true;
        }

        if (!authUser) {
            return NextResponse.json({ error: 'Error al gestionar usuario de autenticación' }, { status: 400 });
        }

        // 2. Create Student Profile
        const { data: student, error: studentError } = await supabaseAdmin
            .from('students')
            .insert({
                auth_user_id: authUser.id,
                rut: normalizedRut || null,
                passport: normalizedPassport || null,
                first_name,
                last_name,
                email: normalizedEmail,
                password, // Store password for corporate login bypass
                gender: gender || null,
                age: age || null,
                company_name: company_name || null,
                client_id: client_id || null, // Insert FK
                role_id: role_id || null,     // Specific role FK
                position: position || null,
                digital_signature_url: digital_signature_url || null,
                language
            })
            .select()
            .single();

        if (studentError) {
            console.error('Error creating student profile:', studentError);

            if (createdAuthUser && authUser?.id) {
                await supabaseAdmin.auth.admin.deleteUser(authUser.id);
            }

            const normalizedStudentError = studentError.message?.toLowerCase() || '';
            if (normalizedStudentError.includes('duplicate key')) {
                return NextResponse.json({ error: DUPLICATE_EMAIL_MESSAGE }, { status: 409 });
            }

            return NextResponse.json(
                { error: studentError.message || 'Error al crear perfil de estudiante' },
                { status: 500 }
            );
        }

        return NextResponse.json({
            success: true,
            student,
            message: 'Estudiante registrado exitosamente'
        });

    } catch (error) {
        console.error('Registration error:', error);
        return NextResponse.json(
            { error: 'Error interno del servidor' },
            { status: 500 }
        );
    }
}
