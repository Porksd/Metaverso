-- Migration: Create job_positions table for dynamic job position management
-- This allows admin to manage job positions with descriptions that appear in registration
CREATE TABLE IF NOT EXISTS job_positions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) UNIQUE NOT NULL,
    name_es VARCHAR(255) NOT NULL,
    name_ht VARCHAR(255),
    description_es TEXT,
    description_ht TEXT,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
-- Create indexes
CREATE INDEX IF NOT EXISTS idx_job_positions_code ON job_positions(code);
CREATE INDEX IF NOT EXISTS idx_job_positions_active ON job_positions(active);
-- Add RLS policies
ALTER TABLE job_positions ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Job positions are viewable by everyone" ON job_positions FOR
SELECT USING (true);
CREATE POLICY "Job positions are editable by authenticated users" ON job_positions FOR ALL USING (auth.role() = 'authenticated');
COMMENT ON TABLE job_positions IS 'Job positions with descriptions for student registration';
COMMENT ON COLUMN job_positions.code IS 'Unique job position code';
COMMENT ON COLUMN job_positions.name_es IS 'Job position name in Spanish';
COMMENT ON COLUMN job_positions.name_ht IS 'Job position name in Haitian Creole';
COMMENT ON COLUMN job_positions.description_es IS 'Job description in Spanish (shown in popup)';
COMMENT ON COLUMN job_positions.description_ht IS 'Job description in Haitian Creole (shown in popup)';