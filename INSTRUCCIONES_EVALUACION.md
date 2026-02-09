# 🎓 SISTEMA DE EVALUACIÓN - INSTRUCCIONES DE IMPLEMENTACIÓN

## 📋 PROBLEMA IDENTIFICADO

El editor dinámico de cursos (`/admin/metaverso/cursos/[id]/contenido`) **NO TIENE** funcionalidad para:
- Crear módulos de evaluación
- Configurar Quiz con preguntas
- Configurar SCORM 
- Establecer ponderaciones (Quiz 80% + SCORM 20%)
- Habilitar generación de certificados

## ✅ FUNCIONALIDAD EXISTENTE

El sistema **SÍ TIENE** implementado:
- `QuizEngine` component - Motor de evaluación con puntajes
- `ScormPlayer` component - Reproductor SCORM con tracking
- `CertificateGenerator` component - Generación de certificados PDF
- Base de datos lista (enrollments, activity_logs, course_progress)
- Curso "Trabajo en Altura" funcionando completamente

## 🎯 LO QUE SE NECESITA AGREGAR

### 1. **Botón "Crear Módulo de Evaluación"** 
Ubicación: `src/app/admin/metaverso/cursos/[id]/contenido/page.tsx`

Agregar después del botón "Agregar Slide de Contenido" (línea ~570):

```tsx
{/* Solo mostrar si NO existe módulo de evaluación */}
{!modules.some(m => m.type === 'evaluation') && (
    <button
        onClick={handleCreateEvaluationModule}
        className="w-full p-6 bg-gradient-to-r from-purple-500/10 to-brand/10 border-2 border-dashed border-purple-500/30 rounded-3xl hover:border-brand hover:scale-[1.02] transition-all group"
    >
        <div className="flex flex-col items-center gap-3">
            <div className="w-16 h-16 rounded-full bg-purple-500/20 flex items-center justify-center group-hover:bg-brand/30 transition-all">
                <PenTool className="w-8 h-8 text-purple-400 group-hover:text-brand" />
            </div>
            <div className="text-center">
                <h3 className="text-lg font-black uppercase text-purple-400 group-hover:text-brand transition-colors">
                    Crear Módulo de Evaluación Final
                </h3>
                <p className="text-sm text-white/40 mt-1">
                    Quiz + SCORM + Certificado Digital
                </p>
            </div>
        </div>
    </button>
)}
```

### 2. **Función para crear módulo de evaluación**

```tsx
const handleCreateEvaluationModule = async () => {
    if (!confirm('¿Crear módulo de evaluación final? Incluirá Quiz y SCORM con ponderaciones configurables.')) return;
    
    setLoading(true);
    
    // 1. Crear módulo tipo "evaluation"
    const evalModulePayload = {
        course_id: courseId,
        title: "Evaluación Final",
        type: 'evaluation',
        order_index: modules.length,
        settings: {
            min_score: 90,  // Puntaje mínimo para aprobar
            quiz_percentage: 80,  // Ponderación Quiz
            scorm_percentage: 20, // Ponderación SCORM
            requires_signature: true,
            max_attempts: 3
        }
    };
    
    const moduleRes = await fetch('/api/admin/content', {
        method: 'POST',
        body: JSON.stringify({ table: 'course_modules', data: evalModulePayload })
    });
    
    const moduleResult = await moduleRes.json();
    
    if (!moduleRes.ok || !moduleResult.success) {
        alert('Error creando módulo de evaluación: ' + (moduleResult.error || 'Error desconocido'));
        setLoading(false);
        return;
    }
    
    const newModuleId = moduleResult.data.id;
    
    // 2. Crear items del módulo (Quiz + SCORM + Signature)
    const items = [
        {
            module_id: newModuleId,
            type: 'scorm',
            content: {
                package_path: '/uploads/courses/DEMO/scorm/package.zip',
                entry_point: 'index.html',
                description: 'Actividad práctica SCORM'
            },
            order_index: 0
        },
        {
            module_id: newModuleId,
            type: 'quiz',
            content: {
                questions: [
                    {
                        id: '1',
                        question: '¿Cuál es la respuesta correcta?',
                        options: [
                            { id: 'A', text: 'Opción A', isCorrect: true },
                            { id: 'B', text: 'Opción B', isCorrect: false },
                            { id: 'C', text: 'Opción C', isCorrect: false },
                            { id: 'D', text: 'Opción D', isCorrect: false }
                        ]
                    }
                ]
            },
            order_index: 1
        },
        {
            module_id: newModuleId,
            type: 'signature',
            content: {
                title: 'Firma Digital',
                description: 'Firma para validar tu participación'
            },
            order_index: 2
        }
    ];
    
    // Insertar items en bulk
    await fetch('/api/admin/content', {
        method: 'POST',
        body: JSON.stringify({ table: 'module_items', data: items })
    });
    
    setLoading(false);
    fetchData(); // Recargar datos
    alert('✅ Módulo de evaluación creado exitosamente');
};
```

