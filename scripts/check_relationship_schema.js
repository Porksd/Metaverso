
const { createClient } = require('@supabase/supabase-js');
const dotenv = require('dotenv');
const fs = require('fs');
const path = require('path');

// Load environment variables
dotenv.config({ path: path.resolve(__dirname, '../.env.local') });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

if (!supabaseUrl || !supabaseKey) {
    console.error('Missing Supabase URL or Key in .env.local');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

async function checkSchema() {
    console.log('Checking company_courses table...');
    
    // We can't query information_schema easily with supabase-js client usually, 
    // but we can try to select a row and see the keys.
    const { data, error } = await supabase.from('company_courses').select('*').limit(1);
    
    if (error) {
        console.error('Error fetching company_courses:', error.message);
    } else {
        if (data && data.length > 0) {
            console.log('company_courses sample keys:', Object.keys(data[0]));
        } else {
            console.log('company_courses table is empty, cannot inspect keys via select.');
            // Try to insert a dummy and see error? No, safer to just assume we need to add it if recent
        }
    }
}

checkSchema();
