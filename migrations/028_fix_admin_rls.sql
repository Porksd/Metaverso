-- Migration 028: Fix RLS for admin_profiles and elevate admin@metaversotec.com
-- Some Supabase environments have issues with circular references in RLS

-- 1. Drop old policies to avoid conflicts
DROP POLICY IF EXISTS "admin_profiles_read_own" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_all_super" ON admin_profiles;

-- 2. Create simplified policies
-- Allow everyone authenticated to READ the admin profiles (necessary for role checking)
CREATE POLICY "admin_profiles_select_all" ON admin_profiles 
FOR SELECT USING (auth.role() = 'authenticated');

-- Allow only superadmins to manage (INSERT/UPDATE/DELETE) profiles
-- We use a non-recursive approach or a simpler check if possible
CREATE POLICY "admin_profiles_manage_super" ON admin_profiles 
FOR ALL 
TO authenticated
USING (
    (SELECT role FROM admin_profiles WHERE email = auth.jwt() ->> 'email') = 'superadmin'
)
WITH CHECK (
    (SELECT role FROM admin_profiles WHERE email = auth.jwt() ->> 'email') = 'superadmin'
);

-- 3. Ensure the primary test admin is SuperAdmin
UPDATE admin_profiles 
SET role = 'superadmin' 
WHERE email = 'admin@metaversotec.com';

-- 4. Add Soporte as SuperAdmin if not exists
INSERT INTO admin_profiles (email, role, permissions)
VALUES ('porksde@gmail.com', 'superadmin', '{"all": true}')
ON CONFLICT (email) DO UPDATE SET role = 'superadmin';
