-- Migration: Update module_items type check constraint
-- This migration adds the 'header' type to the allowed types for module_items

-- Note: We first try to drop the existing constraint if possible.
-- In Supabase/Postgres, we need to know the exact name or use a dynamic approach.
-- Based on the error, the name is "module_items_type_check".

ALTER TABLE module_items 
DROP CONSTRAINT IF EXISTS module_items_type_check;

ALTER TABLE module_items 
ADD CONSTRAINT module_items_type_check 
CHECK (type IN ('video', 'audio', 'image', 'pdf', 'genially', 'scorm', 'quiz', 'signature', 'text', 'header'));

COMMENT ON CONSTRAINT module_items_type_check ON module_items IS 'Updated allowed content types including header';
