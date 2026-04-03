"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
    X,
    ChevronRight,
    ChevronLeft,
    BarChart3,
    Users,
    BookOpen,
    CheckCircle2,
    Zap,
    Target
} from "lucide-react";
import Link from "next/link";

interface DemoStep {
    title: string;
    description: string;
    context: string;
    cta: string;
    ctaHref: string;
    ctaDemoHref?: string;
    icon: any;
    highlights: string[];
}

const DEMO_STEPS: DemoStep[] = [
    {
        title: "Operación Ejecutiva",
        description: "Muestra el control integral de empresas, cupos utilizados y acceso a análisis críticos.",
        context:
            "El cliente ve de un vistazo: cantidad de empresas activas, cupos utilizados vs. disponibles, y capacidad de profundizar en cualquier empresa.",
        cta: "Ver Panel de Control",
        ctaHref: "/admin/metaverso",
        ctaDemoHref: "/demo/empresa-vista",
        icon: BarChart3,
        highlights: [
            "Dashboard con KPIs por empresa",
            "Seguimiento de cupos en tiempo real",
            "Acceso a cualquier empresa para audit",
            "Métricas de adopción y cumplimiento"
        ]
    },
    {
        title: "Arquitectura de Trazabilidad",
        description: "Explica cómo cada curso y rol genera evidencia auditable de competencia.",
        context:
            "Demuestra: cursos diseñados, cargos disponibles y cómo se vinculan para crear rutas certificables que generan evidencia para auditorías.",
        cta: "Explorar Cursos y Cargos",
        ctaHref: "/admin/metaverso/cursos",
        ctaDemoHref: "/demo/empresa-vista",
        icon: BookOpen,
        highlights: [
            "Banco de cursos con evaluaciones",
            "Definición de cargos por empresa",
            "Rutas formativas por rol",
            "Certificados con respaldo total"
        ]
    },
    {
        title: "Decisiones con Inteligencia",
        description: "Cierra con visualización de insights: encuestas, cumplimiento y mejora continua.",
        context:
            "Muestra que la plataforma no sólo ejecuta: recopila, analiza y propone mejoras. El cliente ve sostenibilidad operativa.",
        cta: "Consultar Indicadores",
        ctaHref: "/admin/metaverso/encuestas",
        ctaDemoHref: "/demo/empresa-vista",
        icon: Target,
        highlights: [
            "Encuestas de satisfacción y feedback",
            "Análisis de brecha de cumplimiento",
            "Tendencias de desempeño",
            "Seguimiento de mejora continua"
        ]
    }
];

type CommercialDemoGuideProps = {
    isDemo?: boolean;
};

