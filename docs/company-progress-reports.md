# Informes automaticos de avance por empresa

Este modulo envia informes de avance de cursos por correo usando SMTP.

## Variables de entorno

Configura estas variables en tu entorno de despliegue:

- `SMTP_HOST`: host SMTP (ej. `smtp.zoho.com` o `mail.metaverso.cl`)
- `SMTP_PORT`: puerto SMTP (ej. `587` o `465`)
- `SMTP_USER`: usuario SMTP (ej. `informes@metaverso.cl`)
- `SMTP_PASS`: clave SMTP
- `SMTP_SECURE`: `true` para SSL directo (normalmente puerto 465), `false` para STARTTLS
- `SMTP_FROM`: remitente visible (ej. `Metaverso Informes <informes@metaverso.cl>`)
- `REPORTS_CRON_SECRET`: secreto para proteger la ruta de despacho automatico via header `x-cron-secret` (uso manual/externo)
- `CRON_SECRET`: **obligatorio para que el cron de Vercel funcione**. Vercel Cron Jobs no permiten headers personalizados: al invocar la ruta programada agregan automaticamente `Authorization: Bearer <CRON_SECRET>`. Sin esta variable configurada en Vercel, el despacho automatico (y el informe diario de seguridad) siempre responden 401 y nunca llegan, aunque las pruebas manuales funcionen.

Tambien deben existir las variables de Supabase ya utilizadas por la app:

- `NEXT_PUBLIC_SUPABASE_URL`
- `NEXT_PUBLIC_SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`

## Endpoints

- `POST /api/reports/company-progress/send`
  - Uso: envio manual para una empresa especifica.
  - Auth: `Authorization: Bearer <token_admin>`.
  - Body JSON: `{ "companyId": "<uuid>", "force": true }`.

- `POST|GET /api/reports/company-progress/dispatch`
  - Uso: despacho automatico de empresas activas segun periodicidad.
  - Auth: `x-cron-secret: <REPORTS_CRON_SECRET>` (POST manual/externo), `Authorization: Bearer <CRON_SECRET>` (usado automaticamente por Vercel Cron via GET) o token admin.
  - Body JSON opcional (solo POST): `{ "force": false }` o `{ "companyId": "<uuid>", "force": true }`.

## Programacion automatica

El scheduler ya esta configurado en `vercel.json` (crons de Vercel), una vez al dia:

- `/api/security/reports/daily`
- `/api/reports/company-progress/dispatch`

Antes de esto **no existia ningun cron para `/api/reports/company-progress/dispatch`**, por lo que el despacho automatico nunca se ejecutaba (solo los envios manuales desde el panel funcionaban). Si usas un scheduler externo en su lugar (cron del hosting, GitHub Actions, EasyCron, etc.), la frecuencia recomendada tambien es cada 24 horas.

La logica interna decide si corresponde enviar segun configuracion de cada empresa:

- Diario
- Semanal
- Cada 15 dias
- Mensual

## Configuracion en panel

En `Admin Maestro > Editar Empresa` ahora existen opciones para:

- Activar o desactivar envio automatico
- Elegir periodicidad
- Elegir formato:
  - Dashboard (graficos en el cuerpo)
  - PDF adjunto (detalle)
- Enviar informe inmediato con boton "Enviar Informe Ahora"

## Contenido del correo y del PDF

- El cuerpo del correo es una carta simple (sin graficos ni tablas) con el texto institucional fijo, el nombre de la empresa, los insights y recomendaciones (mismas reglas que el panel "Insights y Recomendaciones" del dashboard, replicadas textualmente en el servidor) y la fecha de envio.
- El PDF adjunto conserva el detalle completo (KPIs, graficos, tabla de cursos y listado de alumnos) y ahora ubica el logo de la empresa en la esquina superior derecha.

