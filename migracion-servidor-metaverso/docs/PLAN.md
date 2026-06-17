# Plan de migracion al servidor de metaverso

## Objetivo
Migrar la plataforma actual desde Supabase a una arquitectura propia sobre PHP 8.1-8.4 + MariaDB 10.11, sin tocar el desarrollo actual mientras se prepara la nueva base en paralelo.

## Regla de trabajo
- Todo el trabajo nuevo vive dentro de `migracion-servidor-metaverso/`.
- El desarrollo actual no se modifica.
- Cada avance importante se registra en `docs/PROGRESS.md`.
- Cada fase tiene un punto de control antes de pasar a la siguiente.

## Flujo de control
1. Definir alcance y restricciones del hosting.
2. Inventariar dependencias funcionales y técnicas.
3. Diseñar base de datos y modelo de migracion.
4. Construir backend PHP y autenticacion.
5. Migrar storage, certificados y archivos.
6. Rehacer frontend o capa de presentacion.
7. Validar, probar y preparar el corte.

## Fase 0: Alineacion tecnica
- Confirmar si el hosting cPanel permite ejecucion PHP pura y almacenamiento de archivos.
- Definir subdominio de la nueva app.
- Definir si la primera version sera solo backend/API o backend + frontend completo.
- Revisar si hay acceso SSH y posibilidad de Git deployment desde cPanel.

## Fase 1: Inventario funcional
- Listar modulos actuales dependientes de Supabase.
- Identificar tablas, relaciones, auth, storage y procesos criticos.
- Separar flujos de admin, empresas, alumnos, cursos y certificados.
- Marcar dependencias que no pueden quedar fuera del corte.

## Fase 2: Modelo de datos MariaDB
- Diseñar esquema inicial equivalente al uso real actual.
- Definir usuarios, roles, empresas, cursos, matriculas, progreso, logs y documentos.
- Preparar migracion de datos desde Supabase a MariaDB.

## Fase 3: Backend PHP
- Crear autenticacion propia.
- Implementar CRUD principal por modulo.
- Reemplazar acceso a datos que hoy hace Supabase.
- Crear endpoints para carga de archivos y tareas administrativas.

## Fase 4: Archivos y certificados
- Definir almacenamiento local o en carpeta servida por el hosting.
- Migrar uploads de contenido y certificados.
- Ajustar URLs publicas y generacion de documentos.

## Fase 5: Frontend
- Rehacer la interfaz que hoy vive en Next.js para consumir el nuevo backend.
- Priorizar login, panel admin, alumnos, empresas y cursos.
- Mantener la experiencia funcional, no necesariamente la misma tecnologia.

## Fase 6: Corte y validacion
- Cargar una copia de prueba completa.
- Validar accesos, permisos, matriculas, subida de archivos y certificados.
- Hacer el cambio de DNS o subdominio cuando la version nueva quede estable.
- Desactivar dependencias de Supabase solo cuando el flujo completo quede verificado.

## Entregables vivos
- Inventario de modulos y dependencias.
- Esquema MariaDB propuesto.
- Estructura base del nuevo proyecto.
- Plan de migracion de datos y archivos.
- Registro de avances y bloqueos.

## Punto de reanudacion
La siguiente sesion debe empezar leyendo este archivo, luego revisar `docs/CHECKPOINTS.md` y finalmente abrir `docs/PROGRESS.md` para saber el ultimo avance real.

## Siguiente paso recomendado
Empezar por el inventario funcional y el esquema de base de datos.
