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
- `REPORTS_CRON_SECRET`: secreto para proteger la ruta de despacho automatico

Tambien deben existir las variables de Supabase ya utilizadas por la app:

- `NEXT_PUBLIC_SUPABASE_URL`
- `NEXT_PUBLIC_SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`

## Endpoints

- `POST /api/reports/company-progress/send`
  - Uso: envio manual para una empresa especifica.
  - Auth: `Authorization: Bearer <token_admin>`.
  - Body JSON: `{ "companyId": "<uuid>", "force": true }`.

- `POST /api/reports/company-progress/dispatch`
  - Uso: despacho automatico de empresas activas segun periodicidad.
  - Auth: `x-cron-secret: <REPORTS_CRON_SECRET>` o token admin.
  - Body JSON opcional: `{ "force": false }` o `{ "companyId": "<uuid>", "force": true }`.

## Programacion automatica

Puedes invocar `dispatch` con un scheduler externo (cron del hosting, GitHub Actions, EasyCron, etc.).

Frecuencia recomendada del scheduler: cada 24 horas.

La logica interna decide si corresponde enviar segun configuracion de cada empresa:

- Diario
- Semanal
- Mensual

## Configuracion en panel

En `Admin Maestro > Editar Empresa` ahora existen opciones para:

- Activar o desactivar envio automatico
- Elegir periodicidad
- Elegir formato:
  - Dashboard (graficos en el cuerpo)
  - PDF adjunto (detalle)
- Enviar informe inmediato con boton "Enviar Informe Ahora"
