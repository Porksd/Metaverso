-- Enforce unique RUT per company while allowing the same RUT across different companies.
-- RUT is optional, so only non-empty values are indexed.
CREATE UNIQUE INDEX IF NOT EXISTS students_client_rut_unique_idx
ON public.students (
  client_id,
  (regexp_replace(upper(rut), '[^0-9K]', '', 'g'))
)
WHERE client_id IS NOT NULL
  AND rut IS NOT NULL
  AND btrim(rut) <> '';
