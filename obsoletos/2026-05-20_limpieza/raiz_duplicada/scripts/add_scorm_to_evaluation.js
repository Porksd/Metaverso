const { createClient } = require('@supabase/supabase-js');
const AdmZip = require('adm-zip');
const fs = require('fs').promises;
const path = require('path');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

const courseId = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';
const scormZipPath = 'J:\\Empres\\MetaversOtec\\Desarrollos\\Cursos\\Scorm\\scorm-2026-01-20-142520.zip';

async function addScormToEvaluation() {
  try {
    console.log('🔍 Buscando módulo de evaluación...');
    
    // Buscar el módulo de evaluación
    const { data: modules } = await supabase
      .from('course_modules')
      .select('*')
      .eq('course_id', courseId)
      .eq('type', 'evaluation')
      .order('order_index', { ascending: true });
    
    if (!modules || modules.length === 0) {
      throw new Error('No se encontró el módulo de evaluación');
    }
    
    const evaluationModule = modules[0];
    console.log('✓ Módulo encontrado:', evaluationModule.title);
    
    // Obtener los items actuales del módulo
    const { data: items } = await supabase
      .from('module_items')
      .select('*')
      .eq('module_id', evaluationModule.id)
      .order('order_index', { ascending: true });
    
    console.log(`📋 Items actuales: ${items.length}`);
    
    // Preparar directorio de destino
    const timestamp = Date.now();
    const extractPath = path.join(process.cwd(), 'public', 'uploads', 'courses', courseId, `scorm_${timestamp}`);
    
    console.log('📦 Extrayendo SCORM...');
    const zip = new AdmZip(scormZipPath);
    zip.extractAllTo(extractPath, true);
    
    // Buscar el archivo de entrada (index.html o imsmanifest.xml)
    const files = await fs.readdir(extractPath);
    let entryPoint = 'index.html';
    
    if (files.includes('imsmanifest.xml')) {
      entryPoint = 'imsmanifest.xml';
    }
    
    const scormUrl = `/uploads/courses/${courseId}/scorm_${timestamp}/${entryPoint}`;
    console.log('✓ SCORM extraído en:', scormUrl);
    
    // Insertar el item SCORM después del quiz (order_index = 2)
    const { data: scormItem, error } = await supabase
      .from('module_items')
      .insert({
        module_id: evaluationModule.id,
        type: 'scorm',
        order_index: 2,
        content: {
          url: scormUrl,
          entryPoint: entryPoint,
          title: 'Simulación SCORM'
        }
      })
      .select()
      .single();
    
    if (error) {
      console.error('❌ Error:', error);
      throw error;
    }
    
    console.log('✅ Item SCORM agregado exitosamente!');
    console.log('   ID:', scormItem.id);
    console.log('   Orden: 2 (después del quiz, antes de la firma)');
    
    // Actualizar configuración del módulo para incluir peso del SCORM
    await supabase
      .from('course_modules')
      .update({
        settings: {
          ...evaluationModule.settings,
          scormPercentage: 30, // 30% SCORM, 70% Quiz
          quizPercentage: 70
        }
      })
      .eq('id', evaluationModule.id);
    
    console.log('✓ Configuración actualizada: 70% Quiz + 30% SCORM');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    throw error;
  }
}

addScormToEvaluation();
