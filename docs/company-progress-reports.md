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
- `NEXT_PUBLIC_APP_URL` (opcional, pero recomendado; por ejemplo `https://metaverso-pi.vercel.app`, para que los QR usen el dominio oficial)

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

La logica interna decide si corresponde enviar segun configuracion de cada empresa. Las opciones visibles en el panel son:

- Todos los días (cada 1 día)
- Cada 7 días
- Cada 15 días
- Cada 30 días

## Validacion de envios posteriores

El despacho automatico se ejecuta una vez al dia mediante Vercel Cron. La ruta debe responder con `200` cuando Vercel la invoca con `Authorization: Bearer <CRON_SECRET>`.

Una consulta sin credenciales debe responder `401`; esto confirma que la ruta esta protegida, pero no valida un envio. Para que los envios posteriores funcionen, `CRON_SECRET` debe estar configurado en Vercel para el entorno Production y debe existir un nuevo deployment despues de guardarlo. En los logs de Vercel, cada ejecucion debe mostrar `sent` para empresas cuyo intervalo ya vencio, o `not_due` cuando aun no corresponde enviar.

El endpoint de diagnostico es:

```text
GET /api/reports/company-progress/dispatch
```

La fecha `report_last_sent_at` se actualiza solo despues de que SMTP confirma el envio. Por eso un error SMTP queda como `sent: false` y no bloquea silenciosamente los siguientes intentos.

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

## Validacion y vigencia del PDF

Cada PDF de informe generado registra un token unico en `report_certificates` y se guarda en el bucket privado `report-certificates`. El pie del documento incluye un QR, la fecha de generacion y la fecha de vencimiento, siete dias despues.

El QR abre `/api/reports/company-progress/certificate/<token>` y devuelve el mismo PDF almacenado. Una vez vencido, la ruta responde `410` y el dispatcher diario elimina el registro y el archivo del bucket.

Antes del primer envio o descarga posterior a este cambio, aplica `migrations/059_add_report_certificate_storage.sql` en Supabase. La migracion crea la tabla, activa RLS sin exponer los archivos y crea el bucket privado; las operaciones de escritura y lectura se realizan exclusivamente con la clave service role en el servidor.

