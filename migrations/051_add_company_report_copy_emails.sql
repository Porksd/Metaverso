ALTER TABLE companies
ADD COLUMN IF NOT EXISTS report_copy_emails text;

COMMENT ON COLUMN companies.report_copy_emails IS 'Correos separados por comas que reciben copia de los informes automáticos de avance.';