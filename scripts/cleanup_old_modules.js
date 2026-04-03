const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

const courseId = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function cleanupDuplicates() {
  console.log('🧹 Limpiando módulos antiguos...\n');
  
  // Obtener todos los módulos
  const { data: modules } = await supabase
    .from('course_modules')
    .select('*')
    .eq('course_id', courseId)
    .order('order_index');
  
  // Identificar módulos a eliminar (los del curso original que tienen order_index 0, 1, 2, 4, 5, 6)
  const modulesToDelete = modules.filter(m => 
    m.title === 'Introducción' || 
    m.title === 'Señalética' ||
    m.title === 'Actividad Interactiva' ||
    m.title === 'Medidas Preventivas' ||
    m.title === 'Práctica' ||
    m.title === 'Consejos Finales' ||
    (m.title === 'Evaluación Final' && m.order_index === 6)
  );
  
  console.log(`📋 Módulos a eliminar: ${modulesToDelete.length}`);
  
  for (const module of modulesToDelete) {
    console.log(`   ❌ ${module.title} (order: ${module.order_index})`);
    
    // Eliminar items del módulo primero
    const { error: itemsError } = await supabase
      .from('module_items')
      .delete()
      .eq('module_id', module.id);
    
    if (itemsError) {
      console.error('      Error eliminando items:', itemsError.message);
    }
    
    // Eliminar el módulo
    const { error: moduleError } = await supabase
      .from('course_modules')
      .delete()
      .eq('id', module.id);
    
    if (moduleError) {
      console.error('      Error eliminando módulo:', moduleError.message);
    }
  }
  
  console.log('\n✅ Limpieza completada');
  console.log('\n📚 Módulos restantes:');
  
  // Mostrar módulos restantes
  const { data: remaining } = await supabase
    .from('course_modules')
    .select('*')
    .eq('course_id', courseId)
    .order('order_index');
  
  remaining.forEach(m => {
    console.log(`   ${m.order_index}. ${m.title} (${m.type})`);
  });
}

cleanupDuplicates();
