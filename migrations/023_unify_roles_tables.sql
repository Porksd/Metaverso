-- Migration: Unify job_positions and company_roles
-- 1. Add missing columns to company_roles
ALTER TABLE company_roles ADD COLUMN IF NOT EXISTS code TEXT;
ALTER TABLE company_roles ADD COLUMN IF NOT EXISTS active BOOLEAN DEFAULT true;

-- Ensure code is unique to allow ON CONFLICT (ignoring NULLs)
-- Drop existing partial index if it exists to replace with a standard one or handle properly
DROP INDEX IF EXISTS idx_company_roles_code;
CREATE UNIQUE INDEX idx_company_roles_code ON company_roles(code) WHERE code IS NOT NULL;

-- 2. Migrate data from job_positions to company_roles
-- We specify the WHERE clause in ON CONFLICT to match the partial index exactly
INSERT INTO company_roles (name, name_ht, description, description_ht, code, active)
SELECT 
    name_es, 
    name_ht, 
    description_es, 
    description_ht, 
    code, 
    active 
FROM job_positions
ON CONFLICT (code) WHERE code IS NOT NULL DO NOTHING;

-- 3. Note: We keep job_positions table for now to avoid breaking old references, 
-- but all new logic should use company_roles.

-- 4. Update the name comment for clarity
COMMENT ON COLUMN company_roles.name IS 'Role name in Spanish (unified with job_positions.name_es)';
