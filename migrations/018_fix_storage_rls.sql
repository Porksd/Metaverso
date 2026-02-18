-- Migration: Fix and Open Storage RLS for course-content
-- This version is more explicit and targets any roles (anon/authenticated)
-- Run this in the Supabase SQL Editor if you get RLS errors during upload.

-- 1. Ensure bucket exists and is public
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'course-content', 
  'course-content', 
  true, 
  524288000, -- 500MB limit
  '{video/*,image/*,application/pdf,application/zip,audio/*}' -- allowed types
)
ON CONFLICT (id) DO UPDATE SET 
  public = true,
  file_size_limit = 524288000,
  allowed_mime_types = '{video/*,image/*,application/pdf,application/zip,audio/*}';

-- 2. Clear existing specific policies to avoid conflicts
DROP POLICY IF EXISTS "Public Access" ON storage.objects;
DROP POLICY IF EXISTS "Public Upload" ON storage.objects;
DROP POLICY IF EXISTS "Public Update" ON storage.objects;
DROP POLICY IF EXISTS "Public Delete" ON storage.objects;
DROP POLICY IF EXISTS "Allow public select" ON storage.objects;
DROP POLICY IF EXISTS "Allow public insert" ON storage.objects;
DROP POLICY IF EXISTS "Allow public update" ON storage.objects;
DROP POLICY IF EXISTS "Allow public delete" ON storage.objects;

-- 3. Create robust policies for anyone to manage files in this specific bucket
-- Note: 'anon' is the role used by the frontend when not logged in with Supabase Auth.
GRANT USAGE ON SCHEMA storage TO anon, authenticated;
GRANT ALL ON TABLE storage.objects TO anon, authenticated;
GRANT ALL ON TABLE storage.buckets TO anon, authenticated;

CREATE POLICY "Public Select" ON storage.objects FOR SELECT TO anon, authenticated USING (bucket_id = 'course-content');
CREATE POLICY "Public Insert" ON storage.objects FOR INSERT TO anon, authenticated WITH CHECK (bucket_id = 'course-content');
CREATE POLICY "Public Update" ON storage.objects FOR UPDATE TO anon, authenticated USING (bucket_id = 'course-content');
CREATE POLICY "Public Delete" ON storage.objects FOR DELETE TO anon, authenticated USING (bucket_id = 'course-content');
