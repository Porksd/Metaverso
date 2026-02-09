// =============================================================================
// CÓDIGO PARA AGREGAR AL EDITOR DE CURSOS
// Ubicación: src/app/admin/metaverso/cursos/[id]/contenido/page.tsx
// =============================================================================

// 1. AGREGAR ESTA FUNCIÓN DESPUÉS DE handleAddModule (línea ~230)
// =============================================================================
const handleCreateEvaluationModule = async () => {
    if (!confirm('¿Crear módulo de evaluación final?\n\nIncluirá:\n✓ Quiz con preguntas\n✓ Actividad SCORM\n✓ Firma digital del alumno\n✓ Generación de certificado')) return;
    
    setLoading(true);
    
    try {
        // 1. Crear módulo tipo "evaluation"
        const evalModulePayload = {
            course_id: courseId,
            title: "Evaluación Final",
            type: 'evaluation',
            order_index: modules.length,
            settings: {
                min_score: 90,  // Puntaje mínimo para aprobar (90% como Trabajo en Altura)
                quiz_percentage: 80,  // Ponderación Quiz (80%)
                scorm_percentage: 20, // Ponderación SCORM (20%)
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
            throw new Error(moduleResult.error || 'Error creando módulo de evaluación');
        }
        
        const newModuleId = moduleResult.data.id;
        
        // 2. Crear items del módulo en el orden correcto
        const items = [
            // Item 1: SCORM (se hace primero)
            {
                module_id: newModuleId,
                type: 'scorm',
                content: {
                    package_path: '',
                    entry_point: 'index.html',
                    description: 'Actividad práctica interactiva'
                },
                order_index: 0
            },
            // Item 2: Quiz (se hace después del SCORM)
            {
                module_id: newModuleId,
                type: 'quiz',
                content: {
                    questions: [
                        {
                            id: '1',
                            question: 'Pregunta de ejemplo - Edita las preguntas usando el botón "Editar Preguntas"',
                            options: [
                                { id: 'A', text: 'Opción A (correcta)', isCorrect: true },
                                { id: 'B', text: 'Opción B', isCorrect: false },
                                { id: 'C', text: 'Opción C', isCorrect: false },
                                { id: 'D', text: 'Opción D', isCorrect: false }
                            ]
                        }
                    ]
                },
                order_index: 1
            },
            // Item 3: Firma digital (último paso antes del certificado)
            {
                module_id: newModuleId,
                type: 'signature',
                content: {
                    title: 'Firma Digital',
                    description: 'Firma digitalmente para validar tu participación y generar el certificado'
                },
                order_index: 2
            }
        ];
        
        // Insertar items en bulk
        const itemsRes = await fetch('/api/admin/content', {
            method: 'POST',
            body: JSON.stringify({ table: 'module_items', data: items })
        });
        
        if (!itemsRes.ok) {
            throw new Error('Error creando items del módulo de evaluación');
        }
        
        alert('✅ Módulo de evaluación creado exitosamente!\n\nAhora puedes:\n1. Subir el paquete SCORM\n2. Editar las preguntas del quiz\n3. Configurar los puntajes');
        
        fetchData(); // Recargar datos
        
    } catch (error: any) {
        console.error('Error creating evaluation module:', error);
        alert('Error al crear módulo de evaluación: ' + error.message);
    } finally {
        setLoading(false);
    }
};


// 2. AGREGAR ESTE COMPONENTE DE UI DESPUÉS DEL ÚLTIMO MÓDULO (línea ~570)
// =============================================================================
/* Botón para crear evaluación */
{!modules.some(m => m.type === 'evaluation') && modules.length > 0 && (
    <div className="mb-8">
        <button
            onClick={handleCreateEvaluationModule}
            disabled={loading}
            className="w-full p-8 bg-gradient-to-br from-purple-500/10 via-brand/5 to-orange-500/10 border-2 border-dashed border-purple-500/30 rounded-3xl hover:border-brand hover:scale-[1.01] active:scale-[0.99] transition-all group disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <div className="flex flex-col items-center gap-4">
                <div className="w-20 h-20 rounded-full bg-gradient-to-br from-purple-500/20 to-brand/20 flex items-center justify-center group-hover:scale-110 transition-transform shadow-xl shadow-purple-500/20">
                    <PenTool className="w-10 h-10 text-purple-400 group-hover:text-brand transition-colors" />
                </div>
                <div className="text-center">
                    <h3 className="text-xl font-black uppercase text-purple-400 group-hover:text-brand transition-colors tracking-wider">
                        + Crear Módulo de Evaluación Final
                    </h3>
                    <p className="text-sm text-white/60 mt-2 max-w-xl mx-auto">
                        Agrega un módulo de evaluación con <span className="text-brand font-bold">Quiz</span> (80%) + 
                        <span className="text-orange-400 font-bold"> SCORM</span> (20%) + 
                        <span className="text-green-400 font-bold"> Firma Digital</span> + 
                        <span className="text-purple-400 font-bold"> Certificado</span>
                    </p>
                    <div className="flex items-center justify-center gap-4 mt-4 text-xs text-white/40">
                        <span className="flex items-center gap-1">
                            <span className="w-2 h-2 rounded-full bg-blue-400"></span>
                            Puntajes automáticos
                        </span>
                        <span className="flex items-center gap-1">
                            <span className="w-2 h-2 rounded-full bg-green-400"></span>
                            Certificado PDF
                        </span>
                        <span className="flex items-center gap-1">
                            <span className="w-2 h-2 rounded-full bg-orange-400"></span>
                            3 intentos máximo
                        </span>
                    </div>
                </div>
            </div>
        </button>
    </div>
)}


// 3. REEMPLAZAR EL RENDERIZADO DEL HEADER DEL MÓDULO cuando type === 'evaluation'
// Buscar la sección donde se renderiza module.isOpen y el título (línea ~570)
// Agregar este código DENTRO del renderizado del módulo:
// =============================================================================
{module.type === 'evaluation' && (
    <>
        {/* Configuración de puntajes */}
        <div className="px-6 py-4 bg-gradient-to-r from-purple-500/10 to-brand/10 border-t border-white/5">
            <div className="flex items-center justify-between mb-4">
                <h4 className="text-sm font-black uppercase text-purple-400 flex items-center gap-2">
                    <Settings className="w-4 h-4" />
                    Configuración de Evaluación
                </h4>
                <button 
                    onClick={async () => {
                        if (!module.id) return;
                        await fetch('/api/admin/content', {
                            method: 'PUT',
                            body: JSON.stringify({ 
                                table: 'course_modules', 
                                id: module.id, 
                                data: { settings: module.settings } 
                            })
                        });
                        alert('✅ Configuración guardada');
                    }}
                    className="px-4 py-2 bg-brand/20 text-brand rounded-lg text-xs font-bold hover:bg-brand hover:text-black transition-all"
                >
                    Guardar Config
                </button>
            </div>
            
            <div className="grid grid-cols-4 gap-4">
                {/* Puntaje Mínimo */}
                <div className="bg-black/40 p-4 rounded-xl border border-purple-500/20">
                    <label className="text-[9px] font-black uppercase text-white/40 mb-2 block text-center">
                        Puntaje Mínimo
                    </label>
                    <input 
                        type="number" 
                        min="0" 
                        max="100"
                        value={module.settings?.min_score || 90}
                        onChange={(e) => {
                            const newMods = [...modules];
                            const idx = newMods.findIndex(m => m.id === module.id);
                            newMods[idx].settings = {
                                ...newMods[idx].settings,
                                min_score: parseInt(e.target.value)
                            };
                            setModules(newMods);
                        }}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-center text-3xl font-black text-purple-400 focus:border-purple-400 focus:outline-none"
                    />
                    <p className="text-[8px] text-white/30 mt-1 text-center">% para aprobar</p>
                </div>
                
                {/* Peso Quiz */}
                <div className="bg-black/40 p-4 rounded-xl border border-blue-500/20">
                    <label className="text-[9px] font-black uppercase text-white/40 mb-2 block text-center">
                        Peso Quiz
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
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-center text-3xl font-black text-blue-400 focus:border-blue-400 focus:outline-none"
                    />
                    <p className="text-[8px] text-white/30 mt-1 text-center">% del total</p>
                </div>
                
                {/* Peso SCORM */}
                <div className="bg-black/40 p-4 rounded-xl border border-orange-500/20">
                    <label className="text-[9px] font-black uppercase text-white/40 mb-2 block text-center">
                        Peso SCORM
                    </label>
                    <input 
                        type="number" 
                        disabled
                        value={module.settings?.scorm_percentage || 20}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-center text-3xl font-black text-orange-400 opacity-50 cursor-not-allowed"
                    />
                    <p className="text-[8px] text-white/30 mt-1 text-center">auto-calculado</p>
                </div>
                
                {/* Requiere Firma */}
                <div className="bg-black/40 p-4 rounded-xl border border-green-500/20 flex flex-col items-center justify-center">
                    <label className="text-[9px] font-black uppercase text-white/40 mb-3 block text-center">
                        Firma Digital
                    </label>
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
                        className={`px-6 py-3 rounded-lg font-black text-sm transition-all ${
                            module.settings?.requires_signature 
                                ? 'bg-green-500 text-black shadow-lg shadow-green-500/30' 
                                : 'bg-white/5 text-white/40'
                        }`}
                    >
                        {module.settings?.requires_signature ? '✓ SÍ' : 'NO'}
                    </button>
                </div>
            </div>
            
            {/* Fórmula visual */}
            <div className="mt-4 p-3 bg-black/40 rounded-lg border border-white/5">
                <p className="text-xs text-white/60 text-center font-mono">
                    <span className="text-blue-400 font-bold">Quiz ({module.settings?.quiz_percentage || 80}%)</span>
                    {' + '}
                    <span className="text-orange-400 font-bold">SCORM ({module.settings?.scorm_percentage || 20}%)</span>
                    {' ≥ '}
                    <span className="text-purple-400 font-bold">{module.settings?.min_score || 90}%</span>
                    {' → '}
                    <span className="text-green-400 font-bold">Certificado ✓</span>
                </p>
            </div>
        </div>
    </>
)}


// 4. AGREGAR RENDERIZADO ESPECIAL PARA ITEMS DE EVALUACIÓN
// Buscar donde se renderizan los items (línea ~720)
// AGREGAR estos casos especiales ANTES del código existente:
// =============================================================================
{/* Renderizado especial para Quiz */}
{item.type === 'quiz' && (
    <div className="p-6 bg-gradient-to-br from-blue-500/10 to-blue-500/5 border-2 border-blue-500/20 rounded-xl">
        <div className="flex justify-between items-start mb-4">
            <div>
                <h4 className="font-black text-blue-400 text-lg mb-1 flex items-center gap-2">
                    <PenTool className="w-5 h-5" />
                    PREGUNTAS DEL QUIZ
                </h4>
                <p className="text-xs text-white/40">
                    Ponderación: <span className="text-blue-400 font-bold">{modules.find(m => m.id === module.id)?.settings?.quiz_percentage || 80}%</span> del puntaje final
                </p>
            </div>
            <button 
                onClick={() => {
                    const moduleIdx = modules.findIndex(m => m.id === module.id);
                    setQuizEditor({
                        moduleIdx,
                        itemIdx: iIdx,
                        questions: item.content.questions || []
                    });
                }}
                className="px-5 py-3 bg-blue-500/20 text-blue-400 rounded-xl text-sm font-black hover:bg-blue-500 hover:text-black transition-all flex items-center gap-2 shadow-lg shadow-blue-500/20"
            >
                <PenTool className="w-4 h-4" />
                Editar Preguntas ({item.content.questions?.length || 0})
            </button>
        </div>
        
        {item.content.questions?.length > 0 ? (
            <div className="space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
                {item.content.questions.map((q: any, idx: number) => (
                    <div key={idx} className="bg-black/40 p-3 rounded-lg border border-white/5 hover:border-blue-500/30 transition-colors">
                        <p className="text-xs font-bold text-white/80 mb-2">
                            {idx + 1}. {q.question}
                        </p>
                        <div className="grid grid-cols-2 gap-2">
                            {q.options?.slice(0, 4).map((opt: any, optIdx: number) => (
                                <div key={optIdx} className={`text-[10px] p-2 rounded ${opt.isCorrect ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-white/5 text-white/40'}`}>
                                    {opt.id}. {opt.text.substring(0, 30)}...
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        ) : (
            <div className="p-8 border-2 border-dashed border-blue-500/20 rounded-xl text-center">
                <p className="text-white/40 text-sm">
                    No hay preguntas configuradas. Haz clic en "Editar Preguntas" para agregarlas.
                </p>
            </div>
        )}
    </div>
)}

{/* Renderizado especial para SCORM */}
{item.type === 'scorm' && (
    <div className="p-6 bg-gradient-to-br from-orange-500/10 to-orange-500/5 border-2 border-orange-500/20 rounded-xl">
        <div className="flex justify-between items-start mb-4">
            <div>
                <h4 className="font-black text-orange-400 text-lg mb-1 flex items-center gap-2">
                    <Gamepad2 className="w-5 h-5" />
                    ACTIVIDAD SCORM
                </h4>
                <p className="text-xs text-white/40">
                    Ponderación: <span className="text-orange-400 font-bold">{modules.find(m => m.id === module.id)?.settings?.scorm_percentage || 20}%</span> del puntaje final
                </p>
            </div>
        </div>
        
        <div className="space-y-3">
            <ContentUploader 
                courseId={courseId}
                sectionKey={`scorm_eval_${module.id}`}
                label="📦 Subir Paquete SCORM (.zip)"
                accept=".zip"
                onUploadComplete={(url) => {
                    const moduleIdx = modules.findIndex(m => m.id === module.id);
                    handleUpdateItemContent(moduleIdx, iIdx, {
                        package_path: url,
                        entry_point: 'index.html'
                    });
                    alert('✅ Paquete SCORM subido correctamente');
                }}
            />
            
            {item.content.package_path ? (
                <div className="p-4 bg-black/40 rounded-lg border border-orange-500/20">
                    <p className="text-xs text-white/60 mb-1 font-bold">📦 Paquete Actual:</p>
                    <p className="text-xs text-orange-400 font-mono break-all">{item.content.package_path}</p>
                    <p className="text-[10px] text-white/30 mt-2">
                        Punto de entrada: <code className="text-white/60">{item.content.entry_point || 'index.html'}</code>
                    </p>
                </div>
            ) : (
                <div className="p-6 border-2 border-dashed border-orange-500/20 rounded-xl text-center">
                    <p className="text-white/40 text-sm">
                        Sube un paquete SCORM para activar la actividad interactiva
                    </p>
                </div>
            )}
        </div>
    </div>
)}

{/* Renderizado especial para Firma Digital */}
{item.type === 'signature' && (
    <div className="p-6 bg-gradient-to-br from-green-500/10 to-green-500/5 border-2 border-green-500/20 rounded-xl">
        <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center flex-shrink-0">
                <PenTool className="w-8 h-8 text-green-400" />
            </div>
            <div className="flex-1">
                <h4 className="font-black text-green-400 text-lg mb-1">
                    ✍️ FIRMA DIGITAL DEL ALUMNO
                </h4>
                <p className="text-xs text-white/60">
                    {item.content.description || 'El estudiante deberá firmar digitalmente después de aprobar para generar su certificado'}
                </p>
            </div>
            <div className="text-right">
                <span className="inline-block px-4 py-2 bg-green-500/20 text-green-400 rounded-lg text-xs font-bold border border-green-500/30">
                    REQUERIDO
                </span>
            </div>
        </div>
        
        <div className="mt-4 p-3 bg-black/40 rounded-lg border border-white/5">
            <p className="text-[10px] text-white/40 leading-relaxed">
                💡 <span className="font-bold">Nota:</span> La firma digital se captura usando canvas HTML5 y se almacena como imagen en la base de datos. 
                Se incluye automáticamente en el certificado PDF junto con las firmas de la empresa.
            </p>
        </div>
    </div>
)}
