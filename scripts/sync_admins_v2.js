const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

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
        const admins = [
            { email: 'apacheco@metaversotec.com', role: 'superadmin', permissions: { all: true } },
            { email: 'porksde@gmail.com', role: 'superadmin', permissions: { all: true } },
            { email: 'soporte@lobus.cl', role: 'superadmin', permissions: { all: true } },
            { email: 'm.poblete.m@gmail.com', role: 'superadmin', permissions: { all: true } },
            { email: 'admin@metaversotec.com', role: 'editor', permissions: { all: false, delete: false } }
        ];
        for (const a of admins) {
            const { error: insErr } = await supabase.from('admin_profiles').upsert(a, { onConflict: 'email' });
            if (insErr) console.error(`Error for ${a.email}:`, insErr.message);
            else console.log(`Success for ${a.email}`);
        }
    }
}
test();
