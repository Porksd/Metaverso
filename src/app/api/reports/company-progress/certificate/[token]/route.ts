import { NextRequest, NextResponse } from 'next/server';
import { getStoredReportCertificate } from '@/lib/server/companyProgressReport';

type RouteContext = { params: Promise<{ token: string }> };

export async function GET(_req: NextRequest, { params }: RouteContext) {
  try {
    const { token } = await params;
    const certificate = await getStoredReportCertificate(token);

    if (certificate.status === 'not_found') {
      return NextResponse.json({ error: 'Certificado no encontrado.' }, { status: 404 });
    }

    if (certificate.status === 'expired') {
      return NextResponse.json({ error: 'Este certificado venció.' }, { status: 410 });
    }

    return new NextResponse(new Uint8Array(certificate.pdf), {
      headers: {
        'Content-Type': 'application/pdf',
        'Content-Disposition': 'inline; filename="certificado-informe.pdf"',
        'Cache-Control': 'public, max-age=300, must-revalidate',
      },
    });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}