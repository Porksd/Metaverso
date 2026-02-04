require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseKey) {
  console.error('Missing SUPABASE env vars in .env.local');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

async function run() {
  console.log('Attempting test insert into companies...');
  const payload = {
    name: `TEST COMPANY ${Date.now()}`,
    tax_id: '00000000-0',
    address: 'Test address',
    phone: '000000000',
    email: 'test@example.com',
    password: 'changeme',
    is_active: true,
    logo_url: null
  };

  const { data, error, status } = await supabase
    .from('companies')
    .insert(payload)
    .select();

  console.log('Status:', status);
  console.log('Error:', error);
  console.log('Data:', data);
}

run().catch(err => {
  console.error('Unhandled error:', err);
  process.exit(1);
});
