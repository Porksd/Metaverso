# Politica de Seguridad y Proteccion de Datos de la Plataforma

Version: 1.1  
Estado: Vigente  
Fecha de entrada en vigor: 2026-06-03  
Propietario: Direccion de Tecnologia y Seguridad
Tipo de documento: Politica corporativa + matriz de cumplimiento auditable

## 1. Proposito
Establecer los requisitos obligatorios de seguridad de la informacion y proteccion de datos personales para la plataforma, con foco en confidencialidad, integridad, disponibilidad, trazabilidad y cumplimiento normativo aplicable en Chile.

## 2. Alcance
Esta politica aplica a:
- Aplicacion web y APIs de la plataforma.
- Base de datos, almacenamiento de archivos y servicios de autenticacion.
- Entornos de desarrollo, prueba y produccion.
- Personal interno, proveedores y terceros con acceso tecnico o funcional.

## 3. Principios rectores
- Minimo privilegio: todo acceso debe ser el minimo necesario para la funcion.
- Necesidad de saber: acceso solo a datos requeridos por rol y proceso.
- Privacidad por defecto: tratamiento de datos personales con minima exposicion.
- Seguridad por defecto: configuraciones seguras desde el diseno.
- Trazabilidad: toda accion sensible debe poder auditarse.

## 4. Clasificacion de datos
Se definen como datos personales y/o sensibles operativos de plataforma:
- Identificadores personales: RUT, pasaporte, correo, nombres y apellidos.
- Datos de perfil y operacion: cargo, empresa, idioma, edad, progreso academico.
- Evidencias de certificacion: firma digital, puntajes, certificados.
- Credenciales y secretos: contrasenas, tokens, claves de servicio.

## 5. Requisitos obligatorios de seguridad

### 5.1 Gestion de accesos
- Se debe usar control de acceso basado en roles (RBAC) para funciones administrativas.
- Toda accion administrativa debe exigir autenticacion valida y autorizacion por rol.
- Queda prohibido exponer operaciones administrativas sin validacion de identidad y rol.
- Cuentas privilegiadas deben ser nominativas, no compartidas, y con registro de uso.

### 5.2 Autenticacion y credenciales
- Queda prohibido almacenar contrasenas en texto plano en tablas de negocio.
- Las contrasenas deben gestionarse exclusivamente mediante el proveedor de autenticacion y almacenamiento seguro de hashes.
- Las claves de servicio y secretos deben residir solo en variables seguras de servidor.
- Debe existir politica de bloqueo por intentos fallidos y control de maximo de intentos.

### 5.3 Control de acceso a datos (RLS)
- Todas las tablas del esquema publico deben mantener RLS habilitado y forzado.
- Las politicas RLS deben usar condiciones restrictivas por usuario, rol y/o empresa.
- Se prohiben politicas generales de tipo USING (true) en produccion para datos personales.
- Toda excepcion temporal debe tener aprobacion formal, fecha de expiracion y plan de remediacion.

### 5.4 Segregacion multiempresa
- Los datos deben segregarse por company_id o equivalente en todas las consultas y escrituras.
- Debe evitarse cualquier configuracion global que permita mezcla de datos entre empresas.
- Las politicas y los indices de unicidad deben reforzar la segregacion por empresa.

### 5.5 Almacenamiento y cargas de archivos
- Los buckets y rutas de archivos deben restringirse por rol y/o ambito de empresa.
- Se prohibe acceso anonimo global a carga/modificacion/borrado de archivos productivos.
- Toda carga debe validar tipo, tamano y ruta permitida.
- Los archivos con datos personales deben tener retencion y borrado seguro definidos.

### 5.6 APIs y operaciones administrativas
- APIs de administracion deben implementar validacion de sesion y autorizacion explicita por rol.
- Se prohbe el uso de parametros de tabla dinamica sin lista blanca de tablas permitidas.
- Operaciones de alto impacto (borrado, reseteo de credenciales, cambios masivos) requieren trazabilidad completa.

### 5.7 Auditoria y monitoreo
- Debe registrarse acceso, modificacion y eliminacion sobre datos personales y configuraciones de seguridad.
- Los logs deben incluir fecha/hora, actor, accion, entidad afectada y resultado.
- Los registros de auditoria deben protegerse contra alteracion y mantener retencion definida.

### 5.8 Consentimiento y derechos de titulares
- Debe registrarse fecha y evidencia de aceptacion de consentimiento cuando corresponda.
- El tratamiento de datos para certificacion debe limitarse al fin declarado.
- Se debe habilitar gestion operativa para atender rectificacion, actualizacion y eliminacion conforme normativa aplicable.

