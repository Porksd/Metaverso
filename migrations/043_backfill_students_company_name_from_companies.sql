-- Backfill: synchronize students.company_name with the official company name.
--
-- Why:
-- company_name was historically denormalized in students and can become stale when
-- a company is renamed in companies. Certificates and exports must use the latest
-- official company data.
--
-- Scope:
-- - Only updates students linked to a real company via client_id.
-- - Leaves custom / uncoupled records untouched when client_id is NULL.
-- - Overwrites stale values on purpose so the stored snapshot matches the official source.
--
-- Safe to re-run:
-- - The UPDATE is idempotent: it always rewrites the denormalized field from companies.name.
-- - Rows without a matching company are ignored.

UPDATE students s
SET company_name = c.name
FROM companies c
WHERE s.client_id = c.id
  AND c.name IS NOT NULL
  AND btrim(c.name) <> ''
  AND (s.company_name IS NULL OR btrim(s.company_name) IS DISTINCT FROM btrim(c.name));

-- Optional verification after running:
-- SELECT s.id, s.client_id, s.company_name AS student_company_name, c.name AS official_company_name
-- FROM students s
-- JOIN companies c ON c.id = s.client_id
-- WHERE s.company_name IS DISTINCT FROM c.name
-- ORDER BY c.name, s.last_name, s.first_name;
