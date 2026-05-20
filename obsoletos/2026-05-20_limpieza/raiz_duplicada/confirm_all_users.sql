-- Script to confirm all existing users and ensure they can log in without email verification
-- Run this in the Supabase SQL Editor

-- 1. Confirm all users in auth.users
UPDATE auth.users 
SET email_confirmed_at = COALESCE(email_confirmed_at, NOW()),
    updated_at = NOW(),
    last_sign_in_at = COALESCE(last_sign_in_at, NOW())
WHERE email_confirmed_at IS NULL;

-- 2. (Optional) If you want to ensure all existing students have their password 
-- populated in the students table for the corporate login to work:
-- This is hard because we don't know their passwords, but we can at least 
-- check which ones are missing it.
-- SELECT email FROM public.students WHERE password IS NULL;