### 5.9 Gestion de vulnerabilidades y cambios
- Todo cambio de seguridad debe pasar por control de cambios y prueba en entorno no productivo.
- Se deben ejecutar verificaciones periodicas de RLS, permisos y configuracion de storage.
- Hallazgos criticos deben corregirse con prioridad maxima.

## 6. Excepciones
Toda excepcion a esta politica requiere:
- Justificacion de negocio documentada.
- Evaluacion de riesgo.
- Aprobacion de propietario de seguridad.
- Fecha de vencimiento y plan de cierre.

## 7. Roles y responsabilidades
- Direccion de Tecnologia: aprobar y financiar cumplimiento tecnico.
- Responsable de Seguridad: definir controles, supervisar cumplimiento y gestionar excepciones.
- Equipo de Desarrollo: implementar controles en codigo, migraciones y APIs.
- Equipo de Operaciones: custodiar secretos, despliegues y evidencias operativas.
- Responsable de Datos: asegurar minimizacion, retencion y atencion de derechos.

## 8. Incidentes de seguridad
- Todo incidente que comprometa datos o disponibilidad debe notificarse internamente de inmediato.
- Se debe activar protocolo de respuesta, contencion, analisis causa raiz y acciones correctivas.
- Debe mantenerse evidencia de tiempos, impacto, alcance y medidas aplicadas.

## 9. Cumplimiento y medidas disciplinarias
El incumplimiento de esta politica puede generar:
- Bloqueo de accesos o funcionalidades.
- Reversion de despliegues inseguros.
- Acciones disciplinarias internas y/o contractuales.

## 10. Revision y mejora continua
- Revision ordinaria: semestral.
- Revision extraordinaria: ante incidentes severos o cambios regulatorios relevantes.
- Toda nueva version debe dejar trazabilidad de cambios.

---

## Anexo A: Controles de cumplimiento inmediato (prioridad alta)
1. Eliminar almacenamiento de contrasena en texto plano en datos de alumnos.  
2. Reemplazar politicas RLS permisivas por politicas de minimo privilegio.  
3. Restringir storage para evitar gestion anonima global en buckets productivos.  
4. Endurecer APIs administrativas con autorizacion explicita por rol en cada operacion.  
5. Implementar lista blanca de tablas para operaciones administrativas genericas.  
6. Consolidar registro de auditoria para altas, bajas, cambios de rol y borrado de datos.

## Anexo B: Evidencia tecnica base en repositorio
- RLS forzado en esquema publico: migrations/038_harden_public_tables_rls.sql  
- RBAC y politicas de admin_profiles: migrations/034_fix_admin_profiles_permissions_and_rls.sql  
- Politicas RLS permisivas historicas: migrations/016_review_and_optimize_rls.sql  
- Apertura amplia de storage en buckets: migrations/022_fix_storage_signatures_rls.sql  
- Intentos de login y bloqueo: migrations/032_generic_password_and_attempts.sql  
- Trazabilidad de consentimiento: migrations/014_add_consent_tracking.sql

## Anexo C: Matriz de cumplimiento (formato auditoria)

Estado de control:
- Cumple: control implementado y con evidencia suficiente.
- Parcial: control implementado en parte o con brechas relevantes.
- No cumple: control ausente o insuficiente para riesgo actual.

