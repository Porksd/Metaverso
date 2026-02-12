import { NextRequest, NextResponse } from 'next/server';
import { writeFile, mkdir } from 'fs/promises';
import path from 'path';
import AdmZip from 'adm-zip';
import { supabaseAdmin } from '@/lib/supabase';

// Helper to determine content type from file extension
const getContentType = (fileName: string) => {
    const ext = fileName.split('.').pop()?.toLowerCase();
    if (['zip'].includes(ext!)) return 'package';
    if (['mp4', 'webm', 'mov'].includes(ext!)) return 'video';
    if (['mp3', 'wav', 'ogg'].includes(ext!)) return 'audio';
    if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(ext!)) return 'image';
    return 'other';
};

export async function POST(request: NextRequest) {
    try {
        console.log('[Upload API] Start upload process');
        const formData = await request.formData();
        const file = formData.get('file') as File;
        const courseId = formData.get('courseId') as string;
        const sectionKey = formData.get('sectionKey') as string;

        console.log(`[Upload API] File: ${file?.name}, Size: ${file?.size}, Course: ${courseId}, Key: ${sectionKey}`);

        if (!file || !courseId || !sectionKey) {
            console.error('[Upload API] Missing required fields');
            return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
        }

        const contentType = getContentType(file.name);
        const timestamp = Date.now();
        const safeName = file.name.replace(/[^a-zA-Z0-9.-]/g, '_');

        // Base directories
        const baseUploadDir = path.join(process.cwd(), 'public', 'uploads', 'courses', courseId);
        const relativeBaseUrl = `/uploads/courses/${courseId}`;

        // Determinar subdirectorio dependiendo del tipo
        let subDir = 'media';
        if (contentType === 'package') {
            subDir = sectionKey.includes('scorm') ? 'scorm' : 'html5';
        }

        const finalUploadDir = path.join(baseUploadDir, subDir);
        console.log(`[Upload API] Final upload directory: ${finalUploadDir}`);
        
        try {
            await mkdir(finalUploadDir, { recursive: true });
        } catch (dirError: any) {
            console.error('[Upload API] Error creating directory:', dirError);
            return NextResponse.json({ error: `Could not create directory: ${dirError.message}` }, { status: 500 });
        }

        // Ruta del archivo destino
        const fileName = `${timestamp}_${safeName}`;
        const filePath = path.join(finalUploadDir, fileName);

        // Guardar archivo
        console.log(`[Upload API] Saving file to: ${filePath}`);
        const bytes = await file.arrayBuffer();
        const buffer = Buffer.from(bytes);
        
        try {
            await writeFile(filePath, buffer);
            console.log('[Upload API] File saved successfully');
        } catch (writeError: any) {
            console.error('[Upload API] Error writing file:', writeError);
            return NextResponse.json({ error: `Could not write file: ${writeError.message}` }, { status: 500 });
        }

        let finalUrl = `${relativeBaseUrl}/${subDir}/${fileName}`;
        let entryPoint = '';

        // Si es un paquete ZIP (SCORM o HTML5), descomprimir
        if (contentType === 'package') {
            console.log('[Upload API] Extracting package...');
            try {
                const zip = new AdmZip(filePath);
                const extractDirName = `${timestamp}_${path.parse(safeName).name}_extracted`;
                const extractPath = path.join(finalUploadDir, extractDirName);

                zip.extractAllTo(extractPath, true);
                console.log(`[Upload API] Package extracted to: ${extractPath}`);

                // Buscar punto de entrada (index.html o imsmanifest.xml)
                const zipEntries = zip.getEntries();
                const indexEntry = zipEntries.find(entry => entry.entryName.endsWith('index.html'));
                const manifestEntry = zipEntries.find(entry => entry.entryName.endsWith('imsmanifest.xml'));

                if (indexEntry) {
                    entryPoint = indexEntry.entryName;
                } else if (manifestEntry) {
                    entryPoint = manifestEntry.entryName;
                }

                if (entryPoint) {
                    finalUrl = `${relativeBaseUrl}/${subDir}/${extractDirName}/${entryPoint}`;
                    console.log(`[Upload API] Entry point found: ${entryPoint}`);
                } else {
                    console.warn('[Upload API] No entry point found in ZIP, using raw file URL');
                }

            } catch (zipError: any) {
                console.error('[Upload API] Error extracting ZIP:', zipError);
                return NextResponse.json({ error: `Failed to extract package: ${zipError.message}` }, { status: 500 });
            }
        }

        if (!supabaseAdmin) {
            console.error('[Upload API] supabaseAdmin is null. Check SUPABASE_SERVICE_ROLE_KEY env var.');
            return NextResponse.json({ error: 'Configuración de servidor incompleta (Admin Client missing)' }, { status: 500 });
        }

        // Actualizar tabla course_content
        console.log(`[Upload API] Upserting to course_content: ${sectionKey} -> ${finalUrl}`);
        const { error: dbError } = await supabaseAdmin
            .from('course_content')
            .upsert({
                course_id: courseId,
                key: sectionKey,
                value: finalUrl,
                updated_at: new Date().toISOString()
            }, { onConflict: 'course_id,key' });

        if (dbError) {
            console.error('[Upload API] Database Error:', dbError);
            throw dbError;
        }

        console.log('[Upload API] Process completed successfully');
        return NextResponse.json({
            success: true,
            url: finalUrl,
            type: contentType
        });

    } catch (error: any) {
        console.error('[Upload API] Catch-all error:', error);
        return NextResponse.json({ error: error.message || 'Unknown internal error' }, { status: 500 });
    }
}
