require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function fixAllVideos() {
  try {
    console.log('🔧 Actualizando todos los videos del curso...\n');
    console.log('Using Supabase URL:', process.env.NEXT_PUBLIC_SUPABASE_URL);

    // Get all modules
    const { data: modules, error: modulesError } = await supabase
      .from('course_modules')
      .select('id, title, order_index')
      .eq('course_id', COURSE_ID)
      .order('order_index');

    if (modulesError) {
      console.error('Error getting modules:', modulesError);
      throw modulesError;
    }

    console.log(`Found ${modules.length} modules\n`);

    for (const module of modules) {
      console.log(`📦 Módulo ${module.order_index}: ${module.title}`);

      // Get video items in this module
      const { data: videoItems, error: itemsError } = await supabase
        .from('module_items')
        .select('*')
        .eq('module_id', module.id)
        .eq('type', 'video');

      if (itemsError) throw itemsError;

      for (const item of videoItems) {
        const url = item.content?.url;
        
        // Check if it's a broken local path
        if (url && url.startsWith('/uploads/courses/ALTURA')) {
          console.log(`  ⚠️  Video roto encontrado: ${url}`);
          
          // Map different videos based on module
          let newUrl = 'https://www.youtube.com/watch?v=9K3VJKfvzCM'; // Default
          
          if (module.title === 'Medidas Preventivas') {
            newUrl = 'https://www.youtube.com/watch?v=ZWaXCP8tPWY'; // Another safety video
          }
          
          const newContent = {
            url: newUrl,
            provider: 'youtube'
          };

          const { error: updateError } = await supabase
            .from('module_items')
            .update({ content: newContent })
            .eq('id', item.id);

          if (updateError) throw updateError;

          console.log(`  ✅ Actualizado a: ${newUrl}`);
        } else if (url) {
          console.log(`  ✔️  Video OK: ${url}`);
        }
      }
    }

    console.log('\n🏁 Todos los videos actualizados exitosamente!');

  } catch (error) {
    console.error('❌ Error:', error);
    process.exit(1);
  }
}

fixAllVideos().then(() => {
  process.exit(0);
}).catch(err => {
  console.error('💥 Error fatal:', err);
  process.exit(1);
});
