-- Migration: Add missing fields to students table for original system
-- This restores all fields from the Apps Script original system
-- Add missing columns to students table
ALTER TABLE students
ADD COLUMN IF NOT EXISTS language VARCHAR(5) DEFAULT 'es',
    ADD COLUMN IF NOT EXISTS email VARCHAR(255),
    ADD COLUMN IF NOT EXISTS gender VARCHAR(50),
    ADD COLUMN IF NOT EXISTS age INTEGER,
    ADD COLUMN IF NOT EXISTS company_name VARCHAR(255),
    ADD COLUMN IF NOT EXISTS passport VARCHAR(100),
    ADD COLUMN IF NOT EXISTS digital_signature_url TEXT;
-- Create index on email for faster lookups
CREATE INDEX IF NOT EXISTS idx_students_email ON students(email);
-- Add comment to table
COMMENT ON COLUMN students.language IS 'Language preference: es (Spanish) or ht (Haitian Creole)';
COMMENT ON COLUMN students.email IS 'Student email address';
COMMENT ON COLUMN students.gender IS 'Gender: Masculino, Femenino, Otro';
COMMENT ON COLUMN students.age IS 'Student age';
COMMENT ON COLUMN students.company_name IS 'Company name (can be from companies_list or custom)';
COMMENT ON COLUMN students.passport IS 'Passport number (alternative to RUT)';
COMMENT ON COLUMN students.digital_signature_url IS 'URL to student digital signature image';