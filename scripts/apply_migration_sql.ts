import { createClient } from '@supabase/supabase-js';
import * as fs from 'fs';
import * as dotenv from 'dotenv';

dotenv.config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !serviceKey) {
  console.error('Missing SUPABASE credentials in .env.local');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, serviceKey);

async function run() {
  try {
    const sql = fs.readFileSync('migrations/005_add_course_id_activity_logs.sql', 'utf8');
    console.log('Executing migration SQL:\n', sql.substring(0, 1000));

    // Try to use postgres.query if available
    // supabase-js v2 exposes supabase.postgres.query on some versions
    // We'll attempt it and fallback to calling the SQL via a function if not available
    // @ts-ignore
    if (supabase.postgres && typeof supabase.postgres.query === 'function') {
      // Some versions accept an object { sql }
      try {
        // @ts-ignore
        const res = await supabase.postgres.query({ sql });
        console.log('Result:', res);
      } catch (err) {
        // @ts-ignore
        const res2 = await supabase.postgres.query(sql);
        console.log('Result2:', res2);
      }
    } else {
      console.log('supabase.postgres.query not available in this client. Cannot run raw SQL programmatically.');
      process.exitCode = 2;
    }
  } catch (err: any) {
    console.error('Error executing migration:', err.message || err);
    process.exitCode = 1;
  }
}

run();
