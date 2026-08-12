-- Migration 055: Security access audit, restricted schedules base, and daily report recipients

CREATE TABLE IF NOT EXISTS security_access_events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    event_type TEXT NOT NULL,
    action TEXT NOT NULL,
    actor_user_id UUID NULL,
    actor_email TEXT NULL,
    target_email TEXT NULL,
    role_at_time TEXT NULL,
    allowed BOOLEAN NOT NULL DEFAULT true,
    reason TEXT NULL,
    ip_address TEXT NULL,
    user_agent TEXT NULL,
    path TEXT NULL,
    geo_country TEXT NULL,
    geo_region TEXT NULL,
    geo_city TEXT NULL,
    geo_comuna TEXT NULL,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);

CREATE INDEX IF NOT EXISTS idx_security_access_events_created_at ON security_access_events (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_security_access_events_actor_email ON security_access_events (actor_email);
CREATE INDEX IF NOT EXISTS idx_security_access_events_event_type ON security_access_events (event_type);
CREATE INDEX IF NOT EXISTS idx_security_access_events_allowed ON security_access_events (allowed);

CREATE TABLE IF NOT EXISTS security_report_recipients (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email TEXT UNIQUE NOT NULL,
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_security_report_recipients_active ON security_report_recipients (active);

CREATE TABLE IF NOT EXISTS admin_access_policies (
    email TEXT PRIMARY KEY,
    is_active BOOLEAN NOT NULL DEFAULT true,
    timezone TEXT NOT NULL DEFAULT 'America/Santiago',
    allowed_weekdays INTEGER[] NOT NULL DEFAULT ARRAY[1,2,3,4,5],
    start_time TIME NOT NULL DEFAULT '08:00:00',
    end_time TIME NOT NULL DEFAULT '18:00:00',
    requires_superadmin_approval BOOLEAN NOT NULL DEFAULT false,
    notes TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

ALTER TABLE security_access_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE security_report_recipients ENABLE ROW LEVEL SECURITY;
ALTER TABLE admin_access_policies ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "security_access_events_select_superadmin" ON security_access_events;
DROP POLICY IF EXISTS "security_report_recipients_select_superadmin" ON security_report_recipients;
DROP POLICY IF EXISTS "security_report_recipients_manage_superadmin" ON security_report_recipients;
DROP POLICY IF EXISTS "admin_access_policies_select_superadmin" ON admin_access_policies;
DROP POLICY IF EXISTS "admin_access_policies_manage_superadmin" ON admin_access_policies;

CREATE POLICY "security_access_events_select_superadmin"
ON security_access_events
FOR SELECT
TO authenticated
USING (
  EXISTS (
    SELECT 1
    FROM admin_profiles ap
    WHERE ap.email = lower(auth.jwt() ->> 'email')
      AND ap.role = 'superadmin'
  )
);

CREATE POLICY "security_report_recipients_select_superadmin"
ON security_report_recipients
FOR SELECT
TO authenticated
USING (
  EXISTS (
    SELECT 1
    FROM admin_profiles ap
    WHERE ap.email = lower(auth.jwt() ->> 'email')
      AND ap.role = 'superadmin'
  )
);

CREATE POLICY "security_report_recipients_manage_superadmin"
ON security_report_recipients
FOR ALL
TO authenticated
USING (
  EXISTS (
    SELECT 1
    FROM admin_profiles ap
    WHERE ap.email = lower(auth.jwt() ->> 'email')
      AND ap.role = 'superadmin'
  )
)
WITH CHECK (
  EXISTS (
    SELECT 1
    FROM admin_profiles ap
    WHERE ap.email = lower(auth.jwt() ->> 'email')
      AND ap.role = 'superadmin'
  )
);

CREATE POLICY "admin_access_policies_select_superadmin"
ON admin_access_policies
FOR SELECT
TO authenticated
USING (
  EXISTS (
    SELECT 1
    FROM admin_profiles ap
    WHERE ap.email = lower(auth.jwt() ->> 'email')
      AND ap.role = 'superadmin'
  )
);

CREATE POLICY "admin_access_policies_manage_superadmin"
ON admin_access_policies
FOR ALL
TO authenticated
USING (
  EXISTS (
    SELECT 1
    FROM admin_profiles ap
    WHERE ap.email = lower(auth.jwt() ->> 'email')
      AND ap.role = 'superadmin'
  )
)
WITH CHECK (
  EXISTS (
    SELECT 1
    FROM admin_profiles ap
    WHERE ap.email = lower(auth.jwt() ->> 'email')
      AND ap.role = 'superadmin'
  )
);
