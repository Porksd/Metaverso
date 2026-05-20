const { createClient } = require('@supabase/supabase-js');
const path = require('path');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY
);

async function main() {
    // Direct query
    const { data, error } = await supabase
        .from('students')
        .select('id, first_name, last_name, rut, digital_signature_url')
        .eq('id', '46911462-3853-4c0c-963e-7383726f2f9a')
        .single();

    if (error) {
        console.error('Error:', error);
        return;
    }

    console.log('Datos del estudiante:');
    console.log('ID:', data.id);
    console.log('Nombre:', data.first_name, data.last_name);
    console.log('RUT:', data.rut);
    console.log('Firma digital:', data.digital_signature_url ? 'PRESENTE (' + data.digital_signature_url.length + ' chars)' : 'NULL');
    
    if (data.digital_signature_url) {
        console.log('Inicio de firma:', data.digital_signature_url.substring(0, 100));
    }
}

main();
