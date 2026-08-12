-- Migration 027: Sync all Admins and Roles
INSERT INTO admin_profiles (email, role, permissions)
VALUES 
('apacheco@lobus.cl', 'superadmin', '{"all": true}'),
('admin@metaversotec.com', 'editor', '{"all": false, "delete": false}')
ON CONFLICT (email) DO UPDATE SET 
    role = EXCLUDED.role,
    permissions = EXCLUDED.permissions;
