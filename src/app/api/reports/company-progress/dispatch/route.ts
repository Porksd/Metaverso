import { NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';
import { resolveAdminRole } from '@/lib/adminAuth';
import { dispatchCompanyProgressReports } from '@/lib/server/companyProgressReport';

type BodyPayload = {
  force?: boolean;
  companyId?: string;
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

async function hasApiAccess(req: Request): Promise<{ ok: boolean; reason?: string }> {
  const cronSecret = process.env.REPORTS_CRON_SECRET;
  const providedSecret = req.headers.get('x-cron-secret') || '';

  if (cronSecret && providedSecret && providedSecret === cronSecret) {
    return { ok: true };
  }

  const { supabaseAdmin, supabaseAuthVerifier } = getSupabaseClients();
  const authHeader = req.headers.get('authorization') || req.headers.get('Authorization') || '';
  const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : '';

  if (!token) {
    return { ok: false, reason: 'No autorizado.' };
  }

  const { data: userData, error: tokenError } = await supabaseAuthVerifier.auth.getUser(token);
  if (tokenError || !userData?.user?.email) {
    return { ok: false, reason: 'Sesion invalida.' };
  }

  const requesterEmail = userData.user.email.toLowerCase();
  const { role } = await resolveAdminRole(supabaseAdmin, requesterEmail, 'api/report-dispatch');
  if (!role) {
    return { ok: false, reason: 'No autorizado.' };
  }

  return { ok: true };
}

export async function POST(req: Request) {
  try {
    const access = await hasApiAccess(req);
    if (!access.ok) {
      return NextResponse.json({ error: access.reason || 'No autorizado.' }, { status: 401 });
    }

    const body = (await req.json().catch(() => ({}))) as BodyPayload;
    const force = body.force === true;
    const companyId = body.companyId?.trim() || undefined;

    const result = await dispatchCompanyProgressReports({ force, companyId });
    return NextResponse.json({ ok: true, ...result });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
