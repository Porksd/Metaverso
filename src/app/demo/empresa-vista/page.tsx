"use client";

import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import { Users, GraduationCap, Settings2, ArrowRight, BarChart3, ShieldCheck, ArrowLeft } from "lucide-react";
import Link from "next/link";
import { supabase } from "@/lib/supabase";

export default function DemoEmpresaPortal() {
    const DEMO_COMPANY_ID = "99999999-9999-9999-9999-999999999999";
    const [company, setCompany] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchCompany = async () => {
            const { data, error } = await supabase
                .from('companies')
                .select('*')
                .eq('id', DEMO_COMPANY_ID)
                .single();
            
            if (data) setCompany(data);
            setLoading(false);
        };
        fetchCompany();
    }, []);

    if (loading) {
        return <div className="min-h-screen bg-black flex items-center justify-center text-white">Cargando...</div>;
    }

    if (!company) {
        return (
            <div className="min-h-screen bg-black flex flex-col items-center justify-center text-white p-6">
                <h1 className="text-3xl font-bold mb-4">Empresa Demo no encontrada</h1>
                <Link href="/demo" className="px-6 py-3 bg-brand text-black font-bold rounded-lg hover:scale-105 transition-transform">
                    Volver a Presentación
                </Link>
            </div>
        );
    }

    return (
        <div className="min-h-screen text-white flex flex-col items-center justify-center p-6 md:p-10 font-sans relative overflow-hidden">
            <div className="absolute inset-0 bg-gradient-to-br from-slate-950 to-black z-0" />
            <div className="fixed inset-0 z-0 pointer-events-none">
                <div className="absolute top-[-12%] right-[-10%] w-[45%] h-[45%] bg-cyan-400/14 rounded-full blur-[130px]" />
                <div className="absolute bottom-[-20%] left-[-15%] w-[55%] h-[55%] bg-brand/12 rounded-full blur-[140px]" />
            </div>

            {/* Botón de retorno */}
            <Link href="/demo" className="fixed top-6 left-6 z-50 flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 border border-white/20 text-white text-sm font-semibold hover:bg-white/20 transition-colors">
                <ArrowLeft className="w-4 h-4" /> Volver a Presentación
            </Link>

            <div className="relative z-10 w-full flex flex-col items-center">
                <div className="max-w-6xl w-full space-y-10">

                    <header className="flex flex-col items-center text-center space-y-4 pt-10">
                        {company.logo_url && (
                            <img src={company.logo_url} className="w-24 mb-4" alt={company.name} />
                        )}
                        <div className="inline-flex items-center gap-2 text-cyan-300 text-[10px] font-black uppercase tracking-[0.22em] border border-cyan-400/30 bg-cyan-400/10 rounded-full px-4 py-1.5">
                            <ShieldCheck className="w-3.5 h-3.5" /> Portal Corporativo Demo
                        </div>
                        <h1 className="text-4xl md:text-6xl font-black tracking-tight leading-[0.95]">{company.welcome_title || "Formación con control"}<span className="block text-brand">resultados y trazabilidad</span></h1>
                        <p className="text-white/65 text-base md:text-lg font-medium max-w-3xl">{company.welcome_message || "Muestra a tu equipo una operación de capacitación robusta: avance visible, evidencia auditable y foco en desempeño real."}</p>
                    </header>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {[
                            { icon: Users, label: "Colaboradores activos", value: "150+" },
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
                        <div className="group">
                            <motion.div whileHover={{ y: -5 }} className="glass p-10 h-full border-white/10 hover:border-cyan-300/40 transition-all space-y-6 relative overflow-hidden bg-black/30">
                                <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity">
                                    <Settings2 className="w-32 h-32" />
                                </div>
                                <div className="w-16 h-16 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                    <Users className="w-8 h-8 text-brand" />
                                </div>
                                <div className="space-y-2">
                                    <h3 className="text-2xl font-black uppercase tracking-tight">Gestión de Colaboradores</h3>
                                    <p className="text-white/55 text-sm leading-relaxed">Visualiza el avance de formación por colaborador, rol y cumplimiento de objetivos de capacitación.</p>
                                </div>
                                <div className="flex items-center gap-2 text-cyan-300 text-xs font-black uppercase tracking-widest pt-4">
                                    Explorar Datos <ArrowRight className="w-4 h-4" />
                                </div>
                            </motion.div>
                        </div>

                        <div className="group">
                            <motion.div whileHover={{ y: -5 }} className="glass p-10 h-full border-white/10 hover:border-cyan-300/40 transition-all space-y-6 relative overflow-hidden bg-black/30">
                                <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity">
                                    <BarChart3 className="w-32 h-32" />
                                </div>
                                <div className="w-16 h-16 rounded-2xl bg-brand/10 flex items-center justify-center border border-brand/20">
                                    <BarChart3 className="w-8 h-8 text-brand" />
                                </div>
                                <div className="space-y-2">
                                    <h3 className="text-2xl font-black uppercase tracking-tight">Reportes & Insights</h3>
                                    <p className="text-white/55 text-sm leading-relaxed">Accede a métricas de satisfacción, cumplimiento y ROI de capacitación para decisiones informadas.</p>
                                </div>
                                <div className="flex items-center gap-2 text-cyan-300 text-xs font-black uppercase tracking-widest pt-4">
                                    Ver Reportes <ArrowRight className="w-4 h-4" />
                                </div>
                            </motion.div>
                        </div>
                    </div>

                    <div className="text-center space-y-4">
                        <p className="text-white/45 text-sm">Empresa Demo precargada con datos ilustrativos | {company.name}</p>
                        <Link href="/demo" className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white/5 border border-white/15 text-white font-bold uppercase tracking-wider text-[11px] hover:border-cyan-400/40 hover:text-cyan-300 transition-colors">
                            <ArrowLeft className="w-4 h-4" /> Volver a Presentación Comercial
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
