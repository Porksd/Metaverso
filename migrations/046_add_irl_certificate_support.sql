-- Migration 046: Add IRL certificate support
-- IRL = INFORMAR LOS RIESGOS LABORALES

ALTER TABLE courses
    ADD COLUMN IF NOT EXISTS irl_certificate_enabled BOOLEAN NOT NULL DEFAULT false;

ALTER TABLE company_courses
    ADD COLUMN IF NOT EXISTS cert_irl_enabled BOOLEAN NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS irl_role_id UUID NULL REFERENCES company_roles(id) ON DELETE SET NULL;

ALTER TABLE enrollments
    ADD COLUMN IF NOT EXISTS irl_confirmed BOOLEAN NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS irl_confirmed_at TIMESTAMPTZ NULL;

CREATE TABLE IF NOT EXISTS course_irl_documents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    course_id UUID NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    file_url TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_course_irl_documents_course_id
    ON course_irl_documents(course_id);

CREATE INDEX IF NOT EXISTS idx_company_courses_irl_role_id
    ON company_courses(irl_role_id);

ALTER TABLE IF EXISTS course_irl_documents ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow course_irl_documents view for everyone" ON course_irl_documents;
CREATE POLICY "Allow course_irl_documents view for everyone"
    ON course_irl_documents FOR SELECT
    USING (true);

DROP POLICY IF EXISTS "Allow course_irl_documents management for everyone" ON course_irl_documents;
CREATE POLICY "Allow course_irl_documents management for everyone"
    ON course_irl_documents FOR ALL
    USING (true)
    WITH CHECK (true);
