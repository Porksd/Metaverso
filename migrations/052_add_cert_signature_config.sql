-- Migration 052: Add cert_signature_config to companies
-- Stores which signature slots (0-based indices) appear on each certificate type.
-- Default: participacion/aprobacion show slots 0+1, irl shows slot 0.
ALTER TABLE companies
  ADD COLUMN IF NOT EXISTS cert_signature_config JSONB
    NOT NULL DEFAULT '{"participacion":[0,1],"aprobacion":[0,1],"irl":[0]}';
