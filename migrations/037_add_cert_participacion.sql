-- Migration 037: Add cert_participacion_enabled to company_courses
-- This controls whether the participation certificate (CertificateCanvas) is shown for a course/company.
-- Default TRUE so existing courses keep showing their certificates.

ALTER TABLE company_courses
    ADD COLUMN IF NOT EXISTS cert_participacion_enabled BOOLEAN DEFAULT true;
