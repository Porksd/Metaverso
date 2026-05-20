-- Migration 034: Fix admin_profiles grants + RLS for admin management
-- Purpose:
-- 1) Ensure authenticated users can read admin_profiles.
-- 2) Ensure only superadmins (or known bootstrap emails) can manage rows.
-- 3) Seed bootstrap superadmins and administrador role.

-- Keep RLS enabled
ALTER TABLE admin_profiles ENABLE ROW LEVEL SECURITY;

-- Ensure authenticated role has SQL privileges (RLS still applies)
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE admin_profiles TO authenticated;

-- Remove old policies to avoid conflicts
DROP POLICY IF EXISTS "admin_profiles_read_own" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_all_super" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_select_all" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_manage_super" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_insert_super" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_update_super" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_delete_super" ON admin_profiles;

-- Read policy: any authenticated user can read (needed for role checks)
CREATE POLICY "admin_profiles_select_all"
ON admin_profiles
FOR SELECT
TO authenticated
USING (true);

-- Seed bootstrap superadmins so policy checks always have known anchors
INSERT INTO admin_profiles (email, role, permissions)
VALUES
    ('apacheco@lobus.cl',         'superadmin', '{"all": true}'),
    ('porksde@gmail.com',         'superadmin', '{"all": true}'),
    ('m.poblete.m@gmail.com',     'superadmin', '{"all": true}'),
    ('soporte@lobus.cl',          'superadmin', '{"all": true}'),
    ('apacheco@metaversotec.com', 'superadmin', '{"all": true}')
ON CONFLICT (email) DO UPDATE SET role = 'superadmin', permissions = '{"all": true}';

-- Ensure dedicated Administrador account
INSERT INTO admin_profiles (email, role, permissions)
VALUES ('admin@metaversotec.com', 'administrador', '{"delete_courses": true, "export_excel": false}')
ON CONFLICT (email) DO UPDATE SET role = 'administrador', permissions = '{"delete_courses": true, "export_excel": false}';

-- Helper expression reused in policies:
-- current user is allowed manager if:
-- A) email belongs to bootstrap superadmins, or
-- B) has role superadmin in admin_profiles

CREATE POLICY "admin_profiles_insert_super"
ON admin_profiles
FOR INSERT
TO authenticated
WITH CHECK (
    lower(auth.jwt() ->> 'email') IN (
        'apacheco@lobus.cl',
        'porksde@gmail.com',
        'm.poblete.m@gmail.com',
        'soporte@lobus.cl',
        'apacheco@metaversotec.com'
    )
    OR EXISTS (
        SELECT 1
        FROM admin_profiles ap
        WHERE ap.email = lower(auth.jwt() ->> 'email')
          AND ap.role = 'superadmin'
    )
);

CREATE POLICY "admin_profiles_update_super"
ON admin_profiles
FOR UPDATE
TO authenticated
USING (
    lower(auth.jwt() ->> 'email') IN (
        'apacheco@lobus.cl',
        'porksde@gmail.com',
        'm.poblete.m@gmail.com',
        'soporte@lobus.cl',
        'apacheco@metaversotec.com'
    )
    OR EXISTS (
        SELECT 1
        FROM admin_profiles ap
        WHERE ap.email = lower(auth.jwt() ->> 'email')
          AND ap.role = 'superadmin'
    )
)
WITH CHECK (
    lower(auth.jwt() ->> 'email') IN (
        'apacheco@lobus.cl',
        'porksde@gmail.com',
        'm.poblete.m@gmail.com',
        'soporte@lobus.cl',
        'apacheco@metaversotec.com'
    )
    OR EXISTS (
        SELECT 1
        FROM admin_profiles ap
        WHERE ap.email = lower(auth.jwt() ->> 'email')
          AND ap.role = 'superadmin'
    )
);

CREATE POLICY "admin_profiles_delete_super"
ON admin_profiles
FOR DELETE
TO authenticated
USING (
    lower(auth.jwt() ->> 'email') IN (
        'apacheco@lobus.cl',
        'porksde@gmail.com',
        'm.poblete.m@gmail.com',
        'soporte@lobus.cl',
        'apacheco@metaversotec.com'
    )
    OR EXISTS (
        SELECT 1
        FROM admin_profiles ap
        WHERE ap.email = lower(auth.jwt() ->> 'email')
          AND ap.role = 'superadmin'
    )
);
