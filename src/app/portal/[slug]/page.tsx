"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { motion } from "framer-motion";
import Link from "next/link";
import { ChevronRight, BookOpen, Lock, Globe } from "lucide-react";
import CompanyLogo from "@/components/CompanyLogo";

export default function CompanyPortal() {
    const params = useParams();
    const rawSlug = params.slug;
    const slug = decodeURIComponent((Array.isArray(rawSlug) ? rawSlug[0] : rawSlug || '').toString()).trim().toLowerCase();

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
                    .ilike('slug', slug)
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
        <div className="relative min-h-screen flex flex-col items-center justify-center p-4 sm:p-6">
            {/* Background image */}
            <img src="/alumno_background.jpg" alt="" aria-hidden="true"
                className="absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none select-none" />
            <div className="absolute inset-0 z-0 bg-black/60" />

            <div className="relative z-10 w-full max-w-5xl space-y-8 sm:space-y-12">
                
                {/* Header */}
                <header className="text-center space-y-6">
                    {company.logo_url ? (
                        <div className="mx-auto mb-8 flex justify-center">
                            <CompanyLogo
                                src={company.logo_url}
                                darkSrc={company.logo_url_dark}
                                lightSrc={company.logo_url_light}
                                alt={company.name}
                                surface="light"
                                frameClassName="w-40 h-24 sm:w-56 sm:h-28 rounded-[2rem] p-4 sm:p-5"
                                imageClassName="w-full h-full object-contain"
                            />
                        </div>
                    ) : (
                        <div className="mx-auto mb-6 flex justify-center">
                            <CompanyLogo alt={company.name} surface="light" frameClassName="w-24 h-24 rounded-3xl p-4" />
                        </div>
                    )}

                    <div className="space-y-4">
                        <h1 className="text-2xl sm:text-4xl md:text-5xl font-black uppercase tracking-tight">
                            {company.welcome_title || company.name}
                        </h1>
                        <p className="text-base sm:text-xl text-white/60 font-medium max-w-2xl mx-auto leading-relaxed">
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
                                className="glass p-5 sm:p-8 rounded-3xl h-full border border-white/5 hover:border-brand/30 hover:bg-white/[0.03] transition-all group-hover:shadow-[0_0_30px_rgba(49,210,45,0.1)] relative overflow-hidden"
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
