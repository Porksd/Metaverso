-- Migration: Fix RLS policies to allow deleting enrollments and related data

-- 1. Drop existing restrictive policies (if any)
DROP POLICY IF EXISTS "Allow company managers to delete enrollments" ON enrollments;
DROP POLICY IF EXISTS "Allow deleting course progress" ON course_progress;
DROP POLICY IF EXISTS "Allow deleting activity logs" ON activity_logs;

-- 2. Create permissive policies for enrollment deletion

-- Allow deletion of enrollments (no restrictions for now - can be refined later)
CREATE POLICY "Enable delete access for enrollments" 
ON enrollments 
FOR DELETE 
USING (true);

-- Allow deletion of course_progress
CREATE POLICY "Enable delete access for course_progress" 
ON course_progress 
FOR DELETE 
USING (true);

-- Allow deletion of activity_logs
CREATE POLICY "Enable delete access for activity_logs" 
ON activity_logs 
FOR DELETE 
USING (true);

-- 3. Ensure students table allows updating digital_signature_url to null
DROP POLICY IF EXISTS "Allow updating student signature" ON students;
CREATE POLICY "Enable update access for students" 
ON students 
FOR UPDATE 
USING (true);

COMMENT ON POLICY "Enable delete access for enrollments" ON enrollments IS 
'Allows deletion of enrollments for admin/manager operations';

COMMENT ON POLICY "Enable delete access for course_progress" ON course_progress IS 
'Allows deletion of course progress when unenrolling students';

COMMENT ON POLICY "Enable delete access for activity_logs" ON activity_logs IS 
'Allows deletion of activity logs when unenrolling students';
