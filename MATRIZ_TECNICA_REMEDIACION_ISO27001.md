# Matriz Tecnica de Remediacion ISO 27001

Version: 1.0  
Fecha: 2026-06-03  
Alcance: Plataforma App (API, DB, Storage, Auth)  
Objetivo: Cerrar brechas tecnicas criticas para avanzar de estado intermedio a estado pre-auditoria ISO 27001.

## 1. Escala de priorizacion
- P1: Critico. Riesgo alto inmediato sobre confidencialidad/integridad o privilegios.
- P2: Alto. Riesgo relevante con explotacion condicionada o impacto acotado.
- P3: Medio. Mejora de robustez, trazabilidad o madurez operativa.

## 2. Matriz tecnica de remediacion

| ID | Prioridad | Brecha | Ubicacion tecnica | Cambio requerido | Esfuerzo | Dependencias | Criterio de cierre (auditable) |
|---|---|---|---|---|---|---|---|
| R-01 | P1 | Password en tabla de negocio | src/app/api/students/register/route.ts | Eliminar persistencia de password en students y migrar flujo a Auth solamente. Crear migracion para remover/invalidar columna password (o cifrado transitorio solo si existe bloqueo operativo). | M | DB migration, QA regresion login | 1) No existe escritura de password en students. 2) Pruebas de registro/login exitosas. 3) Verificacion SQL sin datos de password en texto plano. |
| R-02 | P1 | Endpoint admin con tabla dinamica y sin control de autorizacion explicito | src/app/api/admin/content/route.ts | Exigir autenticacion (Bearer), resolver identidad, validar rol admin/superadmin, implementar lista blanca de tablas y operaciones permitidas, bloquear por defecto. | M | R-04 (RBAC helper unificado) | 1) Requests sin token -> 401. 2) Token sin rol -> 403. 3) Solo tablas whitelist permitidas. 4) Test de intento sobre tabla no permitida falla. |
| R-03 | P1 | RLS permisiva USING (true) en tablas de datos | migrations/016_review_and_optimize_rls.sql | Reemplazar politicas globales por politicas por company_id y/o auth.uid()/rol. Mantener RLS + FORCE RLS. | L | Modelo de roles, mapeo entidad-company_id | 1) No quedan politicas USING (true) en tablas con datos personales. 2) Test de aislamiento entre empresas pasa. 3) Supabase advisor sin hallazgos criticos de acceso global. |
| R-04 | P1 | Endpoints de alto impacto sin guardas de rol consistentes | src/app/api/students/[studentId]/route.ts, src/app/api/upload/route.ts, src/app/api/upload/course-content/route.ts | Crear middleware/helper server-side de autorizacion por rol y aplicarlo en endpoints sensibles (delete user, upload, update content). | M | admin_profiles, tokens, convencion auth headers | 1) Endpoints sensibles rechazan anonimos y roles no autorizados. 2) Pruebas de autorizacion negativas/positivas automatizadas. |
| R-05 | P1 | Storage con permisos amplios anon/authenticated | migrations/022_fix_storage_signatures_rls.sql | Eliminar FOR ALL para anon; separar buckets publicos vs privados; politicas por bucket + company scope + rol; impedir delete/update anonimo. | M | R-04, definicion de rutas por empresa | 1) Politicas storage sin TO anon para write/delete en productivo. 2) Usuario de otra empresa no puede leer/modificar objetos ajenos. |
| R-06 | P2 | Exposicion de archivos en ruta publica local | src/app/api/upload/route.ts | Migrar uploads sensibles a storage privado firmado o restringido; mantener public solo para activos no sensibles. Agregar validacion de tamano maximo y sanitizacion reforzada. | M | R-05 | 1) Firmas y docs sensibles no se sirven desde /public/uploads. 2) URL de acceso controlada (firmada/expirable). |
| R-07 | P2 | Falta de enforcement completo de bloqueo por intentos | migrations/032_generic_password_and_attempts.sql + flujo login | Aplicar logica efectiva de incremento, lockout, cooldown, desbloqueo y auditoria. | M | Flujo login corporativo | 1) Lockout ocurre segun max_login_attempts. 2) Eventos registrados en log. 3) Pruebas automatizadas de brute-force control. |
| R-08 | P2 | Auditoria tecnica no uniforme en acciones criticas | Endpoints admin, students, upload, auth | Estandarizar evento de auditoria (actor, accion, recurso, resultado, timestamp, tenant). Registrar altas/bajas/roles/password reset/delete. | M | Esquema logs | 1) Cobertura de eventos criticos >= 95%. 2) Evidencia consultable por rango temporal y actor. |
| R-09 | P2 | Controles de validacion de payload y contratos | src/app/api/admin/content/route.ts y otros | Incorporar validacion de esquema (zod/valibot) y limites por campo para evitar abuso de payload. | S | Biblioteca de validacion | 1) Payload invalido responde 400 con mensaje controlado. 2) No hay writes con campos no permitidos. |
| R-10 | P3 | Evidencia de consentimiento no obligatoria en todo flujo de certificacion | migrations/014_add_consent_tracking.sql + generacion certificacion | Exigir consentimiento antes de emitir certificacion y registrar evidencia de version de texto legal aceptado. | S | UX formulario, legal text versioning | 1) Certificado no se emite sin consent_accepted_at valido. 2) Se almacena version de consentimiento aceptado. |
| R-11 | P3 | Rotacion y custodia de claves de servicio | Variables de entorno y pipeline despliegue | Definir procedimiento de rotacion, segregacion por entorno y control de acceso a secretos. | S | CI/CD, vault/secrets manager | 1) Politica de rotacion documentada y aplicada. 2) Registro de ultima rotacion por entorno. |
| R-12 | P3 | Cobertura de pruebas de seguridad insuficiente | tests de API/RLS/autorizacion | Incorporar suite minima: authz endpoints, tenant isolation, storage ACL, regresion de políticas RLS. | M | Framework de test existente | 1) Pipeline ejecuta suite de seguridad. 2) Build falla ante regresion de control critico. |

