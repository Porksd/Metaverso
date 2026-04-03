-- Migration: Add missing enrollment fields for exam, survey tracking AND progress
ALTER TABLE enrollments 
ADD COLUMN IF NOT EXISTS last_exam_passed BOOLEAN DEFAULT NULL,
ADD COLUMN IF NOT EXISTS last_exam_score INTEGER DEFAULT NULL,
ADD COLUMN IF NOT EXISTS survey_completed BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS progress INTEGER DEFAULT 0;

COMMENT ON COLUMN enrollments.last_exam_passed IS 'Whether the student passed the final exam (true/false)';
COMMENT ON COLUMN enrollments.last_exam_score IS 'Score of the last exam attempt';
COMMENT ON COLUMN enrollments.survey_completed IS 'Whether the student has completed the mandatory survey';
COMMENT ON COLUMN enrollments.progress IS 'Overall progress percentage (0-100)';
