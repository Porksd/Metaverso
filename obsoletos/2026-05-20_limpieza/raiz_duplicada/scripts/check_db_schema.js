require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function checkDb() {
  console.log('--- Checking Database Tables ---');
  
  const tablesToCheck = ['companies', 'clients', 'students', 'enrollments'];
  
  for (const table of tablesToCheck) {
    try {
      const { data, error, count } = await supabase
        .from(table)
        .select('*', { count: 'exact', head: true });
        
      if (error) {
        console.log(`Table [${table}]: Error - ${error.message}`);
      } else {
        console.log(`Table [${table}]: Exists. Count: ${count}`);
        
        // Get some columns if possible
        const { data: cols } = await supabase.from(table).select('*').limit(1);
        if (cols && cols.length > 0) {
          console.log(`  Columns: ${Object.keys(cols[0]).join(', ')}`);
        }
      }
    } catch (err) {
      console.log(`Table [${table}]: Exception - ${err.message}`);
    }
  }
}

checkDb().then(() => process.exit(0));
