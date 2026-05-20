import { createClient } from '@supabase/supabase-js';
import * as dotenv from 'dotenv';

dotenv.config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

const supabase = createClient(supabaseUrl, serviceKey);

async function run() {
  try {
    const { data, error } = await supabase
      .from('activity_logs')
      .select('course_id')
      .limit(1);

    if (error) {
      console.error('Column `course_id` does NOT exist or query failed:', error.message);
      process.exitCode = 2;
    } else {
      console.log('Column `course_id` exists. Sample row:', data);
    }
  } catch (err: any) {
    console.error('Unexpected error:', err.message || err);
    process.exitCode = 1;
  }
}

run();
