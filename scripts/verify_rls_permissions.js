/**
 * Script de Verificación de Permisos RLS
 * 
 * Este script verifica:
 * 1. Políticas RLS en todas las tablas principales
 * 2. Capacidad de insertar registros (students, enrollments)
 * 3. Capacidad de eliminar registros
 * 4. Capacidad de actualizar contenido
 * 5. Estructura de la tabla students (campos nuevos)
 */

const { createClient } = require('@supabase/supabase-js');
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '..', '.env.local') });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseServiceKey) {
    console.error('❌ Falta configuración de Supabase en .env.local');
    console.error('   Se necesita SUPABASE_SERVICE_ROLE_KEY');
    process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function main() {
    console.log('🔍 VERIFICACIÓN DE PERMISOS RLS Y ESQUEMA\n');
    console.log('='.repeat(60));

    // 1. Verificar estructura de la tabla students
    console.log('\n📋 1. VERIFICANDO ESTRUCTURA DE TABLA STUDENTS');
    console.log('-'.repeat(60));
    
    try {
        const { data: columns, error } = await supabase
            .from('students')
            .select('*')
            .limit(1);

        if (error) {
            console.error('❌ Error al consultar tabla students:', error.message);
        } else {
            if (columns && columns.length > 0) {
                const fields = Object.keys(columns[0]);
                console.log('✅ Campos encontrados en students:');
                const requiredFields = ['language', 'email', 'gender', 'age', 'company_name', 'passport', 'digital_signature_url'];
                requiredFields.forEach(field => {
                    if (fields.includes(field)) {
                        console.log(`   ✓ ${field}`);
                    } else {
                        console.log(`   ✗ ${field} - ¡FALTA!`);
                    }
                });
            } else {
                console.log('⚠️  Tabla students está vacía, no se pueden verificar campos');
            }
        }
    } catch (err) {
        console.error('❌ Error:', err.message);
    }

    // 2. Probar INSERT en students
    console.log('\n📝 2. PROBANDO INSERCIÓN EN STUDENTS');
    console.log('-'.repeat(60));
    
    const testStudent = {
        rut: 'TEST-' + Date.now(),
        first_name: 'Test',
        last_name: 'Usuario',
        email: `test${Date.now()}@ejemplo.com`,
        gender: 'Masculino',
        age: 25,
        company_name: 'Test Company',
        language: 'es',
        client_id: 'c7fd2d19-c6a8-4ea0-b9fa-11082eaacac7' // Sacyr
    };

    try {
        const { data: inserted, error: insertError } = await supabase
            .from('students')
            .insert(testStudent)
            .select()
            .single();

        if (insertError) {
            console.error('❌ Error al insertar estudiante de prueba:', insertError.message);
            console.error('   Código:', insertError.code);
            if (insertError.message.includes('policy')) {
                console.error('   ⚠️  PROBLEMA DE RLS: Las políticas están bloqueando inserciones');
            }
        } else {
            console.log('✅ Inserción exitosa! ID:', inserted.id);
            
            // 3. Probar UPDATE
            console.log('\n✏️  3. PROBANDO ACTUALIZACIÓN EN STUDENTS');
            console.log('-'.repeat(60));
            
            const { error: updateError } = await supabase
                .from('students')
                .update({ age: 26 })
                .eq('id', inserted.id);

            if (updateError) {
                console.error('❌ Error al actualizar:', updateError.message);
            } else {
                console.log('✅ Actualización exitosa!');
            }

            // 4. Probar DELETE
            console.log('\n🗑️  4. PROBANDO ELIMINACIÓN EN STUDENTS');
            console.log('-'.repeat(60));
            
            const { error: deleteError } = await supabase
                .from('students')
                .delete()
                .eq('id', inserted.id);

            if (deleteError) {
                console.error('❌ Error al eliminar:', deleteError.message);
                if (deleteError.message.includes('policy')) {
                    console.error('   ⚠️  PROBLEMA DE RLS: Las políticas están bloqueando eliminaciones');
                }
            } else {
                console.log('✅ Eliminación exitosa!');
            }
        }
    } catch (err) {
        console.error('❌ Error en prueba:', err.message);
    }

    // 5. Verificar políticas RLS activas
    console.log('\n🔐 5. VERIFICANDO POLÍTICAS RLS ACTIVAS');
    console.log('-'.repeat(60));
    
    const tables = [
        'students',
        'enrollments',
        'course_progress',
        'activity_logs',
        'course_content',
        'course_modules',
        'module_items',
        'companies',
        'companies_list'
    ];

    for (const table of tables) {
        try {
            // Intentar un SELECT simple
            const { error } = await supabase
                .from(table)
                .select('*')
                .limit(1);

            if (error) {
                if (error.message.includes('policy')) {
                    console.log(`❌ ${table.padEnd(20)} - Políticas bloqueando SELECT`);
                } else {
                    console.log(`⚠️  ${table.padEnd(20)} - Error: ${error.message.substring(0, 50)}`);
                }
            } else {
                console.log(`✅ ${table.padEnd(20)} - SELECT permitido`);
            }
        } catch (err) {
            console.log(`❌ ${table.padEnd(20)} - Error: ${err.message}`);
        }
    }

    // 6. Probar subida de contenido (course_content)
    console.log('\n📤 6. PROBANDO INSERCIÓN DE CONTENIDO');
    console.log('-'.repeat(60));
    
    const testContent = {
        course_id: '34a730c6-358f-4a42-a6c3-9d74a0e8457e',
        key: 'test_upload_' + Date.now(),
        value: '/test/path.mp4'
    };

    try {
        const { data: contentInserted, error: contentError } = await supabase
            .from('course_content')
            .upsert(testContent, { onConflict: 'course_id, key' })
            .select()
            .single();

        if (contentError) {
            console.error('❌ Error al insertar contenido:', contentError.message);
            if (contentError.message.includes('policy')) {
                console.error('   ⚠️  PROBLEMA DE RLS: Las políticas están bloqueando subida de contenido');
            }
        } else {
            console.log('✅ Inserción de contenido exitosa!');
            
            // Limpiar
            await supabase
                .from('course_content')
                .delete()
                .eq('course_id', testContent.course_id)
                .eq('key', testContent.key);
        }
    } catch (err) {
        console.error('❌ Error:', err.message);
    }

    // Resumen final
    console.log('\n' + '='.repeat(60));
    console.log('📊 RESUMEN DE VERIFICACIÓN');
    console.log('='.repeat(60));
    console.log('\n✅ = Funcionando correctamente');
    console.log('❌ = Requiere atención');
    console.log('⚠️  = Advertencia\n');
    console.log('Si hay problemas de RLS, ejecuta la migración:');
    console.log('   016_review_and_optimize_rls.sql\n');
}

main().catch(console.error);
