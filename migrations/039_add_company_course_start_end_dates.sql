-- Migration 039: Add configurable start date and validity (years) per company course
-- start_date: date when the course becomes available in the company portal
-- validez_anios: certificate validity in years (expiration = student completion_date + validez_anios)

ALTER TABLE company_courses
    ADD COLUMN IF NOT EXISTS start_date DATE,
    ADD COLUMN IF NOT EXISTS validez_anios SMALLINT;
