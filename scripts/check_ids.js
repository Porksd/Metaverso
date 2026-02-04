require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function checkIds() {
  console.log('--- Checking IDs cross-reference ---');
  
  const { data: clients } = await supabase.from('clients').select('id, name');
  const { data: companies } = await supabase.from('companies').select('id, name');
  
  console.log('Clients records:', clients);
  console.log('Companies records count:', companies.length);
  
  for (const client of clients) {
    const found = companies.find(c => c.id === client.id);
    if (found) {
      console.log(`Match found for ${client.name} (ID: ${client.id})`);
    } else {
      console.log(`NO Match for ${client.name} (ID: ${client.id}) in companies table`);
    }
  }
}

checkIds().then(() => process.exit(0));
