const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

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

async function test() {
    const supabase = createClient(process.env.NEXT_PUBLIC_SUPABASE_URL, process.env.SUPABASE_SERVICE_ROLE_KEY);
    console.log('Testing connection...');
    const { data, error } = await supabase.from('companies').select('count', { count: 'exact', head: true });
    if (error) console.error('Error connecting to companies:', error.message);
    else console.log('Successfully connected to companies.');

    const { error: adminError } = await supabase.from('admin_profiles').select('count', { count: 'exact', head: true });
    if (adminError) {
        console.error('admin_profiles NOT FOUND:', adminError.message);
    } else {
        console.log('admin_profiles IS FOUND. Proceeding with sync...');
        const admins = parseAdminsFromEnv();
        if (admins.length === 0) {
            console.error('No admins configured. Define ADMIN_SYNC_ROWS_JSON to run this script safely.');
            process.exit(1);
        }
        for (const a of admins) {
            const { error: insErr } = await supabase.from('admin_profiles').upsert(a, { onConflict: 'email' });
            if (insErr) console.error(`Error for ${a.email}:`, insErr.message);
            else console.log(`Success for ${a.email}`);
        }
    }
}
test();
