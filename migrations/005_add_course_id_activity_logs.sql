-- 005_add_course_id_activity_logs.sql
-- Agrega la columna course_id a la tabla activity_logs y una FK opcional hacia courses(id)
ALTER TABLE IF EXISTS public.activity_logs
ADD COLUMN IF NOT EXISTS course_id uuid;

-- Agregar constraint FK solo si la tabla courses existe y la columna no tiene valores incompatibles
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'courses') THEN
    BEGIN
      ALTER TABLE public.activity_logs
      ADD CONSTRAINT fk_activity_logs_course
      FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE SET NULL;
    EXCEPTION WHEN duplicate_object THEN
      -- constraint already exists, ignore
      NULL;
    END;
  END IF;
END$$;

-- Nota: Si deseas forzar valores no nulos o backfill, crea un script adicional para actualizar registros previos.
