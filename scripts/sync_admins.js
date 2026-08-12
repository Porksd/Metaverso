const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

function parseAdminsFromEnv() {
    const raw = process.env.ADMIN_SYNC_ROWS_JSON || '[]';
    try {
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed
            .filter((row) => row && typeof row.email === 'string' && typeof row.role === 'string')
            .map((row) => ({
                email: row.email.toLowerCase().trim(),
                role: row.role,
                permissions: row.permissions && typeof row.permissions === 'object' ? row.permissions : {}
            }));
    } catch (error) {
        console.error('Invalid ADMIN_SYNC_ROWS_JSON:', error.message);
        return [];
    }
}

if (!supabaseUrl || !supabaseServiceKey) {
    console.error('Missing credentials');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function syncAdmins() {
    const admins = parseAdminsFromEnv();

    if (admins.length === 0) {
        console.error('No admins configured. Define ADMIN_SYNC_ROWS_JSON to run this script safely.');
        process.exit(1);
    }

    console.log('Syncing admins...');
    
    for (const admin of admins) {
        const { error } = await supabase
            .from('admin_profiles')
            .upsert(admin, { onConflict: 'email' });
            
        if (error) {
            console.error(`Error syncing ${admin.email}:`, error.message);
        } else {
            console.log(`Synced ${admin.email} as ${admin.role}`);
        }
    }
}

syncAdmins();