| ID | Control | Evidencia actual | Estado | Riesgo residual | Responsable | Plan de accion | Fecha objetivo |
|---|---|---|---|---|---|---|---|
| C-01 | No almacenar contrasenas en texto plano en tablas de negocio | src/app/api/students/register/route.ts (persistencia de password en students) | No cumple | Alto | Desarrollo + Seguridad | Eliminar columna/logica de password en students y migrar autenticacion al proveedor de Auth | 2026-06-30 |
| C-02 | RBAC para administracion con validacion por rol en endpoints criticos | src/lib/adminAuth.ts, migrations/034_fix_admin_profiles_permissions_and_rls.sql, src/app/api/admin/users/password/route.ts | Parcial | Alto | Desarrollo | Extender verificacion de rol en todos los endpoints admin y negar por defecto | 2026-06-20 |
| C-03 | RLS habilitado y forzado en tablas publicas | migrations/038_harden_public_tables_rls.sql | Cumple | Medio | DBA + Seguridad | Mantener control en cada nueva migracion | Permanente |
| C-04 | Politicas RLS de minimo privilegio (sin USING (true) en produccion) | migrations/016_review_and_optimize_rls.sql, REPORTE_PERMISOS_CORRECCION.md | No cumple | Alto | DBA + Seguridad | Reemplazar politicas permisivas por politicas por rol/empresa/usuario | 2026-06-25 |
| C-05 | Segregacion de datos multiempresa por company_id | migrations/035_scope_companies_list_per_company.sql | Parcial | Medio | Desarrollo + DBA | Extender segregacion a tablas y consultas restantes; pruebas de aislamiento | 2026-07-05 |
| C-06 | Storage sin acceso anonimo global para escritura/modificacion | migrations/022_fix_storage_signatures_rls.sql | No cumple | Alto | Seguridad + Plataforma | Restringir a autenticados por rol y alcance de empresa; separar buckets publicos/privados | 2026-06-22 |
| C-07 | APIs administrativas sin superficie de abuso por parametros dinamicos | src/app/api/admin/content/route.ts | No cumple | Alto | Desarrollo | Implementar lista blanca de tablas y operaciones permitidas + autorizacion por rol | 2026-06-18 |
| C-08 | Control de intentos y bloqueo de acceso | migrations/032_generic_password_and_attempts.sql | Parcial | Medio | Desarrollo | Aplicar enforcement real en login y desbloqueo controlado | 2026-06-28 |
| C-09 | Consentimiento trazable para certificacion | migrations/014_add_consent_tracking.sql | Cumple | Bajo | Producto + Datos | Incorporar validacion de presencia de consentimiento en flujos de certificacion | 2026-06-30 |
| C-10 | Auditoria de acciones sensibles (altas/bajas/roles/credenciales) | activity_logs + reportes internos | Parcial | Medio | Seguridad + Operaciones | Estandarizar eventos obligatorios y retencion de logs | 2026-07-10 |

## Anexo D: Mapeo normativo (orientativo)

### D.1 Referencia ISO/IEC 27001:2022 (Anexo A, orientativo)
- Control de acceso y roles (C-02, C-07): alineado con controles de gestion de accesos y privilegios.
- Proteccion de credenciales (C-01, C-08): alineado con gestion de secretos e identidad.
- Segregacion y minimo privilegio en datos (C-03, C-04, C-05): alineado con control de acceso a informacion y aplicaciones.
- Seguridad de servicios de almacenamiento y transferencia (C-06): alineado con seguridad de servicios y gestion de proveedores/plataformas.
- Registro, monitoreo y trazabilidad (C-10): alineado con logging, monitoreo y respuesta a eventos.
- Privacidad y tratamiento de datos (C-09): alineado con controles de proteccion de informacion personal.

### D.2 Referencia a marco regulatorio de ciberseguridad y datos en Chile
- Gobernanza y medidas de seguridad razonables: C-02, C-03, C-04, C-06, C-10.
- Gestion y trazabilidad de incidentes/eventos: C-10 y seccion 8 de esta politica.
- Proteccion de datos personales y finalidad: C-01, C-09 y seccion 5.8.
- Continuidad y mejora de controles: seccion 10 y plan del Anexo E.

Nota: este mapeo es de gestion interna y no sustituye una evaluacion legal formal externa.

## Anexo E: Plan de implementacion y seguimiento

### E.1 Hitos de corto plazo (0-30 dias)
1. Cerrar brechas criticas C-01, C-04, C-06 y C-07.
2. Publicar procedimiento de excepciones con aprobacion y vencimiento.
3. Ejecutar pruebas tecnicas de verificacion de aislamiento entre empresas.

### E.2 Hitos de mediano plazo (31-90 dias)
1. Completar enforcement de bloqueo de acceso (C-08).
2. Unificar esquema de auditoria de eventos y retencion (C-10).
3. Auditar cumplimiento de RBAC en endpoints y paneles admin (C-02).

### E.3 Cadencia de reporte
- Reporte semanal interno de avance de remediacion (controles no cumple/parcial).
- Reporte mensual de riesgo residual al comite de seguridad.
- Revalidacion semestral de esta politica y su matriz de cumplimiento.

## Control de cambios
- v1.1 (2026-06-03): adaptacion a formato de auditoria con matriz de cumplimiento, mapeo normativo y plan de implementacion.

## Documento complementario
- Matriz tecnica de remediacion ISO 27001: MATRIZ_TECNICA_REMEDIACION_ISO27001.md
