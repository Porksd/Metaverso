"use client";

import { motion } from "framer-motion";
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Briefcase,
    Building2,
    CheckCircle2,
    GraduationCap,
    ShieldCheck,
    Sparkles
} from "lucide-react";
import Link from "next/link";
import CommercialDemoGuide from "@/components/CommercialDemoGuide";

export default function CommercialPresentation() {
    const accessCards = [
        {
            href: "/demo/empresa-vista",
            title: "Operación Ejecutiva",
            description: "Control global de empresas, cupos, cumplimiento y acceso a decisiones críticas.",
            cta: "Entrar al Panel",
            icon: ShieldCheck
        },
        {
            href: "/demo/empresa-vista",
            title: "Arquitectura de Cursos",
            description: "Diseña rutas formativas, banco evaluativo y estandarización técnica de contenidos.",
            cta: "Diseñar Oferta",
            icon: BookOpen
        },
        {
            href: "/demo/empresa-vista",
            title: "Gobierno de Roles",
            description: "Define perfiles laborales y controla la asignación de aprendizaje por función.",
            cta: "Configurar Roles",
            icon: Briefcase
        },
        {
            href: "/demo/empresa-vista",
            title: "Inteligencia de Satisfacción",
            description: "Mide percepción, detecta fricciones y transforma feedback en planes de mejora.",
            cta: "Analizar Señales",
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
                <source src="/techvideo02.mp4?v=demo-home-4" type="video/mp4" />
                <source src="/techvideo01.mov?v=demo-home-4" type="video/quicktime" />
            </video>

            <div className="absolute inset-0 z-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.42)_0%,rgba(2,6,23,0.84)_50%,rgba(2,6,23,0.95)_100%)]" />
            <div className="fixed inset-0 z-0 pointer-events-none">
                <div className="absolute top-[-16%] right-[-12%] w-[48%] h-[48%] bg-cyan-500/15 rounded-full blur-[130px]" />
                <div className="absolute bottom-[-18%] left-[-18%] w-[52%] h-[52%] bg-brand/12 rounded-full blur-[140px]" />
            </div>
            <div className="max-w-7xl w-full space-y-10 md:space-y-12 relative z-10">

                <header className="pt-6 text-center space-y-5">
                    <div className="flex items-center justify-center gap-2 text-brand text-xs font-black uppercase tracking-[0.28em] bg-brand/10 w-fit mx-auto px-4 py-1.5 rounded-full border border-brand/20">
                        <ShieldCheck className="w-4 h-4" /> Ecosistema Metaverso Otec
                    </div>
                    <h1 className="text-4xl md:text-6xl xl:text-7xl font-black tracking-tight leading-[0.95]">
                        Vende confianza, escala capacitación
                        <span className="block text-brand">y demuestra control en vivo</span>
                    </h1>
                    <p className="max-w-4xl mx-auto text-white/65 text-sm md:text-lg font-medium leading-relaxed">
                        Plataforma corporativa para gestionar formación, cumplimiento y desempeño con evidencia trazable por empresa,
                        colaborador y curso. Diseñada para convencer en la demo y sostener la operación diaria.
                    </p>

                    <div className="flex flex-col sm:flex-row gap-3 justify-center pt-2">
                        <Link href="/demo/empresa-vista" className="px-6 py-3 rounded-xl bg-cyan-400 text-slate-950 font-black uppercase tracking-widest text-[11px] hover:scale-[1.02] transition-transform inline-flex items-center justify-center gap-2">
                            Ver Experiencia Empresa <ArrowRight className="w-4 h-4" />
                        </Link>
                        <Link href="/demo/alumno-login" className="px-6 py-3 rounded-xl bg-white/5 border border-white/15 text-white font-black uppercase tracking-widest text-[11px] hover:border-cyan-400/40 hover:text-cyan-300 transition-colors inline-flex items-center justify-center gap-2">
                            Ver Experiencia Alumno <GraduationCap className="w-4 h-4" />
                        </Link>
                    </div>

                    <div className="pt-2 flex justify-center">
                        <CommercialDemoGuide isDemo={true} />
                    </div>
                </header>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                    {[
                        { label: "Empresas activas", value: "30+" },
                        { label: "Cursos trazables", value: "120+" },
                        { label: "Cobertura operativa", value: "24/7" },
                        { label: "Decisiones con datos", value: "Tiempo real" }
                    ].map((kpi) => (
                        <div key={kpi.label} className="rounded-2xl border border-white/15 bg-black/30 backdrop-blur-md px-5 py-4">
                            <p className="text-[10px] uppercase tracking-[0.2em] font-black text-cyan-300/85">{kpi.label}</p>
                            <p className="mt-2 text-2xl font-black text-white">{kpi.value}</p>
                        </div>
                    ))}
                </div>

                <section className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="glass rounded-3xl p-6 md:p-8 border-white/10 space-y-4 bg-black/25">
                        <div className="inline-flex items-center gap-2 text-[10px] uppercase font-black tracking-[0.22em] text-cyan-300">
                            <Sparkles className="w-3.5 h-3.5" /> Ruta sugerida para demo comercial
                        </div>
                        <ol className="space-y-3">
                            {[
                                "Muestra control global en Operación Ejecutiva",
                                "Explora vistas de empresa y alumno con datos precargados",
                                "Cierra con insights de encuestas y mejora continua"
                            ].map((step, index) => (
                                <li key={step} className="flex items-start gap-3 text-sm text-white/85">
                                    <span className="mt-0.5 w-6 h-6 rounded-full bg-cyan-400/20 border border-cyan-300/40 text-cyan-200 text-xs font-black flex items-center justify-center">{index + 1}</span>
                                    <span>{step}</span>
                                </li>
                            ))}
                        </ol>
                    </div>

                    <div className="glass rounded-3xl p-6 md:p-8 border-white/10 bg-black/25">
                        <p className="text-[10px] uppercase tracking-[0.22em] font-black text-brand">Valor que percibe el cliente empresa</p>
                        <div className="mt-4 space-y-3 text-sm text-white/75">
                            <p className="flex items-start gap-2"><CheckCircle2 className="w-4 h-4 text-brand mt-0.5" /> Evidencia clara para auditorias y certificaciones.</p>
                            <p className="flex items-start gap-2"><CheckCircle2 className="w-4 h-4 text-brand mt-0.5" /> Seguimiento por colaborador, rol y brecha de cumplimiento.</p>
                            <p className="flex items-start gap-2"><CheckCircle2 className="w-4 h-4 text-brand mt-0.5" /> Decisiones de formacion con indicadores de avance reales.</p>
                        </div>
                        <div className="mt-6">
                            <Link href="/demo/empresa-vista" className="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand text-black font-black uppercase tracking-widest text-[11px] hover:scale-[1.02] transition-transform">
                                Iniciar Recorrido Comercial <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </section>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {accessCards.map((card, index) => {
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
                    <p className="text-white/25 text-[10px] font-black uppercase tracking-[0.16em]">Metaverso Otec | Demostración Comercial - Plataforma de formacion corporativa orientada a resultados</p>
                </footer>
            </div>
        </div>
    );
}
