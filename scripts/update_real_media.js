require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function updateRealMedia() {
  try {
    console.log('🔧 Actualizando con archivos multimedia reales...\n');

    // Get all modules
    const { data: modules, error: modulesError } = await supabase
      .from('course_modules')
      .select('id, title, order_index')
      .eq('course_id', COURSE_ID)
      .order('order_index');

    if (modulesError) throw modulesError;

    // Update Introducción video
    const introModule = modules.find(m => m.title === 'Introducción');
    if (introModule) {
      console.log('📦 Actualizando video de Introducción...');
      
      const { data: videoItems } = await supabase
        .from('module_items')
        .select('*')
        .eq('module_id', introModule.id)
        .eq('type', 'video');

      if (videoItems && videoItems.length > 0) {
        const { error: updateError } = await supabase
          .from('module_items')
          .update({
            content: {
              url: '/uploads/courses/ALTURA/media/intro.mp4',
              provider: 'html5'
            }
          })
          .eq('id', videoItems[0].id);

        if (updateError) throw updateError;
        console.log('   ✅ Video actualizado: intro.mp4');
      }
    }

    // Update Señalética image
    const senaleticaModule = modules.find(m => m.title === 'Señalética');
    if (senaleticaModule) {
      console.log('\n📦 Actualizando imagen de Señalética...');
      
      const { data: imageItems } = await supabase
        .from('module_items')
        .select('*')
        .eq('module_id', senaleticaModule.id)
        .eq('type', 'image');

      if (imageItems && imageItems.length > 0) {
        const { error: updateError } = await supabase
          .from('module_items')
          .update({
            content: {
              url: '/uploads/courses/ALTURA/media/sacyr.jpg'
            }
          })
          .eq('id', imageItems[0].id);

        if (updateError) throw updateError;
        console.log('   ✅ Imagen actualizada: sacyr.jpg');
      }
    }

    console.log('\n🏁 Archivos multimedia reales actualizados exitosamente!');
    console.log('\n📁 Archivos usados:');
    console.log('   - /uploads/courses/ALTURA/media/intro.mp4');
    console.log('   - /uploads/courses/ALTURA/media/sacyr.jpg');

  } catch (error) {
    console.error('❌ Error:', error);
    process.exit(1);
  }
}

updateRealMedia().then(() => {
  process.exit(0);
}).catch(err => {
  console.error('💥 Error fatal:', err);
  process.exit(1);
});
