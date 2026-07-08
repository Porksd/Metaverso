"use client";

import { useState, useEffect } from "react";
import { Upload, Image as ImageIcon, Save, X } from "lucide-react";
import { supabase } from "@/lib/supabase";

interface CompanyConfigProps {
    companyId: string;
    onClose: () => void;
}

// ─── Cert-signature config helpers ───────────────────────────────────────────
type SigCertType = { participacion: boolean; aprobacion: boolean; irl: boolean };

const DEFAULT_SIG_CERT_TYPES: SigCertType[] = [
    { participacion: true,  aprobacion: true,  irl: true  },
    { participacion: true,  aprobacion: true,  irl: false },
    { participacion: false, aprobacion: false, irl: false },
];

function configToState(cfg: any): SigCertType[] {
    if (!cfg) return DEFAULT_SIG_CERT_TYPES;
    const p: number[] = cfg.participacion ?? [0, 1];
    const a: number[] = cfg.aprobacion   ?? [0, 1];
    const r: number[] = cfg.irl          ?? [0];
    return [0, 1, 2].map(i => ({
        participacion: p.includes(i),
        aprobacion:    a.includes(i),
        irl:           r.includes(i),
    }));
}

function stateToConfig(state: SigCertType[]) {
    return {
        participacion: state.flatMap((s, i) => s.participacion ? [i] : []),
        aprobacion:    state.flatMap((s, i) => s.aprobacion    ? [i] : []),
        irl:           state.flatMap((s, i) => s.irl           ? [i] : []),
    };
}
// ─────────────────────────────────────────────────────────────────────────────

