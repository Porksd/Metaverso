-- Migración para poblar la nueva tabla de asignaciones con los datos existentes
-- Esto evita que los cargos "desaparezcan" tras el cambio de lógica

-- 1. Asignar cargos específicos a sus respectivas empresas
INSERT INTO role_company_assignments (role_id, company_id, is_visible)
SELECT id, company_id, true
FROM company_roles
WHERE company_id IS NOT NULL
ON CONFLICT (role_id, company_id) DO NOTHING;

-- 2. Asignar cargos globales (company_id IS NULL) a TODAS las empresas existentes
-- para que sigan siendo visibles por defecto en los portales hasta que se decida ocultarlos.
INSERT INTO role_company_assignments (role_id, company_id, is_visible)
SELECT cr.id, c.id, true
FROM company_roles cr
CROSS JOIN companies c
WHERE cr.company_id IS NULL
ON CONFLICT (role_id, company_id) DO NOTHING;
