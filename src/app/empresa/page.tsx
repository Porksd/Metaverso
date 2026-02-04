"use client";

import { motion } from "framer-motion";
import { Users, GraduationCap, Settings2, ArrowRight } from "lucide-react";
import Link from "next/link";

export default function EmpresaLanding() {
    return (
        <div className="min-h-screen bg-[#0A0A0A] text-white flex flex-col items-center justify-center p-6 font-sans">
            <div className="max-w-4xl w-full space-y-12">

                <header className="flex flex-col items-center text-center space-y-4">
                    <img src="/cert-assets/logo_sacyr.png" className="w-24 mb-4" alt="Sacyr Logo" />
                    <h1 className="text-5xl md:text-6xl font-black tracking-tighter italic">Sacyr <span className="text-brand">Chile S.A.</span></h1>
                    <p className="text-white/40 text-lg font-medium">MetaversOtec LMS | Ecosistema de Capacitación Corporativa</p>
                </header>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <Link href="/admin/empresa" className="group">
                        <motion.div whileHover={{ scale: 1.02 }} className="glass p-10 h-full border-white/5 hover:border-brand/40 transition-all space-y-6 relative overflow-hidden bg-white/[0.02]">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity">
                                <Settings2 className="w-32 h-32" />
                            </div>
                            <div className="w-16 h-16 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                <Settings2 className="w-8 h-8 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-2xl font-black uppercase tracking-tight">Administrar Mis Cursos</h3>
                                <p className="text-white/40 text-sm leading-relaxed">Acceso para Gerentes y Capacitadores. Gestión de personal, reportes de avance y descarga de certificados.</p>
                            </div>
                            <div className="flex items-center gap-2 text-brand text-xs font-black uppercase tracking-widest pt-4">
                                Acceso Gestión <ArrowRight className="w-4 h-4" />
                            </div>
                        </motion.div>
                    </Link>

                    <Link href="/admin/empresa/alumnos/login" className="group">
                        <motion.div whileHover={{ scale: 1.02 }} className="glass p-10 h-full border-white/5 hover:border-brand/40 transition-all space-y-6 relative overflow-hidden bg-brand/5 border-brand/10">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity">
                                <GraduationCap className="w-32 h-32" />
                            </div>
                            <div className="w-16 h-16 rounded-2xl bg-brand/20 flex items-center justify-center border border-brand/40 shadow-[0_0_15px_rgba(49,210,45,0.2)]">
                                <Users className="w-8 h-8 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-2xl font-black uppercase tracking-tight">Ingreso Alumnos</h3>
                                <p className="text-white/40 text-sm leading-relaxed">Accede a tus cursos vigentes, progresa en tu capacitación y descarga tu certificación oficial.</p>
                            </div>
                            <div className="flex items-center gap-2 text-brand text-xs font-black uppercase tracking-widest pt-4">
                                Aula Virtual <ArrowRight className="w-4 h-4" />
                            </div>
                        </motion.div>
                    </Link>
                </div>

                <footer className="text-center pt-8">
                    <p className="text-white/20 text-[10px] font-black uppercase tracking-widest">Powered by MetaversOtec | Future of Learning</p>
                </footer>
            </div>
        </div>
    );
}
