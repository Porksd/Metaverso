-- Migration: Add consent tracking for data processing agreement
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS consent_accepted_at TIMESTAMP WITH TIME ZONE;

COMMENT ON COLUMN students.consent_accepted_at IS 'Timestamp when student accepted data processing consent for certification';
