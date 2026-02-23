"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { ChevronLeft, LogOut, ShieldCheck, UserCog } from "lucide-react";
import { motion } from "framer-motion";

export default function AdminSidebar({ children }: { children: React.ReactNode }) {
    const router = useRouter();
    const pathname = usePathname();
    const [userRole, setUserRole] = useState<string | null>(null);
    const [userEmail, setUserEmail] = useState<string | null>(null);
    const [isAuthorized, setIsAuthorized] = useState<boolean | null>(null);

    useEffect(() => {
        const checkAuth = async () => {
            const { data: { session } } = await supabase.auth.getSession();
            if (!session) {
                // If we're already on login, don't redirect
                if (pathname.includes('/login')) return;
                router.push("/admin/metaverso/login");
                return;
            }

            const email = session.user.email?.toLowerCase();
            setUserEmail(email || null);
            const { data: profile } = await supabase.from('admin_profiles').select('role').eq('email', email).maybeSingle();

            const superAdmins = ['apacheco@lobus.cl', 'porksde@gmail.com', 'm.poblete.m@gmail.com', 'soporte@lobus.cl', 'apacheco@metaversotec.com'];
            const editors = ['admin@metaversotec.com'];

            let role: string | null = null;
            if (profile) role = profile.role;
            else if (email && superAdmins.includes(email)) role = 'superadmin';
            else if (email && editors.includes(email)) role = 'editor';

            if (role) {
                setUserRole(role);
                setIsAuthorized(true);
            } else {
                setIsAuthorized(false);
                if (!pathname.includes('/login')) router.push("/admin/metaverso/login?error=unauthorized");
            }
        };
        checkAuth();
    }, [pathname, router]);

    const isMainAdmin = pathname === '/admin/metaverso';
    const isLoginPage = pathname.includes('/login');

    if (isLoginPage) return <>{children}</>;
    if (isAuthorized === false) return null;

    return (
        <div className="min-h-screen bg-[#060606] text-white flex flex-col">
            {/* Nav Bar Superior (Sticky p/ navegación rápida) */}
            <header className="sticky top-0 z-[100] bg-black/60 backdrop-blur-xl border-b border-white/5 p-4">
                <div className="max-w-7xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        {!isMainAdmin && (
                            <button 
                                onClick={() => router.push('/admin/metaverso')}
                                className="p-2 rounded-xl bg-white/5 border border-white/10 hover:bg-brand/10 hover:text-brand transition-all flex items-center gap-2 text-[10px] font-black uppercase tracking-widest"
                            >
                                <ChevronLeft className="w-4 h-4" /> Volver al Inicio
                            </button>
                        )}
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-lg bg-brand/10 border border-brand/20 flex items-center justify-center">
                                <ShieldCheck className="w-4 h-4 text-brand" />
                            </div>
                            <span className="text-xs font-black uppercase tracking-tighter">
                                Metaverso <span className="text-brand">Admin</span>
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        {userEmail && (
                            <div className="hidden md:flex flex-col items-end">
                                <span className="text-[9px] font-black uppercase text-white/40 tracking-widest">Identificado como:</span>
                                <span className="text-[10px] font-bold text-brand">{userEmail}</span>
                            </div>
                        )}
                        <div className="flex items-center gap-2 border-l border-white/10 pl-4">
                            {userRole === 'superadmin' && (
                                <button 
                                    onClick={() => router.push('/admin/metaverso/usuarios')}
                                    className="p-2.5 rounded-xl bg-white/5 border border-white/10 text-white/40 hover:text-white transition-all hover:bg-brand/10 hover:text-brand"
                                    title="Gestión de Usuarios"
                                >
                                    <UserCog className="w-4 h-4" />
                                </button>
                            )}
                            <button 
                                onClick={async () => {
                                    await supabase.auth.signOut();
                                    localStorage.clear();
                                    router.push('/admin/metaverso/login');
                                }}
                                className="p-2.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 hover:bg-red-500 hover:text-black transition-all"
                                title="Cerrar Sesión"
                            >
                                <LogOut className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main className="flex-1">
                {children}
            </main>
        </div>
    );
}
