require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

async function checkSchema() {
  try {
    console.log('🔍 Verificando esquema de base de datos...\n');

    // Try different table names
    const tables = [
      'student_courses',
      'course_enrollments',
      'enrollments',
      'student_enrollments'
    ];

    for (const table of tables) {
      const { data, error } = await supabase
        .from(table)
        .select('*')
        .limit(1);

      if (!error) {
        console.log(`✅ Tabla encontrada: ${table}`);
        if (data && data.length > 0) {
          console.log('   Columnas:', Object.keys(data[0]).join(', '));
        }
      } else {
        console.log(`❌ ${table}: ${error.message}`);
      }
    }

    // Check students table structure
    const { data: students } = await supabase
      .from('students')
      .select('*')
      .limit(1);

    if (students && students.length > 0) {
      console.log('\n📋 Estructura de tabla students:');
      console.log('   Columnas:', Object.keys(students[0]).join(', '));
    }

  } catch (error) {
    console.error('❌ Error:', error);
  }
}

checkSchema().then(() => process.exit(0));
