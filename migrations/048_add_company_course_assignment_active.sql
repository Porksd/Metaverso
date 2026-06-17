-- Migration 048: Keep company-course assignment but allow deactivating course availability.

ALTER TABLE company_courses
    ADD COLUMN IF NOT EXISTS assignment_active BOOLEAN NOT NULL DEFAULT true;

CREATE INDEX IF NOT EXISTS idx_company_courses_assignment_active
    ON company_courses(company_id, assignment_active);
