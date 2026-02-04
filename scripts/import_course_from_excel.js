const XLSX = require('xlsx');
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config({ path: '.env.local' });

// Configurar Supabase
const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

console.log('Supabase URL:', supabaseUrl);
console.log('Service Key:', supabaseKey ? 'Configurada ✓' : 'NO configurada ✗');

if (!supabaseUrl || !supabaseKey) {
  console.error('❌ Faltan variables de entorno');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

const courseId = '34a730c6-358f-4a42-a6c3-9d74a0e8457e'; // ID del curso demo

async function importCourseContent() {
  try {
    // 1. Leer el archivo Excel
    console.log('Leyendo archivo Excel...');
    const excelPath = 'J:\\Empres\\MetaversOtec\\Desarrollos\\Cursos\\Curso Trabajo en Altura\\SACYR  TRABAJO EN ALTURA.xlsx';
    const workbook = XLSX.readFile(excelPath);
    
    const detalleSheet = workbook.Sheets['Detalle'];
    const detalleData = XLSX.utils.sheet_to_json(detalleSheet, { header: 1 });
    
    // 2. Extraer preguntas únicas de la hoja Detalle
    console.log('Procesando preguntas...');
    const questionsMap = new Map();
    
    for (let i = 1; i < detalleData.length; i++) {
      const row = detalleData[i];
      if (!row || row.length === 0) continue;
      
      const questionNum = row[8]; // Pregunta #
      const questionText = row[9]; // Pregunta
      const correctLetter = row[12]; // Correcta (letra)
      const correctText = row[13]; // Correcta (texto)
      
      if (!questionNum || !questionText) continue;
      
      if (!questionsMap.has(questionNum)) {
        // Buscar todas las opciones para esta pregunta
        const options = [];
        for (let j = 1; j < detalleData.length; j++) {
          const optRow = detalleData[j];
          if (optRow[8] === questionNum && optRow[11]) { // Marcada (texto)
            const optionLetter = optRow[10]; // Marcada (letra)
            const optionText = optRow[11]; // Marcada (texto)
            if (!options.find(o => o.text === optionText)) {
              options.push({
                id: `opt-${questionNum}-${optionLetter}`,
                text: optionText
              });
            }
          }
        }
        
        // Determinar el tipo de pregunta
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
    
    console.log(`Procesadas ${questionsMap.size} preguntas únicas`);
    
    // 3. Crear módulos en la base de datos
    console.log('Creando módulos...');
    
    // Módulo 1: Bienvenida con video
    const { data: module1, error: error1 } = await supabase
      .from('course_modules')
      .insert({
        course_id: courseId,
        title: 'Bienvenida',
        type: 'content', // Agregar el tipo requerido
        order_index: 1,
        settings: {
          minScore: 70,
          mandatory: true
        }
      })
      .select()
      .single();
    
    if (error1) {
      console.error('Error creando módulo 1:', error1);
      throw error1;
    }
    
    console.log('Módulo 1 creado:', module1.id);
    
    // Item de video de bienvenida
    await supabase.from('module_items').insert({
      module_id: module1.id,
      type: 'video',
      title: 'Video de Bienvenida',
      order_index: 1,
      content: {
        url: 'https://www.youtube.com/watch?v=VIDEO_ID' // Placeholder
      }
    });
    
    // Módulo 2: Contenido teórico
    const { data: module2 } = await supabase
      .from('course_modules')
      .insert({
        course_id: courseId,
        title: 'Contenido del Curso',
        type: 'content',
        order_index: 2,
        settings: {
          minScore: 70,
          mandatory: true
        }
      })
      .select()
      .single();
    
    // Agregar slides de contenido (genially)
    await supabase.from('module_items').insert({
      module_id: module2.id,
      type: 'genially',
      title: 'Presentación Interactiva',
      order_index: 1,
      content: {
        url: 'https://view.genially.com/68a722e1f6a5ddb14ea6622c'
      }
    });
    
    // Módulo 3: Evaluación con quiz
    const { data: module3 } = await supabase
      .from('course_modules')
      .insert({
        course_id: courseId,
        title: 'Evaluación Final',
        type: 'evaluation',
        order_index: 3,
        settings: {
          minScore: 70,
          mandatory: true,
          maxAttempts: 3
        }
      })
      .select()
      .single();
    
    // Agregar quiz con las preguntas extraídas
    const questions = Array.from(questionsMap.values());
    await supabase.from('module_items').insert({
      module_id: module3.id,
      type: 'quiz',
      title: 'Cuestionario de Evaluación',
      order_index: 1,
      content: {
        questions: questions,
        timeLimit: null,
        randomize: false
      }
    });
    
    // Módulo 4: Certificado
    const { data: module4 } = await supabase
      .from('course_modules')
      .insert({
        type: 'content',
        course_id: courseId,
        title: 'Certificado',
        order_index: 4,
        settings: {
          minScore: 70,
          mandatory: true
        }
      })
      .select()
      .single();
    
    // Agregar firma del certificado
    await supabase.from('module_items').insert({
      module_id: module4.id,
      type: 'signature',
      title: 'Firma tu Certificado',
      order_index: 1,
      content: {
        instructions: 'Firma aquí para generar tu certificado'
      }
    });
    
    console.log('✅ Contenido del curso importado exitosamente!');
    console.log(`   - ${questionsMap.size} preguntas cargadas`);
    console.log(`   - 4 módulos creados`);
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    throw error;
  }
}

importCourseContent();
