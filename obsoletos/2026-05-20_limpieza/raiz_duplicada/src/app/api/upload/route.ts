import { NextRequest, NextResponse } from 'next/server';
import { writeFile, mkdir } from 'fs/promises';
import path from 'path';

export async function POST(request: NextRequest) {
    try {
        const formData = await request.formData();
        const file = formData.get('file') as File;
        const companyId = formData.get('companyId') as string;
        const fileType = formData.get('type') as string; // 'signature', 'logo', 'scorm'

        if (!file || !companyId || !fileType) {
            return NextResponse.json(
                { error: 'Missing required fields' },
                { status: 400 }
            );
        }

        // Validar tipo de archivo
        const allowedTypes: { [key: string]: string[] } = {
            signature: ['image/png', 'image/jpeg', 'image/jpg'],
            logo: ['image/png', 'image/jpeg', 'image/jpg'],
            scorm: ['application/zip', 'application/x-zip-compressed']
        };

        if (!allowedTypes[fileType]?.includes(file.type)) {
            return NextResponse.json(
                { error: `Invalid file type. Expected: ${allowedTypes[fileType]?.join(', ')}` },
                { status: 400 }
            );
        }

        // Crear directorio si no existe
        const uploadDir = path.join(
            process.cwd(),
            'public',
            'uploads',
            'companies',
            companyId,
            fileType === 'signature' ? 'signatures' : fileType === 'logo' ? 'logos' : 'scorm'
        );

        await mkdir(uploadDir, { recursive: true });

        // Generar nombre de archivo único
        const timestamp = Date.now();
        const originalName = file.name.replace(/[^a-zA-Z0-9.-]/g, '_');
        const fileName = `${timestamp}_${originalName}`;
        const filePath = path.join(uploadDir, fileName);

        // Guardar archivo
        const bytes = await file.arrayBuffer();
        const buffer = Buffer.from(bytes);
        await writeFile(filePath, buffer);

        // Retornar URL pública
        const publicUrl = `/uploads/companies/${companyId}/${fileType === 'signature' ? 'signatures' : fileType === 'logo' ? 'logos' : 'scorm'}/${fileName}`;

        return NextResponse.json({
            success: true,
            url: publicUrl,
            fileName
        });

    } catch (error) {
        console.error('Upload error:', error);
        return NextResponse.json(
            { error: 'Failed to upload file' },
            { status: 500 }
        );
    }
}
