-- Migration: Add Surveys System
-- 1. Create surveys table
CREATE TABLE IF NOT EXISTS surveys (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title_es TEXT NOT NULL,
    title_ht TEXT,
    description_es TEXT,
    description_ht TEXT,
    settings JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2. Create survey_questions table
CREATE TABLE IF NOT EXISTS survey_questions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    survey_id UUID REFERENCES surveys(id) ON DELETE CASCADE,
    question_type TEXT NOT NULL, -- 'text', 'multiple_choice', 'rating', 'boolean'
    text_es TEXT NOT NULL,
    text_ht TEXT,
    options_es JSONB DEFAULT '[]'::jsonb, -- Array of strings
    options_ht JSONB DEFAULT '[]'::jsonb,
    is_required BOOLEAN DEFAULT true,
    order_index INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 3. Create survey_responses table
CREATE TABLE IF NOT EXISTS survey_responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    survey_id UUID REFERENCES surveys(id) ON DELETE CASCADE,
    student_id UUID REFERENCES students(id) ON DELETE CASCADE,
    enrollment_id UUID REFERENCES enrollments(id) ON DELETE CASCADE,
    answers JSONB NOT NULL, -- { question_id: answer }
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 4. Update module_items type check constraint
ALTER TABLE module_items DROP CONSTRAINT IF EXISTS module_items_type_check;
ALTER TABLE module_items ADD CONSTRAINT module_items_type_check 
CHECK (type IN ('video', 'audio', 'image', 'pdf', 'genially', 'scorm', 'quiz', 'signature', 'text', 'header', 'survey'));

-- 5. RLS
ALTER TABLE surveys ENABLE ROW LEVEL SECURITY;
ALTER TABLE survey_questions ENABLE ROW LEVEL SECURITY;
ALTER TABLE survey_responses ENABLE ROW LEVEL SECURITY;

-- Adjusting policies to be consistent with the project's open RLS for portals but restricted for management
CREATE POLICY "surveys_select_all" ON surveys FOR SELECT USING (true);
CREATE POLICY "surveys_all_admin" ON surveys FOR ALL USING (true); -- Consistent with other tables being open for now in this dev stage

CREATE POLICY "survey_questions_select_all" ON survey_questions FOR SELECT USING (true);
CREATE POLICY "survey_questions_all_admin" ON survey_questions FOR ALL USING (true);

CREATE POLICY "survey_responses_select_all" ON survey_responses FOR SELECT USING (true);
CREATE POLICY "survey_responses_insert_all" ON survey_responses FOR INSERT WITH CHECK (true);
CREATE POLICY "survey_responses_all_admin" ON survey_responses FOR ALL USING (true);

COMMENT ON TABLE surveys IS 'Master templates for surveys';
COMMENT ON TABLE survey_questions IS 'Questions associated with a survey template';
COMMENT ON TABLE survey_responses IS 'Student responses to surveys';

-- 6. Add partial progress support to enrollments table
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS survey_completed BOOLEAN DEFAULT false;
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS last_exam_score INTEGER;
ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS last_exam_passed BOOLEAN;

COMMENT ON COLUMN enrollments.survey_completed IS 'Tracks if the mandatory survey for this course/enrollment was completed';
COMMENT ON COLUMN enrollments.last_exam_score IS 'Temporary storage for exam score while survey is pending';
COMMENT ON COLUMN enrollments.last_exam_passed IS 'Temporary storage for exam pass status while survey is pending';
