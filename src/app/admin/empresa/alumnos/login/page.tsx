"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { User, Lock, ArrowRight, ShieldCheck, Zap, CheckCircle2, GraduationCap } from "lucide-react";
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

        // 1. Buscar alumno por RUT o Email (sin filtrar por password en la query)
        const { data: student, error } = await supabase
            .from('students')
            .select('*, companies:client_id(*)')
            .or(`rut.eq.${identifier},email.eq.${identifier}`)
            .single();

        if (error || !student) {
            alert(t.error_not_found);
            setLoading(false);
            return;
        }

        // 2. Verificar si la cuenta está bloqueada
        if (student.is_locked) {
            alert(lang === 'es' ? 'Tu cuenta está bloqueada por múltiples intentos fallidos. Contacta a tu administrador.' : 'Kont ou an bloke akòz plizyè tantativ ki echwe. Kontakte administratè ou.');
            setLoading(false);
            return;
        }

        // 3. Verificar password
        if (student.password !== password) {
            const newAttempts = (student.login_attempts || 0) + 1;
            const maxAttempts = student.companies?.max_login_attempts || 5;
            const shouldLock = newAttempts >= maxAttempts;
            await supabase.from('students').update({ login_attempts: newAttempts, is_locked: shouldLock }).eq('id', student.id);
            if (shouldLock) {
                alert(lang === 'es' ? 'Tu cuenta ha sido bloqueada por superar el límite de intentos. Contacta al administrador.' : 'Kont ou an bloke. Kontakte administratè a.');
            } else {
                alert(lang === 'es' ? `${t.error_wrong_pass} Intento ${newAttempts}/${maxAttempts}.` : `${t.error_wrong_pass} Tantativ ${newAttempts}/${maxAttempts}.`);
            }
            setLoading(false);
            return;
        }

        // 4. Contraseña correcta — resetear contador de intentos
        if ((student.login_attempts || 0) > 0) {
            await supabase.from('students').update({ login_attempts: 0 }).eq('id', student.id);
        }

        // 5. Guardar sesión y redirigir
        localStorage.setItem("user", JSON.stringify(student));
        window.location.href = "/admin/empresa/alumnos/cursos";
    };

    return (
        <div className="min-h-screen text-white flex items-center justify-center p-4 md:p-8 relative overflow-hidden font-sans">
            <video
                className="absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none"
                autoPlay
                muted
                loop
                playsInline
                poster="/alumno_background.jpg"
            >
                <source src="/techvideo02.mp4" type="video/mp4" />
                <source src="/techvideo01.mov" type="video/quicktime" />
            </video>

            <div className="absolute inset-0 z-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.58)_0%,rgba(2,6,23,0.9)_100%)]" />

            {/* Selector de Idioma */}
            <div className="fixed top-3 right-3 sm:top-6 sm:right-6 z-50 flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-3 sm:px-4 py-2 backdrop-blur-md">
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
            <div className="fixed inset-0 z-0 pointer-events-none">
                <div className="absolute top-[-20%] left-[-10%] w-[70%] h-[70%] bg-cyan-500/12 rounded-full blur-[160px] animate-pulse" />
                <div className="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-brand/8 rounded-full blur-[140px]" />
            </div>

            <div className="w-full max-w-6xl relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 items-center pt-12 sm:pt-0">
                <motion.div
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="space-y-4 sm:space-y-5"
                >
                    <div className="w-20 h-20 bg-gradient-to-br from-brand to-cyan-400 rounded-3xl flex items-center justify-center shadow-[0_0_40px_rgba(49,210,45,0.3)] border border-white/10">
                        <GraduationCap className="w-10 h-10 text-slate-950" />
                    </div>
                    <div>
                        <p className="text-cyan-300 text-[10px] font-black uppercase tracking-[0.2em]">Portal de aprendizaje corporativo</p>
                        <h1 className="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight mt-2 leading-[0.95]">{t.title}<span className="block text-brand">con trazabilidad real</span></h1>
                        <p className="text-white/65 text-sm md:text-base font-medium mt-4 max-w-lg">Cada avance queda registrado, cada curso aporta evidencia y cada colaborador visualiza su ruta de desarrollo profesional.</p>
                    </div>

                    <div className="space-y-2.5">
                        {[
                            "Estado de cursos y evaluaciones en tiempo real",
                            "Certificados descargables con respaldo auditable",
                            "Experiencia guiada para completar formacion sin friccion"
                        ].map((item) => (
                            <p key={item} className="text-sm text-white/75 flex items-start gap-2">
                                <CheckCircle2 className="w-4 h-4 text-brand mt-0.5" /> {item}
                            </p>
                        ))}
                    </div>
                </motion.div>

                <motion.div
                    initial={{ opacity: 0, scale: 0.95 }}
                    animate={{ opacity: 1, scale: 1 }}
                    transition={{ delay: 0.1 }}
                    className="glass p-5 sm:p-8 rounded-[2rem] border-white/10 shadow-2xl relative overflow-hidden bg-black/35"
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
            </div>

            <footer className="absolute bottom-4 left-0 right-0 text-center z-10 hidden sm:block">
                <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.2em] flex items-center justify-center gap-2">
                    <ShieldCheck className="w-3 h-3" /> Powered by Metaverso Otec
                </p>
            </footer>
        </div>
    );
}
