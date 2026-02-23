
const { createClient } = require('@supabase/supabase-js');
const fs = require('fs');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !serviceKey) {
    console.error('Missing Supabase credentials in .env.local');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, serviceKey);

async function checkAndApply() {
    console.log('🔍 Checking database schema...');

    // 1. Check if admin_profiles table exists
    const { data: profiles, error: profilesError } = await supabase
        .from('admin_profiles')
        .select('*')
        .limit(1);

    if (profilesError && profilesError.code === '42P01') { // undefined_table
        console.error('❌ Table "admin_profiles" DOES NOT EXIST.');
    } else if (profilesError) {
        console.error('⚠️ Error checking admin_profiles:', profilesError.message);
    } else {
        console.log('✅ Table "admin_profiles" exists.');
    }

    // 2. Check if companies has user_registration_config
    const { data: companies, error: companiesError } = await supabase
        .from('companies')
        .select('user_registration_config')
        .limit(1);

    if (companiesError) {
        console.error('❌ Column "user_registration_config" likely missing in "companies". Error:', companiesError.message);
    } else {
        console.log('✅ Column "user_registration_config" exists in "companies".');
    }

    console.log('\n===================================================');
    console.log('🛠️  FIX INSTRUCTIONS');
    console.log('===================================================');
    console.log('Please execute the following SQL in your Supabase SQL Editor to fix these issues:\n');

    const sql1 = fs.readFileSync('migrations/026_add_admin_rbac.sql', 'utf8');
    const sql2 = fs.readFileSync('migrations/030_add_company_user_fields_config.sql', 'utf8');

    console.log('--- Step 1: Create Admin Profiles Table (if missing) ---');
    console.log(sql1);
    console.log('\n--- Step 2: Add Configuration Columns ---');
    console.log(sql2);
}

checkAndApply();
