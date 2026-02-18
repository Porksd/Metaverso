-- Migration: Add bilingual fields to company_roles
ALTER TABLE company_roles ADD COLUMN IF NOT EXISTS name_ht TEXT;
ALTER TABLE company_roles ADD COLUMN IF NOT EXISTS description TEXT;
ALTER TABLE company_roles ADD COLUMN IF NOT EXISTS description_ht TEXT;

COMMENT ON COLUMN company_roles.description IS 'Small descriptive text or tip for the role in Spanish';
COMMENT ON COLUMN company_roles.description_ht IS 'Small descriptive text or tip for the role in Haitian Creole';
COMMENT ON COLUMN company_roles.name_ht IS 'Role name in Haitian Creole';
