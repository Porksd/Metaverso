"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import SignatureCanvas from "@/components/SignatureCanvas";
import { supabase } from "@/lib/supabase";

export default function RegisterPage() {
    const router = useRouter();
    const [step, setStep] = useState(1); // 1: datos, 2: firma
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [formData, setFormData] = useState({
        email: "",
        password: "",
        first_name: "",
        last_name: "",
        rut: "",
        passport: "",
        gender: "",
        age: "",
        company_name: "",
        position: "",
        language: "es"
    });

    const [signatureUrl, setSignatureUrl] = useState<string | null>(null);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleStep1Submit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Validation
        if (!formData.email || !formData.password || !formData.first_name || !formData.last_name) {
            setError("Por favor completa todos los campos requeridos");
            return;
        }

        if (!formData.rut && !formData.passport) {
            setError("Debes proporcionar RUT o Pasaporte");
            return;
        }

        setError(null);
        setStep(2); // Go to signature step
    };

    const handleFinalSubmit = async () => {
        if (!signatureUrl) {
            setError("Por favor firma antes de continuar");
            return;
        }

        setLoading(true);
        setError(null);

        try {
            // Call our API route
            const response = await fetch('/api/students/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ...formData,
                    digital_signature_url: signatureUrl
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error al registrar');
            }

            // Success! Redirect to login or dashboard
            alert('¡Registro exitoso! Por favor inicia sesión.');
            router.push('/login');

        } catch (err: any) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-neutral-900 via-neutral-800 to-black flex items-center justify-center p-4">
            <div className="glass max-w-2xl w-full p-8 rounded-3xl border border-white/10">
                {/* Header */}
                <div className="text-center mb-8">
                    <h1 className="text-4xl font-black text-white mb-2">Registro de Estudiante</h1>
                    <p className="text-white/60">Completa tus datos y firma digital</p>
                </div>

                {/* Progress */}
                <div className="flex items-center justify-center gap-4 mb-8">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold ${step >= 1 ? 'bg-brand text-black' : 'bg-white/10 text-white/40'}`}>
                        1
                    </div>
                    <div className={`h-1 w-20 ${step >= 2 ? 'bg-brand' : 'bg-white/10'}`} />
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold ${step >= 2 ? 'bg-brand text-black' : 'bg-white/10 text-white/40'}`}>
                        2
                    </div>
                </div>

                {error && (
                    <div className="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-6">
                        {error}
                    </div>
                )}

                {step === 1 && (
                    <form onSubmit={handleStep1Submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-bold text-white/80 mb-2">Nombre *</label>
                                <input
                                    type="text"
                                    name="first_name"
                                    value={formData.first_name}
                                    onChange={handleChange}
                                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                    required
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-white/80 mb-2">Apellido *</label>
                                <input
                                    type="text"
                                    name="last_name"
                                    value={formData.last_name}
                                    onChange={handleChange}
                                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-white/80 mb-2">Email *</label>
                            <input
                                type="email"
                                name="email"
                                value={formData.email}
                                onChange={handleChange}
                                className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-white/80 mb-2">Contraseña *</label>
                            <input
                                type="password"
                                name="password"
                                value={formData.password}
                                onChange={handleChange}
                                className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                required
                                minLength={6}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-bold text-white/80 mb-2">RUT (chilenos)</label>
                                <input
                                    type="text"
                                    name="rut"
                                    value={formData.rut}
                                    onChange={handleChange}
                                    placeholder="12.345.678-9"
                                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-white/80 mb-2">Pasaporte (extranjeros)</label>
                                <input
                                    type="text"
                                    name="passport"
                                    value={formData.passport}
                                    onChange={handleChange}
                                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-bold text-white/80 mb-2">Género</label>
                                <select
                                    name="gender"
                                    value={formData.gender}
                                    onChange={handleChange}
                                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                >
                                    <option value="">Seleccione</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-white/80 mb-2">Edad</label>
                                <input
                                    type="number"
                                    name="age"
                                    value={formData.age}
                                    onChange={handleChange}
                                    min="18"
                                    max="100"
                                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-white/80 mb-2">Idioma</label>
                                <select
                                    name="language"
                                    value={formData.language}
                                    onChange={handleChange}
                                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                                >
                                    <option value="es">Español</option>
                                    <option value="ht">Kreyòl</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-white/80 mb-2">Empresa</label>
                            <input
                                type="text"
                                name="company_name"
                                value={formData.company_name}
                                onChange={handleChange}
                                placeholder="SACYR, MetaversOtec, etc."
                                className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-white/80 mb-2">Cargo</label>
                            <input
                                type="text"
                                name="position"
                                value={formData.position}
                                onChange={handleChange}
                                placeholder="Operador, Prevencionista, etc."
                                className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-brand"
                            />
                        </div>

                        <button
                            type="submit"
                            className="w-full py-4 bg-brand text-black font-black rounded-xl hover:scale-105 transition-transform"
                        >
                            Continuar a Firma Digital →
                        </button>
                    </form>
                )}

                {step === 2 && (
                    <div className="space-y-6">
                        <div className="text-center">
                            <h3 className="text-2xl font-black text-white mb-2">Firma Digital</h3>
                            <p className="text-white/60">Dibuja tu firma para completar el registro</p>
                        </div>

                        <SignatureCanvas
                            onSave={(url) => setSignatureUrl(url)}
                        />

                        <div className="flex gap-4">
                            <button
                                onClick={() => setStep(1)}
                                className="flex-1 py-4 bg-white/5 text-white font-bold rounded-xl hover:bg-white/10 transition"
                            >
                                ← Volver
                            </button>

                            <button
                                onClick={handleFinalSubmit}
                                disabled={!signatureUrl || loading}
                                className="flex-1 py-4 bg-brand text-black font-black rounded-xl hover:scale-105 transition-transform disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {loading ? 'Registrando...' : 'Completar Registro'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
