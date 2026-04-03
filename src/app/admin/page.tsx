"use client";

import { motion } from "framer-motion";
import {
    ArrowRight,
    Building2,
    Briefcase,
    BookOpen,
    BarChart3,
    GraduationCap,
    ShieldCheck
} from "lucide-react";
import Link from "next/link";

export default function MetaversoLanding() {
    const modules = [
        {
            href: "/admin/metaverso",
            title: "Empresas",
            description: "Administra empresas, cupos y acceso operativo.",
            cta: "Abrir Empresas",
            icon: Building2
        },
        {
            href: "/admin/metaverso/cargos",
            title: "Cargos",
            description: "Define perfiles y asignación formativa por rol.",
            cta: "Abrir Cargos",
            icon: Briefcase
        },
        {
            href: "/admin/metaverso/cursos",
            title: "Cursos",
            description: "Gestiona contenidos, evaluaciones y rutas.",
            cta: "Abrir Cursos",
            icon: BookOpen
        },
        {
            href: "/admin/metaverso/encuestas",
            title: "Encuestas",
            description: "Revisa feedback, métricas e indicadores de satisfacción.",
            cta: "Abrir Encuestas",
            icon: BarChart3
        }
    ] as const;

    return (
        <div className="min-h-screen relative text-white flex flex-col items-center p-6 md:p-10 font-sans overflow-hidden">
            <video
                className="absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none"
                autoPlay
                muted
                loop
                playsInline
                poster="/app_background.jpg"
            >
                <source src="/techvideo02.mp4?v=admin-hub-1" type="video/mp4" />
                <source src="/techvideo01.mov?v=admin-hub-1" type="video/quicktime" />
            </video>

            <div className="absolute inset-0 z-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.42)_0%,rgba(2,6,23,0.84)_50%,rgba(2,6,23,0.95)_100%)]" />
            <div className="fixed inset-0 z-0 pointer-events-none">
                <div className="absolute top-[-16%] right-[-12%] w-[48%] h-[48%] bg-cyan-500/15 rounded-full blur-[130px]" />
                <div className="absolute bottom-[-18%] left-[-18%] w-[52%] h-[52%] bg-brand/12 rounded-full blur-[140px]" />
            </div>
            <div className="max-w-7xl w-full space-y-10 md:space-y-12 relative z-10">

                <header className="pt-6 text-center space-y-5">
                    <div className="flex items-center justify-center gap-2 text-brand text-xs font-black uppercase tracking-[0.28em] bg-brand/10 w-fit mx-auto px-4 py-1.5 rounded-full border border-brand/20">
                        <ShieldCheck className="w-4 h-4" /> Acceso Administrativo
                    </div>
                    <h1 className="text-4xl md:text-6xl xl:text-7xl font-black tracking-tight leading-[0.95]">
                        Panel central
                        <span className="block text-brand">de administradores</span>
                    </h1>
                    <p className="max-w-4xl mx-auto text-white/65 text-sm md:text-lg font-medium leading-relaxed">
                        Gestión completa del ecosistema: empresas, cargos, cursos y encuestas.
                        Desde aquí accedes a todos los módulos operativos.
                    </p>

                    <div className="flex flex-col sm:flex-row gap-3 justify-center pt-2">
                        <Link href="/admin/metaverso" className="px-6 py-3 rounded-xl bg-cyan-400 text-slate-950 font-black uppercase tracking-widest text-[11px] hover:scale-[1.02] transition-transform inline-flex items-center justify-center gap-2">
                            Ir al Dashboard Admin <ArrowRight className="w-4 h-4" />
                        </Link>
                        <Link href="/demo" className="px-6 py-3 rounded-xl bg-white/5 border border-white/15 text-white font-black uppercase tracking-widest text-[11px] hover:border-cyan-400/40 hover:text-cyan-300 transition-colors inline-flex items-center justify-center gap-2">
                            Abrir Presentacion Demo <GraduationCap className="w-4 h-4" />
                        </Link>
                    </div>
                </header>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {modules.map((card, index) => {
                        const Icon = card.icon;
                        return (
                            <Link href={card.href} className="group" key={card.title}>
                                <motion.div
                                    whileHover={{ y: -5 }}
                                    transition={{ type: "spring", stiffness: 260, damping: 18 }}
                                    className="glass p-7 h-full border-white/10 hover:border-cyan-300/35 transition-all space-y-5 relative overflow-hidden bg-black/30"
                                >
                                    <div className="absolute -right-8 -top-8 w-28 h-28 rounded-full bg-cyan-400/10 blur-3xl" />
                                    <div className="w-12 h-12 rounded-xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                        <Icon className="w-6 h-6 text-brand" />
                                    </div>
                                    <div className="space-y-2">
                                        <p className="text-[10px] font-black uppercase tracking-[0.18em] text-white/35">Modulo {index + 1}</p>
                                        <h3 className="text-xl font-black leading-tight">{card.title}</h3>
                                        <p className="text-white/50 text-sm leading-relaxed">{card.description}</p>
                                    </div>
                                    <div className="flex items-center gap-2 text-cyan-300 text-[10px] font-black uppercase tracking-widest pt-1">
                                        {card.cta} <ArrowRight className="w-3 h-3" />
                                    </div>
                                </motion.div>
                            </Link>
                        );
                    })}
                </div>

                <footer className="text-center pt-4 pb-2">
                    <p className="text-white/25 text-[10px] font-black uppercase tracking-[0.16em]">Metaverso Otec | Panel administrativo central</p>
                </footer>
            </div>
        </div>
    );
}
