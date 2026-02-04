require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

const STUDENT_RUT = '20.207.790-0';
const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function checkStudentStatus() {
  try {
    console.log('🔍 Verificando estado del estudiante...\n');
    console.log('RUT:', STUDENT_RUT);
    console.log('Course ID:', COURSE_ID);
    console.log('─'.repeat(60));

    // 1. Check if student exists
    const { data: student, error: studentError } = await supabase
      .from('students')
      .select('*')
      .eq('rut', STUDENT_RUT)
      .single();

    if (studentError) {
      console.log('\n❌ Estudiante NO encontrado en la base de datos');
      console.log('Error:', studentError.message);
      return;
    }

    console.log('\n✅ Estudiante encontrado:');
    console.log('   ID:', student.id);
    console.log('   Nombre:', student.name);
    console.log('   Email:', student.email);
    console.log('   Empresa ID:', student.company_id);
    console.log('   Cargo ID:', student.job_position_id);

    // 2. Check course enrollment
    const { data: enrollment, error: enrollmentError } = await supabase
      .from('student_courses')
      .select('*')
      .eq('student_id', student.id)
      .eq('course_id', COURSE_ID);

    console.log('\n📚 Estado de inscripción:');
    if (enrollmentError) {
      console.log('   ❌ Error al verificar inscripción:', enrollmentError.message);
    } else if (!enrollment || enrollment.length === 0) {
      console.log('   ⚠️  NO está inscrito en el curso');
    } else {
      console.log('   ✅ Está inscrito en el curso');
      console.log('   Estado:', enrollment[0].status);
      console.log('   Progreso:', enrollment[0].progress, '%');
      console.log('   Aprobado:', enrollment[0].approved ? 'Sí' : 'No');
    }

    // 3. Check company
    if (student.company_id) {
      const { data: company } = await supabase
        .from('companies_list')
        .select('*')
        .eq('id', student.company_id)
        .single();

      console.log('\n🏢 Empresa:');
      if (company) {
        console.log('   ✅', company.name);
      } else {
        console.log('   ⚠️  Empresa no encontrada');
      }
    } else {
      console.log('\n🏢 Empresa: ⚠️  No asignada');
    }

    // 4. Check job position
    if (student.job_position_id) {
      const { data: position } = await supabase
        .from('job_positions')
        .select('*')
        .eq('id', student.job_position_id)
        .single();

      console.log('\n💼 Cargo:');
      if (position) {
        console.log('   ✅', position.name);
      } else {
        console.log('   ⚠️  Cargo no encontrado');
      }
    } else {
      console.log('\n💼 Cargo: ⚠️  No asignado');
    }

    // 5. Check if there are companies and positions in DB
    const { data: companies } = await supabase
      .from('companies_list')
      .select('id, name')
      .limit(5);

    const { data: positions } = await supabase
      .from('job_positions')
      .select('id, name')
      .limit(5);

    console.log('\n📊 Datos en sistema:');
    console.log('   Empresas:', companies?.length || 0);
    if (companies && companies.length > 0) {
      companies.forEach(c => console.log('      -', c.name));
    }
    console.log('   Cargos:', positions?.length || 0);
    if (positions && positions.length > 0) {
      positions.forEach(p => console.log('      -', p.name));
    }

    console.log('\n' + '─'.repeat(60));
    console.log('🎯 ACCIONES NECESARIAS:\n');

    if (!enrollment || enrollment.length === 0) {
      console.log('   1. ❗ Inscribir al estudiante en el curso');
    }
    if (!student.company_id) {
      console.log('   2. ❗ Asignar empresa al estudiante');
    }
    if (!student.job_position_id) {
      console.log('   3. ❗ Asignar cargo al estudiante');
    }
    if (!companies || companies.length === 0) {
      console.log('   4. ❗ Cargar empresas desde Excel');
    }
    if (!positions || positions.length === 0) {
      console.log('   5. ❗ Cargar cargos desde Excel');
    }

  } catch (error) {
    console.error('❌ Error:', error);
    process.exit(1);
  }
}

checkStudentStatus().then(() => {
  process.exit(0);
}).catch(err => {
  console.error('💥 Error fatal:', err);
  process.exit(1);
});
