const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

const courseId = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function restoreOriginalContent() {
  console.log('🔄 Restaurando contenido original del curso...\n');
  
  // Módulo 0: Introducción
  const { data: mod0 } = await supabase
    .from('course_modules')
    .insert({
      course_id: courseId,
      title: 'Introducción',
      type: 'content',
      order_index: 0,
      settings: { minScore: 70, mandatory: true }
    })
    .select()
    .single();
  
  await supabase.from('module_items').insert([
    {
      module_id: mod0.id,
      type: 'video',
      order_index: 0,
      content: {
        url: '/uploads/courses/ALTURA/media/intro.mp4',
        provider: 'html5'
      }
    },
    {
      module_id: mod0.id,
      type: 'text',
      order_index: 1,
      content: {}
    }
  ]);
  console.log('✓ Módulo Introducción restaurado');
  
  // Módulo 1: Señalética
  const { data: mod1 } = await supabase
    .from('course_modules')
    .insert({
      course_id: courseId,
      title: 'Señalética',
      type: 'content',
      order_index: 1,
      settings: { minScore: 70, mandatory: true }
    })
    .select()
    .single();
  
  await supabase.from('module_items').insert([
    {
      module_id: mod1.id,
      type: 'text',
      order_index: 0,
      content: {
        text: 'Reconoce la señalización y comprende su significado.'
      }
    },
    {
      module_id: mod1.id,
      type: 'audio',
      order_index: 1,
      content: {
        url: '/uploads/courses/ALTURA/media/slide2.mp3'
      }
    },
    {
      module_id: mod1.id,
      type: 'image',
      order_index: 2,
      content: {
        url: '/uploads/courses/ALTURA/media/slide2.jpg'
      }
    }
  ]);
  console.log('✓ Módulo Señalética restaurado');
  
  // Módulo 2: Actividad Interactiva
  const { data: mod2 } = await supabase
    .from('course_modules')
    .insert({
      course_id: courseId,
      title: 'Actividad Interactiva',
      type: 'content',
      order_index: 2,
      settings: { minScore: 70, mandatory: true }
    })
    .select()
    .single();
  
  await supabase.from('module_items').insert({
    module_id: mod2.id,
    type: 'genially',
    order_index: 0,
    content: {
      url: 'https://view.genial.ly/placeholder'
    }
  });
  console.log('✓ Módulo Actividad Interactiva restaurado');
  
  // Módulo 3: Medidas Preventivas
  const { data: mod3 } = await supabase
    .from('course_modules')
    .insert({
      course_id: courseId,
      title: 'Medidas Preventivas',
      type: 'content',
      order_index: 3,
      settings: { minScore: 70, mandatory: true }
    })
    .select()
    .single();
  
  await supabase.from('module_items').insert({
    module_id: mod3.id,
    type: 'video',
    order_index: 0,
    content: {
      url: '/uploads/courses/ALTURA/media/slide4.mp4'
    }
  });
  console.log('✓ Módulo Medidas Preventivas restaurado');
  
  // Módulo 4: Práctica
  const { data: mod4 } = await supabase
    .from('course_modules')
    .insert({
      course_id: courseId,
      title: 'Práctica',
      type: 'content',
      order_index: 4,
      settings: { minScore: 70, mandatory: true }
    })
    .select()
    .single();
  
  await supabase.from('module_items').insert({
    module_id: mod4.id,
    type: 'genially',
    order_index: 0,
    content: {
      url: 'https://view.genial.ly/placeholder'
    }
  });
  console.log('✓ Módulo Práctica restaurado');
  
  // Módulo 5: Consejos Finales
  const { data: mod5 } = await supabase
    .from('course_modules')
    .insert({
      course_id: courseId,
      title: 'Consejos Finales',
      type: 'content',
      order_index: 5,
      settings: { minScore: 70, mandatory: true }
    })
    .select()
    .single();
  
  await supabase.from('module_items').insert([
    {
      module_id: mod5.id,
      type: 'text',
      order_index: 0,
      content: {
        text: 'Recuerda verificar el estado de los elementos de seguridad diariamente.'
      }
    },
    {
      module_id: mod5.id,
      type: 'audio',
      order_index: 1,
      content: {
        url: '/uploads/courses/ALTURA/media/slide6.mp3'
      }
    }
  ]);
  console.log('✓ Módulo Consejos Finales restaurado');
  
  // Actualizar order_index de los módulos nuevos
  await supabase
    .from('course_modules')
    .update({ order_index: 6 })
    .eq('course_id', courseId)
    .eq('title', 'Bienvenida');
  
  await supabase
    .from('course_modules')
    .update({ order_index: 7 })
    .eq('course_id', courseId)
    .eq('title', 'Contenido del Curso');
  
  await supabase
    .from('course_modules')
    .update({ order_index: 8 })
    .eq('course_id', courseId)
    .eq('title', 'Evaluación Final');
  
  await supabase
    .from('course_modules')
    .update({ order_index: 9 })
    .eq('course_id', courseId)
    .eq('title', 'Certificado');
  
  console.log('\n✅ Contenido original completamente restaurado');
  console.log('   Orden actualizado:');
  console.log('   0-5: Módulos originales');
  console.log('   6-9: Módulos del Excel (Bienvenida, Contenido, Evaluación, Certificado)');
}

restoreOriginalContent();
