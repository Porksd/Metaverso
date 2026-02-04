import { NextRequest, NextResponse } from 'next/server';
import { supabase } from '@/lib/supabase';

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

        // 1. Create Supabase Auth User
        const { data: authData, error: authError } = await supabase.auth.signUp({
            email,
            password,
            options: {
                data: {
                    full_name: `${first_name} ${last_name}`,
                    language
                }
            }
        });

        if (authError || !authData.user) {
            return NextResponse.json(
                { error: authError?.message || 'Error al crear usuario' },
                { status: 400 }
            );
        }

        // 2. Create Student Profile
        const { data: student, error: studentError } = await supabase
            .from('students')
            .insert({
                auth_user_id: authData.user.id,
                rut: rut || null,
                passport: passport || null,
                first_name,
                last_name,
                email,
                gender: gender || null,
                age: age || null,
                company_name: company_name || null,
                client_id: client_id || null, // Insert FK
                position: position || null,
                digital_signature_url: digital_signature_url || null,
                language
            })
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
