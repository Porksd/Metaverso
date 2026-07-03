import { NextRequest, NextResponse } from 'next/server';
import { getCompanyProgressReportPdfPreview } from '@/lib/server/companyProgressReport';

export async function GET(req: NextRequest) {
  try {
    const { searchParams } = new URL(req.url);
    const companyId = (searchParams.get('companyId') || '').trim();
    const includeStudentsParam = (searchParams.get('includeStudents') || '').trim().toLowerCase();
    const includeStudents = includeStudentsParam
      ? ['1', 'true', 'yes', 'on'].includes(includeStudentsParam)
      : undefined;

    if (!companyId) {
      return NextResponse.json({ error: 'companyId es obligatorio.' }, { status: 400 });
    }

    const payload = await getCompanyProgressReportPdfPreview(companyId, { includeStudents });
    if (!payload) {
      return NextResponse.json({ error: 'Empresa no encontrada.' }, { status: 404 });
    }

    const safeCompanyName = (payload.report.company.name || companyId)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || companyId;

    const pdfBytes = new Uint8Array(payload.pdfBuffer);

    return new NextResponse(pdfBytes, {
      headers: {
        'Content-Type': 'application/pdf',
        'Content-Disposition': `attachment; filename="informe-${safeCompanyName}.pdf"`,
        'Cache-Control': 'no-store'
      }
    });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
