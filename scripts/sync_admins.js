const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseServiceKey) {
    console.error('Missing credentials');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function syncAdmins() {
    const admins = [
        { email: 'apacheco@metaversotec.com', role: 'superadmin', permissions: { all: true } },
        { email: 'porksde@gmail.com', role: 'superadmin', permissions: { all: true } },
        { email: 'soporte@lobus.cl', role: 'superadmin', permissions: { all: true } },
        { email: 'm.poblete.m@gmail.com', role: 'superadmin', permissions: { all: true } },
        { email: 'admin@metaversotec.com', role: 'editor', permissions: { all: false, delete: false } }
    ];

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
