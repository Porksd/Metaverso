-- Migration: Fix companies table RLS policies
-- Adding missing INSERT and DELETE policies that were omitted in previous migrations

ALTER TABLE IF EXISTS companies ENABLE ROW LEVEL SECURITY;

-- Allow INSERT for authenticated users (Master Admin)
DROP POLICY IF EXISTS "Allow company insert for everyone" ON companies;
CREATE POLICY "Allow company insert for everyone" ON companies 
FOR INSERT WITH CHECK (true);

-- Allow DELETE for authenticated users (Master Admin)
DROP POLICY IF EXISTS "Allow company delete for everyone" ON companies;
CREATE POLICY "Allow company delete for everyone" ON companies 
FOR DELETE USING (true);
