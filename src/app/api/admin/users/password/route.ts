import { createClient } from '@supabase/supabase-js';
import { NextResponse } from 'next/server';
import { SUPER_ADMIN_EMAILS } from '@/lib/adminAuth';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!;
const serviceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

const supabaseAdmin = createClient(supabaseUrl, serviceRoleKey, {
  auth: { autoRefreshToken: false, persistSession: false }
});

const supabaseAuthVerifier = createClient(supabaseUrl, supabaseAnonKey, {
  auth: { autoRefreshToken: false, persistSession: false }
});

type UpdatePasswordPayload = {
  email?: string;
  password?: string;
};

const findAuthUserByEmail = async (email: string) => {
  const targetEmail = email.toLowerCase().trim();
  const perPage = 1000;

  for (let page = 1; page <= 50; page++) {
    const { data, error } = await supabaseAdmin.auth.admin.listUsers({
      page,
      perPage
    });

    if (error) {
      return { user: null, error };
    }

    const foundUser = data.users.find((u) => (u.email || '').toLowerCase() === targetEmail);
    if (foundUser) {
      return { user: foundUser, error: null };
    }

    if (data.users.length < perPage) {
      break;
    }
  }

  return { user: null, error: null };
};

const isSuperAdmin = async (email: string): Promise<boolean> => {
  const normalizedEmail = email.toLowerCase().trim();
  if (SUPER_ADMIN_EMAILS.includes(normalizedEmail)) return true;

  const { data: profile, error } = await supabaseAdmin
    .from('admin_profiles')
    .select('role')
    .eq('email', normalizedEmail)
    .maybeSingle();

  if (error) {
    console.warn('No se pudo validar admin_profiles en reset de password:', error.message);
    return false;
  }

  return profile?.role === 'superadmin';
};

export async function POST(req: Request) {
  try {
    if (!supabaseUrl || !supabaseAnonKey || !serviceRoleKey) {
      return NextResponse.json({ error: 'Configuracion incompleta del servidor.' }, { status: 500 });
    }

    const authHeader = req.headers.get('authorization') || req.headers.get('Authorization') || '';
    const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : '';

    if (!token) {
      return NextResponse.json({ error: 'No autorizado.' }, { status: 401 });
    }

    const { data: userData, error: tokenError } = await supabaseAuthVerifier.auth.getUser(token);
    if (tokenError || !userData?.user?.email) {
      return NextResponse.json({ error: 'Sesion invalida.' }, { status: 401 });
    }

    const requesterEmail = userData.user.email.toLowerCase();
    const requesterIsSuperAdmin = await isSuperAdmin(requesterEmail);
    if (!requesterIsSuperAdmin) {
      return NextResponse.json({ error: 'Solo superadmin puede actualizar contrasenas.' }, { status: 403 });
    }

    const body = (await req.json()) as UpdatePasswordPayload;
    const targetEmail = (body.email || '').toLowerCase().trim();
    const newPassword = body.password || '';

    if (!targetEmail || !newPassword) {
      return NextResponse.json({ error: 'Email y password son obligatorios.' }, { status: 400 });
    }

    if (newPassword.length < 6) {
      return NextResponse.json({ error: 'La contrasena debe tener al menos 6 caracteres.' }, { status: 400 });
    }

    const { user: targetUser, error: listError } = await findAuthUserByEmail(targetEmail);
    if (listError) {
      return NextResponse.json({ error: `No se pudo listar usuarios: ${listError.message}` }, { status: 500 });
    }

    if (!targetUser) {
      return NextResponse.json({ error: 'Usuario no encontrado en Supabase Auth.' }, { status: 404 });
    }

    const { error: updateError } = await supabaseAdmin.auth.admin.updateUserById(targetUser.id, {
      password: newPassword,
      email_confirm: true
    });

    if (updateError) {
      return NextResponse.json({ error: `No se pudo actualizar password: ${updateError.message}` }, { status: 500 });
    }

    return NextResponse.json({ success: true });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || 'Error inesperado.' }, { status: 500 });
  }
}
