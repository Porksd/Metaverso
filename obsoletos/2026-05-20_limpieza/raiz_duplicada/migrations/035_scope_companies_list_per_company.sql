-- Migration 035: Scope collaborator companies list per client company
-- Problem solved: collaborators were global in companies_list, so edits from one company
-- affected other companies. This migration scopes rows by company_id.

ALTER TABLE companies_list
ADD COLUMN IF NOT EXISTS company_id UUID REFERENCES companies(id) ON DELETE CASCADE;

-- Existing unique(code) blocks reusing collaborator names across companies.
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'companies_list_code_key'
    ) THEN
        ALTER TABLE companies_list DROP CONSTRAINT companies_list_code_key;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_companies_list_company_id ON companies_list(company_id);

-- Keep code stable only inside one company.
CREATE UNIQUE INDEX IF NOT EXISTS idx_companies_list_company_code_unique
ON companies_list(company_id, code)
WHERE company_id IS NOT NULL;

-- Avoid duplicate collaborator names inside the same company.
CREATE UNIQUE INDEX IF NOT EXISTS idx_companies_list_company_name_unique
ON companies_list(company_id, lower(name_es))
WHERE company_id IS NOT NULL;

-- Backfill collaborator catalogs from existing students data.
INSERT INTO companies_list (company_id, code, name_es, name_ht, active)
SELECT
    s.client_id,
    LEFT(
        REGEXP_REPLACE(
            UPPER(REPLACE(s.client_id::text, '-', '') || '_' || TRIM(s.company_name)),
            '[^A-Z0-9_]',
            '_',
            'g'
        ),
        50
    ) AS code,
    TRIM(s.company_name) AS name_es,
    TRIM(s.company_name) AS name_ht,
    true
FROM (
    SELECT DISTINCT client_id, company_name
    FROM students
    WHERE client_id IS NOT NULL
      AND company_name IS NOT NULL
      AND BTRIM(company_name) <> ''
) s
WHERE NOT EXISTS (
    SELECT 1
    FROM companies_list cl
    WHERE cl.company_id = s.client_id
      AND LOWER(cl.name_es) = LOWER(TRIM(s.company_name))
);

COMMENT ON COLUMN companies_list.company_id IS 'Owning company for collaborator catalog rows';
