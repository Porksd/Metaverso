# Puntos de control

## CP-0 Alineacion tecnica
Objetivo: confirmar capacidades reales del hosting y decidir la primera arquitectura.

Criterio de cierre:
- Hosting validado.
- Subdominio definido.
- Ruta de despliegue elegida.

## CP-1 Inventario funcional
Objetivo: saber exactamente que hace la plataforma actual y que depende de Supabase.

Criterio de cierre:
- Modulos enumerados.
- Flujos criticos documentados.
- Dependencias bloqueantes identificadas.

## CP-2 Modelo de datos
Objetivo: tener el esquema MariaDB listo para implementar.

Criterio de cierre:
- Tablas principales definidas.
- Relaciones y claves pensadas.
- Plan de migracion de datos preparado.

## CP-3 Backend base
Objetivo: tener autenticacion y CRUD principal funcionando en PHP.

Criterio de cierre:
- Login operativo.
- Lectura y escritura de datos principales.
- Estructura de APIs estable.

## CP-4 Archivos y certificados
Objetivo: reemplazar storage de Supabase.

Criterio de cierre:
- Subida y descarga de archivos funcionando.
- Certificados generables desde el nuevo entorno.

## CP-5 Frontend funcional
Objetivo: tener una interfaz operativa sobre el backend nuevo.

Criterio de cierre:
- Flujos clave accesibles.
- Pantallas principales listas.
- Permisos respetados.

## CP-6 Corte productivo
Objetivo: activar la nueva plataforma como principal.

Criterio de cierre:
- Pruebas aprobadas.
- Datos migrados.
- Supabase fuera del camino critico.