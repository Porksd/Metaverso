"use client";

import { motion } from "framer-motion";
import { Building2, BookOpen, ShieldCheck, ArrowRight, Briefcase } from "lucide-react";
import Link from "next/link";

export default function MetaversoLanding() {
    return (
        <div className="min-h-screen bg-[#0A0A0A] text-white flex flex-col items-center justify-center p-6 font-sans">
            <div className="max-w-4xl w-full space-y-12">

                <header className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-2 text-brand text-xs font-black uppercase tracking-[0.3em] bg-brand/10 w-fit mx-auto px-4 py-1.5 rounded-full border border-brand/20">
                        <ShieldCheck className="w-4 h-4" /> Ecosistema Metaverso Otec
                    </div>
                    <h1 className="text-5xl md:text-7xl font-black tracking-tighter">Bienvenido al <span className="text-brand">Administrador</span></h1>
                    <p className="text-white/40 text-lg font-medium">Control maestro de infraestructura educativa y clientes corporativos.</p>
                </header>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link href="/admin/metaverso/login?target=empresas" className="group">
                        <motion.div whileHover={{ scale: 1.02 }} className="glass p-8 h-full border-brand/20 hover:border-brand transition-all space-y-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <ShieldCheck className="w-24 h-24" />
                            </div>
                            <div className="w-14 h-14 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                <ShieldCheck className="w-7 h-7 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-xl font-black uppercase tracking-tight">Master Admin</h3>
                                <p className="text-white/40 text-xs leading-relaxed">Control global de la plataforma y configuración maestro.</p>
                            </div>
                            <div className="flex items-center gap-2 text-brand text-[10px] font-black uppercase tracking-widest pt-4">
                                Configurar <ArrowRight className="w-3 h-3" />
                            </div>
                        </motion.div>
                    </Link>

                    <Link href="/admin/metaverso/cursos" className="group">
                        <motion.div whileHover={{ scale: 1.02 }} className="glass p-8 h-full border-brand/20 hover:border-brand transition-all space-y-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <BookOpen className="w-24 h-24" />
                            </div>
                            <div className="w-14 h-14 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                <BookOpen className="w-7 h-7 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-xl font-black uppercase tracking-tight">Administrar Cursos</h3>
                                <p className="text-white/40 text-xs leading-relaxed">Diseño técnico de currículum y bancos de preguntas.</p>
                            </div>
                            <div className="flex items-center gap-2 text-brand text-[10px] font-black uppercase tracking-widest pt-4">
                                Configurar <ArrowRight className="w-3 h-3" />
                            </div>
                        </motion.div>
                    </Link>

                    <Link href="/admin/metaverso/cargos" className="group">
                        <motion.div whileHover={{ scale: 1.02 }} className="glass p-8 h-full border-brand/20 hover:border-brand transition-all space-y-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <Briefcase className="w-24 h-24" />
                            </div>
                            <div className="w-14 h-14 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                <Briefcase className="w-7 h-7 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-xl font-black uppercase tracking-tight">Gestión de Cargos</h3>
                                <p className="text-white/40 text-xs leading-relaxed">Definición de perfiles y cargos para el registro.</p>
                            </div>
                            <div className="flex items-center gap-2 text-brand text-[10px] font-black uppercase tracking-widest pt-4">
                                Gestionar <ArrowRight className="w-3 h-3" />
                            </div>
                        </motion.div>
                    </Link>
                    <Link href="/admin/metaverso/encuestas" className="group">
                        <motion.div whileHover={{ scale: 1.02 }} className="glass p-8 h-full border-brand/20 hover:border-brand transition-all space-y-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <Briefcase className="w-24 h-24" />
                            </div>
                            <div className="w-14 h-14 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                <Briefcase className="w-7 h-7 text-brand" />
                            </div>
                            <div className="space-y-2">
                                <h3 className="text-xl font-black uppercase tracking-tight">Gestión de Encuestas</h3>
                                <p className="text-white/40 text-xs leading-relaxed">Creación de plantillas maestras de satisfacción y feedback.</p>
                            </div>
                            <div className="flex items-center gap-2 text-brand text-[10px] font-black uppercase tracking-widest pt-4">
                                Administrar <ArrowRight className="w-3 h-3" />
                            </div>
                        </motion.div>
                    </Link>                </div>

                <footer className="text-center pt-10">
                    <p className="text-white/20 text-[10px] font-black uppercase tracking-widest">© 2026 Metaverso Otec S.A. - All Systems Operational</p>
                </footer>
            </div>
        </div>
    );
}
