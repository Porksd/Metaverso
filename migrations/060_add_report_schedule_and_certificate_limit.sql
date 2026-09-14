ALTER TABLE public.companies
  ADD COLUMN IF NOT EXISTS report_send_time time NOT NULL DEFAULT '10:00:00';

CREATE INDEX IF NOT EXISTS report_certificates_company_active_idx
  ON public.report_certificates (company_id, expires_at, generated_at);