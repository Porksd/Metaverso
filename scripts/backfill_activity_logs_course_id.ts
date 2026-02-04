import { createClient } from '@supabase/supabase-js';

// Load .env.local explicitly to ensure credentials are available when run via tsx/npx
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

if (!supabaseUrl || !serviceKey) {
  console.error('Missing Supabase credentials in environment (.env.local).');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, serviceKey);

async function backfill() {
  console.log('Starting backfill of activity_logs.course_id...');
  const batchSize = 200;
  let totalProcessed = 0;
  let totalUpdated = 0;

  while (true) {
    const { data: logs, error } = await supabase
      .from('activity_logs')
      .select('id,enrollment_id')
      .is('course_id', null)
      .not('enrollment_id', 'is', null)
      .limit(batchSize);

    if (error) {
      console.error('Error fetching activity_logs batch:', error);
      break;
    }

    if (!logs || logs.length === 0) break;

    for (const row of logs) {
      totalProcessed++;
      try {
        const { data: enrollment, error: enrErr } = await supabase
          .from('enrollments')
          .select('course_id')
          .eq('id', row.enrollment_id)
          .single();

        if (enrErr) {
          // Enrollment missing or error; skip
          continue;
        }

        if (enrollment && enrollment.course_id) {
          const { error: upErr } = await supabase
            .from('activity_logs')
            .update({ course_id: enrollment.course_id })
            .eq('id', row.id);

          if (upErr) {
            console.error('Failed to update activity_log', row.id, upErr);
          } else {
            totalUpdated++;
          }
        }
      } catch (err) {
        console.error('Unexpected error processing row', row, err);
      }
    }

    console.log(`Batch processed: ${logs.length} rows — totalProcessed=${totalProcessed} totalUpdated=${totalUpdated}`);

    if (logs.length < batchSize) break;
  }

  console.log('Backfill complete.', { totalProcessed, totalUpdated });
}

backfill()
  .catch((e) => {
    console.error('Backfill failed:', e);
    process.exit(1);
  })
  .finally(() => process.exit(0));
