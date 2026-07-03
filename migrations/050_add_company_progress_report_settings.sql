ALTER TABLE companies
ADD COLUMN IF NOT EXISTS report_auto_enabled boolean NOT NULL DEFAULT false,
ADD COLUMN IF NOT EXISTS report_frequency text NOT NULL DEFAULT 'weekly',
ADD COLUMN IF NOT EXISTS report_include_dashboard_body boolean NOT NULL DEFAULT true,
ADD COLUMN IF NOT EXISTS report_include_pdf_attachment boolean NOT NULL DEFAULT true,
ADD COLUMN IF NOT EXISTS report_last_sent_at timestamptz;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'companies_report_frequency_check'
      AND conrelid = 'companies'::regclass
  ) THEN
    ALTER TABLE companies
      ADD CONSTRAINT companies_report_frequency_check
      CHECK (report_frequency IN ('daily', 'weekly', 'monthly'));
  END IF;
END $$;
