-- Migration: Add gamma content type for module items
ALTER TABLE module_items DROP CONSTRAINT IF EXISTS module_items_type_check;
ALTER TABLE module_items ADD CONSTRAINT module_items_type_check
CHECK (type IN ('video', 'audio', 'image', 'pdf', 'genially', 'gamma', 'scorm', 'quiz', 'signature', 'text', 'header', 'survey'));

COMMENT ON CONSTRAINT module_items_type_check ON module_items IS 'Allowed content types including gamma embeds';
