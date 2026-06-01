-- Cleanup (general): remove GLOBAL role assignments for ALL companies.
--
-- Why:
-- In scenarios where each company should manage only its own local roles,
-- inherited global role assignments can expose roles that are not manageable
-- from the company profile.
--
-- Scope:
-- Deletes rows from role_company_assignments where the assigned role is global:
--   company_roles.company_id IS NULL
--
-- Safety:
-- - Idempotent: can be executed multiple times.
-- - Only affects assignment links; does not delete roles from company_roles.

WITH global_roles AS (
    SELECT id
    FROM company_roles
    WHERE company_id IS NULL
)
DELETE FROM role_company_assignments rca
USING global_roles gr
WHERE rca.role_id = gr.id;

-- Optional verification (after cleanup):
-- SELECT COUNT(*) AS remaining_global_assignments
-- FROM role_company_assignments rca
-- JOIN company_roles cr ON cr.id = rca.role_id
-- WHERE cr.company_id IS NULL;

-- Optional impact preview (before running DELETE):
-- SELECT c.id AS company_id, c.name AS company_name, COUNT(*) AS global_assignments
-- FROM role_company_assignments rca
-- JOIN company_roles cr ON cr.id = rca.role_id
-- JOIN companies c ON c.id = rca.company_id
-- WHERE cr.company_id IS NULL
-- GROUP BY c.id, c.name
-- ORDER BY global_assignments DESC, c.name;
