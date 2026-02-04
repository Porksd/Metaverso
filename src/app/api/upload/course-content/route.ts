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
        const formData = await request.formData();
        const file = formData.get('file') as File;
        const courseId = formData.get('courseId') as string;
        const sectionKey = formData.get('sectionKey') as string; // e.g., 'scorm_package', 'slide1_video'

        if (!file || !courseId || !sectionKey) {
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
        await mkdir(finalUploadDir, { recursive: true });

        // Ruta del archivo destino
        const fileName = `${timestamp}_${safeName}`;
        const filePath = path.join(finalUploadDir, fileName);

        // Guardar archivo
        const bytes = await file.arrayBuffer();
        const buffer = Buffer.from(bytes);
        await writeFile(filePath, buffer);

        let finalUrl = `${relativeBaseUrl}/${subDir}/${fileName}`;
        let entryPoint = '';

        // Si es un paquete ZIP (SCORM o HTML5), descomprimir
        if (contentType === 'package') {
            try {
                const zip = new AdmZip(filePath);
                const extractDirName = `${timestamp}_${path.parse(safeName).name}_extracted`;
                const extractPath = path.join(finalUploadDir, extractDirName);

                zip.extractAllTo(extractPath, true);

                // Buscar punto de entrada (index.html o imsmanifest.xml)
                const zipEntries = zip.getEntries();
                const indexEntry = zipEntries.find(entry => entry.entryName.endsWith('index.html'));
                const manifestEntry = zipEntries.find(entry => entry.entryName.endsWith('imsmanifest.xml'));

                if (indexEntry) {
                    entryPoint = indexEntry.entryName;
                } else if (manifestEntry) {
                    // Si es SCORM puro sin index en root, asumimos que reproductor buscará manifest
                    entryPoint = manifestEntry.entryName;
                }

                if (entryPoint) {
                    finalUrl = `${relativeBaseUrl}/${subDir}/${extractDirName}/${entryPoint}`;
                } else {
                    console.warn('No entry point found in ZIP, using raw file URL');
                }

            } catch (zipError) {
                console.error('Error extracting ZIP:', zipError);
                return NextResponse.json({ error: 'Failed to extract package' }, { status: 500 });
            }
        }

        if (!supabaseAdmin) {
            return NextResponse.json({ error: 'Configuración de servidor incompleta' }, { status: 500 });
        }

        // Actualizar tabla course_content
        const { error: dbError } = await supabaseAdmin
            .from('course_content')
            .upsert({
                course_id: courseId,
                key: sectionKey,
                value: finalUrl,
                updated_at: new Date().toISOString()
            }, { onConflict: 'course_id, key' });

        if (dbError) throw dbError;

        return NextResponse.json({
            success: true,
            url: finalUrl,
            type: contentType
        });

    } catch (error: any) {
        console.error('Upload handler error:', error);
        return NextResponse.json({ error: error.message }, { status: 500 });
    }
}
