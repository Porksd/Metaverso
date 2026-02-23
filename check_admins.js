const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: 'App/.env.local' }); // Adjust path if needed

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !serviceKey) {
  console.error('Missing Supabase credentials');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, serviceKey);

async function checkAdmins() {
  const { data, error } = await supabase.from('admin_profiles').select('*');
  if (error) {
    console.error('Error fetching admins:', error);
  } else {
    console.log('Admins found:', data);
  }
}

checkAdmins();
