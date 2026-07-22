import { NextRequest, NextResponse } from 'next/server';
import { supabaseAdmin } from '@/lib/supabase';

const DUPLICATE_EMAIL_MESSAGE = 'Ya existe una cuenta registrada con este correo. Inicia sesion o recupera tu contrasena.';
const DUPLICATE_RUT_MESSAGE = 'Ya existe un alumno registrado con este RUT en esta empresa.';

/** Deja solo dígitos+K — usado para comparación de unicidad */
const normalizeRut = (value: string) => value.replace(/[^0-9kK]/g, '').toUpperCase();

/** Convierte cualquier RUT al formato chileno estándar con guión: 12345678-9 */
const formatRutCl = (clean: string): string => {
  if (clean.length < 2) return clean;
  const body = clean.slice(0, -1);
  const dv   = clean.slice(-1);
  return `${body}-${dv}`;
};

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
            client_id, // Add support for direct company linking
            role_id,   // Support for company-specific roles
            position,
            digital_signature_url,
            language = 'es'
        } = body;

        const normalizedClientId = typeof client_id === 'string' ? client_id.trim() : '';

        const normalizedEmail = typeof email === 'string' ? email.trim().toLowerCase() : '';
        const normalizedRut = typeof rut === 'string' ? normalizeRut(rut.trim()) : '';
        const normalizedPassport = typeof passport === 'string' ? passport.trim() : '';

        if (!supabaseAdmin) {
            return NextResponse.json(
                { error: 'Error de configuración: supabaseAdmin no disponible' },
                { status: 500 }
            );
        }

        const admin = supabaseAdmin;

        // Validate required fields
        if (!normalizedEmail || !password || !first_name || !last_name) {
            return NextResponse.json(
                { error: 'Campos requeridos: email, password, first_name, last_name' },
                { status: 400 }
            );
        }

        // 1. Create or Update Supabase Auth User
        // Usamos admin para crear el usuario y confirmar el email inmediatamente.

        let emailQuery = admin
            .from('students')
            .select('id')
            .eq('email', normalizedEmail);

        if (normalizedClientId) {
            emailQuery = emailQuery.eq('client_id', normalizedClientId);
        }

        const { data: existingStudentByEmail, error: existingStudentByEmailError } = await emailQuery.maybeSingle();

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

        // RUT can repeat across different companies, but must be unique inside the same company.
        if (normalizedRut && normalizedClientId) {
            const { data: sameCompanyStudents, error: sameCompanyStudentsError } = await admin
                .from('students')
                .select('id, rut')
                .eq('client_id', normalizedClientId)
                .not('rut', 'is', null);

            if (sameCompanyStudentsError && !isMissingRowError(sameCompanyStudentsError.message)) {
                console.error('Error checking existing student by RUT:', sameCompanyStudentsError);
                return NextResponse.json(
                    { error: 'No se pudo validar el RUT antes del registro' },
                    { status: 500 }
                );
            }

            const rutAlreadyExistsInCompany = (sameCompanyStudents || []).some((student) => {
                const studentRut = typeof student.rut === 'string' ? normalizeRut(student.rut) : '';
                return studentRut && studentRut === normalizedRut;
            });

            if (rutAlreadyExistsInCompany) {
                return NextResponse.json({ error: DUPLICATE_RUT_MESSAGE }, { status: 409 });
            }
        }

        const { data: authUsersData, error: authUsersError } = await admin.auth.admin.listUsers({
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

        let authUser;
        let createdAuthUser = false;
        const existingAuthUser = authUsersData.users.find((user) => user.email?.toLowerCase() === normalizedEmail);

        if (existingAuthUser) {
            const { data: updatedAuthData, error: updateAuthError } = await admin.auth.admin.updateUserById(
                existingAuthUser.id,
                {
                    password,
                    email_confirm: true,
                    user_metadata: {
                        full_name: `${first_name} ${last_name}`,
                        language
                    }
                }
            );

            if (updateAuthError) {
                return NextResponse.json({ error: updateAuthError.message }, { status: 400 });
            }

            authUser = updatedAuthData.user;
        } else {
            const { data: createData, error: createError } = await admin.auth.admin.createUser({
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
                    const refreshedList = await admin.auth.admin.listUsers({ page: 1, perPage: 1000 });
                    if (refreshedList.error) {
                        return NextResponse.json({ error: refreshedList.error.message }, { status: 500 });
                    }

                    const raceAuthUser = refreshedList.data.users.find((user) => user.email?.toLowerCase() === normalizedEmail);
                    if (!raceAuthUser) {
                        return NextResponse.json({ error: DUPLICATE_EMAIL_MESSAGE }, { status: 409 });
                    }

                    const { data: updatedAuthData, error: updateAuthError } = await admin.auth.admin.updateUserById(
                        raceAuthUser.id,
                        {
                            password,
                            email_confirm: true,
                            user_metadata: {
                                full_name: `${first_name} ${last_name}`,
                                language
                            }
                        }
                    );

                    if (updateAuthError) {
                        return NextResponse.json({ error: updateAuthError.message }, { status: 400 });
                    }

                    authUser = updatedAuthData.user;
                } else {
                    return NextResponse.json({ error: createError.message }, { status: 400 });
                }
            } else {
                authUser = createData.user;
                createdAuthUser = true;
            }
        }

        if (!authUser) {
            return NextResponse.json({ error: 'Error al gestionar usuario de autenticación' }, { status: 400 });
        }

        // 2. Create Student Profile
        const { data: student, error: studentError } = await admin
            .from('students')
            .insert({
                auth_user_id: authUser.id,
                rut: normalizedRut ? formatRutCl(normalizedRut) : null,
                passport: normalizedPassport || null,
                first_name,
                last_name,
                email: normalizedEmail,
                password, // Store password for corporate login bypass
                gender: gender || null,
                age: age || null,
                client_id: normalizedClientId || null, // Insert FK
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
                await admin.auth.admin.deleteUser(authUser.id);
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

        // Auto-assign Sacyr IRL if student belongs to Sacyr and has a matching role
        if (normalizedClientId === '3024ab6f-8b7f-4735-992e-f6442d26b09b' && role_id && student?.id) {
            const { data: roleRow } = await admin.from('company_roles').select('name').eq('id', role_id).single();
            const roleName = (roleRow?.name || '').toLowerCase().trim();
            const CARGO_MAP: Record<string, string> = {
                'administrador de contrato':'administrador_de_contrato','ayudante':'ayudante','capataz':'capataz',
                'carpintero obra civiles':'carpintero_obra_civiles','carpintero og':'carpintero_og',
                'carpintero terminaciones':'carpintero_terminaciones','electricista':'electricista',
                'enfierrador':'enfierrador','equipo de topograf\u00eda':'equipo_de_topografia',
                'guardia':'guardia','jefe de terreno':'jefe_de_terreno',
                'maestro de instalaciones':'maestro_de_instalaciones','monitor de seguridad':'monitor_de_seguridad',
                'operador de maquinaria pesada':'operador_de_maquinaria_pesada','operador gt':'operador_gt',
                'operador montacargas':'operador_montacargas','operador de montacargas':'operador_montacargas',
                'personal de aseo':'personal_de_aseo','auxiliar de aseo':'personal_de_aseo',
                'prevencionista de riesgos':'prevencionista_de_riesgos','profesional':'profesional',
                'rigger - se\u00f1alero':'rigger_se\u00f1alero','sanitario':'sanitario',
                'soldador':'soldador','supervisor':'supervisor','visitas':'visitas'
            };
            const slug = CARGO_MAP[roleName];
            if (slug) {
                const { data: form } = await admin.from('sacyr_irl_forms').select('id').eq('slug', slug).single();
                if (form?.id) {
                    await admin.from('sacyr_irl_assignments').upsert({
                        student_id: student.id, form_id: form!.id,
                        company_id: normalizedClientId, assigned_by: 'auto', status: 'pending'
                    }, { onConflict: 'student_id,form_id', ignoreDuplicates: true });
                }
            }
        }

        return NextResponse.json({
            success: true,
            student,
            message: 'Estudiante registrado exitosamente'
        });

        // Auto-assign Sacyr IRL if student belongs to Sacyr and has a matching role
        // (fire-and-forget, does not block the response)
        if (normalizedClientId === '3024ab6f-8b7f-4735-992e-f6442d26b09b' && role_id && student?.id) {
            const { data: roleRow } = await admin.from('company_roles').select('name').eq('id', role_id).single();
            const roleName = (roleRow?.name || '').toLowerCase().trim();
            const CARGO_MAP: Record<string, string> = {
                'administrador de contrato':'administrador_de_contrato','ayudante':'ayudante','capataz':'capataz',
                'carpintero obra civiles':'carpintero_obra_civiles','carpintero og':'carpintero_og',
                'carpintero terminaciones':'carpintero_terminaciones','electricista':'electricista',
                'enfierrador':'enfierrador','equipo de topograf\u00eda':'equipo_de_topografia',
                'guardia':'guardia','jefe de terreno':'jefe_de_terreno',
                'maestro de instalaciones':'maestro_de_instalaciones','monitor de seguridad':'monitor_de_seguridad',
                'operador de maquinaria pesada':'operador_de_maquinaria_pesada','operador gt':'operador_gt',
                'operador montacargas':'operador_montacargas','operador de montacargas':'operador_montacargas',
                'personal de aseo':'personal_de_aseo','auxiliar de aseo':'personal_de_aseo',
                'prevencionista de riesgos':'prevencionista_de_riesgos','profesional':'profesional',
                'rigger - se\u00f1alero':'rigger_se\u00f1alero','sanitario':'sanitario',
                'soldador':'soldador','supervisor':'supervisor','visitas':'visitas'
            };
            const slug = CARGO_MAP[roleName];
            if (slug) {
                const { data: form } = await admin.from('sacyr_irl_forms').select('id').eq('slug', slug).single();
                if (form?.id) {
                    await admin.from('sacyr_irl_assignments').upsert({
                        student_id: student.id, form_id: form!.id,
                        company_id: normalizedClientId, assigned_by: 'auto', status: 'pending'
                    }, { onConflict: 'student_id,form_id', ignoreDuplicates: true });
                }
            }
        }

    } catch (error) {
        console.error('Registration error:', error);
        return NextResponse.json(
            { error: 'Error interno del servidor' },
            { status: 500 }
        );
    }
}
