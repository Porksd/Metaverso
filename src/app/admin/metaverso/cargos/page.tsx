"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { Plus, Trash2, Edit2, Search, Briefcase, Info, X, ChevronRight, Check, ShieldAlert, Building2 } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import RichTextEditor from "@/components/RichTextEditor";

interface JobPosition {
    id: string;
    code: string;
    name: string;
    name_ht: string;
    description: string;
    description_ht: string;
    active: boolean;
    company_id: string | null;
    companies?: {
        name: string;
    };
}

export default function JobPositionsAdmin() {
    const router = useRouter();
    const [positions, setPositions] = useState<JobPosition[]>([]);
    const [loading, setLoading] = useState(true);
    const [isAuthorized, setIsAuthorized] = useState<boolean | null>(null);
    const [searchTerm, setSearchTerm] = useState("");
    const [isEditing, setIsEditing] = useState<JobPosition | null>(null);
    const [showForm, setShowForm] = useState(false);
    const [descRich, setDescRich] = useState("");
    const [descRichHT, setDescRichHT] = useState("");

    useEffect(() => {
        checkAuth();
    }, []);

    const checkAuth = async () => {
        const { data: { session } } = await supabase.auth.getSession();
        
        if (!session) {
            console.log("No session found, redirecting to login...");
            router.push("/admin/metaverso/login?returnUrl=/admin/metaverso/cargos");
            return;
        }

        // Check for metaverso admin email or developer email
        const allowedEmails = ['admin@metaversotec.com', 'porksde@gmail.com', 'apacheco@lobus.cl'];
        if (!allowedEmails.includes(session.user.email || '')) {
            console.log("Unauthorized email:", session.user.email);
            // Optional: redirect to a generic unauthorized page or login
            router.push("/admin/metaverso/login");
            return;
        }
        
        setIsAuthorized(true);
        loadPositions();
    };

    const loadPositions = async () => {
        setLoading(true);
        const { data, error } = await supabase
            .from('company_roles')
            .select('*, companies(name)')
            .order('name');

        if (data) setPositions(data as any);
        setLoading(false);
    };

    const handleSave = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);
        const positionData = {
            name: formData.get('name') as string,
            name_ht: formData.get('name_ht') as string,
            code: (formData.get('code') as string) || null,
            description: descRich || null,
            description_ht: descRichHT || null,
            active: formData.get('active') === 'on'
        };

        if (isEditing) {
            await supabase.from('company_roles').update(positionData).eq('id', isEditing.id);
        } else {
            // New global roles have company_id = null by default in our insert logic
            await supabase.from('company_roles').insert(positionData);
        }

        setShowForm(false);
        setIsEditing(null);
        setDescRich("");
        setDescRichHT("");
        loadPositions();
    };

    const handleDelete = async (id: string) => {
        if (!confirm("¿Seguro que deseas eliminar este cargo?")) return;
        await supabase.from('company_roles').delete().eq('id', id);
        loadPositions();
    };

    const filteredPositions = positions.filter(p =>
        (p.name?.toLowerCase() || "").includes(searchTerm.toLowerCase()) ||
        (p.code?.toLowerCase() || "").includes(searchTerm.toLowerCase())
    );

    if (isAuthorized === null) return (
        <div className="min-h-screen bg-black flex items-center justify-center">
            <div className="text-brand font-black animate-pulse">VERIFICANDO ACCESO...</div>
        </div>
    );

    if (isAuthorized === false) return (
        <div className="min-h-screen bg-black flex flex-col items-center justify-center p-8 text-center space-y-6">
            <ShieldAlert className="w-20 h-20 text-red-500" />
            <h1 className="text-4xl font-black italic tracking-tighter uppercase">Acceso Denegado</h1>
            <p className="text-white/40 max-w-md">No tienes permisos para acceder a este panel de administración.</p>
            <button onClick={() => router.push("/admin/metaverso/login")} className="bg-white text-black px-8 py-4 rounded-2xl font-black uppercase text-xs">Volver al Login</button>
        </div>
    );

    return (
        <div className="min-h-screen bg-[#060606] text-white p-4 md:p-8">
            <div className="max-w-6xl mx-auto space-y-8">

                {/* Header */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-black uppercase tracking-tight flex items-center gap-3">
                            <Briefcase className="w-8 h-8 text-brand" />
                            Gestión de Cargos
                        </h1>
                        <p className="text-white/40 font-medium">Administra las posiciones y perfiles para el registro de alumnos</p>
                    </div>
                    <button
                        onClick={() => { setIsEditing(null); setShowForm(true); }}
                        className="bg-brand text-black px-6 py-3 rounded-2xl font-black uppercase text-xs tracking-widest flex items-center gap-2 hover:scale-105 transition-all shadow-[0_0_20px_rgba(49,210,45,0.3)]"
                    >
                        <Plus className="w-4 h-4" /> Nuevo Cargo
                    </button>
                </div>

                {/* Search and Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div className="md:col-span-3 relative">
                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/20" />
                        <input
                            type="text"
                            placeholder="Buscar por nombre o código..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-4 focus:border-brand/50 outline-none transition-all font-medium"
                        />
                    </div>
                    <div className="bg-white/5 border border-white/10 rounded-2xl p-4 flex flex-col justify-center items-center">
                        <span className="text-xs font-black text-white/20 uppercase">Total Cargos</span>
                        <span className="text-2xl font-black text-brand">{positions.length}</span>
                    </div>
                </div>

                {/* Table/Grid */}
                <div className="bg-white/5 border border-white/10 rounded-3xl overflow-hidden">
                    {loading ? (
                        <div className="p-20 text-center text-white/20 font-black uppercase tracking-widest animate-pulse">Cargando cargos...</div>
                    ) : filteredPositions.length === 0 ? (
                        <div className="p-20 text-center text-white/20">No se encontraron cargos</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-white/5 border-b border-white/10">
                                        <th className="p-6 text-xs uppercase font-black text-white/40">Código</th>
                                        <th className="p-6 text-xs uppercase font-black text-white/40">Nombre (ES / HT)</th>
                                        <th className="p-6 text-xs uppercase font-black text-white/40">Empresa</th>
                                        <th className="p-6 text-xs uppercase font-black text-white/40">Descripción (ES / HT)</th>
                                        <th className="p-6 text-xs uppercase font-black text-white/40 text-center">Estado</th>
                                        <th className="p-6 text-xs uppercase font-black text-white/40 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredPositions.map((pos) => (
                                        <tr key={pos.id} className="border-b border-white/5 hover:bg-white/[0.02] transition-colors group">
                                            <td className="p-6 font-mono text-xs text-brand">{pos.code || 'N/A'}</td>
                                            <td className="p-6">
                                                <div className="font-bold text-white">{pos.name}</div>
                                                <div className="text-[10px] text-white/20 italic">{pos.name_ht || '-'}</div>
                                            </td>
                                            <td className="p-6">
                                                {pos.company_id ? (
                                                    <div className="flex items-center gap-2 text-[10px] font-black uppercase text-blue-400">
                                                        <Building2 className="w-3 h-3" />
                                                        {pos.companies?.name || 'Empresa'}
                                                    </div>
                                                ) : (
                                                    <div className="text-[10px] font-black uppercase text-brand/60">Global</div>
                                                )}
                                            </td>
                                            <td className="p-6 max-w-xs">
                                                <div className="text-xs text-white/40 line-clamp-1 italic prose prose-invert prose-xs" dangerouslySetInnerHTML={{ __html: pos.description || 'Sin descripción' }} />
                                                <div className="text-[10px] text-white/10 line-clamp-1 italic prose prose-invert prose-xs" dangerouslySetInnerHTML={{ __html: pos.description_ht || '-' }} />
                                            </td>
                                            <td className="p-6">
                                                <div className="flex justify-center">
                                                    {pos.active !== false ? (
                                                        <span className="bg-green-500/10 text-green-500 text-[10px] px-2 py-1 rounded-full font-black uppercase border border-green-500/20">Activo</span>
                                                    ) : (
                                                        <span className="bg-red-500/10 text-red-500 text-[10px] px-2 py-1 rounded-full font-black uppercase border border-red-500/20">Inactivo</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="p-6">
                                                <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button
                                                        onClick={() => { 
                                                            setIsEditing(pos); 
                                                            setDescRich(pos.description || ""); 
                                                            setDescRichHT(pos.description_ht || ""); 
                                                            setShowForm(true); 
                                                        }}
                                                        className="p-2 hover:bg-white/10 rounded-lg text-white/60 hover:text-white transition-colors"
                                                    >
                                                        <Edit2 className="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(pos.id)}
                                                        className="p-2 hover:bg-red-500/20 rounded-lg text-white/60 hover:text-red-500 transition-colors"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Form */}
            <AnimatePresence>
                {showForm && (
                    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
                        <motion.div
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            onClick={() => setShowForm(false)}
                            className="absolute inset-0 bg-black/90 backdrop-blur-md"
                        />
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0, y: 20 }}
                            animate={{ scale: 1, opacity: 1, y: 0 }}
                            exit={{ scale: 0.9, opacity: 0, y: 20 }}
                            className="bg-[#0f0f0f] border border-white/10 w-full max-w-4xl rounded-[2.5rem] p-10 relative z-10 shadow-2xl max-h-[95vh] overflow-y-auto custom-scrollbar"
                        >
                            <div className="flex justify-between items-center mb-8">
                                <h3 className="text-2xl font-black uppercase tracking-tight">
                                    {isEditing ? 'Editar Cargo' : 'Nuevo Cargo'}
                                </h3>
                                <button onClick={() => setShowForm(false)} className="p-2 hover:bg-white/10 rounded-full transition-colors font-black">
                                    <X className="w-6 h-6" />
                                </button>
                            </div>

                            <form onSubmit={handleSave} className="space-y-6">
                                <div className="space-y-4">
                                    <div className="grid grid-cols-4 gap-4">
                                        <div className="col-span-1 border-b border-white/5 pb-2">
                                            <label className="text-[10px] font-black uppercase text-white/20 block mb-1">Código</label>
                                            <input
                                                name="code"
                                                defaultValue={isEditing?.code}
                                                placeholder="C001"
                                                className="w-full bg-transparent p-0 text-brand font-mono focus:outline-none"
                                            />
                                        </div>
                                        <div className="col-span-3 border-b border-white/5 pb-2">
                                            <label className="text-[10px] font-black uppercase text-white/20 block mb-1 italic">Nombre (ES) *</label>
                                            <input
                                                name="name"
                                                required
                                                defaultValue={isEditing?.name}
                                                placeholder="Ej: Supervisor de Terreno"
                                                className="w-full bg-transparent p-0 text-white font-bold focus:outline-none placeholder:text-white/10"
                                            />
                                        </div>
                                    </div>

                                    <div className="border-b border-white/5 pb-2">
                                        <label className="text-[10px] font-black uppercase text-white/20 block mb-1 italic">Nombre (Haitití - Creole)</label>
                                        <input
                                            name="name_ht"
                                            defaultValue={isEditing?.name_ht}
                                            placeholder="Ej: Sipèvizè teren"
                                            className="w-full bg-transparent p-0 text-white/60 font-medium focus:outline-none placeholder:text-white/10"
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <label className="text-[10px] font-black uppercase text-white/20 block">Descripción (ES) / Tip</label>
                                            <RichTextEditor content={descRich} onChange={setDescRich} />
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-[10px] font-black uppercase text-white/20 block">Descripción (HT) / Tip</label>
                                            <RichTextEditor content={descRichHT} onChange={setDescRichHT} />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 bg-white/5 p-4 rounded-2xl border border-white/10">
                                        <input
                                            type="checkbox"
                                            name="active"
                                            id="active_check"
                                            defaultChecked={isEditing ? (isEditing.active !== false) : true}
                                            className="w-5 h-5 accent-brand"
                                        />
                                        <label htmlFor="active_check" className="text-sm font-bold uppercase tracking-widest text-white/60 cursor-pointer">Cargo Activo</label>
                                    </div>
                                </div>

                                <div className="pt-4 flex flex-col gap-3">
                                    <button
                                        type="submit"
                                        className="w-full bg-brand text-black py-4 rounded-2xl font-black uppercase text-sm tracking-widest hover:bg-white transition-all shadow-xl shadow-brand/10"
                                    >
                                        {isEditing ? 'Guardar Cambios' : 'Crear Cargo'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowForm(false)}
                                        className="w-full py-4 text-white/40 font-bold uppercase text-xs tracking-widest hover:text-white transition-colors"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>

            {/* Bottom Floating Nav Hint */}
            <div className="fixed bottom-8 left-1/2 -translate-x-1/2 bg-white/5 border border-white/10 backdrop-blur-md px-6 py-3 rounded-full flex items-center gap-4 text-xs font-bold uppercase tracking-widest text-white/40 shadow-2xl">
                <span>Admin Metaverso</span>
                <div className="w-1 h-1 bg-white/20 rounded-full" />
                <span className="text-brand">Base de Datos de Cargos</span>
            </div>
        </div>
    );
}
