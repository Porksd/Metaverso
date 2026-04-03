"use client";

import { useState } from "react";
import { motion } from "framer-motion";
import { CheckCircle2, GraduationCap, ArrowLeft } from "lucide-react";
import { supabase } from "@/lib/supabase";
import { Globe } from "lucide-react";
import Link from "next/link";

const DEMO_RUT = "27.654.321-4";
const DEMO_PASSWORD = "MetaAlumno#2026!";

const translations: any = {
    es: {
        title: "Acceso Corporativo",
        rut_label: "RUT o Email",
        rut_placeholder: "12.345.678-9 o correo@empresa.cl",
        pass_label: "Clave de Acceso",
        pass_placeholder: "••••••",
        login_btn: "Iniciar Sesion",
        verifying: "Verificando...",
        no_account: "Aun no tienes cuenta?",
        register_btn: "Registrarme Ahora",
        error_not_found: "Usuario no encontrado. Verifique su RUT o Email.",
        error_wrong_pass: "Contrasena incorrecta.",
        demo_preset: "Credenciales demo precargadas",
        back_to_presentation: "Volver a Presentacion"
    },
    ht: {
        title: "Akse Koporatif",
        rut_label: "RUT oswa Imel",
        rut_placeholder: "12.345.678-9 oswa mail@konpayi.cl",
        pass_label: "Modpas Akse",
        pass_placeholder: "••••••",
        login_btn: "Konekte",
        verifying: "Verifye...",
        no_account: "Ou pa gen kont anko?",
        register_btn: "Enskri kounye a",
        error_not_found: "Itilizate pa jwenn. Tcheke RUT ou oswa Imel ou.",
        error_wrong_pass: "Modpas korek.",
        demo_preset: "Kredansyal demo prechaje",
        back_to_presentation: "Retounen nan Prezantasyon"
    }
};

export default function DemoStudentLogin() {
    const [rut, setRut] = useState(DEMO_RUT);
    const [password, setPassword] = useState(DEMO_PASSWORD);
    const [loading, setLoading] = useState(false);
    const [lang, setLang] = useState("es");

    const t = translations[lang];

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        const identifier = rut.trim();

        const { data: student, error } = await supabase
            .from("students")
            .select("*, companies:client_id(*)")
            .or(`rut.eq.${identifier},email.eq.${identifier}`)
            .single();

        if (error || !student) {
            alert(t.error_not_found);
            setLoading(false);
            return;
        }

        if (student.password !== password) {
            alert(t.error_wrong_pass);
            setLoading(false);
            return;
        }

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
                <source src="/techvideo01.mov" type="video/quicktime" />
                <source src="/techvideo02.mp4" type="video/mp4" />
            </video>

            <div className="absolute inset-0 z-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.58)_0%,rgba(2,6,23,0.9)_100%)]" />

            <Link href="/demo" className="fixed top-6 left-6 z-50 flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 border border-white/20 text-white text-sm font-semibold hover:bg-white/20 transition-colors">
                <ArrowLeft className="w-4 h-4" /> {t.back_to_presentation}
            </Link>

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

            <div className="fixed inset-0 z-0 pointer-events-none">
                <div className="absolute top-[-20%] left-[-10%] w-[70%] h-[70%] bg-cyan-500/12 rounded-full blur-[160px] animate-pulse" />
                <div className="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-brand/8 rounded-full blur-[140px]" />
            </div>

            <div className="w-full max-w-6xl relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <motion.div initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }} className="space-y-5">
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-400/20 border border-cyan-300/40">
                        <span className="w-2 h-2 rounded-full bg-cyan-400 animate-pulse" />
                        <span className="text-[10px] font-black uppercase tracking-widest text-cyan-300">{t.demo_preset}</span>
                    </div>
                    <div className="w-20 h-20 bg-gradient-to-br from-brand to-cyan-400 rounded-3xl flex items-center justify-center shadow-[0_0_40px_rgba(49,210,45,0.3)] border border-white/10">
                        <GraduationCap className="w-10 h-10 text-slate-950" />
                    </div>
                    <div>
                        <p className="text-cyan-300 text-[10px] font-black uppercase tracking-[0.2em]">Portal de aprendizaje corporativo</p>
                        <h1 className="text-4xl md:text-5xl font-black tracking-tight mt-2 leading-[0.95]">{t.title}<span className="block text-brand">con trazabilidad real</span></h1>
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
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.1 }}
                    className="glass rounded-3xl p-8 md:p-10 border-cyan-400/20 bg-black/40 space-y-6"
                >
                    <form onSubmit={handleLogin} className="space-y-5">
                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-[0.18em] text-white/60 mb-2">{t.rut_label}</label>
                            <input
                                type="text"
                                value={rut}
                                onChange={(e) => setRut(e.target.value)}
                                placeholder={t.rut_placeholder}
                                className="w-full px-4 py-3 rounded-xl bg-white/8 border border-white/15 text-white placeholder-white/40 focus:border-cyan-400/50 focus:outline-none transition-colors"
                            />
                        </div>

                        <div>
                            <label className="block text-[10px] font-black uppercase tracking-[0.18em] text-white/60 mb-2">{t.pass_label}</label>
                            <input
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder={t.pass_placeholder}
                                className="w-full px-4 py-3 rounded-xl bg-white/8 border border-white/15 text-white placeholder-white/40 focus:border-cyan-400/50 focus:outline-none transition-colors"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full py-3 rounded-xl bg-gradient-to-r from-brand to-cyan-400 text-black font-black uppercase tracking-widest text-[11px] hover:scale-[1.02] transition-transform disabled:opacity-50"
                        >
                            {loading ? t.verifying : t.login_btn}
                        </button>
                    </form>
                </motion.div>
            </div>
        </div>
    );
}