export default function CommercialDemoGuide({ isDemo = false }: CommercialDemoGuideProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [currentStep, setCurrentStep] = useState(0);

    const step = DEMO_STEPS[currentStep];
    const StepIcon = step.icon;
    const stepHref = isDemo ? step.ctaDemoHref || step.ctaHref : step.ctaHref;

    const handleNext = () => {
        if (currentStep < DEMO_STEPS.length - 1) {
            setCurrentStep(currentStep + 1);
        }
    };

    const handlePrev = () => {
        if (currentStep > 0) {
            setCurrentStep(currentStep - 1);
        }
    };

    const handleClose = () => {
        setIsOpen(false);
        setCurrentStep(0);
    };

    const handleStepClick = (index: number) => {
        setCurrentStep(index);
    };

    return (
        <>
            <button
                onClick={() => setIsOpen(true)}
                className="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-cyan-400 to-cyan-300 text-slate-950 font-black uppercase tracking-widest text-[11px] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all hover:scale-[1.02] active:scale-95"
            >
                <Zap className="w-4 h-4" />
                Iniciar Tour Comercial
            </button>

            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-[200] bg-black/70 backdrop-blur-md flex items-center justify-center p-4"
                        onClick={handleClose}
                    >
                        <motion.div
                            initial={{ scale: 0.92, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            exit={{ scale: 0.92, opacity: 0 }}
                            transition={{ type: "spring", stiffness: 260, damping: 18 }}
                            onClick={(e) => e.stopPropagation()}
                            className="relative w-full max-w-2xl rounded-3xl border border-white/15 bg-[linear-gradient(135deg,rgba(15,23,42,0.95)_0%,rgba(2,6,23,0.98)_100%)] p-8 md:p-10 space-y-6 shadow-2xl"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex items-start gap-4 flex-1">
                                    <div className="w-14 h-14 rounded-2xl bg-cyan-400/15 border border-cyan-300/30 flex items-center justify-center shrink-0">
                                        <StepIcon className="w-7 h-7 text-cyan-300" />
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-300">
                                            Paso {currentStep + 1} de {DEMO_STEPS.length}
                                        </p>
                                        <h2 className="text-3xl font-black mt-1">{step.title}</h2>
                                        <p className="text-white/65 text-sm leading-relaxed mt-2">{step.description}</p>
                                    </div>
                                </div>
                                <button
                                    onClick={handleClose}
                                    className="p-2 rounded-xl hover:bg-white/10 transition-colors"
                                >
                                    <X className="w-5 h-5 text-white/40" />
                                </button>
                            </div>

                            <div className="rounded-2xl border border-white/10 bg-black/30 p-5 space-y-3">
                                <p className="text-[10px] font-black uppercase tracking-[0.2em] text-white/50">Lo que explicarás aquí:</p>
                                <p className="text-white/75 leading-relaxed">{step.context}</p>
                                <div className="pt-2 space-y-2.5">
                                    {step.highlights.map((highlight) => (
                                        <div key={highlight} className="flex items-start gap-2 text-sm text-white/70">
                                            <CheckCircle2 className="w-4 h-4 text-cyan-400 mt-0.5 shrink-0" />
                                            <span>{highlight}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <Link
                                    href={stepHref}
                                    onClick={handleClose}
                                    className="flex-1 px-4 py-3 rounded-xl bg-cyan-400 text-slate-950 font-black uppercase tracking-widest text-[11px] hover:scale-[1.02] transition-transform text-center"
                                >
                                    {step.cta}
                                </Link>
                                <button
                                    onClick={() => window.open(stepHref, "_blank")}
                                    className="px-4 py-3 rounded-xl bg-white/10 hover:bg-white/15 text-white/60 hover:text-white transition-colors font-bold text-[11px] uppercase"
                                    title="Abrir en nueva pestaña"
                                >
                                    ↗
                                </button>
                            </div>

                            <div className="flex items-center justify-between gap-3 pt-2">
                                <button
                                    onClick={handlePrev}
                                    disabled={currentStep === 0}
                                    className="p-2.5 rounded-xl border border-white/10 text-white/40 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-white/5 transition-colors"
                                >
                                    <ChevronLeft className="w-5 h-5" />
                                </button>

                                <div className="flex items-center gap-2">
                                    {DEMO_STEPS.map((_, index) => (
                                        <button
                                            key={index}
                                            onClick={() => handleStepClick(index)}
                                            className={`h-2 rounded-full transition-all ${
                                                index === currentStep
                                                    ? "bg-cyan-400 w-8"
                                                    : "bg-white/15 w-2 hover:bg-white/25"
                                            }`}
                                        />
                                    ))}
                                </div>

                                <button
                                    onClick={handleNext}
                                    disabled={currentStep === DEMO_STEPS.length - 1}
                                    className="p-2.5 rounded-xl border border-white/10 text-white/40 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-white/5 transition-colors"
                                >
                                    <ChevronRight className="w-5 h-5" />
                                </button>
                            </div>

                            <p className="text-[10px] text-white/20 uppercase tracking-[0.15em] text-center">
                                Tip: Abre en nueva pestaña para comparar pasos en paralelo
                            </p>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
}
