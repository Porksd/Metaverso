CREATE TABLE IF NOT EXISTS public.report_certificates (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  token text NOT NULL UNIQUE,
  company_id uuid NOT NULL REFERENCES public.companies(id) ON DELETE CASCADE,
  storage_path text NOT NULL UNIQUE,
  generated_at timestamptz NOT NULL,
  expires_at timestamptz NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS report_certificates_expires_at_idx
  ON public.report_certificates (expires_at);

ALTER TABLE public.report_certificates ENABLE ROW LEVEL SECURITY;

INSERT INTO storage.buckets (id, name, "public")
VALUES ('report-certificates', 'report-certificates', false)
ON CONFLICT (id) DO NOTHING;