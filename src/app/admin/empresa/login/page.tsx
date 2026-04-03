"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { Building2, Lock, ArrowRight, Mail, Loader2 } from "lucide-react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { resolveAdminRole } from "@/lib/adminAuth";

const DEMO_COMPANY_EMAIL = "demo.empresa@metaverso.cl";
const DEMO_COMPANY_PASSWORD = "MetaEmpresa#2026!";

export default function EmpresaLogin() {
    const [email, setEmail] = useState("");
    const [pass, setPass] = useState("");
    const [loading, setLoading] = useState(false);
    const router = useRouter();
    const searchParams = useSearchParams();
    const isDemoMode = searchParams.get("demo") === "1";

    useEffect(() => {
        // Clear previous session if it's NOT a Meta Admin
        const clearSession = async () => {
            const { data: { session } } = await supabase.auth.getSession();
            if (session) {
                const { role } = await resolveAdminRole(supabase, session.user.email, '/admin/empresa/login');
                if (role) return; // Keep Meta Admin session
            }
            
            await supabase.auth.signOut({ scope: 'local' });
            localStorage.removeItem('empresa_id');
            localStorage.removeItem('empresa_name');
            localStorage.removeItem('empresa_slug');
            sessionStorage.clear();
        };
        clearSession();
    }, []);

    useEffect(() => {
        if (isDemoMode) {
            setEmail(DEMO_COMPANY_EMAIL);
            setPass(DEMO_COMPANY_PASSWORD);
        }
    }, [isDemoMode]);

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        try {
            const { data, error } = await supabase
                .from('companies')
                .select('id, name, email, password, slug')
                .eq('email', email)
                .eq('password', pass)
                .single();

            if (error || !data) {
                alert("Credenciales incorrectas o empresa no registrada.");
            } else {
                sessionStorage.removeItem('is_master_admin');
                sessionStorage.removeItem('master_role');
                sessionStorage.removeItem('master_entry_mode');
                sessionStorage.removeItem('master_return_url');
                localStorage.setItem('empresa_id', data.id);
                localStorage.setItem('empresa_name', data.name);
                if (data.slug) localStorage.setItem('empresa_slug', data.slug);
                router.push("/admin/empresa");
            }
        } catch (err) {
            console.error(err);
            alert("Error al intentar ingresar. Reintente.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen relative text-white flex items-center justify-center p-6 font-sans overflow-hidden">
            <img src="/app_background.jpg" alt="" aria-hidden="true" className="absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none select-none" />
            <div className="absolute inset-0 z-0 bg-black/70" />
            <div className="fixed inset-0 z-0 pointer-events-none">
                <div className="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-brand/8 rounded-full blur-[120px]" />
                <div className="absolute bottom-[-10%] left-[-20%] w-[50%] h-[50%] bg-brand/5 rounded-full blur-[120px]" />
            </div>
            <motion.div initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} className="glass p-12 w-full max-w-md border-white/5 space-y-8 bg-black/60 relative overflow-hidden z-10">

                <div className="text-center space-y-4">
                    <div className="w-20 h-20 bg-brand/10 rounded-full flex items-center justify-center mx-auto mb-6 border border-brand/20">
                        <Building2 className="w-10 h-10 text-brand" />
                    </div>
                    <h1 className="text-3xl font-black tracking-tight italic">Portal <span className="text-brand">Corporativo</span></h1>
                    <p className="text-white/40 text-[10px] font-black uppercase tracking-[0.2em]">Gestión de Capacitación LMS</p>
                </div>

                <form onSubmit={handleLogin} className="space-y-6">
                    {isDemoMode && (
                        <div className="rounded-xl border border-cyan-400/30 bg-cyan-400/10 px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-cyan-300">
                            Credenciales demo de empresa precargadas
                        </div>
                    )}
                    <div className="space-y-4">
                        <div className="relative group">
                            <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20 group-focus-within:text-brand transition-colors" />
                            <input
                                type="email"
                                placeholder="Email Administrativo"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                                className="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm focus:outline-none focus:border-brand/40 transition-all font-medium"
                            />
                        </div>
                        <div className="relative group">
                            <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20 group-focus-within:text-brand transition-colors" />
                            <input
                                type="password"
                                placeholder="Contraseña de Empresa"
                                value={pass}
                                onChange={(e) => setPass(e.target.value)}
                                required
                                className="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm focus:outline-none focus:border-brand/40 transition-all font-medium"
                            />
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        disabled={loading}
                        className="w-full py-4 bg-brand text-black disabled:opacity-50 rounded-2xl font-black uppercase tracking-widest text-[10px] flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-brand/10"
                    >
                        {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : "Ingresar al Panel"}
                        {!loading && <ArrowRight className="w-4 h-4" />}
                    </button>
                </form>

                <div className="pt-4 flex items-center justify-between text-[8px] font-black uppercase tracking-widest text-white/20">
                    <span>Metaverso Corporativo</span>
                    <span className="text-brand/40">V3.0.0</span>
                </div>

                <div className="text-center">
                    <Link href="/admin" className="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-white/20 hover:text-white/50 transition-colors">
                        ← Volver al Inicio
                    </Link>
                </div>
            </motion.div>
        </div>
    );
}
