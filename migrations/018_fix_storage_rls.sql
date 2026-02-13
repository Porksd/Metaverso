-- Migration: Open RLS for Supabase Storage (course-content bucket)
-- This allows the frontend to upload large files directly, bypassing Vercel's 4.5MB limit.

-- Ensure the bucket exists (though typically managed in UI)
INSERT INTO storage.buckets (id, name, public)
VALUES ('course-content', 'course-content', true)
ON CONFLICT (id) DO UPDATE SET public = true;

-- Allow public access to read files
CREATE POLICY "Public Access" ON storage.objects FOR SELECT USING (bucket_id = 'course-content');

-- Allow anyone to upload files (For development/demo stability)
-- In production, you would restrict this to authenticated users
CREATE POLICY "Public Upload" ON storage.objects FOR INSERT WITH CHECK (bucket_id = 'course-content');
CREATE POLICY "Public Update" ON storage.objects FOR UPDATE USING (bucket_id = 'course-content');
CREATE POLICY "Public Delete" ON storage.objects FOR DELETE USING (bucket_id = 'course-content');
