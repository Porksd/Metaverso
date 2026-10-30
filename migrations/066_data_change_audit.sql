CREATE TABLE IF NOT EXISTS public.data_change_audit (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    schema_name TEXT NOT NULL,
    table_name TEXT NOT NULL,
    operation TEXT NOT NULL CHECK (operation IN ('INSERT', 'UPDATE', 'DELETE')),
    actor_user_id UUID NULL,
    actor_email TEXT NULL,
    actor_app_role TEXT NULL,
    jwt_role TEXT NULL,
    database_role TEXT NOT NULL DEFAULT session_user,
    transaction_id BIGINT NOT NULL DEFAULT txid_current(),
    row_pk JSONB NOT NULL DEFAULT '{}'::JSONB,
    old_data JSONB NULL,
    new_data JSONB NULL
);

CREATE INDEX IF NOT EXISTS idx_data_change_audit_occurred_at
    ON public.data_change_audit (occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_data_change_audit_actor_email
    ON public.data_change_audit (actor_email);
CREATE INDEX IF NOT EXISTS idx_data_change_audit_table
    ON public.data_change_audit (schema_name, table_name);
CREATE INDEX IF NOT EXISTS idx_data_change_audit_operation
    ON public.data_change_audit (operation);

CREATE OR REPLACE FUNCTION public.redact_audit_json(value JSONB)
RETURNS JSONB
LANGUAGE plpgsql
IMMUTABLE
SET search_path = pg_catalog, public
AS $$
DECLARE
    result JSONB;
BEGIN
    CASE jsonb_typeof(value)
        WHEN 'object' THEN
            SELECT COALESCE(
                jsonb_object_agg(
                    entry.key,
                    CASE
                        WHEN entry.key ~* '(password|secret|token|api[_-]?key|signature|authorization|cookie)'
                            THEN '"[REDACTED]"'::JSONB
                        ELSE public.redact_audit_json(entry.value)
                    END
                ),
                '{}'::JSONB
            )
            INTO result
            FROM jsonb_each(value) AS entry;
            RETURN result;
        WHEN 'array' THEN
            SELECT COALESCE(
                jsonb_agg(public.redact_audit_json(entry.value)),
                '[]'::JSONB
            )
            INTO result
            FROM jsonb_array_elements(value) AS entry(value);
            RETURN result;
        ELSE
            RETURN value;
    END CASE;
END;
$$;

CREATE OR REPLACE FUNCTION public.capture_data_change()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = pg_catalog, public
AS $$
DECLARE
    old_row JSONB;
    new_row JSONB;
    row_data JSONB;
    row_key JSONB;
    claims JSONB;
    request_headers JSONB;
    actor_id UUID;
    actor_email_value TEXT;
    jwt_role_value TEXT;
    app_role_value TEXT;
BEGIN
    IF TG_OP IN ('UPDATE', 'DELETE') THEN
        old_row := public.redact_audit_json(to_jsonb(OLD));
    END IF;

    IF TG_OP IN ('INSERT', 'UPDATE') THEN
        new_row := public.redact_audit_json(to_jsonb(NEW));
    END IF;

    IF TG_OP = 'UPDATE' AND to_jsonb(OLD) IS NOT DISTINCT FROM to_jsonb(NEW) THEN
        RETURN NEW;
    END IF;

    row_data := CASE WHEN TG_OP = 'DELETE' THEN old_row ELSE new_row END;

    SELECT COALESCE(jsonb_object_agg(attribute.attname, row_data -> attribute.attname), '{}'::JSONB)
    INTO row_key
    FROM pg_index AS primary_index
    JOIN LATERAL unnest(primary_index.indkey) WITH ORDINALITY AS key_column(attnum, ordinal)
        ON key_column.ordinal <= primary_index.indnkeyatts
    JOIN pg_attribute AS attribute
        ON attribute.attrelid = primary_index.indrelid
       AND attribute.attnum = key_column.attnum
    WHERE primary_index.indrelid = TG_RELID
      AND primary_index.indisprimary;

    claims := COALESCE(
        NULLIF(current_setting('request.jwt.claims', true), '')::JSONB,
        '{}'::JSONB
    );
    jwt_role_value := NULLIF(claims ->> 'role', '');
    actor_email_value := NULLIF(lower(claims ->> 'email'), '');

    IF claims ->> 'sub' ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN
        actor_id := (claims ->> 'sub')::UUID;
    END IF;

    IF jwt_role_value = 'service_role' THEN
        request_headers := COALESCE(
            NULLIF(current_setting('request.headers', true), '')::JSONB,
            '{}'::JSONB
        );
        IF (request_headers ->> 'x-audit-actor-user-id') ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN
            actor_id := (request_headers ->> 'x-audit-actor-user-id')::UUID;
            actor_email_value := NULLIF(lower(request_headers ->> 'x-audit-actor-email'), '');
            app_role_value := NULLIF(request_headers ->> 'x-audit-app-role', '');
        END IF;
    ELSIF actor_email_value IS NOT NULL THEN
        SELECT admin_profile.role
        INTO app_role_value
        FROM public.admin_profiles AS admin_profile
        WHERE lower(admin_profile.email) = actor_email_value
        LIMIT 1;
    END IF;

    INSERT INTO public.data_change_audit (
        schema_name,
        table_name,
        operation,
        actor_user_id,
        actor_email,
        actor_app_role,
        jwt_role,
        database_role,
        row_pk,
        old_data,
        new_data
    )
    VALUES (
        TG_TABLE_SCHEMA,
        TG_TABLE_NAME,
        TG_OP,
        actor_id,
        actor_email_value,
        app_role_value,
        jwt_role_value,
        COALESCE(NULLIF(current_setting('role', true), 'none'), session_user::TEXT),
        row_key,
        old_row,
        new_row
    );

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$;

ALTER TABLE public.data_change_audit ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS data_change_audit_select_superadmin ON public.data_change_audit;
CREATE POLICY data_change_audit_select_superadmin
    ON public.data_change_audit
    FOR SELECT
    TO authenticated
    USING (
        EXISTS (
            SELECT 1
            FROM public.admin_profiles AS admin_profile
            WHERE lower(admin_profile.email) = lower(auth.jwt() ->> 'email')
              AND admin_profile.role = 'superadmin'
        )
    );

REVOKE ALL ON public.data_change_audit FROM PUBLIC, anon, authenticated, service_role;
GRANT SELECT ON public.data_change_audit TO authenticated;
REVOKE ALL ON FUNCTION public.redact_audit_json(JSONB) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.capture_data_change() FROM PUBLIC, anon, authenticated, service_role;

DO $$
DECLARE
    target_table RECORD;
BEGIN
    FOR target_table IN
        SELECT namespace.nspname AS schema_name, relation.relname AS table_name
        FROM pg_class AS relation
        JOIN pg_namespace AS namespace ON namespace.oid = relation.relnamespace
        WHERE namespace.nspname = 'public'
          AND relation.relkind IN ('r', 'p')
          AND relation.relname <> 'data_change_audit'
          AND NOT EXISTS (
              SELECT 1
              FROM pg_inherits AS inheritance
              WHERE inheritance.inhrelid = relation.oid
          )
    LOOP
        EXECUTE format(
            'DROP TRIGGER IF EXISTS capture_data_change ON %I.%I',
            target_table.schema_name,
            target_table.table_name
        );
        EXECUTE format(
            'CREATE TRIGGER capture_data_change
             AFTER INSERT OR UPDATE OR DELETE ON %I.%I
             FOR EACH ROW EXECUTE FUNCTION public.capture_data_change()',
            target_table.schema_name,
            target_table.table_name
        );
    END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION public.capture_auth_user_change()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = pg_catalog, public
AS $$
DECLARE
    old_row JSONB;
    new_row JSONB;
    claims JSONB;
    request_headers JSONB;
    actor_id UUID;
    actor_email_value TEXT;
    actor_app_role_value TEXT;
    jwt_role_value TEXT;
BEGIN
    IF TG_OP IN ('UPDATE', 'DELETE') THEN
        old_row := public.redact_audit_json(jsonb_build_object(
            'id', to_jsonb(OLD) -> 'id',
            'email', to_jsonb(OLD) -> 'email',
            'phone', to_jsonb(OLD) -> 'phone',
            'created_at', to_jsonb(OLD) -> 'created_at',
            'updated_at', to_jsonb(OLD) -> 'updated_at',
            'deleted_at', to_jsonb(OLD) -> 'deleted_at',
            'last_sign_in_at', to_jsonb(OLD) -> 'last_sign_in_at',
            'raw_user_meta_data', to_jsonb(OLD) -> 'raw_user_meta_data'
        ));
    END IF;

    IF TG_OP IN ('INSERT', 'UPDATE') THEN
        new_row := public.redact_audit_json(jsonb_build_object(
            'id', to_jsonb(NEW) -> 'id',
            'email', to_jsonb(NEW) -> 'email',
            'phone', to_jsonb(NEW) -> 'phone',
            'created_at', to_jsonb(NEW) -> 'created_at',
            'updated_at', to_jsonb(NEW) -> 'updated_at',
            'deleted_at', to_jsonb(NEW) -> 'deleted_at',
            'last_sign_in_at', to_jsonb(NEW) -> 'last_sign_in_at',
            'raw_user_meta_data', to_jsonb(NEW) -> 'raw_user_meta_data'
        ));
    END IF;

    IF TG_OP = 'UPDATE' AND to_jsonb(OLD) IS NOT DISTINCT FROM to_jsonb(NEW) THEN
        RETURN NEW;
    END IF;

    claims := COALESCE(
        NULLIF(current_setting('request.jwt.claims', true), '')::JSONB,
        '{}'::JSONB
    );
    jwt_role_value := NULLIF(claims ->> 'role', '');
    actor_email_value := NULLIF(lower(claims ->> 'email'), '');

    IF claims ->> 'sub' ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN
        actor_id := (claims ->> 'sub')::UUID;
    END IF;

    IF jwt_role_value = 'service_role' THEN
        request_headers := COALESCE(
            NULLIF(current_setting('request.headers', true), '')::JSONB,
            '{}'::JSONB
        );
        IF (request_headers ->> 'x-audit-actor-user-id') ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN
            actor_id := (request_headers ->> 'x-audit-actor-user-id')::UUID;
            actor_email_value := NULLIF(lower(request_headers ->> 'x-audit-actor-email'), '');
            actor_app_role_value := NULLIF(request_headers ->> 'x-audit-app-role', '');
        END IF;
    ELSIF actor_email_value IS NOT NULL THEN
        SELECT admin_profile.role
        INTO actor_app_role_value
        FROM public.admin_profiles AS admin_profile
        WHERE lower(admin_profile.email) = actor_email_value
        LIMIT 1;
    END IF;

    INSERT INTO public.data_change_audit (
        schema_name,
        table_name,
        operation,
        actor_user_id,
        actor_email,
        actor_app_role,
        jwt_role,
        database_role,
        row_pk,
        old_data,
        new_data
    )
    VALUES (
        TG_TABLE_SCHEMA,
        TG_TABLE_NAME,
        TG_OP,
        actor_id,
        actor_email_value,
        actor_app_role_value,
        jwt_role_value,
        COALESCE(NULLIF(current_setting('role', true), 'none'), session_user::TEXT),
        jsonb_build_object('id', COALESCE(new_row -> 'id', old_row -> 'id')),
        old_row,
        new_row
    );

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$;

DO $$
BEGIN
    IF to_regclass('auth.users') IS NOT NULL THEN
        EXECUTE 'DROP TRIGGER IF EXISTS capture_auth_user_change ON auth.users';
        EXECUTE 'CREATE TRIGGER capture_auth_user_change
                 AFTER INSERT OR UPDATE OR DELETE ON auth.users
                 FOR EACH ROW EXECUTE FUNCTION public.capture_auth_user_change()';
    END IF;
END;
$$;

REVOKE ALL ON FUNCTION public.capture_auth_user_change() FROM PUBLIC, anon, authenticated, service_role;
