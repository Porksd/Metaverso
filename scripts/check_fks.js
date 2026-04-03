require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function checkFKs() {
  console.log('--- Checking All Foreign Keys ---');
  
  const query = `
    SELECT
        tc.table_name, 
        kcu.column_name, 
        ccu.table_name AS foreign_table_name,
        ccu.column_name AS foreign_column_name,
        conname as constraint_name
    FROM 
        information_schema.table_constraints AS tc 
        JOIN information_schema.key_column_usage AS kcu
          ON tc.constraint_name = kcu.constraint_name
          AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
          ON ccu.constraint_name = tc.constraint_name
          AND ccu.table_schema = tc.table_schema
    WHERE tc.constraint_type = 'FOREIGN KEY' 
    AND tc.table_schema = 'public';
  `;

  const { data, error } = await supabase.rpc('run_sql', { sql_query: query });
  
  if (error) {
    // If rpc run_sql doesn't exist, try a direct query if possible or just check manually
    console.log('RPC run_sql not available. Checking common tables manually...');
    
    const tables = ['students', 'company_roles', 'company_courses', 'enrollments'];
    for (const t of tables) {
        // No easy way to get FKs without raw SQL or checking errors
        console.log(`Manual check for ${t} needed in Supabase Dashboard.`);
    }
  } else {
    console.table(data);
  }
}

checkFKs().then(() => process.exit(0));
