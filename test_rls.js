const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

const supabase = createClient(supabaseUrl, supabaseAnonKey);

async function checkRLS() {
    console.log("Checking RLS for module_items...");
    
    // 1. Try to add a dummy item to a known module (if any) or just a test
    const { data, error } = await supabase
        .from('module_items')
        .insert({
            module_id: '00000000-0000-0000-0000-000000000000', // Invalid UUID but will trigger RLS check first
            type: 'header',
            content: {},
            order_index: 0
        });

    if (error) {
        console.log("Insert failed. Error code:", error.code);
        console.log("Error message:", error.message);
        if (error.message.includes("RLS") || error.message.includes("policy")) {
            console.log("CONFIRMED: RLS Policy issue.");
        }
    } else {
        console.log("Insert succeeded (unexpectedly).");
    }
}

checkRLS();
