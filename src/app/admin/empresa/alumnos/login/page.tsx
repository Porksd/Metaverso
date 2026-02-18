"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { User, Lock, ArrowRight, ShieldCheck, Zap, Terminal } from "lucide-react";
import { supabase } from "@/lib/supabase";
import { Globe } from "lucide-react";

const translations: any = {
    es: {
        title: "Acceso Corporativo",
        subtitle: "Plataforma de Capacitación",
        rut_label: "RUT o Email",
        rut_placeholder: "12.345.678-9 o correo@empresa.cl",
        pass_label: "Clave de Acceso",
        pass_placeholder: "••••••",
        login_btn: "Iniciar Sesión",
        verifying: "Verificando...",
        no_account: "¿Aún no tienes cuenta?",
        register_btn: "Registrarme Ahora",
        error_not_found: "Usuario no encontrado. Verifique su RUT o Email.",
        error_wrong_pass: "Contraseña incorrecta.",
    },
    ht: {
        title: "Aksè Kòporatif",
        subtitle: "Platfòm Fòmasyon",
        rut_label: "RUT oswa Imèl",
        rut_placeholder: "12.345.678-9 oswa mail@konpayi.cl",
        pass_label: "Modpas Aksè",
        pass_placeholder: "••••••",
        login_btn: "Konekte",
        verifying: "Verifye...",
        no_account: "Ou pa gen kont ankò?",
        register_btn: "Enskri kounye a",
        error_not_found: "Itilizatè pa jwenn. Tcheke RUT ou oswa Imèl ou.",
        error_wrong_pass: "Modpas kòrèk.",
    }
};

export default function StudentLogin() {
    const [rut, setRut] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);
    const [lang, setLang] = useState("es");

    const t = translations[lang];

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        const identifier = rut.trim();

        // Bypass para Demo Demo
        if (identifier === "20.207.790-0") {
            const { data: student } = await supabase.from('students').select('*, companies:client_id(*)').eq('rut', identifier).single();
            if (student) {
                localStorage.setItem("user", JSON.stringify(student));
                window.location.href = "/admin/empresa/alumnos/cursos";
                return;
            }
        }

        // 1. Buscar alumno por RUT o Email
        // Intentamos unir con 'companies' (client_id) que es la tabla activa
        const { data: student, error } = await supabase
            .from('students')
            .select('*, companies:client_id(*)')
            .or(`rut.eq.${identifier},email.eq.${identifier}`)
            .single();

        if (error || !student) {
            console.error("Login error:", error);
            alert(t.error_not_found);
            setLoading(false);
            return;
        }

        // 2. Verificar password
        if (student.password !== password) {
            alert(t.error_wrong_pass);
            setLoading(false);
            return;
        }

        // 3. Guardar sesión y redirigir
        localStorage.setItem("user", JSON.stringify(student));
        window.location.href = "/admin/empresa/alumnos/cursos";
    };

    return (
        <div className="min-h-screen bg-[#060606] text-white flex items-center justify-center p-4 relative overflow-hidden font-sans">
            {/* Selector de Idioma */}
            <div className="fixed top-6 right-6 z-50 flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-4 py-2 backdrop-blur-md">
                <Globe className="w-4 h-4 text-white/40" />
                <select 
                    value={lang} 
                    onChange={(e) => setLang(e.target.value)}
                    className="bg-transparent border-none text-xs font-bold uppercase tracking-widest outline-none cursor-pointer text-white/70 hover:text-white transition-colors"
                >
                    <option value="es" className="bg-[#111]">ES</option>
                    <option value="ht" className="bg-[#111]">HT</option>
                </select>
            </div>

            {/* Background Premium */}
            <div className="fixed inset-0 z-0">
                <div className="absolute top-[-20%] left-[-10%] w-[70%] h-[70%] bg-blue-600/10 rounded-full blur-[160px] animate-pulse pointer-events-none" />
                <div className="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-brand/5 rounded-full blur-[140px] pointer-events-none" />
            </div>

            <div className="max-w-md w-full relative z-10 space-y-8">
                <motion.div
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="text-center space-y-4"
                >
                    <div className="w-20 h-20 bg-gradient-to-br from-brand to-blue-500 rounded-3xl mx-auto flex items-center justify-center shadow-[0_0_40px_rgba(49,210,45,0.3)] border border-white/10">
                        <ShieldCheck className="w-10 h-10 text-white" />
                    </div>
                    <div>
                        <h1 className="text-3xl font-black tracking-tighter">{t.title}</h1>
                        <p className="text-white/40 text-xs font-bold uppercase tracking-widest mt-2">{t.subtitle}</p>
                    </div>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, scale: 0.95 }}
                    animate={{ opacity: 1, scale: 1 }}
                    transition={{ delay: 0.1 }}
                    className="glass p-8 rounded-[2rem] border-white/5 shadow-2xl relative overflow-hidden"
                >
                    <div className="absolute top-0 right-0 w-32 h-32 bg-brand/10 blur-[50px] pointer-events-none" />

                    <form onSubmit={handleLogin} className="space-y-5 relative z-10">
                        <div className="space-y-1">
                            <label className="text-[10px] font-black uppercase text-white/30 pl-3">{t.rut_label}</label>
                            <div className="relative group">
                                <User className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30 group-focus-within:text-brand transition-colors" />
                                <input
                                    type="text"
                                    value={rut}
                                    onChange={(e) => setRut(e.target.value)}
                                    placeholder={t.rut_placeholder}
                                    className="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm font-bold focus:outline-none focus:border-brand/50 focus:bg-brand/[0.02] transition-all"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <label className="text-[10px] font-black uppercase text-white/30 pl-3">{t.pass_label}</label>
                            <div className="relative group">
                                <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30 group-focus-within:text-brand transition-colors" />
                                <input
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder={t.pass_placeholder}
                                    className="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm font-bold focus:outline-none focus:border-brand/50 focus:bg-brand/[0.02] transition-all"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full py-4 bg-brand text-black font-black uppercase tracking-widest rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all shadow-[0_0_30px_rgba(49,210,45,0.2)] flex items-center justify-center gap-2 group"
                        >
                            {loading ? t.verifying : t.login_btn}
                            {!loading && <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />}
                        </button>

                        <div className="text-center pt-4 border-t border-white/5">
                            <p className="text-xs text-white/40 mb-3">{t.no_account}</p>
                            <a
                                href="/admin/empresa/alumnos/register"
                                className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-white/80 hover:text-white transition-all text-xs font-bold uppercase tracking-widest border border-white/10 hover:border-white/20"
                            >
                                {t.register_btn}
                            </a>
                        </div>
                    </form>
                </motion.div>

                <footer className="text-center">
                    <p className="text-[10px] font-black text-white/10 uppercase tracking-[0.2em] flex items-center justify-center gap-2">
                        <Zap className="w-3 h-3" /> Powered by Metaverso Otec
                    </p>
                </footer>
            </div>
        </div>
    );
}
