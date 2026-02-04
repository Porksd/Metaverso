-- Migration: Open RLS for modules and items
-- This ensures the admin panel (using anon key for now) can manage content

ALTER TABLE IF EXISTS course_modules ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow course_modules for everyone" ON course_modules;
CREATE POLICY "Allow course_modules for everyone" ON course_modules FOR ALL USING (true) WITH CHECK (true);

ALTER TABLE IF EXISTS module_items ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow module_items for everyone" ON module_items;
CREATE POLICY "Allow module_items for everyone" ON module_items FOR ALL USING (true) WITH CHECK (true);

-- Also ensure enrollment PATCH works by allowing anyone to update (already in 007 but double check)
ALTER TABLE IF EXISTS enrollments ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Allow enrollments management for everyone" ON enrollments;
CREATE POLICY "Allow enrollments management for everyone" ON enrollments FOR ALL USING (true) WITH CHECK (true);
