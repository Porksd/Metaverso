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
        const file = formData.get('file') as File;
        const courseId = formData.get('courseId') as string;
        const sectionKey = formData.get('sectionKey') as string;
        if (!file || !courseId || !sectionKey) {
            return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
        }
        const contentType = getContentType(file.name);
        const timestamp = Date.now();
        const safeName = file.name.replace(/[^a-zA-Z0-9.-]/g, '_');
        let subDir = 'media';
        if (contentType === 'package') subDir = sectionKey.includes('scorm') ? 'scorm' : 'html5';
        const bytes = await file.arrayBuffer();
        const buffer = Buffer.from(bytes);
        let finalUrl = '';
        if (contentType === 'package') {
            const zip = new AdmZip(buffer);
            const zipEntries = zip.getEntries();
            const folder = `${timestamp}_${safeName.replace('.zip', '')}`;
            let entryPoint = '';
            const idx = zipEntries.find(e => e.entryName.endsWith('index.html')) || zipEntries.find(e => e.entryName.endsWith('imsmanifest.xml'));
            if (idx) entryPoint = idx.entryName;
            for (const entry of zipEntries) {
                if (entry.isDirectory) continue;
                await supabaseAdmin.storage.from(BUCKET_NAME).upload(`${courseId}/${subDir}/${folder}/${entry.entryName}`, entry.getData(), { contentType: 'auto', upsert: true });
            }
            const { data } = supabaseAdmin.storage.from(BUCKET_NAME).getPublicUrl(`${courseId}/${subDir}/${folder}/${entryPoint || ''}`);
            finalUrl = data.publicUrl;
        } else {
            const path = `${courseId}/${subDir}/${timestamp}_${safeName}`;
            const { error } = await supabaseAdmin.storage.from(BUCKET_NAME).upload(path, buffer, { contentType: file.type, upsert: true });
            if (error) throw error;
            const { data } = supabaseAdmin.storage.from(BUCKET_NAME).getPublicUrl(path);
            finalUrl = data.publicUrl;
        }
        await supabaseAdmin.from('course_content').upsert({ course_id: courseId, key: sectionKey, value: finalUrl, updated_at: new Date().toISOString() }, { onConflict: 'course_id,key' });
        return NextResponse.json({ success: true, url: finalUrl, type: contentType });
    } catch (e: any) {
        return NextResponse.json({ error: e.message }, { status: 500 });
    }
}
