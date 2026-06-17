-- Migration 049: Track certificate downloads/emissions to enable smart reissue prompts.

CREATE TABLE IF NOT EXISTS certificate_issuances (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id uuid NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    student_id uuid NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    enrollment_id uuid NOT NULL REFERENCES enrollments(id) ON DELETE CASCADE,
    course_id uuid NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    certificate_type text NOT NULL CHECK (certificate_type IN ('participacion', 'aprobacion', 'irl')),
    content_signature text NOT NULL,
    issued_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_certificate_issuances_company_enrollment
    ON certificate_issuances(company_id, enrollment_id, certificate_type, issued_at DESC);

ALTER TABLE certificate_issuances ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "certificate_issuances_select_authenticated" ON certificate_issuances;
CREATE POLICY "certificate_issuances_select_authenticated"
ON certificate_issuances
FOR SELECT
TO authenticated
USING (true);

DROP POLICY IF EXISTS "certificate_issuances_insert_authenticated" ON certificate_issuances;
CREATE POLICY "certificate_issuances_insert_authenticated"
ON certificate_issuances
FOR INSERT
TO authenticated
WITH CHECK (true);
