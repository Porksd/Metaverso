import { createClient } from '@supabase/supabase-js';
import { NextResponse } from 'next/server';
import { resolveAdminRole } from '@/lib/adminAuth';
import { sendDailySecurityReport } from '@/lib/server/securityAccess';

type BodyPayload = {
  startAt?: string;
  endAt?: string;
  recipients?: string[];
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

function sanitizeRecipients(input: unknown): string[] {
  if (!Array.isArray(input)) return [];
  return input
    .map((value) => (typeof value === 'string' ? value.trim().toLowerCase() : ''))
    .filter((value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value));
}

export async function POST(req: Request) {
  try {
    const customCronSecret = process.env.SECURITY_REPORT_CRON_SECRET || '';
    const vercelCronSecret = process.env.CRON_SECRET || '';
    const authHeader = req.headers.get('authorization') || req.headers.get('Authorization') || '';
    const bearerToken = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : '';
    const hasCronAccess = (
      (customCronSecret.length > 0 && bearerToken === customCronSecret)
      || (vercelCronSecret.length > 0 && bearerToken === vercelCronSecret)
    );

    if (!hasCronAccess) {
      const { supabaseAdmin, supabaseAuthVerifier } = getSupabaseClients();
      const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : '';

      if (!token) {
        return NextResponse.json({ error: 'No autorizado.' }, { status: 401 });
      }

      const { data: userData, error: tokenError } = await supabaseAuthVerifier.auth.getUser(token);
      if (tokenError || !userData?.user?.email) {
        return NextResponse.json({ error: 'Sesion invalida.' }, { status: 401 });
      }

      const requesterEmail = userData.user.email.toLowerCase();
      const { role } = await resolveAdminRole(supabaseAdmin, requesterEmail, 'api/security/reports/daily');
      if (role !== 'superadmin') {
        return NextResponse.json({ error: 'Solo superadmin puede enviar reportes de seguridad.' }, { status: 403 });
      }
    }

    const body = (await req.json().catch(() => ({}))) as BodyPayload;

    const result = await sendDailySecurityReport({
      startAt: body.startAt,
      endAt: body.endAt,
      recipients: sanitizeRecipients(body.recipients)
    });

    return NextResponse.json({ ok: true, result });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

export async function GET(req: Request) {
  try {
    const customCronSecret = process.env.SECURITY_REPORT_CRON_SECRET || '';
    const vercelCronSecret = process.env.CRON_SECRET || '';
    const authHeader = req.headers.get('authorization') || req.headers.get('Authorization') || '';
    const bearerToken = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : '';

    const hasCronAccess = (
      (customCronSecret.length > 0 && bearerToken === customCronSecret)
      || (vercelCronSecret.length > 0 && bearerToken === vercelCronSecret)
    );

    if (!hasCronAccess) {
      return NextResponse.json({ error: 'No autorizado.' }, { status: 401 });
    }

    const result = await sendDailySecurityReport();
    return NextResponse.json({ ok: true, result });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
