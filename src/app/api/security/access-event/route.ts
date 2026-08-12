import { createClient } from '@supabase/supabase-js';
import { NextResponse } from 'next/server';
import { resolveAdminRole } from '@/lib/adminAuth';
import { logSecurityEvent } from '@/lib/server/securityAccess';

type BodyPayload = {
  eventType?:
    | 'login_success'
    | 'login_denied'
    | 'logout'
    | 'permission_change'
    | 'user_created'
    | 'user_deleted'
    | 'password_changed'
    | 'manual_review';
  action?: string;
  targetEmail?: string;
  allowed?: boolean;
  reason?: string;
  metadata?: Record<string, unknown>;
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

    const body = (await req.json()) as BodyPayload;
    const eventType = body.eventType || 'manual_review';
    const action = (body.action || '').trim();

    if (!action) {
      return NextResponse.json({ error: 'action es obligatorio.' }, { status: 400 });
    }

    const actorEmail = userData.user.email.toLowerCase();
    const { role } = await resolveAdminRole(supabaseAdmin, actorEmail, 'api/security/access-event');

    await logSecurityEvent({
      request: req,
      eventType,
      action,
      actorUserId: userData.user.id,
      actorEmail,
      targetEmail: body.targetEmail || null,
      roleAtTime: role,
      allowed: body.allowed !== false,
      reason: body.reason || null,
      metadata: body.metadata || {}
    });

    return NextResponse.json({ ok: true });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
