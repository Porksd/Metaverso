-- Migration: Create companies_list table for dynamic company management
-- This allows admin to manage list of companies that appear in registration form
CREATE TABLE IF NOT EXISTS companies_list (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) UNIQUE NOT NULL,
    name_es VARCHAR(255) NOT NULL,
    name_ht VARCHAR(255),
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
-- Create index on code for faster lookups
CREATE INDEX IF NOT EXISTS idx_companies_list_code ON companies_list(code);
CREATE INDEX IF NOT EXISTS idx_companies_list_active ON companies_list(active);
-- Add RLS policies
ALTER TABLE companies_list ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Companies list are viewable by everyone" ON companies_list FOR
SELECT USING (true);
CREATE POLICY "Companies list are editable by authenticated users" ON companies_list FOR ALL USING (auth.role() = 'authenticated');
-- Insert default companies from original system
INSERT INTO companies_list (code, name_es, name_ht)
VALUES ('SACYR', 'SACYR', 'SACYR'),
    ('OTRA', 'OTRA', 'LÒT') ON CONFLICT (code) DO NOTHING;
COMMENT ON TABLE companies_list IS 'List of companies available in student registration form';
COMMENT ON COLUMN companies_list.code IS 'Unique company code (used as value in forms)';
COMMENT ON COLUMN companies_list.name_es IS 'Company name in Spanish';
COMMENT ON COLUMN companies_list.name_ht IS 'Company name in Haitian Creole';