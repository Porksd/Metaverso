"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { 
    ShieldCheck, UserPlus, Trash2, Mail, Shield, 
    X, Check, Search, ArrowLeft, MoreVertical
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import AdminSidebar from "@/components/AdminSidebar";

interface AdminProfile {
    id: string;
    email: string;
    role: 'superadmin' | 'editor';
    permissions: any;
    created_at: string;
}

export default function AdminUsersPage() {
    const router = useRouter();
    const [admins, setAdmins] = useState<AdminProfile[]>([]);
    const [loading, setLoading] = useState(true);
    const [isAuthorized, setIsAuthorized] = useState<boolean | null>(null);
    const [searchTerm, setSearchTerm] = useState("");
    const [showForm, setShowForm] = useState(false);
    const [editingAdmin, setEditingAdmin] = useState<Partial<AdminProfile> | null>(null);

    useEffect(() => {
        checkAuth();
    }, []);

    const checkAuth = async () => {
        const { data: { session } } = await supabase.auth.getSession();
        
        if (!session) {
            router.push("/admin/metaverso/login");
            return;
        }

        // Only superadmins can manage other admins
        const { data: profile } = await supabase
            .from('admin_profiles')
            .select('role')
            .eq('email', session.user.email?.toLowerCase())
            .maybeSingle();

        if (profile?.role !== 'superadmin') {
            // Hardcoded fallback for the main admin to setup the first time
            const allowedSuperAdmins = ['admin@metaversotec.com', 'porksde@gmail.com', 'apacheco@lobus.cl'];
            if (!session.user.email || !allowedSuperAdmins.includes(session.user.email.toLowerCase())) {
                setIsAuthorized(false);
                return;
            }
        }
        
        setIsAuthorized(true);
        loadAdmins();
    };

    const loadAdmins = async () => {
        setLoading(true);
        const { data, error } = await supabase
            .from('admin_profiles')
            .select('*')
            .order('created_at', { ascending: false });
        
        if (data) setAdmins(data);
        if (error) console.error("Error loading admins:", error);
        setLoading(false);
    };

    const handleSaveAdmin = async (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget as HTMLFormElement);
        const email = (formData.get('email') as string).toLowerCase();
        const role = formData.get('role') as 'superadmin' | 'editor';

        const adminData = { email, role };

        if (editingAdmin?.id) {
            await supabase.from('admin_profiles').update(adminData).eq('id', editingAdmin.id);
        } else {
            await supabase.from('admin_profiles').insert([adminData]);
        }

        setShowForm(false);
        setEditingAdmin(null);
        loadAdmins();
    };

    const handleDeleteAdmin = async (id: string, email: string) => {
        const { data: { session } } = await supabase.auth.getSession();
        if (session?.user.email?.toLowerCase() === email.toLowerCase()) {
            return alert("No puedes eliminarte a ti mismo.");
        }

        if (!confirm(`¿Seguro que desea eliminar el acceso de ${email}?`)) return;
        await supabase.from('admin_profiles').delete().eq('id', id);
        loadAdmins();
    };

    const filteredAdmins = admins.filter(a => 
        a.email.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (isAuthorized === null) return (
        <div className="min-h-screen bg-black flex items-center justify-center">
            <div className="text-brand font-black animate-pulse uppercase tracking-widest text-xs">Verificando Credenciales...</div>
        </div>
    );

    if (isAuthorized === false) return (
        <div className="min-h-screen bg-black flex flex-col items-center justify-center p-8 text-center space-y-6">
            <ShieldCheck className="w-20 h-20 text-red-500" />
            <h1 className="text-4xl font-black italic tracking-tighter uppercase text-white">Acceso Restringido</h1>
            <p className="text-white/40 max-w-md uppercase text-[10px] font-bold">Solo usuarios con perfil SUPERADMIN pueden gestionar permisos.</p>
            <button onClick={() => router.push("/admin/metaverso")} className="bg-white text-black px-8 py-4 rounded-xl font-black uppercase text-xs">Regresar al Panel</button>
        </div>
    );

    return (
        <AdminSidebar title="Gestión de Accesos">
            <div className="min-h-screen bg-[#060606] text-white p-4 md:p-8 font-sans pt-20">
                <div className="max-w-5xl mx-auto space-y-10">
                    
                    {/* Header */}
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <h1 className="text-4xl font-black uppercase tracking-tighter flex items-center gap-4">
                                <ShieldCheck className="w-10 h-10 text-brand" />
                                Control de Acceso
                            </h1>
                            <p className="text-white/40 font-medium text-sm mt-1">Gestión de usuarios administradores y niveles de permiso</p>
                        </div>
                        <button
                            onClick={() => { setEditingAdmin(null); setShowForm(true); }}
                            className="bg-brand text-black px-8 py-4 rounded-xl font-black uppercase text-[10px] tracking-widest flex items-center gap-2 hover:scale-105 transition-all shadow-xl shadow-brand/20"
                        >
                            <UserPlus className="w-4 h-4" /> Agregar Administrador
                        </button>
                    </div>

                {/* List */}
                <div className="space-y-4">
                    <div className="relative">
                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                        <input
                            type="text"
                            placeholder="Buscar por email..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full bg-white/[0.02] border border-white/5 rounded-2xl py-4 pl-12 pr-4 focus:border-brand/40 outline-none transition-all font-medium text-sm"
                        />
                    </div>

                    <div className="glass overflow-hidden border-white/5">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-white/5 text-[10px] font-black uppercase tracking-widest text-white/40 border-b border-white/10">
                                    <th className="px-6 py-4">Usuario / Email</th>
                                    <th className="px-6 py-4">Nivel de Acceso</th>
                                    <th className="px-6 py-4">Fecha Registro</th>
                                    <th className="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/5">
                                {loading ? (
                                    <tr><td colSpan={4} className="px-6 py-20 text-center text-white/20 font-black uppercase tracking-widest text-xs">Sincronizando Usuarios...</td></tr>
                                ) : filteredAdmins.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-20 text-center space-y-4">
                                            <Mail className="w-12 h-12 text-white/10 mx-auto" />
                                            <div className="space-y-1">
                                                <p className="text-white/40 font-black uppercase tracking-widest text-xs">No hay administradores registrados</p>
                                                <p className="text-white/20 text-[10px] font-medium max-w-xs mx-auto">Usa el botón "Agregar Administrador" para registrar correos en la base de datos y asignar roles (Editor/SuperAdmin).</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : filteredAdmins.map(admin => (
                                    <tr key={admin.id} className="hover:bg-white/[0.02] transition-colors group">
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-brand/20 transition-all">
                                                    <Mail className="w-5 h-5 text-white/20 group-hover:text-brand" />
                                                </div>
                                                <span className="font-bold text-sm tracking-tight">{admin.email}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <span className={`px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter border ${
                                                admin.role === 'superadmin' 
                                                ? 'bg-brand/10 text-brand border-brand/20' 
                                                : 'bg-blue-500/10 text-blue-400 border-blue-500/20'
                                            }`}>
                                                {admin.role}
                                            </span>
                                        </td>
                                        <td className="px-6 py-5">
                                            <span className="text-[10px] font-mono text-white/20">{new Date(admin.created_at).toLocaleDateString()}</span>
                                        </td>
                                        <td className="px-6 py-5 text-right">
                                            <div className="flex justify-end gap-2">
                                                <button 
                                                    onClick={() => { setEditingAdmin(admin); setShowForm(true); }}
                                                    className="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all border border-white/5"
                                                >
                                                    <Shield className="w-4 h-4" />
                                                </button>
                                                <button 
                                                    onClick={() => handleDeleteAdmin(admin.id, admin.email)}
                                                    className="p-2.5 rounded-xl bg-white/5 hover:bg-red-500/10 text-white/20 hover:text-red-400 transition-all border border-white/5"
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
                </div>

                {/* Modal Form */}
                <AnimatePresence>
                    {showForm && (
                        <div className="fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                            <motion.div initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.9 }} className="glass p-10 w-full max-w-md border-brand/20 space-y-8">
                                <div className="flex justify-between items-center">
                                    <h2 className="text-2xl font-black uppercase italic tracking-tighter text-brand">/permisos_usuario</h2>
                                    <button onClick={() => { setShowForm(false); setEditingAdmin(null); }} className="text-white/20 hover:text-white"><X className="w-6 h-6" /></button>
                                </div>

                                <form onSubmit={handleSaveAdmin} className="space-y-6">
                                    <div className="space-y-2">
                                        <label className="text-[10px] uppercase font-black text-white/20 pl-1">Correo Electrónico</label>
                                        <input 
                                            name="email" 
                                            defaultValue={editingAdmin?.email} 
                                            required 
                                            readOnly={!!editingAdmin?.id}
                                            className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand outline-none transition-all disabled:opacity-50" 
                                            placeholder="ejemplo@lobus.cl"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-[10px] uppercase font-black text-white/20 pl-1">Rol de Administrador</label>
                                        <select 
                                            name="role" 
                                            defaultValue={editingAdmin?.role || 'editor'} 
                                            className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-brand outline-none transition-all"
                                        >
                                            <option value="superadmin" className="bg-[#0A0A0A]">SuperAdmin (Acceso Total)</option>
                                            <option value="editor" className="bg-[#0A0A0A]">Editor (Gestión sin Eliminación)</option>
                                        </select>
                                    </div>

                                    <div className="pt-4 flex gap-3">
                                        <button type="submit" className="flex-1 py-4 bg-brand text-black rounded-xl font-black uppercase text-xs tracking-widest hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                                            <Check className="w-4 h-4" /> {editingAdmin?.id ? 'Actualizar' : 'Autorizar'}
                                        </button>
                                    </div>
                                </form>
                            </motion.div>
                        </div>
                    )}
                </AnimatePresence>

                </div>
            </div>
        </AdminSidebar>
    );
}
