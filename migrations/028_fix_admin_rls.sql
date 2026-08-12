-- Migration 028: Fix RLS for admin_profiles and set correct permissions
-- This ensures the admin list is visible to authenticated users but editable only by superadmins

-- 1. Drop old policies to avoid conflicts
DROP POLICY IF EXISTS "admin_profiles_read_own" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_all_super" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_select_all" ON admin_profiles;
DROP POLICY IF EXISTS "admin_profiles_manage_super" ON admin_profiles;

-- 2. Create simplified policies
-- Allow everyone authenticated to READ the admin profiles
-- This is necessary so the frontend can check your role to authorize access to the page 
CREATE POLICY "admin_profiles_select_all" ON admin_profiles 
FOR SELECT USING (auth.role() = 'authenticated');

-- Allow only superadmins to manage (INSERT/UPDATE/DELETE) profiles
-- This uses a non-recursive approach (using auth.jwt() claims would be better but role is in table)
-- We check if the current user has 'superadmin' role in the table itself
CREATE POLICY "admin_profiles_manage_super" ON admin_profiles 
FOR ALL 
TO authenticated
USING (
    (SELECT role FROM admin_profiles WHERE email = auth.jwt() ->> 'email') = 'superadmin'
)
WITH CHECK (
    (SELECT role FROM admin_profiles WHERE email = auth.jwt() ->> 'email') = 'superadmin'
);

-- 3. Ensure dedicated administrador account keeps its limited role
UPDATE admin_profiles 
SET role = 'administrador', permissions = '{"delete_courses": true, "export_excel": false}'
WHERE email = 'admin@metaversotec.com';

-- 4. (Removed) No additional bootstrap superadmins are auto-seeded here.

-- 5. Ensure RLS is enabled
ALTER TABLE admin_profiles ENABLE ROW LEVEL SECURITY;

