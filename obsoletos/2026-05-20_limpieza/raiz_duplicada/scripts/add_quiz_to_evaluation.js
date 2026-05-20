const XLSX = require('xlsx');
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

const courseId = '34a730c6-358f-4a42-a6c3-9d74a0e8457e';

async function addQuizToEvaluation() {
  try {
    console.log('📖 Leyendo archivo Excel...');
    const excelPath = 'J:\\Empres\\MetaversOtec\\Desarrollos\\Cursos\\Curso Trabajo en Altura\\SACYR  TRABAJO EN ALTURA.xlsx';
    const workbook = XLSX.readFile(excelPath);
    
    const detalleSheet = workbook.Sheets['Detalle'];
    const detalleData = XLSX.utils.sheet_to_json(detalleSheet, { header: 1 });
    
    // Extraer preguntas únicas
    console.log('🔍 Procesando preguntas...');
    const questionsMap = new Map();
    
    for (let i = 1; i < detalleData.length; i++) {
      const row = detalleData[i];
      if (!row || row.length === 0) continue;
      
      const questionNum = row[8];
      const questionText = row[9];
      const correctLetter = row[12];
      const correctText = row[13];
      
      if (!questionNum || !questionText) continue;
      
      if (!questionsMap.has(questionNum)) {
        // Buscar todas las opciones para esta pregunta
        const options = [];
        for (let j = 1; j < detalleData.length; j++) {
          const optRow = detalleData[j];
          if (optRow[8] === questionNum && optRow[11]) {
            const optionLetter = optRow[10];
            const optionText = optRow[11];
            if (!options.find(o => o.text === optionText)) {
              options.push({
                id: `opt-${questionNum}-${optionLetter}`,
                text: optionText
              });
            }
          }
        }
        
        const questionType = options.length <= 2 ? 'truefalse' : 'single';
        const correctAnswerId = `opt-${questionNum}-${correctLetter}`;
        
        questionsMap.set(questionNum, {
          type: questionType,
          question: questionText,
          options: options,
          correctAnswer: correctAnswerId
        });
      }
    }
    
    console.log(`✓ Procesadas ${questionsMap.size} preguntas`);
    
    // Buscar el módulo de evaluación
    console.log('🔍 Buscando módulo de evaluación...');
    const { data: modules } = await supabase
      .from('course_modules')
      .select('*')
      .eq('course_id', courseId)
      .eq('type', 'evaluation');
    
    const evaluationModule = modules[0];
    console.log('✓ Módulo encontrado:', evaluationModule.title);
    
    // Agregar el quiz como primer item
    const questions = Array.from(questionsMap.values());
    const { data: quizItem, error } = await supabase
      .from('module_items')
      .insert({
        module_id: evaluationModule.id,
        type: 'quiz',
        order_index: 1,
        content: {
          title: 'Cuestionario de Evaluación',
          questions: questions,
          timeLimit: null,
          randomize: false
        }
      })
      .select()
      .single();
    
    if (error) {
      console.error('❌ Error:', error);
      throw error;
    }
    
    console.log('✅ Quiz agregado exitosamente!');
    console.log(`   ${questions.length} preguntas cargadas`);
    console.log('   Orden: 1 (antes del SCORM)');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    throw error;
  }
}

addQuizToEvaluation();
