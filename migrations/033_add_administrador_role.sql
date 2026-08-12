-- Migration 033: Add 'administrador' role and keep a minimal bootstrap seed.
-- Ensures only the primary superadmin and administrador have guaranteed DB rows.

-- 1. Upsert only the primary bootstrap superadmin
INSERT INTO admin_profiles (email, role, permissions)
VALUES
    ('apacheco@lobus.cl',          'superadmin', '{"all": true}')
ON CONFLICT (email) DO UPDATE SET role = 'superadmin';

-- 2. Set admin@metaversotec.com as 'administrador' (can delete courses, cannot export Excel)
INSERT INTO admin_profiles (email, role, permissions)
VALUES ('admin@metaversotec.com', 'administrador', '{"delete_courses": true, "export_excel": false}')
ON CONFLICT (email) DO UPDATE SET role = 'administrador', permissions = '{"delete_courses": true, "export_excel": false}';

-- 3. No RLS structural change is required here.

-- 4. Ensure RLS is still enabled
ALTER TABLE admin_profiles ENABLE ROW LEVEL SECURITY;