## 3. Plan por fases

### Fase 1 (0-15 dias) - Bloqueo de riesgos criticos
- Ejecutar R-01, R-02, R-04, R-05.
- Salida esperada: sin password en negocio, endpoints admin blindados, storage y autorizacion endurecidos.

### Fase 2 (16-30 dias) - Aislamiento y cumplimiento tecnico base
- Ejecutar R-03, R-06, R-07.
- Salida esperada: RLS de minimo privilegio y controles operativos de login/upload consolidados.

### Fase 3 (31-60 dias) - Madurez de auditoria
- Ejecutar R-08, R-09, R-10, R-11, R-12.
- Salida esperada: trazabilidad, validaciones y evidencias listas para pre-auditoria ISO.

## 4. Evidencia minima requerida por remediacion
- Pull request con descripcion de riesgo y control aplicado.
- Evidencia de pruebas (capturas/logs/reportes automatizados).
- SQL/migraciones aplicadas y validadas en entorno controlado.
- Registro de aprobacion de Seguridad para cierre de cada ID.

## 5. Indicadores de avance (KPI)
- % de remediaciones P1 cerradas.
- # de endpoints criticos con autorizacion explicita por rol.
- # de politicas RLS permisivas remanentes.
- % de eventos criticos cubiertos por auditoria.
- % de pruebas de seguridad pasando en CI.

## 6. Estado inicial recomendado (baseline)
- P1 abiertos: R-01, R-02, R-03, R-04, R-05.
- P2 abiertos: R-06, R-07, R-08, R-09.
- P3 abiertos: R-10, R-11, R-12.

Nota: Esta matriz es tecnica y operativa. El cierre regulatorio/legal final debe validarse con asesoria legal y de cumplimiento.