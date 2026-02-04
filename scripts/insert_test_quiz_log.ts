import { createClient } from '@supabase/supabase-js';
import path from 'path';

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

if (!supabaseUrl || !serviceKey) {
  console.error('Missing Supabase credentials in .env.local');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, serviceKey);

const STUDENT_ID = '46911462-3853-4c0c-963e-7383726f2f9a';
const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function main() {
  console.log('Inserting test final_quiz log for student', STUDENT_ID);

  const { data: enrollment, error: enrollErr } = await supabase
    .from('enrollments')
    .select('id')
    .eq('student_id', STUDENT_ID)
    .eq('course_id', COURSE_ID)
    .single();

  if (enrollErr || !enrollment) {
    console.error('Enrollment not found', enrollErr);
    process.exit(1);
  }

  // Build a weighted-per-question payload (example)
  const perQuestion = [
    { id: 'q1', correct: true, weight: 5 },
    { id: 'q2', correct: true, weight: 4 },
    { id: 'q3', correct: true, weight: 4 },
    { id: 'q4', correct: false, weight: 3 },
    { id: 'q5', correct: true, weight: 9 },
  ];

  // We'll set a quiz score that represents these results (for this test):
  const quizScore = 92; // chosen to be the best quiz for the enrollment

  const answers = {
    q1: 'a', q2: 'b', q3: 'c', q4: 'd', q5: 'a'
  };

  const { data: inserted, error: insertErr } = await supabase
    .from('activity_logs')
    .insert([{
      enrollment_id: enrollment.id,
      interaction_type: 'final_quiz',
      score: quizScore,
      raw_data: { answers, perQuestion },
    }])
    .select()
    .single();

  if (insertErr) {
    console.error('Failed to insert test quiz log', insertErr);
    process.exit(1);
  }

  console.log('Inserted final_quiz log id:', inserted.id, 'score:', inserted.score);

  // Also add a SCORM passed log at 100% to ensure SCORM best is 100
  const { data: scormInserted, error: sciErr } = await supabase
    .from('activity_logs')
    .insert([{
      enrollment_id: enrollment.id,
      interaction_type: 'passed',
      score: 100,
      raw_data: { 'cmi.core.score.raw': '100' }
    }])
    .select()
    .single();

  if (sciErr) {
    console.error('Failed to insert scorm log (non-fatal):', sciErr);
  } else {
    console.log('Inserted scorm log id:', scormInserted.id, 'score:', scormInserted.score);
  }

  console.log('Done inserting test logs.');
}

main().catch(e => { console.error(e); process.exit(1); });
