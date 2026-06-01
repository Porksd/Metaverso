-- Add optional branch/zone identifier for company subsidiaries.
--
-- This field helps distinguish operational branches that share a main legal name
-- while keeping certificates under the principal company branding.

ALTER TABLE companies
ADD COLUMN IF NOT EXISTS branch_zone text;

COMMENT ON COLUMN companies.branch_zone IS 'Optional branch/zone label used to differentiate subsidiaries in management views.';
