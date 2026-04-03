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

        if ((!file && !storagePath) || !courseId || !sectionKey) {
            return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
        }

        let buffer: Buffer;
        let fileName: string;
        let contentTypeStr: string;

        if (file) {
            // Direct upload (limited to 4.5MB on Vercel)
            const bytes = await file.arrayBuffer();
            buffer = Buffer.from(bytes);
            fileName = file.name;
            contentTypeStr = file.type;
        } else {
            // Processing from storage (No size limit proxying through server)
            console.log(`Processing ZIP from storage path: ${storagePath}`);
            const { data, error } = await supabaseAdmin.storage.from(BUCKET_NAME).download(storagePath!);
            if (error) throw new Error(`Error downloading from storage: ${error.message}`);
            
            const bytes = await data.arrayBuffer();
            buffer = Buffer.from(bytes);
            fileName = storagePath!.split('/').pop() || 'package.zip';
            contentTypeStr = 'application/zip';
        }

        const contentType = getContentType(fileName);
        const timestamp = Date.now();
        const safeName = fileName.replace(/[^a-zA-Z0-9.-]/g, '_');
        let subDir = 'media';
        
        if (contentType === 'package') subDir = sectionKey.includes('scorm') ? 'scorm' : 'html5';

        let finalUrl = '';
        if (contentType === 'package') {
            const zip = new AdmZip(buffer);
            const zipEntries = zip.getEntries();
            const folder = `${timestamp}_${safeName.replace('.zip', '')}`;
            let entryPoint = '';
            
            const idx = zipEntries.find(e => e.entryName.endsWith('index.html')) || 
                        zipEntries.find(e => e.entryName.endsWith('imsmanifest.xml'));
            
            if (idx) entryPoint = idx.entryName;

            console.log(`Extracting ${zipEntries.length} files to ${courseId}/${subDir}/${folder}`);

            for (const entry of zipEntries) {
                if (entry.isDirectory) continue;
                await supabaseAdmin.storage.from(BUCKET_NAME).upload(
                    `${courseId}/${subDir}/${folder}/${entry.entryName}`, 
                    entry.getData(), 
                    { contentType: 'auto', upsert: true }
                );
            }
            
            const { data } = supabaseAdmin.storage.from(BUCKET_NAME).getPublicUrl(`${courseId}/${subDir}/${folder}/${entryPoint || ''}`);
            finalUrl = data.publicUrl;

            // Optional: Cleanup the temporary ZIP from storage if it was passed via storagePath
            if (storagePath) {
                await supabaseAdmin.storage.from(BUCKET_NAME).remove([storagePath]);
            }
        } else {
            // This part is legacy or for small files, but we'll keep it for compatibility
            const path = `${courseId}/${subDir}/${timestamp}_${safeName}`;
            const { error } = await supabaseAdmin.storage.from(BUCKET_NAME).upload(path, buffer, { contentType: contentTypeStr, upsert: true });
            if (error) throw error;
            
            const { data } = supabaseAdmin.storage.from(BUCKET_NAME).getPublicUrl(path);
            finalUrl = data.publicUrl;
        }

        await supabaseAdmin.from('course_content').upsert({ 
            course_id: courseId, 
            key: sectionKey, 
            value: finalUrl, 
            updated_at: new Date().toISOString() 
        }, { onConflict: 'course_id,key' });

        return NextResponse.json({ success: true, url: finalUrl, type: contentType });
    } catch (e: any) {
        console.error("Upload error:", e);
        return NextResponse.json({ error: e.message }, { status: 500 });
    }
}
