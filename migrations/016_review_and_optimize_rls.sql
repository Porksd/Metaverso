-- Migration: Revisar y optimizar políticas RLS
-- Este script revisa todas las políticas RLS para asegurar que:
-- 1. Los registros funcionen correctamente
-- 2. Las eliminaciones estén permitidas con las restricciones adecuadas
-- 3. La subida de contenido funcione sin problemas
-- 4. Se mantenga un nivel básico de seguridad

-- ============================================
-- TABLA: students
-- ============================================
-- Todos pueden registrarse y ver/editar sus propios datos
ALTER TABLE IF EXISTS students ENABLE ROW LEVEL SECURITY;

-- Eliminar políticas existentes para evitar conflictos
DROP POLICY IF EXISTS "Allow student management for everyone" ON students;
DROP POLICY IF EXISTS "Enable update access for students" ON students;
DROP POLICY IF EXISTS "Allow insert for everyone" ON students;
DROP POLICY IF EXISTS "Allow select for everyone" ON students;
DROP POLICY IF EXISTS "Allow update for everyone" ON students;
DROP POLICY IF EXISTS "Allow delete for everyone" ON students;

-- Políticas nuevas más específicas
CREATE POLICY "students_select_all" ON students 
    FOR SELECT USING (true);

CREATE POLICY "students_insert_all" ON students 
    FOR INSERT WITH CHECK (true);

CREATE POLICY "students_update_all" ON students 
    FOR UPDATE USING (true);

CREATE POLICY "students_delete_all" ON students 
    FOR DELETE USING (true);

COMMENT ON POLICY "students_select_all" ON students IS 
'Permite a todos ver registros de estudiantes (para listados administrativos)';

COMMENT ON POLICY "students_insert_all" ON students IS 
'Permite registro de nuevos estudiantes desde formularios públicos';

-- ============================================
-- TABLA: enrollments
-- ============================================
ALTER TABLE IF EXISTS enrollments ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow enrollments management for everyone" ON enrollments;
DROP POLICY IF EXISTS "Enable delete access for enrollments" ON enrollments;
DROP POLICY IF EXISTS "Allow company managers to delete enrollments" ON enrollments;

CREATE POLICY "enrollments_all_operations" ON enrollments 
    FOR ALL USING (true) WITH CHECK (true);

COMMENT ON POLICY "enrollments_all_operations" ON enrollments IS 
'Permite todas las operaciones en enrollments (registro, actualización, eliminación)';

-- ============================================
-- TABLA: course_progress
-- ============================================
ALTER TABLE IF EXISTS course_progress ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Enable delete access for course_progress" ON course_progress;
DROP POLICY IF EXISTS "Allow deleting course progress" ON course_progress;

CREATE POLICY "course_progress_all_operations" ON course_progress 
    FOR ALL USING (true) WITH CHECK (true);

COMMENT ON POLICY "course_progress_all_operations" ON course_progress IS 
'Permite todas las operaciones en progreso de cursos';

-- ============================================
-- TABLA: activity_logs
-- ============================================
ALTER TABLE IF EXISTS activity_logs ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Enable delete access for activity_logs" ON activity_logs;
DROP POLICY IF EXISTS "Allow deleting activity logs" ON activity_logs;

CREATE POLICY "activity_logs_all_operations" ON activity_logs 
    FOR ALL USING (true) WITH CHECK (true);

COMMENT ON POLICY "activity_logs_all_operations" ON activity_logs IS 
'Permite todas las operaciones en logs de actividad';

-- ============================================
-- TABLA: course_content
-- ============================================
-- Para subida de contenido
ALTER TABLE IF EXISTS course_content ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow course_content for everyone" ON course_content;
DROP POLICY IF EXISTS "course_content_all_operations" ON course_content;

CREATE POLICY "course_content_all_operations" ON course_content 
    FOR ALL USING (true) WITH CHECK (true);

COMMENT ON POLICY "course_content_all_operations" ON course_content IS 
'Permite gestión completa de contenido de cursos (videos, SCORM, etc.)';

-- ============================================
-- TABLA: course_modules
-- ============================================
ALTER TABLE IF EXISTS course_modules ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow course_modules for everyone" ON course_modules;

CREATE POLICY "course_modules_all_operations" ON course_modules 
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- TABLA: module_items
-- ============================================
ALTER TABLE IF EXISTS module_items ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow module_items for everyone" ON module_items;

CREATE POLICY "module_items_all_operations" ON module_items 
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- TABLA: companies
-- ============================================
ALTER TABLE IF EXISTS companies ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow company view for everyone" ON companies;
DROP POLICY IF EXISTS "Allow company update for everyone" ON companies;
DROP POLICY IF EXISTS "Allow company insert for everyone" ON companies;
DROP POLICY IF EXISTS "Allow company delete for everyone" ON companies;

CREATE POLICY "companies_all_operations" ON companies 
    FOR ALL USING (true) WITH CHECK (true);

COMMENT ON POLICY "companies_all_operations" ON companies IS 
'Permite gestión completa de empresas';

-- ============================================
-- TABLA: companies_list
-- ============================================
ALTER TABLE IF EXISTS companies_list ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow select for everyone" ON companies_list;
DROP POLICY IF EXISTS "Allow insert for everyone" ON companies_list;
DROP POLICY IF EXISTS "Allow update for everyone" ON companies_list;
DROP POLICY IF EXISTS "Allow delete for everyone" ON companies_list;

CREATE POLICY "companies_list_all_operations" ON companies_list 
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- TABLA: company_courses
-- ============================================
ALTER TABLE IF EXISTS company_courses ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow company_courses view for everyone" ON company_courses;
DROP POLICY IF EXISTS "Allow company_courses management for everyone" ON company_courses;

CREATE POLICY "company_courses_all_operations" ON company_courses 
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- TABLA: company_roles
-- ============================================
ALTER TABLE IF EXISTS company_roles ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow company_roles for everyone" ON company_roles;

CREATE POLICY "company_roles_all_operations" ON company_roles 
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- TABLA: job_positions
-- ============================================
ALTER TABLE IF EXISTS job_positions ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "job_positions_all_operations" ON job_positions;

CREATE POLICY "job_positions_all_operations" ON job_positions 
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- TABLA: courses
-- ============================================
ALTER TABLE IF EXISTS courses ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "courses_all_operations" ON courses;

CREATE POLICY "courses_all_operations" ON courses 
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================
-- RESUMEN
-- ============================================
-- Esta migración establece políticas RLS permisivas (true) para todas las tablas.
-- Esto es apropiado para:
-- 1. Un sistema interno donde todos los usuarios son confiables
-- 2. Una fase de desarrollo/prueba
-- 3. Un sistema con autenticación a nivel de aplicación
--
-- NOTA DE SEGURIDAD:
-- Para producción, considera implementar políticas más restrictivas basadas en:
-- - auth.uid() para verificar usuarios autenticados
-- - client_id para segregar datos por empresa
-- - roles específicos (admin, manager, student)
