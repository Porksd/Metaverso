"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { supabase } from "@/lib/supabase";

export default function SignOutPage() {
    const router = useRouter();

    useEffect(() => {
        supabase.auth.signOut().finally(() => {
            router.push("/admin/metaverso/login");
        });
    }, []);

    return (
        <div className="min-h-screen bg-black flex items-center justify-center">
            <p className="text-white/40 font-black uppercase tracking-widest text-xs animate-pulse">Cerrando sesión...</p>
        </div>
    );
}
