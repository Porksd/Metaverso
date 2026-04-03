-- Migration: Add registration_mode to company_courses
-- This allows different registration modes (open/restricted) for the same course in different companies.

ALTER TABLE company_courses 
ADD COLUMN IF NOT EXISTS registration_mode VARCHAR(50) DEFAULT 'open';

COMMENT ON COLUMN company_courses.registration_mode IS 'open (auto-enroll) or restricted (whitelist only) for this specific company assignment';

-- Optional: updates existing rows to match the course's default if needed, 
-- but since we just added the column on courses, we can migrate data if we want.
-- UPDATE company_courses cc
-- SET registration_mode = c.registration_mode
-- FROM courses c
-- WHERE cc.course_id = c.id;
