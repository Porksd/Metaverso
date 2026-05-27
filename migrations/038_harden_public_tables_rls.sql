-- Migration 038: Harden public schema by enabling RLS on any unprotected table.
-- This addresses Supabase advisor alerts like: rls_disabled_in_public.

DO $$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN
        SELECT
            n.nspname AS schema_name,
            c.relname AS table_name
        FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE n.nspname = 'public'
          AND c.relkind IN ('r', 'p')
          AND c.relrowsecurity = false
    LOOP
        EXECUTE format(
            'ALTER TABLE %I.%I ENABLE ROW LEVEL SECURITY',
            rec.schema_name,
            rec.table_name
        );

        -- FORCE RLS prevents table owner bypass and keeps behavior explicit.
        EXECUTE format(
            'ALTER TABLE %I.%I FORCE ROW LEVEL SECURITY',
            rec.schema_name,
            rec.table_name
        );
    END LOOP;
END $$;

-- Optional verification query after running this migration:
-- SELECT n.nspname AS schema_name, c.relname AS table_name, c.relrowsecurity, c.relforcerowsecurity
-- FROM pg_class c
-- JOIN pg_namespace n ON n.oid = c.relnamespace
-- WHERE n.nspname = 'public' AND c.relkind IN ('r', 'p')
-- ORDER BY c.relname;
