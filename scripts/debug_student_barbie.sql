-- Diagnostico rapido de existencia de alumno en students/auth
-- Ejecutar en Supabase SQL Editor

-- 1) students por correo o RUT (con y sin guion)
SELECT id, client_id, rut, email, auth_user_id, first_name, last_name, created_at
FROM public.students
WHERE LOWER(email) = LOWER('barbiepacheco@gmail.com')
   OR rut IN ('23468353-5', '234683535')
ORDER BY created_at DESC;

-- 2) enrollments colgantes del mismo alumno (si existe)
SELECT e.id, e.student_id, e.course_id, e.status, e.created_at
FROM public.enrollments e
JOIN public.students s ON s.id = e.student_id
WHERE LOWER(s.email) = LOWER('barbiepacheco@gmail.com')
   OR s.rut IN ('23468353-5', '234683535')
ORDER BY e.created_at DESC;

-- 3) auth.users por correo
SELECT id, email, email_confirmed_at, created_at
FROM auth.users
WHERE LOWER(email) = LOWER('barbiepacheco@gmail.com');
