/**
 * Script para verificar qué company_names existen en la base de datos
 */

import { createClient } from '@supabase/supabase-js';
import * as dotenv from 'dotenv';

dotenv.config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY!;

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function checkCompanyNames() {
    console.log('🔍 Verificando company_names en la base de datos...\n');

    // Obtener todos los estudiantes con sus company_names
    const { data: students, error } = await supabase
        .from('students')
        .select('id, rut, first_name, last_name, company_name')
        .order('company_name');

    if (error) {
        console.error('❌ Error:', error.message);
        return;
    }

    if (!students || students.length === 0) {
        console.log('❌ No se encontraron estudiantes en la base de datos');
        return;
    }

    // Agrupar por company_name
    const byCompany = new Map<string, any[]>();
    students.forEach(student => {
        const company = student.company_name || 'Sin empresa';
        if (!byCompany.has(company)) {
            byCompany.set(company, []);
        }
        byCompany.get(company)!.push(student);
    });

    console.log(`✅ Total de estudiantes: ${students.length}\n`);
    console.log('📊 Estudiantes por empresa:\n');

    Array.from(byCompany.entries())
        .sort((a, b) => b[1].length - a[1].length)
        .forEach(([company, studentList]) => {
            console.log(`  🏢 ${company}`);
            console.log(`     Estudiantes: ${studentList.length}`);
            console.log(`     Ejemplos: ${studentList.slice(0, 3).map(s => `${s.first_name} ${s.last_name}`).join(', ')}`);
            console.log('');
        });

    // Verificar enrollments existentes
    const { data: enrollments, error: enrollError } = await supabase
        .from('enrollments')
        .select('*, students!inner(company_name)')
        .order('created_at', { ascending: false })
        .limit(10);

    if (!enrollError && enrollments) {
        console.log('📋 Últimos 10 enrollments:\n');
        enrollments.forEach((e, i) => {
            console.log(`  ${i + 1}. Empresa: ${(e as any).students?.company_name || 'N/A'}, Status: ${e.status}, Score: ${e.best_score || 'N/A'}`);
        });
    }

    console.log('\n✅ Verificación completada');
}

checkCompanyNames()
    .then(() => process.exit(0))
    .catch(error => {
        console.error('❌ Error:', error);
        process.exit(1);
    });
