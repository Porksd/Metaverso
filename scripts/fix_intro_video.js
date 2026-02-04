require('dotenv').config({ path: '.env.local' });
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

const COURSE_ID = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function fixIntroVideo() {
  try {
    console.log('🔧 Actualizando video de introducción...');
    console.log('📍 Course ID:', COURSE_ID);

    // Get the first module (Introducción)
    const { data: module, error: moduleError } = await supabase
      .from('course_modules')
      .select('id')
      .eq('course_id', COURSE_ID)
      .eq('order_index', 0)
      .single();

    if (moduleError) {
      console.error('Error buscando módulo:', moduleError);
      throw moduleError;
    }

    console.log('📦 Módulo encontrado:', module.id);

    // Get the video item
    const { data: items, error: itemsError } = await supabase
      .from('module_items')
      .select('*')
      .eq('module_id', module.id)
      .eq('type', 'video')
      .order('order_index');

    if (itemsError) throw itemsError;

    if (items.length === 0) {
      console.log('⚠️ No se encontró ningún video en el módulo de Introducción');
      return;
    }

    const videoItem = items[0];
    console.log('🎥 Video actual:', videoItem.content);

    // Update with a working YouTube video about workplace safety
    const newContent = {
      url: 'https://www.youtube.com/watch?v=9K3VJKfvzCM', // Video sobre trabajo en altura
      provider: 'youtube'
    };

    const { error: updateError } = await supabase
      .from('module_items')
      .update({ content: newContent })
      .eq('id', videoItem.id);

    if (updateError) throw updateError;

    console.log('✅ Video actualizado exitosamente!');
    console.log('🔗 Nueva URL:', newContent.url);

  } catch (error) {
    console.error('❌ Error:', error);
    process.exit(1);
  }
}

fixIntroVideo().then(() => {
  console.log('🏁 Script finalizado');
  process.exit(0);
}).catch(err => {
  console.error('💥 Error fatal:', err);
  process.exit(1);
});
