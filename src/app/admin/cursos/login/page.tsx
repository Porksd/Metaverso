"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

export default function CursosLogin() {
    const router = useRouter();

    useEffect(() => {
        router.replace("/admin/metaverso/login?returnUrl=/admin/cursos");
    }, [router]);

    return (
        <div className="min-h-screen bg-[#0A0A0A] text-white flex items-center justify-center p-6 font-sans">
            <p className="text-sm text-white/60">Redirigiendo al inicio de sesión administrativo...</p>
        </div>
    );
}
