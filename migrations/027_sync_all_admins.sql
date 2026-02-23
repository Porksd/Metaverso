-- Migration 027: Sync all Admins and Roles
INSERT INTO admin_profiles (email, role, permissions)
VALUES 
('apacheco@metaversotec.com', 'superadmin', '{"all": true}'),
('porksde@gmail.com', 'superadmin', '{"all": true}'),
('soporte@lobus.cl', 'superadmin', '{"all": true}'),
('m.poblete.m@gmail.com', 'superadmin', '{"all": true}'),
('admin@metaversotec.com', 'editor', '{"all": false, "delete": false}')
ON CONFLICT (email) DO UPDATE SET 
    role = EXCLUDED.role,
    permissions = EXCLUDED.permissions;
