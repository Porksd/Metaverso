import { randomBytes } from 'crypto';
import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@supabase/supabase-js';

const VALIDITY_DAYS = 30;

function getAdminClient() {
  const url = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const key = process.env.SUPABASE_SERVICE_ROLE_KEY;
  if (!url || !key) throw new Error('Faltan credenciales Supabase.');
  return createClient(url, key, { auth: { autoRefreshToken: false, persistSession: false } });
}

function getBaseUrl() {
  return (process.env.NEXT_PUBLIC_APP_URL || 'https://metaverso-pi.vercel.app').replace(/\/$/, '');
}

export async function POST(req: NextRequest) {
  try {
    const supabase = getAdminClient();
    const contentType = req.headers.get('content-type') || '';

    if (contentType.includes('multipart/form-data')) {
      const form = await req.formData();
      const token = String(form.get('token') || '').trim();
      const overwriteToken = String(form.get('overwriteToken') || '').trim();
      const file = form.get('file');
      if (!token || !(file instanceof File)) {
        return NextResponse.json({ error: 'token y archivo son obligatorios.' }, { status: 400 });
      }

      const { data: certificate, error: certificateError } = await supabase
        .from('report_certificates')
        .select('storage_path, certificate_type')
        .eq('token', token)
        .eq('certificate_type', 'training')
        .maybeSingle();
      if (certificateError) throw certificateError;
      if (!certificate) return NextResponse.json({ error: 'Certificado no encontrado.' }, { status: 404 });

      const { error: uploadError } = await supabase.storage
        .from('report-certificates')
        .upload(certificate.storage_path, Buffer.from(await file.arrayBuffer()), {
          contentType: 'application/pdf',
          upsert: false,
        });
      if (uploadError) throw uploadError;
      if (overwriteToken) {
        const { data: previous } = await supabase
          .from('report_certificates')
          .select('storage_path')
          .eq('token', overwriteToken)
          .eq('certificate_type', 'training')
          .maybeSingle();
        if (previous) {
          await supabase.storage.from('report-certificates').remove([previous.storage_path]);
          await supabase.from('report_certificates').delete().eq('token', overwriteToken);
        }
      }
      return NextResponse.json({ ok: true });
    }

    const body = await req.json();
    const companyId = String(body.companyId || '').trim();
    const overwriteToken = String(body.overwriteToken || '').trim();
    if (!companyId) return NextResponse.json({ error: 'companyId es obligatorio.' }, { status: 400 });

    const { data: active, error: activeError } = await supabase
      .from('report_certificates')
      .select('token, generated_at')
      .eq('company_id', companyId)
      .eq('certificate_type', 'training')
      .gt('expires_at', new Date().toISOString())
      .order('generated_at', { ascending: true });
    if (activeError) throw activeError;
    const oldest = active?.[0];
    if ((active?.length || 0) >= 10 && (!overwriteToken || overwriteToken !== oldest?.token)) {
      return NextResponse.json({
        error: 'Se alcanzó el límite de 10 certificados activos.',
        reason: 'certificate_limit_reached',
        oldestCertificate: oldest ? { token: oldest.token, generatedAt: oldest.generated_at } : null,
      }, { status: 409 });
    }

    const token = randomBytes(32).toString('hex');
    const generatedAt = new Date();
    const expiresAt = new Date(generatedAt.getTime() + VALIDITY_DAYS * 24 * 60 * 60 * 1000);
    const storagePath = `${companyId}/training/${token}.pdf`;
    const { error } = await supabase.from('report_certificates').insert({
      token,
      company_id: companyId,
      storage_path: storagePath,
      generated_at: generatedAt.toISOString(),
      expires_at: expiresAt.toISOString(),
      certificate_type: 'training',
    });
    if (error) throw error;

    return NextResponse.json({
      token,
      generatedAt: generatedAt.toISOString(),
      expiresAt: expiresAt.toISOString(),
      verificationUrl: `${getBaseUrl()}/api/reports/company-progress/certificate/${token}`,
      overwriteToken: overwriteToken || null,
    });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}