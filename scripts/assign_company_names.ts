/**
 * Script para asignar company_name a estudiantes existentes
 */

import { createClient } from '@supabase/supabase-js';
import * as dotenv from 'dotenv';

dotenv.config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function assignCompanyNames() {
    console.log('🏢 Asignando company_names a estudiantes...\n');

    // Obtener primeros 20 estudiantes sin company_name
    const { data: students, error } = await supabase
        .from('students')
        .select('id, rut, first_name, last_name, company_name')
        .is('company_name', null)
        .limit(20);

    if (error) {
        console.error('❌ Error:', error.message);
        return;
    }

    if (!students || students.length === 0) {
        console.log('✅ Todos los estudiantes ya tienen company_name asignado');
        return;
    }

    console.log(`📝 Actualizando ${students.length} estudiantes a "Sacyr Chile S.A."\n`);

    let updated = 0;
    for (const student of students) {
        const { error: updateError } = await supabase
            .from('students')
            .update({ company_name: 'Sacyr Chile S.A.' })
            .eq('id', student.id);

        if (updateError) {
            console.error(`  ❌ Error actualizando ${student.first_name} ${student.last_name}: ${updateError.message}`);
        } else {
            updated++;
            console.log(`  ✅ ${updated}. ${student.first_name} ${student.last_name} (${student.rut})`);
        }
    }

    console.log(`\n🎉 ${updated} estudiantes actualizados correctamente`);
    console.log('\n💡 Ahora puedes ejecutar: npm run populate-dashboard');
}

assignCompanyNames()
    .then(() => process.exit(0))
    .catch(error => {
        console.error('❌ Error:', error);
        process.exit(1);
    });
