const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

async function backfill() {
    const supabase = createClient(process.env.NEXT_PUBLIC_SUPABASE_URL, process.env.SUPABASE_SERVICE_ROLE_KEY);
    
    console.log("Starting backfill...");
    
    // Get roles
    const { data: roles, error: rErr } = await supabase.from('company_roles').select('id, company_id');
    if (rErr) throw rErr;
    
    // Get companies
    const { data: companies, error: cErr } = await supabase.from('companies').select('id');
    if (cErr) throw cErr;
    
    const assignments = [];
    
    for (const role of roles) {
        if (role.company_id) {
            assignments.push({ role_id: role.id, company_id: role.company_id, is_visible: true });
        } else {
            // Global roles to all companies
            for (const comp of companies) {
                assignments.push({ role_id: role.id, company_id: comp.id, is_visible: true });
            }
        }
    }
    
    console.log(`Prepared ${assignments.length} assignments. Upserting...`);
    
    if (assignments.length > 0) {
        const { data, error } = await supabase.from('role_company_assignments').upsert(assignments, { onConflict: 'role_id,company_id' });
        if (error) {
            console.error("Error during upsert:", error);
        } else {
            console.log(`Successfully backfilled ${assignments.length} assignments.`);
        }
    }
}

backfill().catch(err => console.error("Fatal error:", err));