### 3. **UI para editar configuración de evaluación**

Cuando se renderiza un módulo `type === 'evaluation'`, mostrar:

```tsx
{module.type === 'evaluation' && (
    <div className="p-6 bg-purple-500/5 border-2 border-purple-500/20 rounded-2xl space-y-4">
        <h3 className="text-lg font-black text-purple-400 mb-4 flex items-center gap-2">
            <PenTool className="w-5 h-5" />
            CONFIGURACIÓN DE EVALUACIÓN
        </h3>
        
        <div className="grid grid-cols-3 gap-4">
            <div>
                <label className="text-[10px] font-black uppercase text-white/40 mb-1 block">
                    Puntaje Mínimo (%)
                </label>
                <input 
                    type="number" 
                    min="0" 
                    max="100"
                    value={module.settings?.min_score || 60}
                    onChange={(e) => {
                        const newMods = [...modules];
                        const idx = newMods.findIndex(m => m.id === module.id);
                        newMods[idx].settings = {
                            ...newMods[idx].settings,
                            min_score: parseInt(e.target.value)
                        };
                        setModules(newMods);
                    }}
                    className="w-full bg-white/5 border border-white/10 rounded-lg p-3 text-center text-2xl font-bold text-brand"
                />
            </div>
            
            <div>
                <label className="text-[10px] font-black uppercase text-white/40 mb-1 block">
                    Peso Quiz (%)
                </label>
                <input 
                    type="number" 
                    min="0" 
                    max="100"
                    value={module.settings?.quiz_percentage || 80}
                    onChange={(e) => {
                        const newMods = [...modules];
                        const idx = newMods.findIndex(m => m.id === module.id);
                        const quizVal = parseInt(e.target.value);
                        newMods[idx].settings = {
                            ...newMods[idx].settings,
                            quiz_percentage: quizVal,
                            scorm_percentage: 100 - quizVal
                        };
                        setModules(newMods);
                    }}
                    className="w-full bg-white/5 border border-white/10 rounded-lg p-3 text-center text-2xl font-bold text-blue-400"
                />
            </div>
            
            <div>
                <label className="text-[10px] font-black uppercase text-white/40 mb-1 block">
                    Peso SCORM (%)
                </label>
                <input 
                    type="number" 
                    disabled
                    value={module.settings?.scorm_percentage || 20}
                    className="w-full bg-white/5 border border-white/10 rounded-lg p-3 text-center text-2xl font-bold text-orange-400 opacity-50"
                />
                <p className="text-[8px] text-white/30 mt-1 text-center">Auto-calculado</p>
            </div>
        </div>
        
        <div className="flex items-center justify-between p-4 bg-black/40 rounded-xl">
            <span className="text-sm font-bold text-white/60">
                ¿Requiere firma digital del alumno?
            </span>
            <button 
                onClick={() => {
                    const newMods = [...modules];
                    const idx = newMods.findIndex(m => m.id === module.id);
                    newMods[idx].settings = {
                        ...newMods[idx].settings,
                        requires_signature: !newMods[idx].settings?.requires_signature
                    };
                    setModules(newMods);
                }}
                className={`px-4 py-2 rounded-lg font-bold text-xs transition-all ${
                    module.settings?.requires_signature 
                        ? 'bg-brand text-black' 
                        : 'bg-white/5 text-white/40'
                }`}
            >
                {module.settings?.requires_signature ? 'SÍ' : 'NO'}
            </button>
        </div>
    </div>
)}
```

### 4. **Renderizar items de evaluación** 

Para los items del módulo de evaluación, necesitas manejar tipos especiales:

