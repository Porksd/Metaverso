const { createClient } = require('@supabase/supabase-js');
const fs = require('fs');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

if (!supabaseUrl || !supabaseAnonKey) {
    console.error('Supabase credentials missing in .env.local');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseAnonKey);

async function migrate() {
    const sqlFile = fs.readFileSync('import_students.sql', 'utf8');
    const lines = sqlFile.split('\n').filter(line => line.trim() !== '');

    console.log(`Starting migration of ${lines.length} students...`);

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        // Extraer los valores del INSERT INTO script generado
        // INSERT INTO public.students (client_id, rut, first_name, last_name, email, gender, age, position) VALUES (...)
        const matches = line.match(/VALUES \((.*)\) ON CONFLICT/);
        if (!matches) continue;

        const valuesStr = matches[1];
        const parts = valuesStr.split(',').map(s => s.trim().replace(/^'|'$/g, ''));

        // parts mapping based on: (client_id, rut, first_name, last_name, email, gender, age, position)
        const [client_id, rut, first_name, last_name, email, gender, age, position] = parts;

        const { error } = await supabase.from('students').upsert({
            client_id,
            rut,
            first_name,
            last_name,
            email: email === 'NULL' ? null : email,
            gender,
            age: age === 'NULL' ? null : parseInt(age),
            position
        }, { onConflict: 'client_id,rut' });

        if (error) {
            console.error(`Error migrating student ${rut}:`, error.message);
        }

        if ((i + 1) % 100 === 0) {
            console.log(`Migrated ${i + 1} students...`);
        }
    }

    console.log('Migration finished.');
}

migrate();
