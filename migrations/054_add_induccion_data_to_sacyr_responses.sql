-- Migration 054: Add induccion_data column to sacyr_irl_responses
-- Stores the full induction checklist, equipment, EPP and comprehension data
-- so the PDF can be regenerated at any time from saved data.
ALTER TABLE sacyr_irl_responses
  ADD COLUMN IF NOT EXISTS induccion_data JSONB NOT NULL DEFAULT '{}';
