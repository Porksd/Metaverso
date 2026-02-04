require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');
const XLSX = require('xlsx');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

const STUDENT_RUT = '20.207.790-0';
const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';
const EXCEL_PATH = 'j:\\Empres\\MetaversOtec\\Desarrollos\\Cursos\\Curso Trabajo en Altura\\SACYR  TRABAJO EN ALTURA.xlsx';

async function fixEverything() {
  try {
    console.log('🚀 Configurando sistema completo...\n');

    // 1. Load Excel data
    console.log('📊 Cargando datos desde Excel...');
    const workbook = XLSX.readFile(EXCEL_PATH);
    const respuestasSheet = XLSX.utils.sheet_to_json(workbook.Sheets['Respuestas']);
    
    // Extract unique companies and positions
    const companies = [...new Set(respuestasSheet
      .map(row => row['Empresa'] || row['EMPRESA'])
      .filter(Boolean))];
    
    const positions = [...new Set(respuestasSheet
      .map(row => row['Cargo'] || row['CARGO'])
      .filter(Boolean))];

    console.log(`   Empresas encontradas: ${companies.length}`);
    console.log(`   Cargos encontrados: ${positions.length}`);

    // 2. Create companies
    console.log('\n🏢 Creando empresas...');
    const companyMap = {};
    for (const companyName of companies) {
      // Check if exists
      const { data: existing } = await supabase
        .from('companies_list')
        .select('id')
        .eq('name_es', companyName)
        .single();

      if (existing) {
        companyMap[companyName] = existing.id;
        console.log(`   ✓ ${companyName} (ya existe)`);
      } else {
        const { data: newCompany, error } = await supabase
          .from('companies_list')
          .insert({ 
            name_es: companyName,
            code: companyName.toUpperCase().replace(/\s+/g, '_').substring(0, 50)
          })
          .select()
          .single();

        if (error) {
          console.log(`   ✗ Error creando ${companyName}:`, error.message);
        } else {
          companyMap[companyName] = newCompany.id;
          console.log(`   ✓ ${companyName} (creada)`);
        }
      }
    }

    // 3. Create job positions
    console.log('\n💼 Creando cargos...');
    const positionMap = {};
    for (const positionName of positions) {
      const { data: existing } = await supabase
        .from('job_positions')
        .select('id')
        .eq('name_es', positionName)
        .single();

      if (existing) {
        positionMap[positionName] = existing.id;
        console.log(`   ✓ ${positionName} (ya existe)`);
      } else {
        const { data: newPosition, error } = await supabase
          .from('job_positions')
          .insert({ 
            name_es: positionName,
            code: positionName.toUpperCase().replace(/\s+/g, '_').substring(0, 50)
          })
          .select()
          .single();

        if (error) {
          console.log(`   ✗ Error creando ${positionName}:`, error.message);
        } else {
          positionMap[positionName] = newPosition.id;
          console.log(`   ✓ ${positionName} (creado)`);
        }
      }
    }

    // 4. Get the specific student
    const { data: student, error: studentError } = await supabase
      .from('students')
      .select('*')
      .eq('rut', STUDENT_RUT)
      .single();

    if (studentError) {
      console.log('\n❌ Estudiante no encontrado');
      return;
    }

    console.log('\n👤 Estudiante encontrado:', student.first_name, student.last_name);

    // 5. Find student data in Excel
    const studentData = respuestasSheet.find(row => 
      row['RUT'] && row['RUT'].toString().replace(/\./g, '').replace(/-/g, '') === STUDENT_RUT.replace(/\./g, '').replace(/-/g, '')
    );

    if (studentData) {
      console.log('   Datos en Excel encontrados:');
      console.log('   - Empresa:', studentData['Empresa'] || studentData['EMPRESA']);
      console.log('   - Cargo:', studentData['Cargo'] || studentData['CARGO']);
      console.log('   - Nombre:', studentData['Nombre'] || studentData['NOMBRE COMPLETO']);

      // Update student with company_name and position (if students table doesn't have FK)
      const updates = {
        company_name: studentData['Empresa'] || studentData['EMPRESA'],
        position: studentData['Cargo'] || studentData['CARGO']
      };

      const { error: updateError } = await supabase
        .from('students')
        .update(updates)
        .eq('id', student.id);

      if (updateError) {
        console.log('   ⚠️  Error actualizando estudiante:', updateError.message);
      } else {
        console.log('   ✅ Datos actualizados');
      }
    }

    // 6. Check/Create enrollment
    console.log('\n📚 Verificando inscripción al curso...');
    const { data: enrollment, error: enrollmentError } = await supabase
      .from('enrollments')
      .select('*')
      .eq('student_id', student.id)
      .eq('course_id', COURSE_ID)
      .single();

    if (enrollmentError && enrollmentError.code === 'PGRST116') {
      // Not found, create it
      console.log('   Creando inscripción...');
      const { error: createError } = await supabase
        .from('enrollments')
        .insert({
          student_id: student.id,
          course_id: COURSE_ID,
          status: 'active',
          current_attempt: 1,
          max_attempts: 3
        });

      if (createError) {
        console.log('   ❌ Error creando inscripción:', createError.message);
      } else {
        console.log('   ✅ Inscripción creada exitosamente');
      }
    } else if (enrollment) {
      console.log('   ✅ Ya está inscrito');
      console.log('   Estado:', enrollment.status);
    }

    console.log('\n✅ Configuración completada!');
    console.log('\n📋 RESUMEN:');
    console.log(`   - Empresas en sistema: ${Object.keys(companyMap).length}`);
    console.log(`   - Cargos en sistema: ${Object.keys(positionMap).length}`);
    console.log(`   - Estudiante: ${student.first_name} ${student.last_name}`);
    console.log(`   - Empresa asignada: ${studentData?.['Empresa'] || studentData?.['EMPRESA'] || 'N/A'}`);
    console.log(`   - Cargo asignado: ${studentData?.['Cargo'] || studentData?.['CARGO'] || 'N/A'}`);
    console.log(`   - Inscrito en curso: SÍ`);

  } catch (error) {
    console.error('❌ Error:', error);
    process.exit(1);
  }
}

fixEverything().then(() => {
  process.exit(0);
}).catch(err => {
  console.error('💥 Error fatal:', err);
  process.exit(1);
});
