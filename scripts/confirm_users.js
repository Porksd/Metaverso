// Script to confirm all users in Supabase Auth
// Usage: node scripts/confirm_users.js

require('dotenv').config({ path: '.env.local' });
if (!require('fs').existsSync('.env.local')) {
    require('dotenv').config({ path: '../.env.local' });
}
const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseServiceKey) {
    console.error('Missing credentials in .env.local');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function confirmAllUsers() {
    console.log('Fetching users...');
    const { data: { users }, error } = await supabase.auth.admin.listUsers();
    
    if (error) {
        console.error('Error listing users:', error.message);
        return;
    }

    console.log(`Found ${users.length} users. Checking confirmation status...`);

    for (const user of users) {
        if (!user.email_confirmed_at) {
            console.log(`Confirming user: ${user.email} (${user.id})`);
            const { error: updateError } = await supabase.auth.admin.updateUserById(
                user.id,
                { email_confirm: true }
            );
            
            if (updateError) {
                console.error(`Error confirming ${user.email}:`, updateError.message);
            } else {
                console.log(`Successfully confirmed ${user.email}`);
            }
        } else {
            // console.log(`User already confirmed: ${user.email}`);
        }
    }
    
    // Also, let's make sure the 'students' table has the password if it's in the metadata 
    // (though usually we don't store it there, but maybe some do)
    // Actually, let's just output a message.
    console.log('All users processed.');
}

confirmAllUsers();
