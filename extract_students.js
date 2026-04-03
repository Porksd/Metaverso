const XLSX = require('xlsx');
const path = require('path');

const excelPath = 'j:\\Empres\\MetaversOtec\\Desarrollos\\Cursos\\Cursos Sacyr\\UNION CURSOS SACYR.xlsx';

try {
    const workbook = XLSX.readFile(excelPath);
    const worksheet = workbook.Sheets['Respuestas'] || workbook.Sheets[workbook.SheetNames[0]];
    const data = XLSX.utils.sheet_to_json(worksheet, { defval: "" });

    const clientId = 'c7fd2d19-c6a8-4ea0-b9fa-11082eaacac7'; // Sacyr ID
    const studentsMap = new Map();

    data.forEach(row => {
        const rut = String(row.RUT || "").trim().toUpperCase();
        if (!rut || rut === "RUT") return;

        if (!studentsMap.has(rut)) {
            studentsMap.set(rut, {
                client_id: clientId,
                rut: rut,
                first_name: String(row.Nombre || "").trim(),
                last_name: String(row.Apellido || "").trim(),
                email: String(row.Correo || "").trim().toLowerCase(),
                gender: String(row.Género || "").trim(),
                age: parseInt(row.Edad) || null,
                position: String(row.Cargo || "").trim()
            });
        }
    });

    const uniqueStudents = Array.from(studentsMap.values());
    console.log(`Total unique students found: ${uniqueStudents.length}`);

    // Generar SQL simple para los primeros 100 como prueba
    const sql = uniqueStudents.map(s => {
        return `INSERT INTO public.students (client_id, rut, first_name, last_name, email, gender, age, position) VALUES ('${s.client_id}', '${s.rut}', '${s.first_name.replace(/'/g, "''")}', '${s.last_name.replace(/'/g, "''")}', ${s.email ? `'${s.email}'` : 'NULL'}, '${s.gender}', ${s.age || 'NULL'}, '${s.position.replace(/'/g, "''")}') ON CONFLICT (client_id, rut) DO NOTHING;`;
    }).join('\n');

    require('fs').writeFileSync('import_students.sql', sql);
    console.log('SQL generated in import_students.sql');
} catch (error) {
    console.error('Error reading Excel:', error.message);
    process.exit(1);
}
