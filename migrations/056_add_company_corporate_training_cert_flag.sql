ALTER TABLE public.companies
ADD COLUMN IF NOT EXISTS cert_capacitaciones_enabled BOOLEAN NOT NULL DEFAULT FALSE;

COMMENT ON COLUMN public.companies.cert_capacitaciones_enabled IS
'Habilita la generacion del certificado corporativo de capacitaciones por empresa.';
