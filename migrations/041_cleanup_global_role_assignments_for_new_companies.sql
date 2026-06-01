-- Cleanup: remove inherited GLOBAL role assignments for specific companies.
--
-- Why:
-- Some new companies were created without local role configuration, but still ended up
-- with global roles visible in student portal due to existing assignments.
--
-- Scope:
-- This script only removes assignments where:
--   1) assignment belongs to target company, and
--   2) assigned role is GLOBAL (company_roles.company_id IS NULL)
--
-- Safe to re-run (idempotent): if rows were already removed, DELETE affects 0 rows.

WITH target_companies AS (
    SELECT id
    FROM companies
    WHERE lower(name) IN (
        lower('G&D Logistica y Distribucion SpA'),
        lower('G&D Logística y Distribución SpA')
    )
),
global_roles AS (
    SELECT id
    FROM company_roles
    WHERE company_id IS NULL
)
DELETE FROM role_company_assignments rca
USING target_companies tc, global_roles gr
WHERE rca.company_id = tc.id
  AND rca.role_id = gr.id;

-- Optional verification query (manual):
-- SELECT rca.*
-- FROM role_company_assignments rca
-- JOIN company_roles cr ON cr.id = rca.role_id
-- JOIN companies c ON c.id = rca.company_id
-- WHERE c.name ILIKE 'G&D Log%' AND cr.company_id IS NULL;
