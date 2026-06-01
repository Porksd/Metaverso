-- Mark students.company_name as legacy and keep it for compatibility only.
--
-- The application should no longer write this field for new registrations.
-- Current source of truth for company data is students.client_id -> companies.id.

COMMENT ON COLUMN students.company_name IS 'Legacy snapshot of company name. Do not write for new records; use students.client_id with companies as the source of truth.';