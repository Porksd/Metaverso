-- Migration: Fix students table foreign key constraint
-- The students table's client_id currently points to an old 'clients' table.
-- It should point to the 'companies' table which is the active one.

-- 1. Identify and drop the old constraint
ALTER TABLE IF EXISTS students 
DROP CONSTRAINT IF EXISTS students_client_id_fkey;

-- 2. Add the new constraint pointing to companies table
ALTER TABLE IF EXISTS students
ADD CONSTRAINT students_client_id_fkey 
FOREIGN KEY (client_id) 
REFERENCES companies(id) 
ON DELETE CASCADE;

-- 3. Update the company_roles table too if it has a similar issue
-- (I saw in check_db_schema that company_id is used there)
ALTER TABLE IF EXISTS company_roles
DROP CONSTRAINT IF EXISTS company_roles_company_id_fkey;

ALTER TABLE IF EXISTS company_roles
ADD CONSTRAINT company_roles_company_id_fkey
FOREIGN KEY (company_id)
REFERENCES companies(id)
ON DELETE CASCADE;

COMMENT ON CONSTRAINT students_client_id_fkey ON students IS 'Point students to companies table instead of old clients table';
