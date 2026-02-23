-- Migration 026: RBAC System for Admins
CREATE TABLE IF NOT EXISTS admin_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email TEXT UNIQUE NOT NULL,
    role TEXT NOT NULL DEFAULT 'editor', -- 'superadmin', 'admin', 'editor'
    permissions JSONB DEFAULT '{}', -- Fine-grained permissions if needed
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Initial SuperAdmin (assuming apacheco@lobus.cl as requested "solo yo")
INSERT INTO admin_profiles (email, role, permissions)
VALUES 
('apacheco@lobus.cl', 'superadmin', '{"all": true}'),
('admin@metaversotec.com', 'superadmin', '{"all": true}'),
('porksde@gmail.com', 'superadmin', '{"all": true}')
ON CONFLICT (email) DO UPDATE SET role = 'superadmin', permissions = '{"all": true}';

-- RLS
ALTER TABLE admin_profiles ENABLE ROW LEVEL SECURITY;
CREATE POLICY "admin_profiles_read_own" ON admin_profiles FOR SELECT USING (true); -- Everyone can read (to check own role)
CREATE POLICY "admin_profiles_all_super" ON admin_profiles FOR ALL USING (
    EXISTS (SELECT 1 FROM admin_profiles WHERE email = auth.jwt() ->> 'email' AND role = 'superadmin')
);
