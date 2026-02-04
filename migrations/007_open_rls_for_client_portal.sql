-- Migration: Open RLS for tables used in the Corporate Portal (Empresa)
-- Since the Client Portal uses a custom login (not Supabase Auth), 
-- it operates with the 'anon' role.

-- 1. Table: companies_list (Sub-contractors)
ALTER TABLE IF EXISTS companies_list ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Companies list are viewable by everyone" ON companies_list;
DROP POLICY IF EXISTS "Companies list are editable by authenticated users" ON companies_list;
DROP POLICY IF EXISTS "Allow select for everyone" ON companies_list;
DROP POLICY IF EXISTS "Allow insert for everyone" ON companies_list;
DROP POLICY IF EXISTS "Allow update for everyone" ON companies_list;
DROP POLICY IF EXISTS "Allow delete for everyone" ON companies_list;

CREATE POLICY "Allow select for everyone" ON companies_list FOR SELECT USING (true);
CREATE POLICY "Allow insert for everyone" ON companies_list FOR INSERT WITH CHECK (true);
CREATE POLICY "Allow update for everyone" ON companies_list FOR UPDATE USING (true);
CREATE POLICY "Allow delete for everyone" ON companies_list FOR DELETE USING (true);

-- 2. Table: company_roles (Cargos)
ALTER TABLE IF EXISTS company_roles ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow company_roles for everyone" ON company_roles;
CREATE POLICY "Allow company_roles for everyone" ON company_roles FOR ALL USING (true) WITH CHECK (true);

-- 3. Table: students (Alumnos)
ALTER TABLE IF EXISTS students ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow student management for everyone" ON students;
CREATE POLICY "Allow student management for everyone" ON students FOR ALL USING (true) WITH CHECK (true);

-- 4. Table: companies (Main Company profile)
ALTER TABLE IF EXISTS companies ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow company view for everyone" ON companies;
CREATE POLICY "Allow company view for everyone" ON companies FOR SELECT USING (true);
DROP POLICY IF EXISTS "Allow company update for everyone" ON companies;
CREATE POLICY "Allow company update for everyone" ON companies FOR UPDATE USING (true);

-- 5. Table: company_courses (Assigned courses)
ALTER TABLE IF EXISTS company_courses ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow company_courses view for everyone" ON company_courses;
CREATE POLICY "Allow company_courses view for everyone" ON company_courses FOR SELECT USING (true);
DROP POLICY IF EXISTS "Allow company_courses management for everyone" ON company_courses;
CREATE POLICY "Allow company_courses management for everyone" ON company_courses FOR ALL USING (true) WITH CHECK (true);

-- 6. Table: enrollments (Student enrollment in courses)
ALTER TABLE IF EXISTS enrollments ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow enrollments management for everyone" ON enrollments;
CREATE POLICY "Allow enrollments management for everyone" ON enrollments FOR ALL USING (true) WITH CHECK (true);

