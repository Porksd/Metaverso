-- Migration 031: Create Demo Company and Demo Student for Commercial Presentations
-- Adds a demo company and pre-configured demo student for vendor presentations

-- 1. Insert demo company
INSERT INTO companies (id, name, tax_id, address, phone, email, password, is_active, total_quotas, slug, welcome_title, welcome_message, primary_color, secondary_color)
VALUES (
    '99999999-9999-9999-9999-999999999999'::uuid,
    'Demo Metaverso',
    '99.999.999-9',
    'Av. Demo, Santiago, Chile',
    '+56 2 9999 9999',
    'demo@metaverso.cl',
    'demo123',
    true,
    1000,
    'demo-metaverso',
    'Plataforma Demo - Metaverso Otec',
    '<p>Bienvenido a la demostración de Metaverso Otec. Esta es una empresa de demostración configurada para explorar todas las capacidades de la plataforma corporativa de formación, trazabilidad y cumplimiento.</p>',
    '#31D22D',
    '#000000'
)
ON CONFLICT (id) DO UPDATE SET
    name = 'Demo Metaverso',
    email = 'demo@metaverso.cl',
    password = 'demo123',
    is_active = true;

-- 2. Insert demo student
INSERT INTO students (client_id, rut, first_name, last_name, email, password, gender, age, position, company_name, language)
VALUES (
    '99999999-9999-9999-9999-999999999999'::uuid,
    '11.111.111-1',
    'Juan',
    'Demo',
    'juan.demo@metaverso.cl',
    'demo123',
    'Masculino',
    35,
    'Supervisor Demo',
    'Demo Metaverso',
    'es'
)
ON CONFLICT (client_id, rut) DO UPDATE SET
    first_name = 'Juan',
    last_name = 'Demo',
    email = 'juan.demo@metaverso.cl',
    password = 'demo123',
    position = 'Supervisor Demo';

COMMENT ON COLUMN students.password IS 'Plaintext password for corporate login (demo purposes)';
