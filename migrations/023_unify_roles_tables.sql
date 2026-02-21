-- Migration: Unify job_positions and company_roles
-- 1. Add missing columns to company_roles
ALTER TABLE company_roles ADD COLUMN IF NOT EXISTS code TEXT;
ALTER TABLE company_roles ADD COLUMN IF NOT EXISTS active BOOLEAN DEFAULT true;

-- Ensure code is unique if we want to synchronize data easily
-- (Note: some company roles may not have codes, which is fine as they can be null)
CREATE UNIQUE INDEX IF NOT EXISTS idx_company_roles_code ON company_roles(code) WHERE code IS NOT NULL;

-- 2. Migrate data from job_positions to company_roles if it doesn't exist
INSERT INTO company_roles (name, name_ht, description, description_ht, code, active)
SELECT 
    name_es, 
    name_ht, 
    description_es, 
    description_ht, 
    code, 
    active 
FROM job_positions
ON CONFLICT (code) DO NOTHING;

-- 3. Note: We keep job_positions table for now to avoid breaking old references, 
-- but all new logic should use company_roles.

-- 4. Update the name comment for clarity
COMMENT ON COLUMN company_roles.name IS 'Role name in Spanish (unified with job_positions.name_es)';
