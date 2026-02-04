-- Migration: Add score tracking to enrollments for weighted evaluations
ALTER TABLE enrollments 
ADD COLUMN IF NOT EXISTS quiz_score INTEGER DEFAULT 0,
ADD COLUMN IF NOT EXISTS scorm_score INTEGER DEFAULT 0;

COMMENT ON COLUMN enrollments.quiz_score IS 'Score obtained in the final quiz (0-100)';
COMMENT ON COLUMN enrollments.scorm_score IS 'Score obtained in the SCORM activity (0-100)';
