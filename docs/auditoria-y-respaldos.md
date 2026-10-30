# Auditoría de datos y respaldos

## Registro de cambios

La migración `066_data_change_audit.sql` instala triggers para registrar inserciones, modificaciones y eliminaciones en las tablas actuales del esquema `public`. También registra altas, modificaciones y eliminaciones de usuarios de `auth.users`, guardando únicamente campos de identidad permitidos. Los valores antiguos y nuevos se conservan para poder revisar y recuperar cambios; claves cuyos nombres indiquen contraseñas, tokens, secretos, firmas, cookies o credenciales se redactan recursivamente.

El historial se consulta en **Admin → Auditoría de Datos**. El acceso a las filas está limitado por RLS a usuarios cuyo rol en `admin_profiles` sea `superadmin`. El registro es de solo lectura para los roles de la aplicación. La migración cubre las tablas que existen al aplicarla; cada tabla pública nueva debe recibir el trigger `capture_data_change`.

El actor se obtiene de las claims JWT verificadas por Supabase. Los endpoints de contenido, eliminación de alumnos y gestión IRL usan `adminApiFetch` en el navegador y `createAuditedAdminClient` en el servidor para validar el token antes de enviar `x-audit-actor-user-id`, `x-audit-actor-email` y el rol administrativo conocido a PostgREST. El trigger solo acepta esos encabezados cuando el rol JWT de la conexión es `service_role`; nunca se deben copiar directamente encabezados no verificados del navegador. Otras operaciones de servidor que no usen este mecanismo, como los flujos públicos o el acceso antiguo a configuración de cursos que solo valida una marca local, aparecen como `service_role` sin atribución personal.

El editor de cursos técnicos ya no usa la contraseña compartida/local `cursos123`: ahora requiere una cuenta individual de Supabase con rol en `admin_profiles`. Asigna cuentas personales y sus permisos antes de usarlo; la pantalla de acceso antigua redirige al inicio de sesión administrativo común.

La auditoría empieza al aplicar la migración; no recupera cambios anteriores. Los eventos de auditoría también contienen datos de alumnos, por lo que la página y la tabla deben mantenerse restringidas a superadministradores. Aplique la política de retención de datos de acuerdo con las obligaciones de privacidad de la organización.

## Respaldo semanal cifrado en almacenamiento S3-compatible

El workflow `.github/workflows/weekly-db-backup.yml` queda inicialmente disponible solo para ejecución manual, porque todavía no se han configurado credenciales. Exporta el esquema `public` de PostgreSQL, valida el archivo, lo cifra localmente con `age`, lo carga a `metaverso/weekly/` y elimina copias de ese prefijo que tengan más de 90 días. La clave privada de descifrado nunca se guarda en el repositorio. Después de configurar y probar las credenciales, se puede activar el cron semanal (lunes a las 08:00 UTC) agregando un evento `schedule` al workflow.

Antes de habilitar el workflow:

1. En **Settings → Environments**, crea el entorno `production-restore` y exige aprobación de una persona responsable antes de ejecutar trabajos en ese entorno.
2. En **Settings → Secrets and variables → Actions**, configura estas variables:
   - `BACKUP_S3_ENDPOINT`: URL del endpoint S3-compatible.
   - `BACKUP_S3_BUCKET`: bucket privado dedicado a los respaldos.
   - `BACKUP_S3_REGION`: región requerida por el proveedor.
   - `BACKUP_AGE_RECIPIENT`: clave pública de `age` generada fuera del repositorio.
3. Configura estos secretos:
   - `SUPABASE_DB_URL`: URL PostgreSQL del proyecto, preferentemente conexión directa o pooler en modo sesión. No uses un pooler en modo transacción para `pg_dump`.
   - `BACKUP_S3_ACCESS_KEY_ID` y `BACKUP_S3_SECRET_ACCESS_KEY`: credenciales con acceso únicamente al bucket/prefijo de respaldo.
   - `BACKUP_AGE_IDENTITY`: clave privada de `age` correspondiente a `BACKUP_AGE_RECIPIENT`.
   - `SUPABASE_RESTORE_DB_URL`: conexión PostgreSQL con privilegios suficientes para restaurar en la base destino. Guárdala como secreto del entorno `production-restore`, no como secreto general del repositorio.
4. Verifica permisos de lectura, escritura, listado y eliminación solo en `metaverso/weekly/`. No publiques el bucket ni la clave privada.
5. Ejecuta `Weekly encrypted database backup` manualmente y confirma en el historial de Actions que el dump se validó y ambos archivos se cargaron. Comprueba también que el objeto se puede descifrar con la clave privada guardada.

Para generar un par de claves `age`, ejecuta `age-keygen` en un equipo seguro. Registra la clave pública como variable y guarda la clave privada directamente como secreto en GitHub; no la copies al repositorio, issue o chat.

## Restauración

La restauración está separada en `.github/workflows/restore-db-backup.yml`. En GitHub Actions selecciona `Restore encrypted database backup`, pega la clave exacta del respaldo y escribe `RESTORE-PRODUCTION`. El trabajo espera la aprobación configurada en `production-restore`, verifica el checksum y el archivo antes de llamar a `pg_restore`.

**La restauración reemplaza los objetos y datos del esquema `public` incluidos en el dump y descarta cambios posteriores al punto elegido.** Verifica proyecto, destino y fecha; avisa a los responsables y confirma que hay aprobación antes de ejecutarla. Haz primero un ensayo de restauración en un proyecto/base aislados y verifica la aplicación antes de usar producción.

Este proceso es una copia externa del esquema de aplicación `public`, no una copia completa del proyecto Supabase: no contiene los usuarios del esquema `auth`, los objetos binarios de Storage, secretos/configuración del proyecto ni copias completas administradas del proveedor. Conserva también los respaldos nativos de Supabase para recuperación del proyecto completo y revisa sus opciones de retención. Los archivos subidos a Storage requieren una estrategia de copia independiente.
