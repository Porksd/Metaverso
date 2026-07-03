import { NextRequest, NextResponse } from 'next/server';
import { getCompanyProgressReportPreview } from '@/lib/server/companyProgressReport';

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

    const payload = await getCompanyProgressReportPreview(companyId, { includeStudents });
    if (!payload) {
      return NextResponse.json({ error: 'Empresa no encontrada.' }, { status: 404 });
    }

    return new NextResponse(payload.html, {
      headers: {
        'Content-Type': 'text/html; charset=utf-8',
        'Cache-Control': 'no-store'
      }
    });
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Error inesperado.';
    return NextResponse.json({ error: message }, { status: 500 });
  }
}