```tsx
{item.type === 'quiz' && (
    <div className="p-6 bg-blue-500/5 border border-blue-500/20 rounded-xl">
        <div className="flex justify-between items-center mb-4">
            <h4 className="font-bold text-blue-400">Preguntas del Quiz</h4>
            <button 
                onClick={() => {
                    const moduleIdx = modules.findIndex(m => m.id === module.id);
                    setQuizEditor({
                        moduleIdx,
                        itemIdx: iIdx,
                        questions: item.content.questions || []
                    });
                }}
                className="px-4 py-2 bg-blue-500/20 text-blue-400 rounded-lg text-xs font-bold hover:bg-blue-500/30"
            >
                Editar Preguntas ({item.content.questions?.length || 0})
            </button>
        </div>
        {item.content.questions?.length > 0 && (
            <div className="space-y-2">
                {item.content.questions.slice(0, 3).map((q: any, idx: number) => (
                    <div key={idx} className="text-xs text-white/60 bg-black/40 p-2 rounded">
                        {idx + 1}. {q.question.substring(0, 80)}...
                    </div>
                ))}
                {item.content.questions.length > 3 && (
                    <p className="text-xs text-white/40 text-center mt-2">
                        + {item.content.questions.length - 3} preguntas más
                    </p>
                )}
            </div>
        )}
    </div>
)}

{item.type === 'scorm' && (
    <div className="p-6 bg-orange-500/5 border border-orange-500/20 rounded-xl">
        <h4 className="font-bold text-orange-400 mb-3">Paquete SCORM</h4>
        <ContentUploader 
            courseId={courseId}
            sectionKey={`scorm_${module.id}`}
            label="Subir paquete .zip SCORM"
            accept=".zip"
            onUploadComplete={(url) => {
                const moduleIdx = modules.findIndex(m => m.id === module.id);
                handleUpdateItemContent(moduleIdx, iIdx, {
                    package_path: url,
                    entry_point: 'index.html'
                });
            }}
        />
        {item.content.package_path && (
            <div className="mt-3 p-3 bg-black/40 rounded-lg">
                <p className="text-xs text-white/60">📦 {item.content.package_path}</p>
            </div>
        )}
    </div>
)}

{item.type === 'signature' && (
    <div className="p-6 bg-green-500/5 border border-green-500/20 rounded-xl">
        <h4 className="font-bold text-green-400">✍️ Firma Digital del Alumno</h4>
        <p className="text-xs text-white/40 mt-2">
            El estudiante deberá firmar digitalmente antes de generar el certificado
        </p>
    </div>
)}
```

## 📊 FLUJO COMPLETO DEL SISTEMA

1. **Admin crea curso** → Agrega slides de contenido
2. **Admin crea módulo de evaluación** → Configura Quiz + SCORM + Puntajes
3. **Estudiante toma curso** → Ve slides, completa SCORM, hace Quiz
4. **Sistema calcula nota** → (Quiz 80% + SCORM 20%)
5. **Si aprueba** (≥90%) → Puede firmar y descargar certificado
6. **Certificado se genera** → PDF con firma digital + logos empresa

## 🔧 ARCHIVOS A MODIFICAR

1. ✅ `src/app/admin/metaverso/cursos/[id]/contenido/page.tsx` - Agregar UI evaluación
2. ✅ Componentes existentes ya funcionan (QuizEngine, ScormPlayer, CertificateGenerator)
3. ✅ Base de datos ya tiene las tablas necesarias
4. ✅ CoursePlayer ya maneja la lógica de evaluación

## ⚠️ IMPORTANTE

- El módulo de evaluación SIEMPRE debe ser el último (order_index más alto)
- Solo puede haber UN módulo de evaluación por curso
- Las ponderaciones deben sumar 100% (Quiz + SCORM = 100%)
- El puntaje mínimo recomendado es 90% (como Trabajo en Altura)

## 📝 CHECKLIST DE IMPLEMENTACIÓN

- [ ] Agregar botón "Crear Módulo de Evaluación"
- [ ] Implementar función `handleCreateEvaluationModule`
- [ ] Agregar UI de configuración de puntajes
- [ ] Renderizar items especiales (quiz, scorm, signature)
- [ ] Integrar QuizBuilder para editar preguntas
- [ ] Integrar ContentUploader para SCORM
- [ ] Probar creación de curso completo
- [ ] Verificar que CoursePlayer muestre evaluación correctamente
- [ ] Confirmar generación de certificados

---

**Siguiente paso:** Implementar estos cambios en el archivo `page.tsx` del editor de cursos.
