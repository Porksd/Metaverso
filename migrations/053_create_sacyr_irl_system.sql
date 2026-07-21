-- Migration 053: Sacyr-specific IRL form system
-- These 15 forms are exclusively for Sacyr (company_id = 'c7fd2d19-c6a8-4ea0-b9fa-11082eaacac7')
-- Each form is tied to a specific job role (cargo), contains a quiz, and requires worker signature.

-- ── 1. Form templates ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sacyr_irl_forms (
    id           UUID    PRIMARY KEY DEFAULT gen_random_uuid(),
    slug         TEXT    NOT NULL UNIQUE,          -- 'ayudante', 'electricista', etc.
    cargo_name   TEXT    NOT NULL,
    area         TEXT    NOT NULL DEFAULT 'Edificación',
    descripcion_puesto TEXT NOT NULL DEFAULT '',
    tareas       TEXT[]  NOT NULL DEFAULT '{}',
    lugares_trabajo TEXT[] NOT NULL DEFAULT '{}',
    herramientas TEXT[]  NOT NULL DEFAULT '{}',
    orden_aseo   TEXT[]  NOT NULL DEFAULT '{}',
    -- Part 1: multiple-choice questions (shared by all forms, stored per form for flexibility)
    preguntas    JSONB   NOT NULL DEFAULT '[]',
    is_active    BOOLEAN NOT NULL DEFAULT true,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_sacyr_irl_forms_slug ON sacyr_irl_forms(slug);
CREATE INDEX IF NOT EXISTS idx_sacyr_irl_forms_active ON sacyr_irl_forms(is_active);

-- ── 2. Admin assigns specific forms to specific students ─────────────────────
CREATE TABLE IF NOT EXISTS sacyr_irl_assignments (
    id           UUID    PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id   UUID    NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    form_id      UUID    NOT NULL REFERENCES sacyr_irl_forms(id) ON DELETE CASCADE,
    company_id   UUID    NOT NULL REFERENCES companies(id),
    assigned_by  TEXT,                              -- name of admin who assigned
    assigned_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    status       TEXT    NOT NULL DEFAULT 'pending'
                         CHECK (status IN ('pending', 'completed')),
    completed_at TIMESTAMPTZ,
    UNIQUE(student_id, form_id)                    -- one assignment per form per student
);

CREATE INDEX IF NOT EXISTS idx_sacyr_irl_assign_student ON sacyr_irl_assignments(student_id);
CREATE INDEX IF NOT EXISTS idx_sacyr_irl_assign_form    ON sacyr_irl_assignments(form_id);
CREATE INDEX IF NOT EXISTS idx_sacyr_irl_assign_status  ON sacyr_irl_assignments(status);
CREATE INDEX IF NOT EXISTS idx_sacyr_irl_assign_company ON sacyr_irl_assignments(company_id);

-- ── 3. Student responses (one row per completed assignment) ──────────────────
CREATE TABLE IF NOT EXISTS sacyr_irl_responses (
    id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    assignment_id        UUID NOT NULL REFERENCES sacyr_irl_assignments(id) ON DELETE CASCADE UNIQUE,
    student_id           UUID NOT NULL REFERENCES students(id),
    form_id              UUID NOT NULL REFERENCES sacyr_irl_forms(id),
    -- IRL reason checkbox (required)
    motivo               TEXT NOT NULL CHECK (motivo IN (
                             'nueva_incorporacion',
                             'cambio_proceso',
                             'nuevas_actividades'
                         )),
    -- Part 1: {q_index: selected_option_index}
    respuestas_parte1    JSONB NOT NULL DEFAULT '{}',
    -- Part 2: 5 risks identified by worker
    riesgos_identificados JSONB NOT NULL DEFAULT '[]',
    -- Part 2: image analysis
    imagen_riesgo_1      TEXT DEFAULT NULL,
    imagen_medidas_1     TEXT DEFAULT NULL,
    imagen_riesgo_2      TEXT DEFAULT NULL,
    imagen_medidas_2     TEXT DEFAULT NULL,
    -- Signatures
    student_signature_url TEXT,
    student_name         TEXT NOT NULL DEFAULT '',
    student_rut          TEXT NOT NULL DEFAULT '',
    -- Snapshot of company sig at time of signing
    relator_signature_url TEXT,
    relator_name         TEXT,
    relator_role         TEXT,
    completed_at         TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_sacyr_irl_resp_student    ON sacyr_irl_responses(student_id);
CREATE INDEX IF NOT EXISTS idx_sacyr_irl_resp_assignment ON sacyr_irl_responses(assignment_id);

-- ── 4. RLS ───────────────────────────────────────────────────────────────────
ALTER TABLE sacyr_irl_forms        ENABLE ROW LEVEL SECURITY;
ALTER TABLE sacyr_irl_assignments  ENABLE ROW LEVEL SECURITY;
ALTER TABLE sacyr_irl_responses    ENABLE ROW LEVEL SECURITY;

-- Open read for everyone (authenticated queries via service role bypass anyway)
DROP POLICY IF EXISTS "sacyr_irl_forms_select"       ON sacyr_irl_forms;
CREATE POLICY "sacyr_irl_forms_select"       ON sacyr_irl_forms       FOR SELECT USING (true);
DROP POLICY IF EXISTS "sacyr_irl_forms_all"          ON sacyr_irl_forms;
CREATE POLICY "sacyr_irl_forms_all"          ON sacyr_irl_forms       FOR ALL    USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "sacyr_irl_assignments_select" ON sacyr_irl_assignments;
CREATE POLICY "sacyr_irl_assignments_select" ON sacyr_irl_assignments FOR SELECT USING (true);
DROP POLICY IF EXISTS "sacyr_irl_assignments_all"    ON sacyr_irl_assignments;
CREATE POLICY "sacyr_irl_assignments_all"    ON sacyr_irl_assignments FOR ALL    USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "sacyr_irl_responses_select"  ON sacyr_irl_responses;
CREATE POLICY "sacyr_irl_responses_select"  ON sacyr_irl_responses   FOR SELECT USING (true);
DROP POLICY IF EXISTS "sacyr_irl_responses_all"     ON sacyr_irl_responses;
CREATE POLICY "sacyr_irl_responses_all"     ON sacyr_irl_responses   FOR ALL    USING (true) WITH CHECK (true);
