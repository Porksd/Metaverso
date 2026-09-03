import { NextRequest, NextResponse } from 'next/server';
import { supabaseAdmin } from '@/lib/supabase';

// Supabase Storage forces text/plain + sandboxed CSP on any object it detects as HTML,
// even in public buckets, so SCORM/HTML5 packages can't be loaded directly from the public URL.
// This route streams the file server-side and sets the real Content-Type ourselves.
const BUCKET_NAME = 'course-content';

const getMimeType = (fileName: string): string => {
    const ext = fileName.split('.').pop()?.toLowerCase() || '';
    const map: Record<string, string> = {
        html: 'text/html', htm: 'text/html', js: 'application/javascript', mjs: 'application/javascript',
        css: 'text/css', json: 'application/json', xml: 'application/xml',
        png: 'image/png', jpg: 'image/jpeg', jpeg: 'image/jpeg', gif: 'image/gif', svg: 'image/svg+xml', webp: 'image/webp', ico: 'image/x-icon',
        mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg', mp4: 'video/mp4', webm: 'video/webm',
        woff: 'font/woff', woff2: 'font/woff2', ttf: 'font/ttf', eot: 'application/vnd.ms-fontobject', otf: 'font/otf',
        pdf: 'application/pdf', txt: 'text/plain',
    };
    return map[ext] || 'application/octet-stream';
};

export async function GET(_request: NextRequest, context: { params: Promise<{ path: string[] }> }) {
    const { path } = await context.params;

    if (!supabaseAdmin) {
        return NextResponse.json({ error: 'Configuración de servidor incompleta' }, { status: 500 });
    }

    if (!path?.length || path.some(segment => segment === '..' || segment.includes('\\'))) {
        return NextResponse.json({ error: 'Ruta inválida' }, { status: 400 });
    }

    const storagePath = path.join('/');
    const { data, error } = await supabaseAdmin.storage.from(BUCKET_NAME).download(storagePath);

    if (error || !data) {
        return NextResponse.json({ error: 'Archivo no encontrado' }, { status: 404 });
    }

    const buffer = Buffer.from(await data.arrayBuffer());
    return new NextResponse(buffer, {
        headers: {
            'Content-Type': getMimeType(storagePath),
            'Cache-Control': 'public, max-age=3600',
        },
    });
}
