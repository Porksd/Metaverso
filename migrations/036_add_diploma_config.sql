-- Migration 036: Diploma Metaverso configuration system

-- 1. Add diploma toggle to company_courses
ALTER TABLE company_courses
    ADD COLUMN IF NOT EXISTS diploma_metaverso_enabled BOOLEAN DEFAULT false;

-- 2. Global diploma config table (single row: the Metaverso cert template)
CREATE TABLE IF NOT EXISTS diploma_config (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    background_url TEXT DEFAULT '/cert-assets/metaverso-cert-bg.jpg',
    fields_config JSONB DEFAULT '{
        "student_name": true,
        "rut": true,
        "company_name": true,
        "company_rut": true,
        "course_name": true,
        "hours": true,
        "date": true
    }'::jsonb,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert single global config row (fixed ID so we always upsert the same record)
INSERT INTO diploma_config (id, background_url)
VALUES ('00000000-0000-0000-0000-000000000001', '/cert-assets/metaverso-cert-bg.jpg')
ON CONFLICT (id) DO NOTHING;

-- 3. RLS
ALTER TABLE diploma_config ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "diploma_config_select" ON diploma_config;
DROP POLICY IF EXISTS "diploma_config_all"    ON diploma_config;

CREATE POLICY "diploma_config_select" ON diploma_config FOR SELECT USING (true);
CREATE POLICY "diploma_config_all"    ON diploma_config FOR ALL   USING (auth.role() = 'authenticated');
