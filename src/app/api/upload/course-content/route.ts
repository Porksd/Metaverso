import { NextRequest, NextResponse } from 'next/server';
import AdmZip from 'adm-zip';
import { supabaseAdmin } from '@/lib/supabase';

const getContentType = (fileName: string) => {
    const ext = fileName.split('.').pop()?.toLowerCase();
    if (['zip'].includes(ext!)) return 'package';
    if (['mp4', 'webm', 'mov'].includes(ext!)) return 'video';
    if (['mp3', 'wav', 'ogg'].includes(ext!)) return 'audio';
    if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(ext!)) return 'image';
    return 'other';
};

// Supabase Storage rejects the literal "auto" Content-Type, so each extracted file needs a real MIME type.
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

const BUCKET_NAME = 'course-content';

export async function POST(request: NextRequest) {
    try {
        if (!supabaseAdmin) {
            return NextResponse.json({ error: 'Configuración de servidor incompleta (Admin Client missing)' }, { status: 500 });
        }
        
        const formData = await request.formData();
        const file = formData.get('file') as File | null;
        const storagePath = formData.get('storagePath') as string | null;
        const courseId = formData.get('courseId') as string;
        const sectionKey = formData.get('sectionKey') as string;
        const skipDbSave = formData.get('skipDbSave') === 'true';

        if ((!file && !storagePath) || !courseId || !sectionKey) {
            return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
        }

        let buffer: Buffer | null = null;
        let fileName: string;
        let contentTypeStr = 'application/octet-stream';

        if (file) {
            // Direct upload (limited to 4.5MB on Vercel)
            const bytes = await file.arrayBuffer();
            buffer = Buffer.from(bytes);
            fileName = file.name;
            contentTypeStr = file.type;
        } else {
            // Processing from an already uploaded object in storage
            fileName = storagePath!.split('/').pop() || 'package.zip';
        }

        const contentType = getContentType(fileName);
        const timestamp = Date.now();
        const safeName = fileName.replace(/[^a-zA-Z0-9.-]/g, '_');
        let subDir = 'media';
        
        if (contentType === 'package') subDir = sectionKey.includes('scorm') ? 'scorm' : 'html5';

        let finalUrl = '';
        if (contentType === 'package') {
            if (!buffer && storagePath) {
                console.log(`Processing ZIP from storage path: ${storagePath}`);
                const { data, error } = await supabaseAdmin.storage.from(BUCKET_NAME).download(storagePath);
                if (error) throw new Error(`Error downloading from storage: ${error.message}`);

                const bytes = await data.arrayBuffer();
                buffer = Buffer.from(bytes);
            }

            if (!buffer) throw new Error('No package file data found');

            const zip = new AdmZip(buffer);
            const zipEntries = zip.getEntries();
            const folder = `${timestamp}_${safeName.replace('.zip', '')}`;
            let entryPoint = '';
            
            const idx = zipEntries.find(e => e.entryName.endsWith('index.html')) || 
                        zipEntries.find(e => e.entryName.endsWith('imsmanifest.xml'));
            
            if (idx) entryPoint = idx.entryName;

            console.log(`Extracting ${zipEntries.length} files to ${courseId}/${subDir}/${folder}`);

            let failedUploads = 0;
            for (const entry of zipEntries) {
                if (entry.isDirectory) continue;
                const { error: entryError } = await supabaseAdmin.storage.from(BUCKET_NAME).upload(
                    `${courseId}/${subDir}/${folder}/${entry.entryName}`,
                    entry.getData(),
                    { contentType: getMimeType(entry.entryName), upsert: true }
                );
                if (entryError) {
                    failedUploads++;
                    console.error(`Failed to upload entry ${entry.entryName}:`, entryError.message);
                }
            }

            if (failedUploads > 0) {
                throw new Error(`Fallaron ${failedUploads} de ${zipEntries.length} archivos al subir el paquete SCORM. El contenido no quedó disponible, intenta nuevamente.`);
            }

            if (!entryPoint) {
                throw new Error('El paquete no contiene index.html ni imsmanifest.xml. Revisa que el .zip tenga el contenido SCORM en la raíz.');
            }

            // Serve via our own proxy route: Supabase Storage forces text/plain + sandbox CSP on HTML
            // content even in public buckets, which breaks rendering the SCORM package in an iframe.
            finalUrl = `/api/scorm-content/${courseId}/${subDir}/${folder}/${entryPoint}`;

            // Optional: Cleanup the temporary ZIP from storage if it was passed via storagePath
            if (storagePath) {
                await supabaseAdmin.storage.from(BUCKET_NAME).remove([storagePath]);
            }
        } else {
            if (storagePath) {
                // Reuse object already uploaded from client to avoid duplicate upload.
                const { data } = supabaseAdmin.storage.from(BUCKET_NAME).getPublicUrl(storagePath);
                finalUrl = data.publicUrl;
            } else {
                // Legacy direct upload flow for small files
                const path = `${courseId}/${subDir}/${timestamp}_${safeName}`;
                if (!buffer) throw new Error('No file data found');
                const { error } = await supabaseAdmin.storage.from(BUCKET_NAME).upload(path, buffer, { contentType: contentTypeStr, upsert: true });
                if (error) throw error;

                const { data } = supabaseAdmin.storage.from(BUCKET_NAME).getPublicUrl(path);
                finalUrl = data.publicUrl;
            }
        }

        if (!skipDbSave) {
            const { error: dbError } = await supabaseAdmin.from('course_content').upsert({
                course_id: courseId,
                key: sectionKey,
                value: finalUrl,
                updated_at: new Date().toISOString()
            }, { onConflict: 'course_id,key' });

            if (dbError) throw dbError;
        }

        return NextResponse.json({ success: true, url: finalUrl, type: contentType });
    } catch (e: any) {
        console.error("Upload error:", e);
        return NextResponse.json({ error: e.message }, { status: 500 });
    }
}
