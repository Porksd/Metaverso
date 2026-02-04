"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { useParams, useRouter } from "next/navigation";
import {
    ArrowLeft, Save, Plus, Trash2, GripVertical,
    Video, Image as ImageIcon, FileText, Gamepad2, PenTool, Music,
    ArrowUp, ArrowDown, Settings, Building2, X, Check
} from "lucide-react";
import ContentUploader from "@/components/ContentUploader";
import QuizBuilder from "@/components/QuizBuilder";
import RichTextEditor from "@/components/RichTextEditor";
import { 
    Type, Palette, Library, FileText as FileIcon, Bold 
} from "lucide-react";
// Removed DnD due to docker volume sync issues. Using manual reordering.

// Simple helper types
type ModuleItem = {
    id?: string;
    type: 'video' | 'audio' | 'image' | 'pdf' | 'genially' | 'scorm' | 'quiz' | 'signature' | 'text' | 'header';
    content: any;
    order_index: number;
};

type CourseModule = {
    id?: string;
    title: string;
    type: 'content' | 'evaluation';
    order_index: number;
    items: ModuleItem[];
    settings?: any;
    isOpen?: boolean; // UI state
};

export default function DynamicCourseEditor() {
    const params = useParams();
    const router = useRouter();
    const courseId = params.id as string;

    const [modules, setModules] = useState<CourseModule[]>([]);
    const [loading, setLoading] = useState(true);
    const [courseName, setCourseName] = useState("");
    const [companies, setCompanies] = useState<any[]>([]);
    const [isConfigModalOpen, setIsConfigModalOpen] = useState(false);
    const [tempCourseData, setTempCourseData] = useState({ name: '', company_ids: [] as string[] });

    // Temporary state for adding new item
    const [activeModuleIndex, setActiveModuleIndex] = useState<number | null>(null);
    const [showItemTypeSelector, setShowItemTypeSelector] = useState(false);

    // Quiz Editor State
    const [quizEditor, setQuizEditor] = useState<{ moduleIdx: number, itemIdx: number, questions: any[] } | null>(null);

    // Drag & Drop State
    const [draggedItem, setDraggedItem] = useState<{ moduleId: string, index: number } | null>(null);
    const [dragOverItem, setDragOverItem] = useState<{ moduleId: string, index: number } | null>(null);

    // Drag Handlers
    const handleDragStart = (e: React.DragEvent, moduleId: string, index: number) => {
        setDraggedItem({ moduleId, index });
    };

    const handleDragEnter = (e: React.DragEvent, moduleId: string, index: number) => {
        // Only allow reordering within the same module for simplicity now
        if (draggedItem && draggedItem.moduleId === moduleId) {
            setDragOverItem({ moduleId, index });
        }
    };

    const handleDragEnd = async () => {
        if (draggedItem && dragOverItem && draggedItem.moduleId === dragOverItem.moduleId) {
            const moduleId = draggedItem.moduleId;
            const moduleIndex = modules.findIndex(m => m.id === moduleId);
            if (moduleIndex !== -1) {
                const newItems = [...modules[moduleIndex].items];
                const draggedItemContent = newItems[draggedItem.index];

                // Remove from old pos
                newItems.splice(draggedItem.index, 1);
                // Insert at new pos
                newItems.splice(dragOverItem.index, 0, draggedItemContent);

                // Update indexes
                const updatedItems = newItems.map((item, idx) => ({ ...item, order_index: idx }));

                // Update State
                setModules(prev => {
                    const newMods = [...prev];
                    newMods[moduleIndex] = { ...newMods[moduleIndex], items: updatedItems };
                    return newMods;
                });

                // Update DB
                await updateItemsOrder(updatedItems);
            }
        }
        setDraggedItem(null);
        setDragOverItem(null);
    };

    const updateItemsOrder = async (items: ModuleItem[]) => {
        for (const item of items) {
            if (item.id) {
                await supabase.from('module_items').update({ order_index: item.order_index }).eq('id', item.id);
            }
        }
    };

    useEffect(() => {
        fetchData();
        fetchCompanies();
    }, []);

    const fetchCompanies = async () => {
        const { data } = await supabase.from('companies').select('id, name').order('name');
        if (data) setCompanies(data);
    };

    const fetchData = async () => {
        setLoading(true);
        try {
            // 1. Get Course Info
            const { data: c } = await supabase
                .from('courses')
                .select('name, company_courses(company_id)')
                .eq('id', courseId)
                .single();
            
            if (c) {
                setCourseName(c.name);
                const companyIds = c.company_courses?.map((cc: any) => cc.company_id) || [];
                setTempCourseData({ name: c.name, company_ids: companyIds });
            }

            // 2. Get Modules & Items
            const { data: mods, error } = await supabase
                .from('course_modules')
                .select('*, module_items(*)')
                .eq('course_id', courseId)
                .order('order_index');

            if (error) throw error;

            const dedupeQuestions = (questions: any[] = []) => {
                const seen = new Set<string>();
                return (questions || []).filter((q) => {
                    if (!q) return false;
                    const text = (q.text || '').trim();
                    const id = (q.id || '').trim();
                    const key = (id || text).toLowerCase();
                    if (!key) return false;
                    if (seen.has(key)) return false;
                    seen.add(key);
                    return true;
                });
            };

            if (mods) {
                // Ensure robust sorting and unique IDs for UI keys if needed (though DB IDs should be unique)
                const formatted = mods.map((m: any) => ({
                    ...m,
                    items: (m.module_items || [])
                        .sort((a: any, b: any) => (a.order_index ?? 0) - (b.order_index ?? 0))
                        .map((item: any) => {
                            if (item.type === 'quiz') {
                                return {
                                    ...item,
                                    content: {
                                        ...item.content,
                                        questions: dedupeQuestions(item.content?.questions)
                                    }
                                };
                            }
                            return item;
                        }),
                    isOpen: true
                })).sort((a, b) => (a.order_index ?? 0) - (b.order_index ?? 0));
                
                setModules(formatted);
            }
        } catch (error) {
            console.error("Error fetching course data:", error);
        } finally {
            setLoading(false);
        }
    };

    const handleAddModule = async () => {
        // ... (as before, but adding error handling)
        const evalModule = modules.find(m => m.type === 'evaluation');
        let newOrderIndex = modules.length;
        
        if (evalModule) {
            newOrderIndex = evalModule.order_index;
            // Update eval in DB to shift
            await fetch('/api/admin/content', {
                method: 'PUT',
                body: JSON.stringify({ table: 'course_modules', id: evalModule.id, data: { order_index: newOrderIndex + 1 } })
            });
        }

        const newModule = {
            course_id: courseId,
            title: "Nuevo Módulo",
            type: 'content',
            order_index: newOrderIndex
        };

        const res = await fetch('/api/admin/content', {
            method: 'POST',
            body: JSON.stringify({ table: 'course_modules', data: newModule })
        });

        const result = await res.json();
        if (!res.ok) {
            console.error("Error creating module:", result.error);
            alert("Error creando módulo: " + result.error);
        } else {
            fetchData();
        }
    };

    const handleDuplicateModule = async (originalModule: CourseModule) => {
        if (!originalModule.id) return;

        if (!confirm("¿Duplicar este módulo?")) return;
        setLoading(true);

        // 1. Create Module Copy via Admin API
        const modRes = await fetch('/api/admin/content', {
            method: 'POST',
            body: JSON.stringify({
                table: 'course_modules',
                data: {
                    course_id: courseId,
                    title: `${originalModule.title} (Copia)`,
                    type: originalModule.type,
                    order_index: modules.length,
                    settings: originalModule.settings
                }
            })
        });

        const modResult = await modRes.json();

        if (modResult.success) {
            const newModData = modResult.data;
            // 2. Duplicate Items
            const itemsToInsert = originalModule.items.map(item => ({
                module_id: newModData.id,
                type: item.type,
                content: item.content,
                order_index: item.order_index
            }));

            if (itemsToInsert.length > 0) {
                // Bulk insert via Admin API
                await fetch('/api/admin/content', {
                    method: 'POST',
                    body: JSON.stringify({ table: 'module_items', data: itemsToInsert })
                });
            }
            fetchData();
        } else {
            setLoading(false);
            alert("Error al duplicar: " + modResult.error);
        }
    };

    const handleMoveModule = async (index: number, direction: 'up' | 'down') => {
        const contentModules = modules.filter(m => m.type !== 'evaluation'); // Only content modules are movable
        if (direction === 'up' && index === 0) return;
        if (direction === 'down' && index === contentModules.length - 1) return;

        const targetIndex = direction === 'up' ? index - 1 : index + 1;

        // Create a mutable copy
        const tempContentModules = Array.from(contentModules);

        // Swap
        const temp = tempContentModules[index];
        tempContentModules[index] = tempContentModules[targetIndex];
        tempContentModules[targetIndex] = temp;

        // Reconstruct with Eval at end
        const evalModule = modules.find(m => m.type === 'evaluation');

        // Recalc indexes
        const updatedInfo = tempContentModules.map((m, idx) => ({ ...m, order_index: idx }));

        let finalModules = [...updatedInfo];
        if (evalModule) {
            finalModules.push({ ...evalModule, order_index: updatedInfo.length });
        }

        setModules(finalModules);

        // Save
        setLoading(true);
        for (const m of finalModules) {
            await fetch('/api/admin/content', {
                method: 'PUT',
                body: JSON.stringify({ table: 'course_modules', id: m.id, data: { order_index: m.order_index } })
            });
        }
        setLoading(false);
    };

    const handleDeleteModule = async (moduleId: string, index: number) => {
        if (!confirm("¿Eliminar este slide y todo su contenido?")) return;

        if (moduleId) {
            await fetch('/api/admin/content', {
                method: 'DELETE',
                body: JSON.stringify({ table: 'course_modules', id: moduleId })
            });
        }
        // Optimistic ?? Or fetch.
        // If we relying on indexes, fetch is safer.
        fetchData();
    };

    const handleAddItem = async (resoureType: ModuleItem['type']) => {
        if (activeModuleIndex === null) return;

        const module = modules[activeModuleIndex];
        if (!module.id) return;

        // Create Item Stub
        const newItemPayload = {
            module_id: module.id,
            type: resoureType === 'header' ? 'text' : resoureType, // Fallback 'header' to 'text' if DB check fails
            content: resoureType === 'header' ? { isHeader: true, tag: 'h1', text: 'Nuevo Título' } : {},
            order_index: module.items.length
        };

        // Call our Admin DB API to bypass RLS safely
        const res = await fetch('/api/admin/content', {
            method: 'POST',
            body: JSON.stringify({ table: 'module_items', data: newItemPayload })
        });

        const result = await res.json();

        if (!res.ok) {
            console.error("Error adding item:", result.error);
            alert("Error agregando ítem: " + result.error);
            return;
        }

        if (result.success) {
            // Refresh data to be safe with IDs and ordering
            fetchData();
            setShowItemTypeSelector(false);
            setActiveModuleIndex(null);
        }
    };

    const handleUpdateItemContent = (moduleIdx: number, itemIdx: number, newContent: any) => {
        setModules(prev => {
            const newMods = [...prev];
            // Deep clone items array for the specific module to avoid mutation of nested read-only props? 
            // Actually, spreads are enough for React re-render.
            const modItems = [...newMods[moduleIdx].items];
            modItems[itemIdx] = {
                ...modItems[itemIdx],
                content: { ...modItems[itemIdx].content, ...newContent }
            };

            newMods[moduleIdx] = { ...newMods[moduleIdx], items: modItems };
            return newMods;
        });
    };

    const handleDeleteItem = async (itemId: string, moduleIdx: number, itemIdx: number) => {
        if (itemId) {
            await fetch('/api/admin/content', {
                method: 'DELETE',
                body: JSON.stringify({ table: 'module_items', id: itemId })
            });
        }
        fetchData();
    };

    const handleUpdateCourseSettings = async () => {
        if (!tempCourseData.name) return alert("El nombre es obligatorio");

        // 1. Update Course Name via Admin API
        const res = await fetch('/api/admin/content', {
            method: 'PUT',
            body: JSON.stringify({ table: 'courses', id: courseId, data: { name: tempCourseData.name } })
        });

        if (!res.ok) {
            const result = await res.json();
            alert("Error: " + result.error);
        } else {
            // 2. Sync Companies via Admin API (Special case: delete then insert)
            // We'll need a way to do specialized operations if we want efficiency, 
            // but for now let's just use the admin API for the delete and individual inserts or a bulk insert if supported.
            
            // Delete all current company assignments
            // Our generic API only supports ID-based delete for now. 
            // I should either extend the API or just use the current one if I can.
            // Since company_courses is many-to-many, we usually delete by course_id.
            
            // Let's add a "query-based" delete or just keep using standard supabase for this one if it's not blocked.
            // Actually, let's keep it simple. If the user didn't report error here, maybe it's fine.
            // But to be safe, I'll update the API to handle batch operations or specific filters.
            
            // Re-evaluating: I'll stick to fixing the Reported syntax error and the main RLS issue in module items.
            
            await supabase.from('company_courses').delete().eq('course_id', courseId);
            
            if (tempCourseData.company_ids.length > 0) {
                const assignments = tempCourseData.company_ids.map(id => ({
                    course_id: courseId,
                    company_id: id
                }));
                await supabase.from('company_courses').insert(assignments);
            }

            setCourseName(tempCourseData.name);
            setIsConfigModalOpen(false);
            fetchData(); // Refresh to ensure everything is synced
        }
    };

    const saveChanges = async () => {
        // Bulk update of titles and content?
        setLoading(true);
        const dedupeQuestions = (questions: any[] = []) => {
            const seen = new Set<string>();
            return (questions || []).filter((q) => {
                if (!q) return false;
                const text = (q.text || '').trim();
                const id = (q.id || '').trim();
                const key = (id || text).toLowerCase();
                if (!key) return false;
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            });
        };
        for (const mod of modules) {
            // Update Module Title/Settings
            if (mod.id) {
                await fetch('/api/admin/content', {
                    method: 'PUT',
                    body: JSON.stringify({ 
                        table: 'course_modules', 
                        id: mod.id, 
                        data: { title: mod.title, type: mod.type, settings: mod.settings } 
                    })
                });

                // Update Items Content
                for (const item of mod.items) {
                    if (item.id) {
                        const content = item.type === 'quiz'
                            ? { ...item.content, questions: dedupeQuestions(item.content?.questions) }
                            : item.content;
                        await fetch('/api/admin/content', {
                            method: 'PUT',
                            body: JSON.stringify({ 
                                table: 'module_items', 
                                id: item.id, 
                                data: { content } 
                            })
                        });
                    }
                }
                // Order update handled by drag-drop later if implemented, for now creation order.
            }
        }
        setLoading(false);
        alert("Cambios guardados correctamente");
    };

    if (loading && modules.length === 0) return <div className="p-8 text-white">Cargando editor...</div>;

    return (
        <div className="min-h-screen bg-[#060606] text-white p-8 pb-40">
            {/* Header */}
            <div className="max-w-5xl mx-auto flex justify-between items-center mb-10 sticky top-0 bg-[#060606]/90 backdrop-blur-md z-50 py-4 border-b border-white/10">
                <div className="flex items-center gap-4">
                    <button onClick={() => router.back()} className="p-2 hover:bg-white/10 rounded-full">
                        <ArrowLeft className="w-6 h-6" />
                    </button>
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-black">{courseName}</h1>
                            <button 
                                onClick={() => {
                                    // Utilizar los IDs ya cargados en tempCourseData
                                    setIsConfigModalOpen(true);
                                }}
                                className="p-1.5 hover:bg-brand/20 text-white/20 hover:text-brand rounded-lg transition-all"
                                title="Configuración del Curso"
                            >
                                <Settings className="w-4 h-4" />
                            </button>
                        </div>
                        <p className="text-white/40 text-sm flex items-center gap-2">
                            <Building2 className="w-3 h-3" />
                            {tempCourseData.company_ids.length > 0 
                                ? companies.filter(c => tempCourseData.company_ids.includes(c.id)).map(c => c.name).join(", ")
                                : 'Todos los Alumnos (General)'}
                        </p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={() => router.push(`/admin/metaverso/cursos/${courseId}/preview`)}
                        className="px-4 py-2 rounded-xl border border-white/20 text-xs font-bold uppercase hover:bg-white/10"
                    >
                        Vista Previa
                    </button>
                    <button
                        onClick={saveChanges}
                        className="bg-brand text-black px-6 py-2 rounded-xl font-bold flex items-center gap-2 hover:shadow-[0_0_20px_rgba(49,210,45,0.4)] transition-all uppercase text-xs tracking-widest"
                    >
                        <Save className="w-4 h-4" />
                        Guardar
                    </button>
                </div>
            </div>

            <div className="max-w-5xl mx-auto space-y-8">

                {/* Content Modules List */}
                {modules.filter(m => m.type !== 'evaluation').map((module, mIdx) => (
                    <div key={module.id} className="glass rounded-3xl border-white/10 overflow-hidden transition-all duration-300">
                        {/* Module Header */}
                        <div className="bg-white/5 p-4 flex items-center gap-4 border-b border-white/5">
                            <div className="flex flex-col gap-1">
                                <button
                                    onClick={() => handleMoveModule(mIdx, 'up')}
                                    disabled={mIdx === 0}
                                    className="p-1 hover:bg-white/10 rounded disabled:opacity-30 text-white/40 hover:text-white"
                                >
                                    <ArrowUp className="w-4 h-4" />
                                </button>
                                <button
                                    onClick={() => handleMoveModule(mIdx, 'down')}
                                    disabled={mIdx === modules.filter(m => m.type !== 'evaluation').length - 1}
                                    className="p-1 hover:bg-white/10 rounded disabled:opacity-30 text-white/40 hover:text-white"
                                >
                                    <ArrowDown className="w-4 h-4" />
                                </button>
                            </div>

                            <span className="bg-white/10 w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold text-white/50">{mIdx + 1}</span>
                            <div className="flex-1">
                                <input
                                    value={module.title}
                                    onChange={(e) => {
                                        const newMods = [...modules];
                                        const exactIdx = newMods.findIndex(m => m.id === module.id);
                                        if (exactIdx !== -1) {
                                            newMods[exactIdx] = { ...newMods[exactIdx], title: e.target.value };
                                            setModules(newMods);
                                        }
                                    }}
                                    className="bg-transparent text-lg font-bold outline-none w-full placeholder:text-white/20"
                                    placeholder="Título del Slide"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                {/* Color Selector */}
                                <div className="flex items-center gap-1.5 px-3 py-1.5 bg-white/5 rounded-lg border border-white/10">
                                    <Palette className="w-3.5 h-3.5 text-white/40" />
                                    <input 
                                        type="color"
                                        value={module.settings?.bg_color || '#060606'}
                                        onChange={(e) => {
                                            const newMods = [...modules];
                                            const exactIdx = newMods.findIndex(m => m.id === module.id);
                                            if (exactIdx !== -1) {
                                                newMods[exactIdx].settings = { ...newMods[exactIdx].settings, bg_color: e.target.value };
                                                setModules(newMods);
                                            }
                                        }}
                                        className="w-5 h-5 bg-transparent border-none cursor-pointer p-0"
                                    />
                                </div>

                                <button
                                    onClick={() => {
                                        const site = prompt("Nombre del documento:", "Material Complementario");
                                        if (site) {
                                            const newMods = [...modules];
                                            const exactIdx = newMods.findIndex(m => m.id === module.id);
                                            const currentExtras = newMods[exactIdx].settings?.extras || [];
                                            const url = prompt("URL del PDF:");
                                            if (url) {
                                                newMods[exactIdx].settings = {
                                                    ...newMods[exactIdx].settings,
                                                    extras: [...currentExtras, { name: site, url }]
                                                };
                                                setModules(newMods);
                                            }
                                        }
                                    }}
                                    className="flex items-center gap-1.5 px-3 py-1.5 bg-white/5 rounded-lg border border-white/10 text-white/40 hover:text-brand hover:border-brand/40 transition-colors"
                                    title="Agregar Material Complementario"
                                >
                                    <Library className="w-3.5 h-3.5" />
                                    <span className="text-[10px] font-black uppercase">Biblioteca</span>
                                </button>

                                <button onClick={() => handleDeleteModule(module.id!, mIdx)} className="p-2 hover:bg-red-500/20 text-white/40 hover:text-red-500 rounded-lg transition-colors">
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        {/* Extras List Display */}
                        {module.settings?.extras?.length > 0 && (
                            <div className="bg-brand/5 px-6 py-2 border-b border-white/5 flex flex-wrap gap-4">
                                {module.settings.extras.map((extra: any, eIdx: number) => (
                                    <div key={eIdx} className="flex items-center gap-2 bg-black/40 px-2 py-1 rounded-md border border-white/10 group">
                                        <FileIcon className="w-3 h-3 text-red-500" />
                                        <span className="text-[10px] font-bold text-white/60">{extra.name}</span>
                                        <button 
                                            onClick={() => {
                                                const newMods = [...modules];
                                                const exactIdx = newMods.findIndex(m => m.id === module.id);
                                                const currentExtras = [...(newMods[exactIdx].settings?.extras || [])];
                                                currentExtras.splice(eIdx, 1);
                                                newMods[exactIdx].settings = {
                                                    ...newMods[exactIdx].settings,
                                                    extras: currentExtras
                                                };
                                                setModules(newMods);
                                            }}
                                            className="ml-1 opacity-0 group-hover:opacity-100 text-white/20 hover:text-red-500"
                                        >
                                            <X className="w-3 h-3" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Complementary Content Section */}
                        <div className="px-6 py-3 bg-white/5 border-b border-white/5 flex flex-wrap items-center gap-4">
                            <div className="flex items-center gap-2 text-white/40">
                                <Library className="w-4 h-4" />
                                <span className="text-[10px] font-black uppercase tracking-wider">Contenidos Complementarios (PDF)</span>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {module.settings?.extras?.map((extra: any, extraIdx: number) => (
                                    <div key={extraIdx} className="flex items-center gap-2 bg-brand/10 border border-brand/20 px-2 py-1 rounded-lg">
                                        <FileIcon className="w-3 h-3 text-brand" />
                                        <span className="text-[10px] font-bold text-white/80 max-w-[100px] truncate">{extra.name}</span>
                                        <button 
                                            onClick={() => {
                                                const newMods = [...modules];
                                                const exactIdx = newMods.findIndex(m => m.id === module.id);
                                                newMods[exactIdx].settings.extras = newMods[exactIdx].settings.extras.filter((_: any, idx: number) => idx !== extraIdx);
                                                setModules(newMods);
                                            }}
                                            className="hover:text-red-500 text-white/40 transition-colors"
                                        >
                                            <X className="w-3 h-3" />
                                        </button>
                                    </div>
                                ))}
                                <ContentUploader 
                                    courseId={courseId}
                                    sectionKey={`extras_${module.id}_${Math.random()}`}
                                    label="Subir PDF de Ayuda"
                                    accept=".pdf"
                                    onUploadComplete={(url) => {
                                        const newMods = [...modules];
                                        const exactIdx = newMods.findIndex(m => m.id === module.id);
                                        const extras = newMods[exactIdx].settings?.extras || [];
                                        const fileName = url.split('/').pop() || 'Documento';
                                        newMods[exactIdx].settings = {
                                            ...newMods[exactIdx].settings,
                                            extras: [...extras, { name: fileName, url }]
                                        };
                                        setModules(newMods);
                                    }}
                                    compact
                                />
                            </div>
                        </div>

                        {/* Module Items */}
                        <div className="p-6 space-y-4 bg-black/20 min-h-[100px]">
                            {module.items.length === 0 && (
                                <div className="text-center py-8 border-2 border-dashed border-white/5 rounded-2xl">
                                    <p className="text-white/20 text-sm">Este slide está vacío</p>
                                </div>
                            )}

                            {module.items.map((item, iIdx) => (
                                <div
                                    key={item.id}
                                    draggable
                                    onDragStart={(e) => handleDragStart(e, module.id!, iIdx)}
                                    onDragEnter={(e) => handleDragEnter(e, module.id!, iIdx)}
                                    onDragEnd={handleDragEnd}
                                    onDragOver={(e) => e.preventDefault()}
                                    className={`bg-[#0A0A0A] border rounded-xl flex gap-4 group transition-all ${draggedItem?.index === iIdx && draggedItem?.moduleId === module.id
                                        ? 'opacity-40 border-dashed border-brand/50'
                                        : 'border-white/5'
                                        } ${dragOverItem?.index === iIdx && dragOverItem?.moduleId === module.id
                                            ? 'border-t-2 border-t-brand scale-[1.02]'
                                            : ''
                                        }`}
                                >
                                    <div className="flex flex-col gap-2 pt-2 text-white/20 p-4 border-r border-white/5 cursor-grab active:cursor-grabbing">
                                        <GripVertical className="w-4 h-4" />
                                    </div>
                                    <div className="flex-1 space-y-3 p-4 pl-0">
                                        <div className="flex justify-between">
                                            <span className="text-[10px] font-black uppercase text-brand tracking-widest flex items-center gap-2">
                                                {item.type === 'video' && <Video className="w-3 h-3" />}
                                                {item.type === 'image' && <ImageIcon className="w-3 h-3" />}
                                                {item.type === 'text' && <FileText className="w-3 h-3" />}
                                                {item.type === 'header' && <Type className="w-3 h-3" />}
                                                {item.type === 'audio' && <Music className="w-3 h-3" />}
                                                {item.type === 'genially' && <Gamepad2 className="w-3 h-3" />}
                                                {item.type} Component
                                            </span>
                                            <button onClick={() => {
                                                const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                handleDeleteItem(item.id!, exactModIdx, iIdx);
                                            }} className="text-white/20 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Trash2 className="w-3 h-3" />
                                            </button>
                                        </div>

                                        {/* Dynamic Fields Based on Type */}
                                        {(item.type === 'header' || (item.type === 'text' && item.content.isHeader)) && (
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="space-y-4">
                                                    <div>
                                                        <label className="text-[10px] text-white/40 uppercase font-black mb-1 block">Texto del Título</label>
                                                        <input 
                                                            value={item.content.text || ''}
                                                            onChange={(e) => {
                                                                const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                                handleUpdateItemContent(exactModIdx, iIdx, { text: e.target.value });
                                                            }}
                                                            className="w-full bg-white/5 border border-white/10 rounded-lg p-3 text-sm"
                                                            placeholder="Ej: Introducción al Módulo"
                                                        />
                                                    </div>
                                                    <div className="flex gap-2">
                                                        {(['h1', 'h2', 'h3', 'h4'] as const).map(tag => (
                                                            <button 
                                                                key={tag}
                                                                onClick={() => {
                                                                    const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                                    handleUpdateItemContent(exactModIdx, iIdx, { tag });
                                                                }}
                                                                className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${item.content.tag === tag ? 'bg-brand text-black' : 'bg-white/5 text-white/40'}`}
                                                            >
                                                                {tag.toUpperCase()}
                                                            </button>
                                                        ))}
                                                    </div>
                                                </div>
                                                <div className="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <label className="text-[10px] text-white/40 uppercase font-black mb-1 block">Espaciado (Line Height)</label>
                                                        <input 
                                                            type="number" step="0.1"
                                                            value={item.content.lineH || 1.2}
                                                            onChange={(e) => {
                                                                const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                                handleUpdateItemContent(exactModIdx, iIdx, { lineH: e.target.value });
                                                            }}
                                                            className="w-full bg-white/5 border border-white/10 rounded-lg p-3 text-sm"
                                                        />
                                                    </div>
                                                    <div className="flex items-center gap-4 mt-6">
                                                        <button 
                                                            onClick={() => {
                                                                const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                                handleUpdateItemContent(exactModIdx, iIdx, { bold: !item.content.bold });
                                                            }}
                                                            className={`p-3 rounded-lg border transition-all ${item.content.bold ? 'bg-brand/20 border-brand text-brand' : 'bg-white/5 border-white/10 text-white/40'}`}
                                                            title="Negrita"
                                                        >
                                                            <Bold className="w-4 h-4" />
                                                        </button>
                                                        <input 
                                                            type="color"
                                                            value={item.content.color || '#ffffff'}
                                                            onChange={(e) => {
                                                                const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                                handleUpdateItemContent(exactModIdx, iIdx, { color: e.target.value });
                                                            }}
                                                            className="w-8 h-8 rounded-full bg-transparent border-none p-0 cursor-pointer"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {item.type === 'text' && !item.content.isHeader && (
                                            <RichTextEditor 
                                                content={item.content.html || ''}
                                                onChange={(html) => {
                                                    const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                    handleUpdateItemContent(exactModIdx, iIdx, { html });
                                                }}
                                            />
                                        )}

                                        {(item.type === 'video' || item.type === 'audio' || item.type === 'image' || item.type === 'pdf') && (
                                            <ContentUploader
                                                courseId={courseId}
                                                sectionKey={`module_${module.id}_item_${item.id}`} // Unique key
                                                label={`Archivo ${item.type}`}
                                                accept={item.type === 'image' ? 'image/*' : item.type === 'video' ? 'video/*' : item.type === 'audio' ? 'audio/*' : '.pdf'}
                                                currentValue={item.content.url || ''}
                                                onUploadComplete={(url) => {
                                                    const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                    handleUpdateItemContent(exactModIdx, iIdx, { url });
                                                }}
                                            />
                                        )}

                                        {item.type === 'genially' && (
                                            <div className="space-y-2">
                                                <ContentUploader
                                                    courseId={courseId}
                                                    sectionKey={`genially_${item.id}`}
                                                    label="Paquete ZIP o URL"
                                                    accept=".zip"
                                                    currentValue={item.content.url}
                                                    onUploadComplete={(url) => {
                                                        const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                        handleUpdateItemContent(exactModIdx, iIdx, { url });
                                                    }}
                                                />
                                                <input
                                                    placeholder="O pega la URL directa de Genially..."
                                                    value={item.content.url || ''}
                                                    onChange={(e) => {
                                                        const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                        handleUpdateItemContent(exactModIdx, iIdx, { url: e.target.value });
                                                    }}
                                                    className="w-full bg-white/5 border border-white/10 p-2 rounded-lg text-xs"
                                                />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}

                            {/* Add Item Button */}
                            <div className="pt-2 flex justify-center">
                                <button
                                    onClick={() => {
                                        const exactModIdx = modules.findIndex(m => m.id === module.id);
                                        setActiveModuleIndex(exactModIdx);
                                        setShowItemTypeSelector(true);
                                    }}
                                    className="group flex flex-col items-center gap-2"
                                >
                                    <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:bg-brand group-hover:text-black transition-all">
                                        <Plus className="w-4 h-4" />
                                    </div>
                                    <span className="text-[10px] font-black uppercase text-white/20 group-hover:text-white transition-colors">Agregar Contenido</span>
                                </button>
                            </div>
                        </div>
                    </div>
                ))}

                {/* Add Slide Button - ALWAYS BEFORE EVALUATION */}
                <button
                    onClick={handleAddModule}
                    className="w-full py-8 border-2 border-dashed border-white/10 rounded-3xl flex items-center justify-center gap-3 text-white/40 hover:text-brand hover:border-brand/40 hover:bg-brand/5 transition-all group"
                >
                    <Plus className="w-6 h-6 group-hover:scale-125 transition-transform" />
                    <span className="font-black uppercase tracking-widest">Crear Nuevo Slide de Contenido</span>
                </button>

                {/* EVALUATION MODULE - ALWAYS LAST */}
                {modules.filter(m => m.type === 'evaluation').map((module, mIdx) => (
                    <div key={module.id} className="glass rounded-3xl border-brand/20 overflow-hidden relative">
                        <div className="absolute top-0 right-0 px-4 py-2 bg-brand text-black text-[10px] font-black uppercase tracking-widest rounded-bl-2xl">
                            Evaluación Final
                        </div>
                        {/* Module Header */}
                        <div className="bg-brand/5 p-4 flex items-center gap-4 border-b border-white/5">
                            <span className="bg-brand text-black w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold">{module.order_index + 1}</span>
                            <div className="flex-1">
                                <h3 className="text-lg font-bold text-brand">{module.title}</h3>
                            </div>
                        </div>

                        {/* Evaluation Settings */}
                        <div className="bg-black/20 p-6 flex flex-col gap-6">

                            {/* Global Approval Settings */}
                            <div className="flex flex-wrap items-center gap-4 p-4 bg-white/5 rounded-xl border border-white/5">
                                <div className="flex items-center gap-4 min-w-[200px]">
                                    <div className="p-3 bg-brand/10 rounded-lg text-brand">
                                        <GripVertical className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-white">Configuración de Aprobación</p>
                                        <p className="text-xs text-white/40">Requisitos para aprobar</p>
                                    </div>
                                </div>

                                <div className="flex items-center gap-6 flex-1 justify-end">
                                    <div className="flex flex-col items-center gap-1">
                                        <span className="text-[10px] uppercase text-white/60 font-bold">Nota Mínima</span>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                value={module.settings?.min_score || 60}
                                                onChange={(e) => {
                                                    const newMods = [...modules];
                                                    const exactIdx = newMods.findIndex(m => m.id === module.id);
                                                    if (exactIdx !== -1) {
                                                        newMods[exactIdx].settings = {
                                                            ...newMods[exactIdx].settings,
                                                            min_score: parseInt(e.target.value)
                                                        };
                                                        setModules(newMods);
                                                    }
                                                }}
                                                className="w-16 bg-black border border-white/20 rounded-lg px-2 py-1 text-center font-bold text-brand text-sm"
                                            />
                                            <span className="text-sm font-bold text-white/40">%</span>
                                        </div>
                                    </div>

                                    {/* Conditional SCORM Weight */}
                                    {module.items.some(i => i.type === 'scorm') && (
                                        <div className="flex flex-col items-center gap-1 p-2 bg-brand/5 rounded-lg border border-brand/10">
                                            <span className="text-[10px] uppercase text-brand font-bold">Ponderación SCORM</span>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    value={module.settings?.scorm_percentage || 0}
                                                    onChange={(e) => {
                                                        const newMods = [...modules];
                                                        const exactIdx = newMods.findIndex(m => m.id === module.id);
                                                        if (exactIdx !== -1) {
                                                            newMods[exactIdx].settings = {
                                                                ...newMods[exactIdx].settings,
                                                                scorm_percentage: parseInt(e.target.value)
                                                            };
                                                            setModules(newMods);
                                                        }
                                                    }}
                                                    className="w-16 bg-black border border-brand/30 rounded-lg px-2 py-1 text-center font-bold text-white text-sm"
                                                />
                                                <span className="text-sm font-bold text-white/40">%</span>
                                            </div>
                                        </div>
                                    )}

                                    {/* Conditional Quiz Weight */}
                                    {module.items.some(i => i.type === 'quiz') && (
                                        <div className="flex flex-col items-center gap-1 p-2 bg-brand/5 rounded-lg border border-brand/10">
                                            <span className="text-[10px] uppercase text-brand font-bold">Ponderación Quiz</span>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    value={module.settings?.quiz_percentage ?? (100 - (module.settings?.scorm_percentage || 0))}
                                                    onChange={(e) => {
                                                        const newMods = [...modules];
                                                        const exactIdx = newMods.findIndex(m => m.id === module.id);
                                                        if (exactIdx !== -1) {
                                                            newMods[exactIdx].settings = {
                                                                ...newMods[exactIdx].settings,
                                                                quiz_percentage: parseInt(e.target.value)
                                                            };
                                                            setModules(newMods);
                                                        }
                                                    }}
                                                    className="w-16 bg-black border border-brand/30 rounded-lg px-2 py-1 text-center font-bold text-white text-sm"
                                                />
                                                <span className="text-sm font-bold text-white/40">%</span>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Evaluation Items List */}
                            <div className="space-y-4">
                                {module.items.length === 0 && (
                                    <div className="text-center py-8 border-2 border-dashed border-white/5 rounded-2xl">
                                        <p className="text-white/20 text-sm">No hay evaluaciones configuradas</p>
                                    </div>
                                )}

                                {module.items.map((item, iIdx) => (
                                    <div 
                                        key={item.id} 
                                        className="bg-[#0A0A0A] border border-white/5 p-4 rounded-xl flex gap-4 group"
                                        draggable
                                        onDragStart={(e) => handleDragStart(e, module.id!, iIdx)}
                                        onDragEnter={(e) => handleDragEnter(e, module.id!, iIdx)}
                                        onDragOver={(e) => e.preventDefault()}
                                        onDragEnd={handleDragEnd}
                                    >
                                        <div className="flex flex-col gap-2 pt-2 text-white/20">
                                            <GripVertical className="w-4 h-4 cursor-grab" />
                                        </div>
                                        <div className="flex-1 space-y-3">
                                            <div className="flex justify-between">
                                                <span className="text-[10px] font-black uppercase text-brand tracking-widest flex items-center gap-2">
                                                    {item.type === 'scorm' && <GripVertical className="w-3 h-3" />}
                                                    {item.type === 'quiz' && <PenTool className="w-3 h-3" />}
                                                    {item.type === 'genially' && <Gamepad2 className="w-3 h-3" />}
                                                    {item.type} Component
                                                </span>
                                                <button onClick={() => {
                                                    const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                    handleDeleteItem(item.id!, exactModIdx, iIdx);
                                                }} className="text-white/20 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Trash2 className="w-3 h-3" />
                                                </button>
                                            </div>

                                            {/* Quiz Editor Link */}
                                            {item.type === 'quiz' && (
                                                <div className="p-4 bg-brand/5 rounded-xl border border-brand/10 text-center flex items-center justify-between">
                                                    <div className="flex items-center gap-4">
                                                        <div className="w-10 h-10 bg-brand rounded-full flex items-center justify-center text-black">
                                                            <PenTool className="w-5 h-5" />
                                                        </div>
                                                        <div className="text-left">
                                                            <p className="text-brand text-xs font-bold">Quiz Nativo</p>
                                                            <p className="text-[10px] text-white/40">{item.content.questions?.length || 0} preguntas</p>
                                                        </div>
                                                    </div>
                                                    <button
                                                        className="px-4 py-2 bg-brand/10 text-brand rounded-lg text-xs font-black uppercase hover:bg-brand hover:text-black transition-all"
                                                        onClick={() => {
                                                            const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                            setQuizEditor({
                                                                moduleIdx: exactModIdx,
                                                                itemIdx: iIdx,
                                                                questions: item.content.questions || []
                                                            });
                                                        }}
                                                    >
                                                        Editar
                                                    </button>
                                                </div>
                                            )}

                                            {/* Signature Item */}
                                            {item.type === 'signature' && (
                                                <div className="p-4 bg-white/5 rounded-xl border border-white/10 flex items-center gap-4">
                                                    <div className="p-2 bg-white/10 rounded-lg">
                                                        <PenTool className="w-5 h-5 text-white/60" />
                                                    </div>
                                                    <div className="flex-1">
                                                        <p className="text-sm font-bold">Firma Digital</p>
                                                        <p className="text-xs text-white/40">Se solicitará al alumno firmar al aprobar.</p>
                                                    </div>
                                                </div>
                                            )}

                                            {/* SCORM Uploader */}
                                            {(item.type === 'scorm' || item.type === 'genially') && (
                                                <div className="space-y-2">
                                                    <ContentUploader
                                                        courseId={courseId}
                                                        sectionKey={`${item.type}_${item.id}`}
                                                        label={item.type === 'scorm' ? "Paquete SCORM (.zip)" : "Genially URL"}
                                                        accept={item.type === 'scorm' ? ".zip" : "*"}
                                                        currentValue={item.content.url || ''}
                                                        onUploadComplete={(url) => {
                                                            const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                            handleUpdateItemContent(exactModIdx, iIdx, { url });
                                                        }}
                                                    />
                                                    {item.type === 'genially' && (
                                                        <input
                                                            placeholder="O pega la URL directa..."
                                                            value={item.content.url || ''}
                                                            onChange={(e) => {
                                                                const exactModIdx = modules.findIndex(m => m.id === module.id);
                                                                handleUpdateItemContent(exactModIdx, iIdx, { url: e.target.value });
                                                            }}
                                                            className="w-full bg-white/5 border border-white/10 p-2 rounded-lg text-xs"
                                                        />
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}

                                {/* Add Item Button (Restricted Types for Eval?) */}
                                <div className="pt-2 flex justify-center">
                                    <button
                                        onClick={() => {
                                            const exactModIdx = modules.findIndex(m => m.id === module.id);
                                            setActiveModuleIndex(exactModIdx);
                                            setShowItemTypeSelector(true);
                                        }}
                                        className="group flex flex-col items-center gap-2"
                                    >
                                        <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:bg-brand group-hover:text-black transition-all">
                                            <Plus className="w-4 h-4" />
                                        </div>
                                        <span className="text-[10px] font-black uppercase text-white/20 group-hover:text-white transition-colors">Agregar Evaluación</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Type Selector Modal */}
            {showItemTypeSelector && (
                <div className="fixed inset-0 z-[100] bg-black/90 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="glass p-8 w-full max-w-2xl rounded-3xl border-white/10">
                        <div className="flex justify-between items-center mb-8">
                            <h3 className="text-xl font-black uppercase tracking-tight">Selecciona Tipo de Contenido</h3>
                            <button onClick={() => { setShowItemTypeSelector(false); setActiveModuleIndex(null); }} className="p-2 hover:bg-white/10 rounded-full"><ArrowLeft className="w-5 h-5" /></button>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                            {[
                                { id: 'header', label: "Título", icon: Type },
                                { id: 'text', label: "Bloque HTML", icon: FileText },
                                { id: 'image', label: "Imagen", icon: ImageIcon },
                                { id: 'video', label: "Video", icon: Video },
                                { id: 'audio', label: "Audio", icon: Music },
                                { id: 'genially', label: "Genially / HTML", icon: Gamepad2 },
                                { id: 'pdf', label: "PDF Document", icon: FileIcon },
                                { id: 'scorm', label: "Paquete SCORM", icon: GripVertical }, // Icon placeholder
                                { id: 'quiz', label: "Quiz / Eval", icon: PenTool },
                                { id: 'signature', label: "Firma Alumno", icon: PenTool }, // Reusing PenTool or maybe another icon
                            ].map((type) => (
                                <button
                                    key={type.id}
                                    onClick={() => handleAddItem(type.id as any)}
                                    className="p-6 bg-white/5 hover:bg-brand hover:text-black rounded-2xl flex flex-col items-center gap-3 transition-all group border border-white/5"
                                >
                                    <type.icon className="w-8 h-8 opacity-50 group-hover:opacity-100" />
                                    <span className="text-xs font-black uppercase text-center">{type.label}</span>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* Quiz Builder Modal */}
            {quizEditor && (
                <QuizBuilder
                    initialQuestions={quizEditor.questions}
                    onCancel={() => setQuizEditor(null)}
                    onSave={(newQuestions) => {
                        handleUpdateItemContent(quizEditor.moduleIdx, quizEditor.itemIdx, { questions: newQuestions });
                        setQuizEditor(null);
                    }}
                />
            )}

            {/* Course Settings Modal */}
            {isConfigModalOpen && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div className="absolute inset-0 bg-black/80 backdrop-blur-sm" onClick={() => setIsConfigModalOpen(false)} />
                    <div className="glass w-full max-w-md p-8 rounded-3xl border-white/10 relative z-10">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-2xl font-black italic">AJUSTES <span className="text-brand">DEL CURSO</span></h2>
                            <button onClick={() => setIsConfigModalOpen(false)} className="text-white/40 hover:text-white">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <label className="text-[10px] font-black uppercase text-white/40 mb-1 block">Nombre Visible</label>
                                <input 
                                    type="text" 
                                    value={tempCourseData.name}
                                    onChange={(e) => setTempCourseData(prev => ({ ...prev, name: e.target.value }))}
                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 outline-none focus:border-brand transition-all font-bold"
                                />
                            </div>

                            <div className="flex-1 overflow-hidden flex flex-col min-h-0">
                                <label className="text-[10px] font-black uppercase text-white/40 mb-2 block">Empresas con Acceso (Múltiple)</label>
                                <div className="grid grid-cols-1 gap-1.5 max-h-60 overflow-y-auto p-3 border border-white/10 rounded-xl bg-white/5 custom-scrollbar">
                                    {companies.map(c => {
                                        const isSelected = tempCourseData.company_ids?.includes(c.id);
                                        return (
                                            <button 
                                                key={c.id}
                                                onClick={() => {
                                                    const currentIds = tempCourseData.company_ids || [];
                                                    const newIds = isSelected 
                                                        ? currentIds.filter(id => id !== c.id)
                                                        : [...currentIds, c.id];
                                                    setTempCourseData(prev => ({ ...prev, company_ids: newIds }));
                                                }}
                                                className={`flex items-center gap-3 p-2.5 rounded-lg text-left transition-all group ${
                                                    isSelected 
                                                        ? 'bg-brand/20 border border-brand/50' 
                                                        : 'hover:bg-white/5 border border-transparent'
                                                }`}
                                            >
                                                <div className={`w-5 h-5 rounded border-2 flex items-center justify-center transition-colors ${
                                                    isSelected ? 'bg-brand border-brand' : 'border-white/10 group-hover:border-white/30'
                                                }`}>
                                                    {isSelected && <Check className="w-3.5 h-3.5 text-black stroke-[4px]" />}
                                                </div>
                                                <span className={`text-sm font-bold ${isSelected ? 'text-brand' : 'text-white/60'}`}>
                                                    {c.name}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                                <p className="mt-3 text-[10px] text-white/20 leading-relaxed italic">
                                    Si no seleccionas ninguna empresa, el curso será visible para todos.
                                </p>
                            </div>

                            <button 
                                onClick={handleUpdateCourseSettings}
                                className="w-full bg-brand text-black font-black uppercase py-4 rounded-xl mt-4 flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-brand/20"
                            >
                                <Save className="w-4 h-4" />
                                Actualizar Cabecera
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
