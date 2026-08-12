-- Migration 030: Add Company User Registration Config & Fix Admins
-- Adds configuration for: Collaborating Company, Job Position, Gender, Age (Visible/Required)

ALTER TABLE companies
ADD COLUMN IF NOT EXISTS user_registration_config JSONB DEFAULT '{
    "company_collab": { "visible": true, "required": true },
    "job_position": { "visible": true, "required": true },
    "gender": { "visible": true, "required": true },
    "age": { "visible": true, "required": true }
}'::jsonb;

-- Fix: Ensure Admin Profiles exist for key users (if they were missing)
INSERT INTO admin_profiles (email, role, permissions)
VALUES 
('apacheco@lobus.cl', 'superadmin', '{"all": true}'),
    ('admin@metaversotec.com', 'administrador', '{"delete_courses": true, "export_excel": false}')
ON CONFLICT (email) DO UPDATE SET 
role = EXCLUDED.role,
permissions = EXCLUDED.permissions;
