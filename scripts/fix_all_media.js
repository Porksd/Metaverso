require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

// Placeholder resources - replace with real ones or create them
const PLACEHOLDER_IMAGE = 'https://via.placeholder.com/800x600/1a1a1a/31d22d?text=Trabajo+en+Altura';
const PLACEHOLDER_AUDIO = 'https://www2.cs.uic.edu/~i101/SoundFiles/BabyElephantWalk60.wav'; // Will replace

async function fixAllMedia() {
  try {
    console.log('🔧 Actualizando todos los recursos multimedia...\n');

    // Get all modules
    const { data: modules, error: modulesError } = await supabase
      .from('course_modules')
      .select('id, title, order_index')
      .eq('course_id', COURSE_ID)
      .order('order_index');

    if (modulesError) throw modulesError;

    for (const module of modules) {
      console.log(`\n📦 Módulo ${module.order_index}: ${module.title}`);

      // Get all items in this module
      const { data: items, error: itemsError } = await supabase
        .from('module_items')
        .select('*')
        .eq('module_id', module.id)
        .order('order_index');

      if (itemsError) throw itemsError;

      for (const item of items) {
        const url = item.content?.url;
        
        // Check if it's a broken local path
        if (url && url.startsWith('/uploads/courses/ALTURA')) {
          console.log(`  ⚠️  ${item.type.toUpperCase()} roto: ${url}`);
          
          let newContent = { ...item.content };
          
          if (item.type === 'audio') {
            // For now, just remove the broken audio
            // You can upload real audio files later
            console.log(`  🔇 Eliminando audio roto (puedes subir uno real después)`);
            
            // Delete the item instead of updating
            const { error: deleteError } = await supabase
              .from('module_items')
              .delete()
              .eq('id', item.id);
              
            if (deleteError) throw deleteError;
            console.log(`  ✅ Item eliminado`);
            continue;
          }
          
          if (item.type === 'image') {
            newContent.url = PLACEHOLDER_IMAGE;
          }
          
          const { error: updateError } = await supabase
            .from('module_items')
            .update({ content: newContent })
            .eq('id', item.id);

          if (updateError) throw updateError;

          console.log(`  ✅ Actualizado a: ${newContent.url}`);
        } else if (url) {
          console.log(`  ✔️  ${item.type.toUpperCase()} OK`);
        }
      }
    }

    console.log('\n\n🏁 Todos los recursos actualizados exitosamente!');
    console.log('\n📝 NOTAS:');
    console.log('   - Los audios rotos fueron eliminados');
    console.log('   - Las imágenes usan placeholders');
    console.log('   - Puedes subir archivos reales usando el admin');

  } catch (error) {
    console.error('❌ Error:', error);
    process.exit(1);
  }
}

fixAllMedia().then(() => {
  process.exit(0);
}).catch(err => {
  console.error('💥 Error fatal:', err);
  process.exit(1);
});
