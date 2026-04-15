"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { Lock, ArrowRight, Mail, Loader2 } from "lucide-react";
import { useRouter, useParams } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { resolveAdminRole } from "@/lib/adminAuth";
import CompanyLogo from "@/components/CompanyLogo";

type CompanyInfo = {
    name: string;
    id: string;
    slug?: string;
    logo_url?: string;
    logo_url_dark?: string;
    logo_url_light?: string;
    primary_color?: string;
    secondary_color?: string;
};

export default function EmpresaPortalLogin() {
    const params = useParams();
    const slugParam = params.slug;
    const slug = decodeURIComponent((Array.isArray(slugParam) ? slugParam[0] : slugParam || '').toString()).trim().toLowerCase();
    const [email, setEmail] = useState("");
    const [pass, setPass] = useState("");
    const [loading, setLoading] = useState(false);
    const [companyInfo, setCompanyInfo] = useState<CompanyInfo | null>(null);
    const [errorMsg, setErrorMsg] = useState("");
    const [isAuthenticating, setIsAuthenticating] = useState(true);
    const [isCompanyLoading, setIsCompanyLoading] = useState(true);
    const router = useRouter();

    const resolveCompanyBySlug = async (fields: string): Promise<CompanyInfo | null> => {
        const { data, error } = await supabase
            .from('companies')
            .select(fields)
            .ilike('slug', slug)
            .limit(1);

        if (error) throw error;
        if (data && data.length > 0) return data[0] as unknown as CompanyInfo;

        const { data: fallbackData, error: fallbackError } = await supabase
            .from('companies')
            .select(fields)
            .not('slug', 'is', null);

        if (fallbackError) throw fallbackError;

        const fallbackCompanies = (fallbackData ?? []) as unknown as CompanyInfo[];
        return fallbackCompanies.find((company) => company.slug?.toString().trim().toLowerCase() === slug) ?? null;
    };

    useEffect(() => {
        let isCancelled = false;

        const bootstrap = async () => {
            setIsAuthenticating(true);
            setIsCompanyLoading(true);
            setErrorMsg("");

            if (!slug) {
                if (!isCancelled) {
                    setErrorMsg("Empresa no encontrada.");
                    setIsCompanyLoading(false);
                    setIsAuthenticating(false);
                }
                return;
            }

            try {
                const company = await resolveCompanyBySlug('id, name, slug, logo_url, logo_url_dark, logo_url_light, primary_color, secondary_color');

                if (isCancelled) return;

                if (!company) {
                    setCompanyInfo(null);
                    setErrorMsg("Empresa no encontrada.");
                    setIsCompanyLoading(false);
                    setIsAuthenticating(false);
                    return;
                }

                setCompanyInfo(company);
                setIsCompanyLoading(false);

                const { data: { session } } = await supabase.auth.getSession();
                if (isCancelled) return;

                if (session) {
                    const email = session.user.email?.toLowerCase();
                    const { role } = await resolveAdminRole(supabase, email, '/admin/empresa/portal');

                    if (isCancelled) return;

                    if (role) {
                        sessionStorage.setItem('empresa_id', company.id);
                        sessionStorage.setItem('empresa_name', company.name);
                        sessionStorage.setItem('empresa_slug', company.slug || slug);
                        sessionStorage.setItem('is_master_admin', 'true');
                        sessionStorage.setItem('master_role', role);
                        sessionStorage.setItem('master_entry_mode', 'managed-tab');
                        sessionStorage.setItem('master_return_url', '/admin/metaverso');
                        console.log("Acceso Maestro detectado: Redirigiendo al Dashboard...");
                        router.push('/admin/empresa');
                        return;
                    }
                }
            } catch (error) {
                console.error('Error resolving company admin portal:', error);
                if (!isCancelled) {
                    setCompanyInfo(null);
                    setErrorMsg("Empresa no encontrada.");
                }
            } finally {
                if (!isCancelled) {
                    setIsAuthenticating(false);
                    setIsCompanyLoading(false);
                }
            }
        };

        bootstrap();

        return () => {
            isCancelled = true;
        };
    }, [slug, router]);

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        try {
            const { data, error } = await supabase
                .from('companies')
                .select('id, name, email, password')
                .eq('id', companyInfo?.id)
                .eq('email', email)
                .eq('password', pass)
                .single();

            if (error || !data) {
                alert("Credenciales incorrectas.");
            } else {
                sessionStorage.removeItem('is_master_admin');
                sessionStorage.removeItem('master_role');
                sessionStorage.removeItem('master_entry_mode');
                sessionStorage.removeItem('master_return_url');
                localStorage.setItem('empresa_id', data.id);
                localStorage.setItem('empresa_name', data.name);
                localStorage.setItem('empresa_slug', companyInfo?.slug || slug); // Store canonical slug for logout redirect
                router.push("/admin/empresa");
            }
        } catch (err) {
            console.error(err);
            alert("Error al intentar ingresar.");
        } finally {
            setLoading(false);
        }
    };

    if (errorMsg) return (
        <div className="relative min-h-screen text-white flex items-center justify-center p-6">
            <img src="/empresa_background.jpg" alt="" className="absolute inset-0 w-full h-full object-cover object-center z-0" />
            <div className="absolute inset-0 bg-black/60 z-0" />
            <p className="relative z-10 text-red-500 font-bold">{errorMsg}</p>
        </div>
    );

    if (isAuthenticating || isCompanyLoading) return (
        <div className="relative min-h-screen flex flex-col items-center justify-center space-y-4">
            <img src="/empresa_background.jpg" alt="" className="absolute inset-0 w-full h-full object-cover object-center z-0" />
            <div className="absolute inset-0 bg-black/60 z-0" />
            <Loader2 className="relative z-10 w-10 h-10 text-brand animate-spin" />
            <p className="relative z-10 text-white/40 text-xs font-black uppercase tracking-widest">Validando Acceso Maestro...</p>
        </div>
    );

    return (
        <div className="relative min-h-screen text-white flex items-center justify-center p-6 font-sans">
            <img src="/empresa_background.jpg" alt="" className="absolute inset-0 w-full h-full object-cover object-center z-0" />
            <div className="absolute inset-0 bg-black/60 z-0" />
            <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} className="relative z-10 glass p-12 w-full max-w-md border-white/5 space-y-8 bg-white/[0.02]">

                <div className="text-center space-y-4">
                    <div className="mx-auto mb-6 flex justify-center">
                        <CompanyLogo
                            src={companyInfo?.logo_url}
                            darkSrc={companyInfo?.logo_url_dark}
                            lightSrc={companyInfo?.logo_url_light}
                            alt={companyInfo?.name || "Empresa"}
                            surface="light"
                            frameClassName="w-24 h-24 rounded-full p-4"
                            imageClassName="w-full h-full object-contain"
                            iconClassName="w-10 h-10 text-slate-700"
                        />
                    </div>
                    <p 
                        className="text-brand text-[10px] font-black uppercase tracking-[0.2em]"
                        style={companyInfo?.primary_color ? { color: companyInfo.primary_color } : {}}
                    >Portal Corporativo</p>
                    <h1 className="text-3xl font-black tracking-tight italic" style={companyInfo?.primary_color ? { color: companyInfo.primary_color } : {}}>
                        {companyInfo ? companyInfo.name : "..."}
                    </h1>
                    <p className="text-white/40 text-[9px] font-medium uppercase">Acceso Administrativo</p>
                </div>

                <form onSubmit={handleLogin} className="space-y-6">
                    <div className="space-y-4">
                        <div className="relative group">
                            <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                            <input
                                type="email"
                                placeholder="Email Administrativo"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                                className="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm focus:outline-none focus:border-brand/40"
                                style={companyInfo?.primary_color ? { transition: 'border-color 0.2s' } : {}}
                                onFocus={(e) => { if(companyInfo?.primary_color) e.target.style.borderColor = companyInfo.primary_color }}
                                onBlur={(e) => { e.target.style.borderColor = '' }}
                            />
                        </div>
                        <div className="relative group">
                            <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                            <input
                                type="password"
                                placeholder="Contraseña de Empresa"
                                value={pass}
                                onChange={(e) => setPass(e.target.value)}
                                required
                                className="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm focus:outline-none focus:border-brand/40"
                                style={companyInfo?.primary_color ? { transition: 'border-color 0.2s' } : {}}
                                onFocus={(e) => { if(companyInfo?.primary_color) e.target.style.borderColor = companyInfo.primary_color }}
                                onBlur={(e) => { e.target.style.borderColor = '' }}
                            />
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        disabled={loading || !companyInfo}
                        className="w-full py-4 bg-brand text-black disabled:opacity-50 rounded-2xl font-black uppercase tracking-widest text-[10px] flex items-center justify-center gap-2"
                        style={companyInfo?.primary_color ? { backgroundColor: companyInfo.primary_color } : {}}
                    >
                        {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : "Ingresar Gestión"}
                        {!loading && <ArrowRight className="w-4 h-4" />}
                    </button>
                </form>
            </motion.div>
        </div>
    );
}
