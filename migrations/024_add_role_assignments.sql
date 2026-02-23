CREATE TABLE IF NOT EXISTS role_company_assignments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    role_id UUID REFERENCES company_roles(id) ON DELETE CASCADE,
    company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
    is_visible BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(role_id, company_id)
);

-- Policy to allow anyone to see assignments (consistent with project's current state)
ALTER TABLE role_company_assignments ENABLE ROW LEVEL SECURITY;
CREATE POLICY "role_assignments_select_all" ON role_company_assignments FOR SELECT USING (true);
CREATE POLICY "role_assignments_all_admin" ON role_company_assignments FOR ALL USING (true);
