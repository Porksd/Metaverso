require('dotenv').config({path: '.env.local'});
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL,
    process.env.SUPABASE_SERVICE_ROLE_KEY
);

(async () => {
    const { data, error } = await supabase
        .from('students')
        .select('consent_accepted_at')
        .limit(1);
    
    if (error && error.message.includes('column')) {
        console.log('❌ Column consent_accepted_at does not exist');
        console.log('');
        console.log('Run this SQL in Supabase Dashboard:');
        console.log('ALTER TABLE students ADD COLUMN IF NOT EXISTS consent_accepted_at TIMESTAMP WITH TIME ZONE;');
    } else {
        console.log('✅ Column consent_accepted_at exists and ready');
    }
})();
