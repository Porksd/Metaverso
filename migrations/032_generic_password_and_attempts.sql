-- Migration 032: Generic Password + Course/Login Attempt Tracking
-- Run in Supabase SQL editor or via migration tool

-- 1. Add generic password fields to company_courses
ALTER TABLE company_courses
  ADD COLUMN IF NOT EXISTS use_generic_password BOOLEAN DEFAULT false,
  ADD COLUMN IF NOT EXISTS generic_password TEXT;

-- 2. Add max_attempts to courses (admins set this per-course; default 3)
ALTER TABLE courses
  ADD COLUMN IF NOT EXISTS max_attempts INTEGER DEFAULT 3;

-- 3. Add login blocking fields to students
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS login_attempts INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT false;

-- 4. Add configurable max login attempts to companies (default 5)
ALTER TABLE companies
  ADD COLUMN IF NOT EXISTS max_login_attempts INTEGER DEFAULT 5;
