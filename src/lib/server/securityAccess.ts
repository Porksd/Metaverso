import { createClient } from '@supabase/supabase-js';
import nodemailer from 'nodemailer';

type SecurityEventType =
  | 'login_success'
  | 'login_denied'
  | 'logout'
  | 'permission_change'
  | 'user_created'
  | 'user_deleted'
  | 'password_changed'
  | 'manual_review';

type GeoData = {
  country?: string | null;
  region?: string | null;
  city?: string | null;
  comuna?: string | null;
};

type LogSecurityEventInput = {
  request: Request;
  eventType: SecurityEventType;
  action: string;
  actorUserId?: string | null;
  actorEmail?: string | null;
  targetEmail?: string | null;
  roleAtTime?: string | null;
  allowed?: boolean;
  reason?: string | null;
  metadata?: Record<string, unknown>;
};

type SendDailySecurityReportInput = {
  startAt?: string;
  endAt?: string;
  recipients?: string[];
};

function getSupabaseAdminClient() {
  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const serviceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

  if (!supabaseUrl || !serviceRoleKey) {
    throw new Error('Faltan credenciales Supabase para seguridad.');
  }

  return createClient(supabaseUrl, serviceRoleKey, {
    auth: { autoRefreshToken: false, persistSession: false }
  });
}

function getHeader(request: Request, name: string): string {
  return request.headers.get(name) || '';
}

export function getClientIpAddress(request: Request): string | null {
  const candidates = [
    getHeader(request, 'x-vercel-forwarded-for'),
    getHeader(request, 'x-forwarded-for'),
    getHeader(request, 'cf-connecting-ip'),
    getHeader(request, 'x-real-ip')
  ];

  for (const value of candidates) {
    if (!value) continue;
    const ip = value.split(',')[0].trim();
    if (ip) return ip;
  }

  return null;
}

function isPrivateIp(ip: string): boolean {
  const normalized = ip.trim().toLowerCase();
  if (!normalized) return true;
  if (normalized === '::1' || normalized === 'localhost') return true;
  if (normalized.startsWith('10.')) return true;
  if (normalized.startsWith('127.')) return true;
  if (normalized.startsWith('192.168.')) return true;
  if (/^172\.(1[6-9]|2[0-9]|3[0-1])\./.test(normalized)) return true;
  if (normalized.startsWith('fc') || normalized.startsWith('fd')) return true;
  return false;
}

async function resolveGeoFromIp(ipAddress: string | null): Promise<GeoData> {
  if (!ipAddress || isPrivateIp(ipAddress)) {
    return {};
  }

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 1500);

  try {
    const response = await fetch(`https://ipapi.co/${encodeURIComponent(ipAddress)}/json/`, {
      method: 'GET',
      cache: 'no-store',
      signal: controller.signal
    });

    if (!response.ok) return {};

    const payload = (await response.json()) as Record<string, unknown>;
    const comuna = typeof payload.region === 'string' ? payload.region : null;

    return {
      country: typeof payload.country_name === 'string' ? payload.country_name : null,
      region: typeof payload.region === 'string' ? payload.region : null,
      city: typeof payload.city === 'string' ? payload.city : null,
      comuna
    };
  } catch {
    return {};
  } finally {
    clearTimeout(timeout);
  }
}

function parseEmailList(rawValue: string | null | undefined): string[] {
  return (rawValue || '')
    .split(',')
    .map((value) => value.trim().toLowerCase())
    .filter((value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value));
}

