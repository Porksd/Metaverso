
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseKey) {
  console.error("Missing credentials");
  process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

async function checkSchema() {
  console.log("Checking Companies table...");
  const { data: companies, error: errComp } = await supabase.from('companies').select('*').limit(1);
  if (errComp) console.log("Companies error:", errComp.message);
  else console.log("Companies sample:", companies.length > 0 ? Object.keys(companies[0]) : "Empty");

  console.log("Checking Students table...");
  const { data: students, error: errStud } = await supabase.from('students').select('*').limit(1);
  if (errStud) console.log("Students error:", errStud.message);
  else console.log("Students sample:", students.length > 0 ? Object.keys(students[0]) : "Empty");

  console.log("Checking Courses table...");
  const { data: courses, error: errCourse } = await supabase.from('courses').select('*').limit(1);
  if (errCourse) console.log("Courses error:", errCourse.message);
  else console.log("Courses sample:", courses.length > 0 ? Object.keys(courses[0]) : "Empty");
}

checkSchema();
