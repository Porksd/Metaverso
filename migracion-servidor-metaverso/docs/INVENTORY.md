# Inventario funcional inicial

## Estado del proyecto
La plataforma actual depende de Supabase en tres frentes:
- autenticacion de usuarios,
- acceso a datos,
- almacenamiento de archivos y certificados.

Eso significa que la migracion no es un simple cambio de conexion, sino una reconstruccion por capas.

## Modulos principales

### Publico y acceso
- `src/app/page.tsx`
- `src/app/register/page.tsx`
- `src/app/empresa/page.tsx`
- `src/app/portal/[slug]/page.tsx`
- `src/app/portal/[slug]/curso/[courseCode]/page.tsx`
- `src/app/courses/page.tsx`
- `src/app/courses/[id]/page.tsx`

### Administracion
- `src/app/admin/page.tsx`
- `src/app/admin/metaverso/page.tsx`
- `src/app/admin/metaverso/login/page.tsx`
- `src/app/admin/metaverso/usuarios/page.tsx`
- `src/app/admin/metaverso/empresas/page.tsx`
- `src/app/admin/metaverso/cursos/page.tsx`
- `src/app/admin/metaverso/cursos/[id]/contenido/page.tsx`
- `src/app/admin/metaverso/cargos/page.tsx`
- `src/app/admin/metaverso/diplomas/page.tsx`
- `src/app/admin/metaverso/encuestas/page.tsx`
- `src/app/admin/metaverso/encuestas/[id]/stats/page.tsx`
- `src/app/admin/metaverso/signout/page.tsx`

### Empresa y alumnos
- `src/app/admin/empresa/page.tsx`
- `src/app/admin/empresa/login/page.tsx`
- `src/app/admin/empresa/portal/[slug]/page.tsx`
- `src/app/admin/empresa/alumnos/login/page.tsx`
- `src/app/admin/empresa/alumnos/register/page.tsx`
- `src/app/admin/empresa/alumnos/cursos/page.tsx`

### Cursos y contenido
- `src/app/admin/cursos/page.tsx`
- `src/app/admin/cursos/login/page.tsx`
- `src/app/demo/page.tsx`
- `src/app/demo/empresa-vista/page.tsx`
- `src/app/demo/alumno-login/page.tsx`

## APIs y procesos de backend
- `src/app/api/upload/route.ts`
- `src/app/api/upload/course-content/route.ts`
- `src/app/api/students/register/route.ts`
- `src/app/api/students/[studentId]/route.ts`
- `src/app/api/certificate/route.ts`
- `src/app/api/seed/content/route.ts`
- `src/app/api/admin/users/password/route.ts`
- `src/app/api/admin/content/route.ts`

## Librerias y servicios acoplados
- `src/lib/supabase.ts`
- `src/lib/adminAuth.ts`
- `src/lib/scorm-driver.ts`
- `src/lib/generateMetaversoCert.ts`

## Componentes criticos
- `src/components/ContentUploader.tsx`
- `src/components/CompanyConfig.tsx`
- `src/components/CertificateCanvas.tsx`
- `src/components/ScormPlayer.tsx`
- `src/components/CoursePlayer.tsx`
- `src/components/SurveyEngine.tsx`
- `src/components/SurveyBuilder.tsx`
- `src/components/QuizEngine.tsx`
- `src/components/QuizBuilder.tsx`
- `src/components/RichTextEditor.tsx`
- `src/components/GeniallyEmbed.tsx`
- `src/components/VideoPlayer.tsx`
- `src/components/SignatureCanvas.tsx`

## Dependencias de Supabase detectadas
- `NEXT_PUBLIC_SUPABASE_URL`
- `NEXT_PUBLIC_SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`
- `supabase.auth`
- `supabase.from(...)`
- `supabase.storage`
- `supabaseAdmin`

## Bloques de migracion prioritarios
1. Autenticacion y sesiones.
2. Consultas y mutaciones de base de datos.
3. Subida y descarga de archivos.
4. Generacion de certificados.
5. Flujos de administracion de empresas, cursos y alumnos.

## Riesgos visibles
- El login actual depende de Supabase Auth.
- Los archivos de contenido dependen de Supabase Storage.
- Hay rutas administrativas que usan service role en servidor.
- Hay componentes de curso y certificados que esperan URLs publicas de Supabase.

## Proximo paso
Convertir este inventario en una matriz de migracion por modulo con prioridad, reemplazo y dependencias.