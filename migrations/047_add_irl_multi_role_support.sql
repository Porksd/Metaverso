-- Migration 047: Allow IRL certificate to target multiple roles per course-company assignment

ALTER TABLE company_courses
    ADD COLUMN IF NOT EXISTS irl_role_ids UUID[] NOT NULL DEFAULT '{}';

UPDATE company_courses
SET irl_role_ids = ARRAY[irl_role_id]
WHERE irl_role_id IS NOT NULL
  AND (irl_role_ids IS NULL OR array_length(irl_role_ids, 1) IS NULL);
