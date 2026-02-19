-- Migration: Add missing enrollment fields for exam and survey tracking
ALTER TABLE enrollments 
ADD COLUMN IF NOT EXISTS last_exam_passed BOOLEAN DEFAULT NULL,
ADD COLUMN IF NOT EXISTS last_exam_score INTEGER DEFAULT NULL,
ADD COLUMN IF NOT EXISTS survey_completed BOOLEAN DEFAULT FALSE;

COMMENT ON COLUMN enrollments.last_exam_passed IS 'Whether the student passed the final exam (true/false)';
COMMENT ON COLUMN enrollments.last_exam_score IS 'Score of the last exam attempt';
COMMENT ON COLUMN enrollments.survey_completed IS 'Whether the student has completed the mandatory survey';
