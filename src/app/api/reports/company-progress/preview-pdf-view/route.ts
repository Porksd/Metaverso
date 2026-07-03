import { NextRequest, NextResponse } from 'next/server';
import { getCompanyProgressReportPdfPreview } from '@/lib/server/companyProgressReport';

export async function GET(req: NextRequest) {
  try {
    const { searchParams } = new URL(req.url);
    const companyId = (searchParams.get('companyId') || '').trim();

    if (!companyId) {
      return NextResponse.json({ error: 'companyId es obligatorio.' }, { status: 400 });
    }

    const payload = await getCompanyProgressReportPdfPreview(companyId);
    if (!payload) {
      return NextResponse.json({ error: 'Empresa no encontrada.' }, { status: 404 });
    }

    const base64 = payload.pdfBuffer.toString('base64');
    const html = `<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Preview PDF Reporte</title>
    <style>
      body { margin: 0; background: #0b1220; color: #e2e8f0; font-family: Segoe UI, Arial, sans-serif; }
      .bar { position: sticky; top: 0; z-index: 2; padding: 10px 16px; background: #111827; border-bottom: 1px solid #1f2937; font-size: 13px; }
      .wrap { height: calc(100vh - 44px); }
      embed, iframe { width: 100%; height: 100%; border: 0; }
    </style>
  </head>
  <body>
    <div class="bar">Vista previa PDF (incluye listado de participantes en pagina 2)</div>
    <div class="wrap">
      <embed type="application/pdf" src="data:application/pdf;base64,${base64}" />
    </div>
  </body>
</html>`;

    return new NextResponse(html, {
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
