"use client";

import { motion } from "framer-motion";
import { Users, GraduationCap, Settings2, ArrowRight, BarChart3, ShieldCheck } from "lucide-react";
import Link from "next/link";

export default function EmpresaLanding() {
    return (
        <div className="min-h-screen text-white flex flex-col items-center justify-center p-6 md:p-10 font-sans relative overflow-hidden">
            <video
                className="absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none"
                autoPlay
                muted
                loop
                playsInline
                poster="/empresa_background.jpg"
            >
                <source src="/techvideo01.mov" type="video/quicktime" />
                <source src="/techvideo02.mp4" type="video/mp4" />
            </video>

            <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.55)_0%,rgba(2,6,23,0.88)_100%)] z-0" />

            <div className="fixed inset-0 z-0 pointer-events-none">
                <div className="absolute top-[-12%] right-[-10%] w-[45%] h-[45%] bg-cyan-400/14 rounded-full blur-[130px]" />
                <div className="absolute bottom-[-20%] left-[-15%] w-[55%] h-[55%] bg-brand/12 rounded-full blur-[140px]" />
            </div>

            <div className="relative z-10 w-full flex flex-col items-center">
            <div className="max-w-6xl w-full space-y-10">

                <header className="flex flex-col items-center text-center space-y-4">
                    <img src="/cert-assets/logo_sacyr.png" className="w-24 mb-4" alt="Sacyr Logo" />
                    <div className="inline-flex items-center gap-2 text-cyan-300 text-[10px] font-black uppercase tracking-[0.22em] border border-cyan-400/30 bg-cyan-400/10 rounded-full px-4 py-1.5">
                        <ShieldCheck className="w-3.5 h-3.5" /> Portal Corporativo
                    </div>
                    <h1 className="text-4xl md:text-6xl font-black tracking-tight leading-[0.95]">Formacion con control,<span className="block text-brand">resultados y trazabilidad</span></h1>
                    <p className="text-white/65 text-base md:text-lg font-medium max-w-3xl">Muestra a tu equipo y a tus clientes una operación de capacitación robusta: avance visible, evidencia auditable y foco en desempeño real.</p>
                </header>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {[
                        { icon: Users, label: "Colaboradores activos", value: "+1.200" },
                        { icon: BarChart3, label: "Seguimiento en tiempo real", value: "100% trazable" },
                        { icon: GraduationCap, label: "Rutas certificables", value: "Por rol y empresa" }
                    ].map((item) => {
                        const Icon = item.icon;
                        return (
                            <div key={item.label} className="rounded-2xl border border-white/15 bg-black/30 backdrop-blur-md p-5">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-brand/15 border border-brand/25 flex items-center justify-center">
                                        <Icon className="w-5 h-5 text-brand" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] uppercase font-black tracking-[0.18em] text-white/45">{item.label}</p>
                                        <p className="text-lg font-black text-white">{item.value}</p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <Link href="/admin/empresa" className="group">
                        <motion.div whileHover={{ y: -5 }} className="glass p-10 h-full border-white/10 hover:border-cyan-300/40 transition-all space-y-6 relative overflow-hidden bg-black/30">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity">
                                <Settings2 className="w-32 h-32" />
                            </div>
                            <div className="w-16 h-16 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                <Settings2 className="w-8 h-8 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-2xl font-black uppercase tracking-tight">Vista Ejecutiva Empresa</h3>
                                <p className="text-white/55 text-sm leading-relaxed">Gestiona dotación, progreso, cumplimiento y certificados desde una vista de control integral para operaciones.</p>
                            </div>
                            <div className="flex items-center gap-2 text-cyan-300 text-xs font-black uppercase tracking-widest pt-4">
                                Abrir Panel Empresa <ArrowRight className="w-4 h-4" />
                            </div>
                        </motion.div>
                    </Link>

                    <Link href="/admin/empresa/alumnos/login" className="group">
                        <motion.div whileHover={{ y: -5 }} className="glass p-10 h-full border-brand/20 hover:border-brand/45 transition-all space-y-6 relative overflow-hidden bg-brand/10">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity">
                                <GraduationCap className="w-32 h-32" />
                            </div>
                            <div className="w-16 h-16 rounded-2xl bg-brand/20 flex items-center justify-center border border-brand/40 shadow-[0_0_15px_rgba(49,210,45,0.2)]">
                                <Users className="w-8 h-8 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-2xl font-black uppercase tracking-tight">Vista Colaborador</h3>
                                <p className="text-white/60 text-sm leading-relaxed">Acceso directo al aula virtual para iniciar cursos, completar evaluaciones y descargar certificaciones.</p>
                            </div>
                            <div className="flex items-center gap-2 text-brand text-xs font-black uppercase tracking-widest pt-4">
                                Entrar como Alumno <ArrowRight className="w-4 h-4" />
                            </div>
                        </motion.div>
                    </Link>
                </div>

                <footer className="text-center pt-8">
                    <p className="text-white/25 text-[10px] font-black uppercase tracking-[0.16em]">Metaverso Otec | Capacitacion corporativa para decisiones con datos</p>
                </footer>
            </div>
            </div>
        </div>
    );
}
