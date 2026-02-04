-- 1. Enable Company Slugs for Portals
ALTER TABLE companies ADD COLUMN IF NOT EXISTS slug text UNIQUE;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS welcome_title text DEFAULT 'Plataforma de Capacitación';
ALTER TABLE companies ADD COLUMN IF NOT EXISTS welcome_message text DEFAULT 'Bienvenido a tu portal de aprendizaje corporativo.';

-- 2. Enable Controlled Registration in Courses
ALTER TABLE courses ADD COLUMN IF NOT EXISTS registration_mode text CHECK (registration_mode IN ('open', 'restricted')) DEFAULT 'open';

-- 3. (Optional) If client_id is NOT a foreign key yet, we might want to enforce it, 
-- but we'll leave it loosely coupled for now to avoid breaking existing data.
-- We WILL use client_id to link students to companies in the new portal flow.
