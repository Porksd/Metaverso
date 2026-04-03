import { createClient } from '@supabase/supabase-js';
require('dotenv').config({ path: require('path').join(__dirname, '..', '.env.local') });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

if (!supabaseUrl || !serviceKey) {
  console.error('Missing Supabase credentials in .env.local');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, serviceKey);

const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e'; // Trabajo en Altura (test)

async function main() {
  console.log('Updating evaluation module scorm_percentage to 20% for course', COURSE_ID);

  const { data: modules, error } = await supabase
    .from('course_modules')
    .select('*')
    .eq('course_id', COURSE_ID)
    .eq('type', 'evaluation');

  if (error) {
    console.error('Error fetching modules:', error);
    process.exit(1);
  }

  if (!modules || modules.length === 0) {
    console.log('No evaluation module found for course');
    return;
  }

  for (const m of modules) {
    const settings = m.settings || {};
    settings.scorm_percentage = 20;
    settings.quiz_percentage = 80;

    const { error: upErr } = await supabase
      .from('course_modules')
      .update({ settings })
      .eq('id', m.id);

    if (upErr) {
      console.error('Failed to update module', m.id, upErr);
    } else {
      console.log('Updated module', m.id);
    }
  }

  console.log('Done.');
}

main().catch((e) => { console.error(e); process.exit(1); });
