"use client";

import { Suspense } from "react";
import { useState } from "react";
import { motion } from "framer-motion";
import { ShieldCheck, Lock, ArrowRight, User } from "lucide-react";
import { useRouter, useSearchParams } from "next/navigation";

import { supabase } from "@/lib/supabase";

function LoginForm() {
    const [email, setEmail] = useState("apacheco@lobus.cl");
    const [pass, setPass] = useState("");
    const [loading, setLoading] = useState(false);
    const router = useRouter();
    const searchParams = useSearchParams();
    const returnUrl = searchParams.get('returnUrl') || "/admin/metaverso";

    const handleLogin = async (e: React.FormEvent) => {
        // ... (rest of the handleLogin logic same as before)
        e.preventDefault();
        setLoading(true);

        const { data, error } = await supabase.auth.signInWithPassword({
            email: email,
            password: pass
        });

        if (error) {
            console.error("Login incorrecto", error);
            alert("Credenciales Invalidas: " + error.message);
        } else {
            // Force refresh session state to be sure
            await supabase.auth.getSession();
            router.push(returnUrl);
        }
        setLoading(false);
    };

    return (
        <div className="min-h-screen bg-[#060606] text-white flex items-center justify-center p-6 font-sans relative overflow-hidden">

            {/* Background Aesthetics - MASTER LOGIN */}
            <div className="fixed inset-0 z-0">
                <div className="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-brand/10 rounded-full blur-[120px] animate-pulse" />
                <div className="absolute bottom-[-10%] left-[-20%] w-[50%] h-[50%] bg-brand/5 rounded-full blur-[120px]" />
            </div>

            <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} className="glass p-12 w-full max-w-md border-brand/20 space-y-8 relative overflow-hidden shadow-2xl bg-black/60 z-10">

                {/* Background Aesthetics - MASTER ADMIN STYLE */}
                <div className="absolute top-0 right-0 w-64 h-64 bg-brand/10 rounded-full blur-[100px] -mr-32 -mt-32" />
                <div className="absolute bottom-0 left-0 w-64 h-64 bg-brand/5 rounded-full blur-[100px] -ml-32 -mb-32" />

                <div className="text-center space-y-4 relative">
                    <div className="w-20 h-20 rounded-3xl bg-brand/10 border border-brand/20 flex items-center justify-center mx-auto mb-6 shadow-[0_0_30px_rgba(49,210,45,0.1)]">
                        <ShieldCheck className="w-10 h-10 text-brand" />
                    </div>
                    <h1 className="text-3xl font-black tracking-tight uppercase">Metaverso <span className="text-brand">Admin</span></h1>
                    <p className="text-white/40 text-xs font-bold uppercase tracking-widest">Master Access Protocol</p>
                </div>

                <form onSubmit={handleLogin} className="space-y-6 relative">
                    <div className="space-y-4">
                        <div className="relative">
                            <User className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                            <input
                                type="email"
                                placeholder="Email Administrativo"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm focus:outline-none focus:border-brand/40 transition-all font-medium"
                            />
                        </div>
                        <div className="relative">
                            <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                            <input
                                type="password"
                                placeholder="Contraseña Maestra"
                                value={pass}
                                onChange={(e) => setPass(e.target.value)}
                                className="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-sm focus:outline-none focus:border-brand/40 transition-all font-medium"
                            />
                        </div>
                    </div>

                    <button type="submit" className="w-full py-4 bg-brand text-black rounded-2xl font-black uppercase tracking-widest text-xs flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-brand/20">
                        Autorizar Acceso <ArrowRight className="w-4 h-4" />
                    </button>
                </form>

                <p className="text-center text-[10px] text-white/20 font-black uppercase tracking-widest relative">
                    Restricted Area - Unauthorized attempts are logged
                </p>
            </motion.div>
        </div>
    );
}

export default function MetaversoLogin() {
    return (
        <Suspense fallback={
            <div className="min-h-screen bg-[#030303] flex items-center justify-center">
                <div className="text-white/20 animate-pulse uppercase tracking-widest text-xs font-black">
                    Iniciando Sistema de Seguridad...
                </div>
            </div>
        }>
            <LoginForm />
        </Suspense>
    );
}
