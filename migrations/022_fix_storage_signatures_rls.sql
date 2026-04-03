-- Migration: Fix Storage RLS for Signatures and Logos
-- This migration ensures that anonymous users (managers in the portal) 
-- can upload and update files in the 'company-logos' bucket.

-- 1. Ensure the bucket exists and is public
INSERT INTO storage.buckets (id, name, public)
VALUES ('company-logos', 'company-logos', true)
ON CONFLICT (id) DO UPDATE SET public = true;

-- 2. Drop existing policies for this bucket to avoid conflicts
DROP POLICY IF EXISTS "Public Select CL" ON storage.objects;
DROP POLICY IF EXISTS "Public Insert CL" ON storage.objects;
DROP POLICY IF EXISTS "Public Update CL" ON storage.objects;
DROP POLICY IF EXISTS "Public Delete CL" ON storage.objects;
DROP POLICY IF EXISTS "Allow All for company-logos" ON storage.objects;

-- 3. Create a single, broad policy for the bucket
-- Using 'FOR ALL' covers INSERT, SELECT, UPDATE, and DELETE
CREATE POLICY "Allow management of company-logos"
ON storage.objects FOR ALL
TO anon, authenticated
USING (bucket_id = 'company-logos')
WITH CHECK (bucket_id = 'company-logos');

-- 4. Do the same for course-content just in case
DROP POLICY IF EXISTS "Public Select CC" ON storage.objects;
DROP POLICY IF EXISTS "Public Insert CC" ON storage.objects;
DROP POLICY IF EXISTS "Public Update CC" ON storage.objects;
DROP POLICY IF EXISTS "Public Delete CC" ON storage.objects;

CREATE POLICY "Allow management of course-content"
ON storage.objects FOR ALL
TO anon, authenticated
USING (bucket_id = 'course-content')
WITH CHECK (bucket_id = 'course-content');

-- 5. Ensure bucket selection is also allowed
-- Storage API often needs to check bucket properties
GRANT SELECT ON TABLE storage.buckets TO anon, authenticated;

-- 6. Open access to storage.buckets via RLS if enabled
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_class c 
        JOIN pg_namespace n ON n.oid = c.relnamespace 
        WHERE n.nspname = 'storage' AND c.relname = 'buckets' AND c.relrowsecurity = true
    ) THEN
        DROP POLICY IF EXISTS "Public Bucket Access" ON storage.buckets;
        CREATE POLICY "Public Bucket Access" ON storage.buckets FOR SELECT TO anon, authenticated USING (true);
    END IF;
END $$;
