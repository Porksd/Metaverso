-- Add branding fields to companies table
ALTER TABLE companies ADD COLUMN IF NOT EXISTS primary_color text;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS secondary_color text;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS logo_url text;
