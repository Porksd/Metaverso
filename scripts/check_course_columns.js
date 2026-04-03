const { createClient } = require('@supabase/supabase-js');
const path = require('path');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY
);

async function main() {
    // Get course and see actual structure
    const { data: course, error } = await supabase
        .from('courses')
        .select('*')
        .eq('id', '34a730c6-358f-4a42-a6c3-9d74a0e8457e')
        .single();

    if (error) {
        console.error('Error:', error);
        return;
    }

    console.log('Columnas del curso:');
    console.log(Object.keys(course));
    console.log('\nDatos completos:');
    console.log(JSON.stringify(course, null, 2));
}

main();
