const { createClient } = require('@supabase/supabase-js');
const path = require('path');

require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

// Try with service role key if available, otherwise anon
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
console.log('Using key type:', process.env.SUPABASE_SERVICE_ROLE_KEY ? 'SERVICE_ROLE' : 'ANON');

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    supabaseKey
);

const STUDENT_ID = '46911462-3853-4c0c-963e-7383726f2f9a';

// Simple test signature
const TEST_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

async function main() {
    console.log('🖊️  Intentando guardar firma...\n');

    // Try update
    const { data, error } = await supabase
        .from('students')
        .update({ digital_signature_url: TEST_SIGNATURE })
        .eq('id', STUDENT_ID)
        .select();

    if (error) {
        console.error('❌ Error al actualizar:', error);
        console.error('   Código:', error.code);
        console.error('   Mensaje:', error.message);
        console.error('   Detalles:', error.details);
        return;
    }

    console.log('✅ Actualización ejecutada');
    console.log('Rows affected:', data?.length || 0);
    
    if (data && data.length > 0) {
        console.log('\nDatos actualizados:');
        console.log('ID:', data[0].id);
        console.log('Firma guardada:', data[0].digital_signature_url ? 'SÍ' : 'NO');
        
        if (data[0].digital_signature_url) {
            console.log('Tamaño:', data[0].digital_signature_url.length, 'chars');
        }
    }

    // Verify with fresh query
    console.log('\n🔍 Verificando con query nueva...');
    const { data: check } = await supabase
        .from('students')
        .select('digital_signature_url')
        .eq('id', STUDENT_ID)
        .single();

    if (check) {
        console.log('Firma en DB:', check.digital_signature_url ? '✅ PRESENTE' : '❌ NULL');
    }
}

main().catch(console.error);
