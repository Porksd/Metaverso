"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { BookOpen, Lock, ArrowRight, User, LogOut } from "lucide-react";
import { useRouter } from "next/navigation";

export default function CursosLogin() {
    const [pass, setPass] = useState("");
    const router = useRouter();

    const handleLogin = (e: React.FormEvent) => {
        e.preventDefault();
        if (pass === "cursos123") {
            localStorage.setItem('cursos_auth', 'true');
            router.push("/admin/cursos");
        } else {
            alert("Credenciales de catálogo técnico incorrectas.");
        }
    };

    return (
        <div className="min-h-screen bg-[#0A0A0A] text-white flex items-center justify-center p-6 font-sans">
            <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} className="glass p-12 w-full max-w-md border-brand/20 space-y-8 relative overflow-hidden">

                <div className="absolute top-0 right-0 p-4">
                    <button onClick={() => window.location.href = '/'} className="p-2 rounded-xl bg-white/5 text-white/20 hover:text-white transition-colors">
                        <LogOut className="w-4 h-4 rotate-180" />
                    </button>
                </div>

                <div className="text-center space-y-4">
                    <div className="w-20 h-20 rounded-3xl bg-brand/10 border border-brand/20 flex items-center justify-center mx-auto mb-6">
                        <BookOpen className="w-10 h-10 text-brand" />
                    </div>
                    <h1 className="text-3xl font-black tracking-tight">Catálogo <span className="text-brand">Técnico</span></h1>
                    <p className="text-white/40 text-xs font-bold uppercase tracking-widest leading-relaxed">System Configuration Interface</p>
                </div>

                <form onSubmit={handleLogin} className="space-y-6">
                    <div className="space-y-4">
                        <div className="relative">
                            <User className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                            <input
                                type="text"
                                defaultValue="tech_editor"
                                disabled
                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm font-medium opacity-50 cursor-not-allowed"
                            />
                        </div>
                        <div className="relative">
                            <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                            <input
                                type="password"
                                placeholder="Contraseña de Configuración"
                                value={pass}
                                onChange={(e) => setPass(e.target.value)}
                                required
                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm focus:border-brand/40 outline-none transition-all font-medium"
                            />
                        </div>
                    </div>

                    <button type="submit" className="w-full py-4 bg-brand text-black rounded-2xl font-black uppercase tracking-widest text-xs flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-brand/20">
                        Entrar al Editor <ArrowRight className="w-4 h-4" />
                    </button>
                </form>

                <div className="pt-4 text-center">
                    <p className="text-[10px] text-white/20 font-black uppercase tracking-[0.2em]">Authorized Technical Personnel Only</p>
                </div>
            </motion.div>
        </div>
    );
}
