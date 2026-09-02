-- Migration 057: allow 'biweekly' (Cada 15 dias) as a valid report_frequency value

DO $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'companies_report_frequency_check'
      AND conrelid = 'companies'::regclass
  ) THEN
    ALTER TABLE companies DROP CONSTRAINT companies_report_frequency_check;
  END IF;

  ALTER TABLE companies
    ADD CONSTRAINT companies_report_frequency_check
    CHECK (report_frequency IN ('daily', 'weekly', 'biweekly', 'monthly'));
END $$;
