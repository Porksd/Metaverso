const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

const courseId = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function verifyContent() {
  // Obtener módulos
  const { data: modules } = await supabase
    .from('course_modules')
    .select('*')
    .eq('course_id', courseId)
    .order('order_index');
  
  console.log('\n📚 MÓDULOS DEL CURSO:\n');
  
  for (const module of modules) {
    console.log(`\n${module.order_index}. ${module.title} (${module.type})`);
    
    // Obtener items del módulo
    const { data: items } = await supabase
      .from('module_items')
      .select('*')
      .eq('module_id', module.id)
      .order('order_index');
    
    if (items && items.length > 0) {
      items.forEach(item => {
        console.log(`   ${item.order_index}. [${item.type}]`);
        console.log(`      Content:`, JSON.stringify(item.content, null, 2));
      });
    } else {
      console.log('   (sin items)');
    }
  }
}

verifyContent();
