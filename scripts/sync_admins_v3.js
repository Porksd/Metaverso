const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

async function test() {
    const supabase = createClient(process.env.NEXT_PUBLIC_SUPABASE_URL, process.env.SUPABASE_SERVICE_ROLE_KEY);
    console.log('Inserting one admin...');
    const { error } = await supabase.from('admin_profiles').insert({ email: 'admin@metaversotec.com', role: 'editor' });
    if (error) console.error('Error:', error.message);
    else console.log('Successfully inserted admin@metaversotec.com');
}
test();
