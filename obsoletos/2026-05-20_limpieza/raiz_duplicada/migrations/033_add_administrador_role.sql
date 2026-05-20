-- Migration 033: Add 'administrador' role and seed fallback superadmins
-- Ensures all hardcoded fallback superadmins have DB rows (required for RLS insert/update),
-- sets admin@metaversotec.com to 'administrador' role, and allows the new role in RLS.

-- 1. Upsert all fallback superadmins so their DB row exists (needed for RLS write policies)
INSERT INTO admin_profiles (email, role, permissions)
VALUES
    ('apacheco@lobus.cl',          'superadmin', '{"all": true}'),
    ('porksde@gmail.com',          'superadmin', '{"all": true}'),
    ('m.poblete.m@gmail.com',      'superadmin', '{"all": true}'),
    ('soporte@lobus.cl',           'superadmin', '{"all": true}'),
    ('apacheco@metaversotec.com',  'superadmin', '{"all": true}')
ON CONFLICT (email) DO UPDATE SET role = 'superadmin';

-- 2. Set admin@metaversotec.com as 'administrador' (can delete courses, cannot export Excel)
INSERT INTO admin_profiles (email, role, permissions)
VALUES ('admin@metaversotec.com', 'administrador', '{"delete_courses": true, "export_excel": false}')
ON CONFLICT (email) DO UPDATE SET role = 'administrador', permissions = '{"delete_courses": true, "export_excel": false}';

-- 3. Update the RLS manage policy to also allow existing superadmins to manage
-- (no structural change needed since existing policy reads role from the table and
--  all fallback superadmins now have rows with role='superadmin')

-- 4. Ensure RLS is still enabled
ALTER TABLE admin_profiles ENABLE ROW LEVEL SECURITY;
