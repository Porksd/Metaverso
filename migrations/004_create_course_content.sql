-- Migration: Create course_content table for editable course content (key-value)
-- This replicates the "Contenido" sheet from the original Excel system
CREATE TABLE IF NOT EXISTS course_content (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    course_id UUID REFERENCES courses(id) ON DELETE CASCADE,
    key VARCHAR(255) NOT NULL,
    value TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(course_id, key)
);
-- Create indexes
CREATE INDEX IF NOT EXISTS idx_course_content_course_id ON course_content(course_id);
CREATE INDEX IF NOT EXISTS idx_course_content_key ON course_content(key);
-- Add RLS policies
ALTER TABLE course_content ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Course content is viewable by everyone" ON course_content FOR
SELECT USING (true);
CREATE POLICY "Course content is editable by authenticated users" ON course_content FOR ALL USING (auth.role() = 'authenticated');
COMMENT ON TABLE course_content IS 'Editable course content in key-value format (like Excel Contenido sheet)';
COMMENT ON COLUMN course_content.course_id IS 'Reference to course';
COMMENT ON COLUMN course_content.key IS 'Content key (e.g., tituloCurso, videoIntro_es, pregunta1_es)';
COMMENT ON COLUMN course_content.value IS 'Content value (text, URL, or JSON)';
-- Example keys from original system:
-- tituloCurso, logoEmpresa
-- empresas (pipe-separated IDs)
-- empresa_{ID}_{lang}
-- cargos (pipe-separated IDs)
-- cargo_{ID}_{lang}, cargo_{ID}_desc_{lang}
-- videoIntro_{lang}, texto2_{lang}, audio2_{lang}, imagen2
-- juegoUrl, videoSlide4, juegoUrlSlide5
-- texto6_{lang}, audio6_{lang}, imagen6
-- pregunta{1-10}_{lang}, pregunta{1-10}_opciones_{lang}, pregunta{1-10}_correcta