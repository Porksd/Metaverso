require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function checkTables() {
  try {
    // Check companies_list
    const { data: companies } = await supabase
      .from('companies_list')
      .select('*')
      .limit(1);

    if (companies && companies.length > 0) {
      console.log('companies_list columns:', Object.keys(companies[0]));
    } else {
      console.log('companies_list is empty');
    }

    // Check job_positions
    const { data: positions } = await supabase
      .from('job_positions')
      .select('*')
      .limit(1);

    if (positions && positions.length > 0) {
      console.log('job_positions columns:', Object.keys(positions[0]));
    } else {
      console.log('job_positions is empty');
    }

    // Check enrollments
    const { data: enrolls } = await supabase
      .from('enrollments')
      .select('*')
      .limit(1);

    if (enrolls && enrolls.length > 0) {
      console.log('enrollments columns:', Object.keys(enrolls[0]));
    } else {
      console.log('enrollments is empty');
    }

  } catch (error) {
    console.error('Error:', error);
  }
}

checkTables().then(() => process.exit(0));
