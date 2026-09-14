ALTER TABLE public.companies
  ADD COLUMN IF NOT EXISTS report_send_time time NOT NULL DEFAULT '10:00:00';

CREATE INDEX IF NOT EXISTS report_certificates_company_active_idx
  ON public.report_certificates (company_id, expires_at, generated_at);

ALTER TABLE public.report_certificates
  ADD COLUMN IF NOT EXISTS certificate_type text NOT NULL DEFAULT 'report';

CREATE INDEX IF NOT EXISTS report_certificates_type_active_idx
  ON public.report_certificates (company_id, certificate_type, expires_at, generated_at);