import { NextRequest, NextResponse } from 'next/server';
import { supabase, supabaseAdmin } from '@/lib/supabase';

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
            position,
            digital_signature_url,
            language = 'es'
        } = body;

        // Validate required fields
        if (!email || !password || !first_name || !last_name) {
            return NextResponse.json(
                { error: 'Campos requeridos: email, password, first_name, last_name' },
                { status: 400 }
            );
        }

        // Must have either RUT or Passport
        if (!rut && !passport) {
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

        // Primero intentamos crear, si ya existe lo actualizamos para asegurar que esté confirmado
        let authUser;
        const { data: createData, error: createError } = await supabaseAdmin.auth.admin.createUser({
            email,
            password,
            email_confirm: true,
            user_metadata: {
                full_name: `${first_name} ${last_name}`,
                language
            }
        });

        if (createError) {
            if (createError.message.includes('already registered') || createError.message.includes('already exists')) {
                // Si el usuario existe, lo buscamos y lo actualizamos para confirmar su email y setear la clave
                const { data: { users }, error: listError } = await supabaseAdmin.auth.admin.listUsers();
                const existingUser = users?.find(u => u.email === email);
                
                if (existingUser) {
                    const { data: updateData, error: updateError } = await supabaseAdmin.auth.admin.updateUserById(
                        existingUser.id,
                        { 
                            password, 
                            email_confirm: true,
                            user_metadata: {
                                full_name: `${first_name} ${last_name}`,
                                language
                            }
                        }
                    );
                    if (updateError) {
                        return NextResponse.json({ error: updateError.message }, { status: 400 });
                    }
                    authUser = updateData.user;
                } else {
                    return NextResponse.json({ error: 'Usuario ya existe en Auth pero no se pudo encontrar para actualizar' }, { status: 400 });
                }
            } else {
                return NextResponse.json({ error: createError.message }, { status: 400 });
            }
        } else {
            authUser = createData.user;
        }

        if (!authUser) {
            return NextResponse.json({ error: 'Error al gestionar usuario de autenticación' }, { status: 400 });
        }

        // 2. Create or Update Student Profile
        const { data: student, error: studentError } = await supabase
            .from('students')
            .upsert({
                auth_user_id: authUser.id,
                rut: rut || null,
                passport: passport || null,
                first_name,
                last_name,
                email,
                password, // Store password for corporate login bypass
                gender: gender || null,
                age: age || null,
                company_name: company_name || null,
                client_id: client_id || null, // Insert FK
                position: position || null,
                job_position: position || null,
                digital_signature_url: digital_signature_url || null,
                language
            }, { onConflict: 'email' }) // Use email as conflict target if available
            .select()
            .single();

        if (studentError) {
            console.error('Error creating student profile:', studentError);
            // Rollback: delete auth user
            await supabase.auth.admin.deleteUser(authData.user.id);
            return NextResponse.json(
                { error: 'Error al crear perfil de estudiante' },
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
