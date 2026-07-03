import { NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';
import { resolveAdminRole } from '@/lib/adminAuth';
import { sendCompanyProgressReport } from '@/lib/server/companyProgressReport';

type BodyPayload = {
  companyId?: string;
  force?: boolean;
  overrides?: {
    includeStudents?: boolean;
    includePdfAttachment?: boolean;
    copyEmails?: string | null;
  };
};

function getSupabaseClients() {
  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
  const serviceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

  if (!supabaseUrl || !supabaseAnonKey || !serviceRoleKey) {
    throw new Error('Configuracion de Supabase incompleta.');
  }

  const supabaseAuthVerifier = createClient(supabaseUrl, supabaseAnonKey, {
    auth: { autoRefreshToken: false, persistSession: false }
  });

  const supabaseAdmin = createClient(supabaseUrl, serviceRoleKey, {
    auth: { autoRefreshToken: false, persistSession: false }
  });

  return { supabaseAuthVerifier, supabaseAdmin };
}

export async function POST(req: Request) {
  try {
    const { supabaseAdmin, supabaseAuthVerifier } = getSupabaseClients();

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
    const { role } = await resolveAdminRole(supabaseAdmin, requesterEmail, 'api/report-send');
    if (!role) {
      return NextResponse.json({ error: 'No autorizado.' }, { status: 403 });
    }

    const body = (await req.json()) as BodyPayload;
    const companyId = (body.companyId || '').trim();
    const force = body.force === true;
    const overrides = body.overrides || {};

    if (!companyId) {
      return NextResponse.json({ error: 'companyId es obligatorio.' }, { status: 400 });
    }

    const result = await sendCompanyProgressReport(companyId, {
      force,
      overrides: {
        includeStudents: typeof overrides.includeStudents === 'boolean' ? overrides.includeStudents : undefined,
        includePdfAttachment: typeof overrides.includePdfAttachment === 'boolean' ? overrides.includePdfAttachment : undefined,
        copyEmails: typeof overrides.copyEmails === 'string' || overrides.copyEmails === null ? overrides.copyEmails : undefined
      }
    });
    return NextResponse.json({ ok: true, result });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