export default function CompanyConfig({ companyId, onClose }: CompanyConfigProps) {
    const [uploading, setUploading] = useState(false);
    const [sigCertTypes, setSigCertTypes] = useState<SigCertType[]>(DEFAULT_SIG_CERT_TYPES);
    const [signatures, setSignatures] = useState<Array<{
        file: File | null;
        preview: string | null;
        name: string;
        role: string;
        url: string | null;
    }>>([
        { file: null, preview: null, name: "", role: "", url: null },
        { file: null, preview: null, name: "", role: "", url: null },
        { file: null, preview: null, name: "", role: "", url: null }
    ]);

    useEffect(() => {
        loadCurrentConfig();
    }, [companyId]);

    const loadCurrentConfig = async () => {
        const { data } = await supabase
            .from('companies')
            .select('signature_url_1, signature_name_1, signature_role_1, signature_url_2, signature_name_2, signature_role_2, signature_url_3, signature_name_3, signature_role_3, cert_signature_config')
            .eq('id', companyId)
            .single();

        if (data) {
            setSignatures([
                { file: null, preview: data.signature_url_1, name: data.signature_name_1 || "", role: data.signature_role_1 || "", url: data.signature_url_1 },
                { file: null, preview: data.signature_url_2, name: data.signature_name_2 || "", role: data.signature_role_2 || "", url: data.signature_url_2 },
                { file: null, preview: data.signature_url_3, name: data.signature_name_3 || "", role: data.signature_role_3 || "", url: data.signature_url_3 }
            ]);
            setSigCertTypes(configToState(data.cert_signature_config));
        }
    };

    const handleFileSelect = (index: number, file: File) => {
        const reader = new FileReader();
        reader.onloadend = () => {
            const newSignatures = [...signatures];
            newSignatures[index].file = file;
            newSignatures[index].preview = reader.result as string;
            setSignatures(newSignatures);
        };
        reader.readAsDataURL(file);
    };

    const handleSave = async () => {
        setUploading(true);

        try {
            const uploadedUrls: string[] = [...signatures.map(s => s.url || "")];

            // Subir cada firma
            for (let i = 0; i < signatures.length; i++) {
                const sig = signatures[i];

                if (sig.file) {
                    // Subir nuevo archivo directamente a Supabase storage
                    const fileExt = sig.file.name.split('.').pop();
                    const fileName = `${Date.now()}_${sig.file.name.replace(/[^a-zA-Z0-9.-]/g, '_')}`;
                    const filePath = `uploads/companies/${companyId}/signatures/${fileName}`;

                    const { error: uploadError } = await supabase.storage
                        .from('company-logos')
                        .upload(filePath, sig.file, { upsert: true });

                    if (uploadError) throw uploadError;

                    // Obtener URL pública
                    const { data: { publicUrl } } = supabase.storage
                        .from('company-logos')
                        .getPublicUrl(filePath);

                    uploadedUrls[i] = publicUrl;
                }
            }

            // Actualizar BD
            const updateData: any = {};
            signatures.forEach((sig, i) => {
                updateData[`signature_url_${i + 1}`] = uploadedUrls[i] || null;
                updateData[`signature_name_${i + 1}`] = sig.name || null;
                updateData[`signature_role_${i + 1}`] = sig.role || null;
            });
            updateData.cert_signature_config = stateToConfig(sigCertTypes);

            const { error } = await supabase
                .from('companies')
                .update(updateData)
                .eq('id', companyId);

            if (error) throw error;

            alert('Configuración guardada exitosamente');
            onClose();
        } catch (error: any) {
            console.error('Error saving config:', error);
            alert(`Error al guardar la configuración: ${error.message || 'Error desconocido'}`);
        } finally {
            setUploading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="glass max-w-4xl w-full p-8 rounded-3xl border-white/10 max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-2xl font-black">Configuración de Firmas</h2>
                    <button onClick={onClose} className="p-2 hover:bg-white/10 rounded-xl transition-colors">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="space-y-6">
                    {signatures.map((sig, index) => (
                        <div key={index} className="bg-white/5 border border-white/10 rounded-2xl p-6 space-y-4">
                            <h3 className="font-bold text-lg">Firma {index + 1}</h3>

                            {/* Preview de firma */}
                            <div className="flex items-center gap-4">
                                <div className="w-48 h-24 bg-white/5 border-2 border-dashed border-white/20 rounded-xl flex items-center justify-center overflow-hidden">
                                    {sig.preview ? (
                                        <img src={sig.preview} alt={`Firma ${index + 1}`} className="max-w-full max-h-full object-contain" />
                                    ) : (
                                        <ImageIcon className="w-8 h-8 text-white/20" />
                                    )}
                                </div>

                                <label className="flex-1">
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg"
                                        onChange={(e) => {
                                            const file = e.target.files?.[0];
                                            if (file) handleFileSelect(index, file);
                                        }}
                                        className="hidden"
                                    />
                                    <div className="px-4 py-3 bg-brand/10 text-brand border border-brand/30 rounded-xl hover:bg-brand hover:text-black transition-all cursor-pointer flex items-center justify-center gap-2 font-bold text-sm">
                                        <Upload className="w-4 h-4" />
                                        Subir Firma (PNG/JPG)
                                    </div>
                                </label>
                            </div>

                            {/* Datos del firmante */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs text-white/40 uppercase font-bold mb-2">Nombre Completo</label>
                                    <input
                                        type="text"
                                        value={sig.name}
                                        onChange={(e) => {
                                            const newSignatures = [...signatures];
                                            newSignatures[index].name = e.target.value;
                                            setSignatures(newSignatures);
                                        }}
                                        placeholder="Ej: Juan Pérez"
                                        className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs text-white/40 uppercase font-bold mb-2">Cargo</label>
                                    <input
                                        type="text"
                                        value={sig.role}
                                        onChange={(e) => {
                                            const newSignatures = [...signatures];
                                            newSignatures[index].role = e.target.value;
                                            setSignatures(newSignatures);
                                        }}
                                        placeholder="Ej: Gerente General"
                                        className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                                    />
                                </div>
                            </div>

                            {/* Certificados donde aparece esta firma */}
                            <div>
                                <label className="block text-xs text-white/40 uppercase font-bold mb-2">Aparece en certificados</label>
                                <div className="flex gap-5 flex-wrap">
                                    {([
                                        { key: 'participacion', label: 'Participación', disabled: false },
                                        { key: 'irl',           label: 'IRL',           disabled: false },
                                    ] as { key: keyof SigCertType; label: string; disabled: boolean }[]).map(({ key, label }) => (
                                        <label key={key} className="flex items-center gap-2 cursor-pointer select-none">
                                            <input
                                                type="checkbox"
                                                checked={sigCertTypes[index][key]}
                                                onChange={(e) => {
                                                    const next = sigCertTypes.map((s, i) =>
                                                        i === index ? { ...s, [key]: e.target.checked } : s
                                                    );
                                                    setSigCertTypes(next);
                                                }}
                                                className="w-4 h-4 accent-brand cursor-pointer"
                                            />
                                            <span className="text-sm text-white/70">{label}</span>
                                        </label>
                                    ))}
                                    {/* Aprobación usa la firma del fondo de Metaverso — no configurable aquí */}
                                    <div className="flex items-center gap-2 opacity-35 select-none" title="El certificado de aprobación usa la firma del fondo de Metaverso">
                                        <input type="checkbox" disabled className="w-4 h-4 cursor-not-allowed" />
                                        <span className="text-sm text-white/50 line-through">Aprobación</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="flex gap-4 mt-8">
                    <button
                        onClick={onClose}
                        className="flex-1 py-4 bg-white/5 border border-white/10 rounded-xl font-bold uppercase text-sm hover:bg-white/10 transition-all"
                    >
                        Cancelar
                    </button>
                    <button
                        onClick={handleSave}
                        disabled={uploading}
                        className="flex-1 py-4 bg-brand text-black rounded-xl font-bold uppercase text-sm hover:bg-white transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Save className="w-5 h-5" />
                        {uploading ? 'Guardando...' : 'Guardar Configuración'}
                    </button>
                </div>
            </div>
        </div>
    );
}
