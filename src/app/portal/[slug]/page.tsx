"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { motion } from "framer-motion";
import Link from "next/link";
import { Building2, ChevronRight, BookOpen, Lock, Globe } from "lucide-react";

export default function CompanyPortal() {
    const params = useParams();
    const slug = params.slug as string;

    const [company, setCompany] = useState<any>(null);
    const [courses, setCourses] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchPortalData = async () => {
            setLoading(true);
            try {
                // 1. Get Company by Slug
                const { data: comp, error: compError } = await supabase
                    .from('companies')
                    .select('*')
                    .eq('slug', slug)
                    .single();

                if (compError || !comp) {
                    throw new Error("Empresa no encontrada o enlace inválido.");
                }
                setCompany(comp);

                // 2. Get Courses linked to this company via company_courses join
                const { data: assignments, error: courseError } = await supabase
                    .from('company_courses')
                    .select('registration_mode, courses(*)')
                    .eq('company_id', comp.id);

                if (courseError) throw courseError;

                // Flatten the courses array and inject the specific registration_mode
                const courseData = assignments
                    ?.map((a: any) => ({
                        ...a.courses,
                        registration_mode: a.registration_mode || a.courses?.registration_mode || 'open'
                    }))
                    .filter((c: any) => c.id && c.is_active !== false) || [];

                setCourses(courseData);

            } catch (err: any) {
                console.error(err);
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        if (slug) fetchPortalData();
    }, [slug]);

    if (loading) return <div className="min-h-screen flex items-center justify-center text-white/40 font-mono text-sm animate-pulse">Cargando portal...</div>;
    if (error) return <div className="min-h-screen flex items-center justify-center text-red-500 font-bold">{error}</div>;

    return (
        <div className="relative min-h-screen flex flex-col items-center justify-center p-6 overflow-hidden">
            {/* Background Ambience */}
            <div className="absolute inset-0 z-0">
                <div className="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-brand/5 to-transparent opacity-20" />
                <div className="absolute bottom-0 right-0 w-[500px] h-[500px] rounded-full bg-blue-500/5 blur-[100px]" />
            </div>

            <div className="relative z-10 w-full max-w-5xl space-y-12">
                
                {/* Header */}
                <header className="text-center space-y-6">
                    {company.logo_url ? (
                        <div className="w-48 h-24 mx-auto relative mb-8">
                            <img 
                                src={company.logo_url} 
                                alt={company.name} 
                                className="w-full h-full object-contain filter drop-shadow-[0_0_20px_rgba(255,255,255,0.1)]" 
                            />
                        </div>
                    ) : (
                        <div className="w-24 h-24 mx-auto bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 mb-6">
                            <Building2 className="w-10 h-10 text-white/40" />
                        </div>
                    )}

                    <div className="space-y-4">
                        <h1 className="text-4xl md:text-5xl font-black uppercase tracking-tight">
                            {company.welcome_title || company.name}
                        </h1>
                        <p className="text-xl text-white/60 font-medium max-w-2xl mx-auto leading-relaxed">
                            {company.welcome_message || "Portal de Capacitación y Desarrollo Profesional"}
                        </p>
                    </div>
                </header>

                <div className="w-full h-px bg-gradient-to-r from-transparent via-white/10 to-transparent" />

                {/* Courses Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {courses.map((course, idx) => (
                        <Link href={`/portal/${slug}/curso/${course.code}`} key={course.id} className="group">
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: idx * 0.1 }}
                                className="glass p-8 rounded-3xl h-full border border-white/5 hover:border-brand/30 hover:bg-white/[0.03] transition-all group-hover:shadow-[0_0_30px_rgba(49,210,45,0.1)] relative overflow-hidden"
                            >
                                <div className="absolute top-4 right-4 text-[10px] font-black uppercase tracking-widest bg-white/5 px-2 py-1 rounded border border-white/5 text-white/40 group-hover:text-brand group-hover:border-brand/20 transition-colors">
                                    {course.code}
                                </div>

                                <div className="mb-6 mt-2">
                                    <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-white/5 to-transparent border border-white/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <BookOpen className="w-5 h-5 text-brand" />
                                    </div>
                                </div>

                                <h3 className="text-xl font-bold mb-3 leading-tight group-hover:text-brand transition-colors">
                                    {course.name}
                                </h3>
                                <p className="text-sm text-white/40 line-clamp-3 mb-6">
                                    {course.description || "Contenido exclusivo de capacitación corporativa."}
                                </p>

                                <div className="flex items-center justify-between mt-auto pt-6 border-t border-white/5">
                                    <span className="text-xs font-bold text-white/30 group-hover:text-white/60 transition-colors">
                                        {course.registration_mode === 'restricted' ? (
                                            <span className="flex items-center gap-1.5"><Lock className="w-3 h-3" /> Acceso Controlado</span>
                                        ) : (
                                            <span className="flex items-center gap-1.5"><Globe className="w-3 h-3" /> Registro Abierto</span>
                                        )}
                                    </span>
                                    <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-brand group-hover:text-black transition-all">
                                        <ChevronRight className="w-4 h-4" />
                                    </div>
                                </div>
                            </motion.div>
                        </Link>
                    ))}

                    {courses.length === 0 && (
                        <div className="col-span-full text-center py-20 text-white/20 font-mono text-sm">
                            No hay cursos disponibles en este momento.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