async function getReportRecipients(explicitRecipients?: string[]): Promise<string[]> {
  if (explicitRecipients && explicitRecipients.length > 0) {
    return parseEmailList(explicitRecipients.join(','));
  }

  const supabase = getSupabaseAdminClient();
  const { data, error } = await supabase
    .from('security_report_recipients')
    .select('email')
    .eq('active', true)
    .order('created_at', { ascending: true });

  if (!error && data && data.length > 0) {
    return data
      .map((row) => (row.email || '').toLowerCase().trim())
      .filter((email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
  }

  return parseEmailList(process.env.SECURITY_REPORT_RECIPIENTS);
}

function buildHtmlReport(params: {
  generatedAt: Date;
  startAt: Date;
  endAt: Date;
  totalEvents: number;
  totalAllowed: number;
  totalDenied: number;
  uniqueActors: number;
  topActions: Array<{ action: string; total: number }>;
  topIps: Array<{ ip: string; total: number }>;
}) {
  const {
    generatedAt,
    startAt,
    endAt,
    totalEvents,
    totalAllowed,
    totalDenied,
    uniqueActors,
    topActions,
    topIps
  } = params;

  const actionRows = topActions.length > 0
    ? topActions.map((row) => `<tr><td>${row.action}</td><td style=\"text-align:right\">${row.total}</td></tr>`).join('')
    : '<tr><td colspan="2">Sin acciones registradas</td></tr>';

  const ipRows = topIps.length > 0
    ? topIps.map((row) => `<tr><td>${row.ip}</td><td style=\"text-align:right\">${row.total}</td></tr>`).join('')
    : '<tr><td colspan="2">Sin IPs registradas</td></tr>';

  return `
    <div style="font-family:Arial,sans-serif;line-height:1.45;color:#111">
      <h2 style="margin-bottom:4px">Informe Diario de Seguridad</h2>
      <p style="margin-top:0;color:#555">Generado: ${generatedAt.toLocaleString('es-CL')}</p>
      <p><strong>Ventana:</strong> ${startAt.toLocaleString('es-CL')} - ${endAt.toLocaleString('es-CL')}</p>
      <ul>
        <li>Total eventos: <strong>${totalEvents}</strong></li>
        <li>Accesos permitidos: <strong>${totalAllowed}</strong></li>
        <li>Accesos denegados: <strong>${totalDenied}</strong></li>
        <li>Actores unicos: <strong>${uniqueActors}</strong></li>
      </ul>
      <h3>Top acciones</h3>
      <table style="border-collapse:collapse;min-width:320px" border="1" cellpadding="6">
        <thead><tr><th>Accion</th><th>Total</th></tr></thead>
        <tbody>${actionRows}</tbody>
      </table>
      <h3>Top IPs</h3>
      <table style="border-collapse:collapse;min-width:320px" border="1" cellpadding="6">
        <thead><tr><th>IP</th><th>Total</th></tr></thead>
        <tbody>${ipRows}</tbody>
      </table>
    </div>
  `;
}

export async function logSecurityEvent(input: LogSecurityEventInput): Promise<void> {
  const supabase = getSupabaseAdminClient();
  const ipAddress = getClientIpAddress(input.request);
  const userAgent = getHeader(input.request, 'user-agent') || null;
  const geo = await resolveGeoFromIp(ipAddress);

  const { error } = await supabase
    .from('security_access_events')
    .insert({
      event_type: input.eventType,
      action: input.action,
      actor_user_id: input.actorUserId || null,
      actor_email: (input.actorEmail || null),
      target_email: (input.targetEmail || null),
      role_at_time: input.roleAtTime || null,
      allowed: input.allowed !== false,
      reason: input.reason || null,
      ip_address: ipAddress,
      user_agent: userAgent,
      path: new URL(input.request.url).pathname,
      geo_country: geo.country || null,
      geo_region: geo.region || null,
      geo_city: geo.city || null,
      geo_comuna: geo.comuna || null,
      metadata: input.metadata || {}
    });

  if (error) {
    throw new Error(`No se pudo guardar security_access_events: ${error.message}`);
  }
}

export async function sendDailySecurityReport(input: SendDailySecurityReportInput = {}) {
  const supabase = getSupabaseAdminClient();

  const endAt = input.endAt ? new Date(input.endAt) : new Date();
  const startAt = input.startAt ? new Date(input.startAt) : new Date(endAt.getTime() - (24 * 60 * 60 * 1000));

  if (Number.isNaN(startAt.getTime()) || Number.isNaN(endAt.getTime())) {
    throw new Error('Fechas invalidas para el reporte de seguridad.');
  }

  const { data: events, error } = await supabase
    .from('security_access_events')
    .select('created_at,event_type,action,actor_email,allowed,ip_address')
    .gte('created_at', startAt.toISOString())
    .lt('created_at', endAt.toISOString())
    .order('created_at', { ascending: false });

  if (error) {
    throw new Error(`No se pudo cargar eventos de seguridad: ${error.message}`);
  }

  const rows = events || [];
  const totalEvents = rows.length;
  const totalAllowed = rows.filter((row) => row.allowed === true).length;
  const totalDenied = rows.filter((row) => row.allowed === false).length;
  const uniqueActors = new Set(rows.map((row) => (row.actor_email || '').toLowerCase()).filter(Boolean)).size;

  const actionMap = new Map<string, number>();
  const ipMap = new Map<string, number>();

  for (const row of rows) {
    const action = row.action || 'unknown';
    actionMap.set(action, (actionMap.get(action) || 0) + 1);

    const ip = row.ip_address || 'unknown';
    ipMap.set(ip, (ipMap.get(ip) || 0) + 1);
  }

  const topActions = Array.from(actionMap.entries())
    .map(([action, total]) => ({ action, total }))
    .sort((a, b) => b.total - a.total)
    .slice(0, 10);

  const topIps = Array.from(ipMap.entries())
    .map(([ip, total]) => ({ ip, total }))
    .sort((a, b) => b.total - a.total)
    .slice(0, 10);

  const recipients = await getReportRecipients(input.recipients);
  if (recipients.length === 0) {
    return {
      sent: false,
      recipients: [],
      reason: 'No hay destinatarios configurados',
      totalEvents,
      totalAllowed,
      totalDenied,
      uniqueActors
    };
  }

  const smtpHost = process.env.SMTP_HOST;
  const smtpPort = Number(process.env.SMTP_PORT || 587);
  const smtpUser = process.env.SMTP_USER;
  const smtpPass = process.env.SMTP_PASS;
  const smtpSecure = process.env.SMTP_SECURE === 'true' || smtpPort === 465;
  const smtpFrom = process.env.SMTP_FROM || smtpUser;

  if (!smtpHost || !smtpFrom) {
    throw new Error('Faltan SMTP_HOST y/o SMTP_FROM para enviar el reporte de seguridad.');
  }

  const transporter = nodemailer.createTransport({
    host: smtpHost,
    port: smtpPort,
    secure: smtpSecure,
    auth: smtpUser ? { user: smtpUser, pass: smtpPass } : undefined
  });

  const generatedAt = new Date();
  const html = buildHtmlReport({
    generatedAt,
    startAt,
    endAt,
    totalEvents,
    totalAllowed,
    totalDenied,
    uniqueActors,
    topActions,
    topIps
  });

  await transporter.sendMail({
    from: smtpFrom,
    to: recipients.join(','),
    subject: `[Seguridad] Informe diario ${generatedAt.toLocaleDateString('es-CL')}`,
    html
  });

  return {
    sent: true,
    recipients,
    totalEvents,
    totalAllowed,
    totalDenied,
    uniqueActors,
    topActions,
    topIps,
    startAt: startAt.toISOString(),
    endAt: endAt.toISOString()
  };
}
