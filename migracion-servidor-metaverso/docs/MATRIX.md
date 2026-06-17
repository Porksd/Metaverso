# Matriz de migracion por modulo

## Leyenda
- Prioridad 1: bloquea el arranque o el corte.
- Prioridad 2: necesaria para operar la plataforma.
- Prioridad 3: importante pero puede esperar a una segunda pasada.

## Matriz

| Modulo | Prioridad | Reemplazo propuesto | Dependencias | Checkpoint |
| --- | --- | --- | --- | --- |
| Login y sesiones | 1 | PHP sessions + cookies seguras + tabla de usuarios propia | usuarios, roles, admin_profiles | CP-3 |
| Registro publico de alumnos | 1 | Formulario PHP + validacion server-side + MariaDB | companies, students, enrollments | CP-2 / CP-3 |
| Panel admin metaverso | 1 | Backend PHP + vistas server-side o API interna | auth, roles, companies, courses, students | CP-3 / CP-5 |
| Panel admin empresa | 1 | Backend PHP + control de permisos por empresa | companies, company_roles, assignments | CP-3 / CP-5 |
| Cursos y matriculas | 1 | CRUD PHP sobre MariaDB | courses, enrollments, company_courses, progress | CP-2 / CP-3 |
| Certificados | 1 | Generacion local con PHP | courses, students, enrollments, certificate assets | CP-4 |
| Upload de contenido | 1 | Upload local en hosting / directorio protegido | storage local, course_content | CP-4 |
| Progreso SCORM | 2 | Persistencia PHP + MariaDB | course_progress, activity_logs | CP-3 |
| Encuestas | 2 | CRUD PHP + tablas propias | surveys, answers, stats | CP-3 / CP-5 |
| Empresas | 1 | CRUD PHP + MariaDB | companies, companies_list, company_roles | CP-2 / CP-3 |
| Alumnos empresa | 1 | CRUD PHP + MariaDB | students, enrollments, auth_user_id | CP-2 / CP-3 |
| Cargos y roles | 2 | CRUD PHP + reglas de visibilidad | job_positions, company_roles, role_company_assignments | CP-2 / CP-3 |
| Contenido de cursos | 1 | Estructura local o DB + archivos servidos por PHP | course_content, content assets | CP-4 |
| Dashboard y reportes | 2 | Consultas MariaDB + vistas PHP | enrollments, progress, logs, surveys | CP-5 |
| Demo pages | 3 | Reutilizar solo si aportan valor comercial | datos de ejemplo | CP-5 |

## Orden de implementacion recomendado
1. Login y sesiones.
2. Empresas, alumnos y cursos.
3. Matriculas y progreso.
4. Certificados y uploads.
5. Paneles admin.
6. Encuestas, reportes y extras.

## Bloqueos criticos
- Si login no existe, ninguna otra pantalla es usable.
- Si no existe MariaDB con el esquema base, no se puede migrar datos.
- Si no existe upload local, los cursos quedan incompletos.
- Si no existe generacion de certificados, el flujo comercial queda roto.

## Decision abierta
Antes de construir el frontend definitivo hay que decidir si la primera version sera:
- PHP server-rendered simple, o
- PHP API + frontend separado.

## Proximo artefacto
Crear el esquema inicial MariaDB con las tablas base del checkpoint CP-2.