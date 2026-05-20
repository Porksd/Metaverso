const XLSX = require('xlsx');
const fs = require('fs');
const path = require('path');

// Leer el archivo Excel
const excelPath = 'J:\\Empres\\MetaversOtec\\Desarrollos\\Cursos\\Curso Trabajo en Altura\\SACYR  TRABAJO EN ALTURA.xlsx';
const workbook = XLSX.readFile(excelPath);

console.log('Hojas disponibles:', workbook.SheetNames);

// Extraer datos de cada hoja
const courseData = {};

workbook.SheetNames.forEach(sheetName => {
    const sheet = workbook.Sheets[sheetName];
    const data = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    courseData[sheetName] = data;
    
    console.log(`\n=== ${sheetName} ===`);
    console.log('Primeras 10 filas:');
    data.slice(0, 10).forEach((row, idx) => {
        console.log(`Fila ${idx}:`, row);
    });
});

// Guardar como JSON para análisis
fs.writeFileSync(
    path.join(__dirname, 'course_content_extracted.json'),
    JSON.stringify(courseData, null, 2),
    'utf8'
);

console.log('\n✓ Datos extraídos y guardados en course_content_extracted.json');
