"use client";

import { useState, useEffect, useRef } from "react";
import { motion } from "framer-motion";
import {
    Award, Upload, Save, Eye, ToggleLeft, ToggleRight,
    Image as ImageIcon, Loader2, CheckCircle2, ArrowLeft, RefreshCw
} from "lucide-react";
import { supabase } from "@/lib/supabase";
import AdminSidebar from "@/components/AdminSidebar";
import { generateMetaversoCert, LayoutConfig, DEFAULT_LAYOUT } from "@/lib/generateMetaversoCert";
import { useRouter } from "next/navigation";

const DIPLOMA_CONFIG_ID = "00000000-0000-0000-0000-000000000001";

interface FieldsConfig {
    student_name: boolean;
    rut: boolean;
    company_name: boolean;
    company_rut: boolean;
    course_name: boolean;
    hours: boolean;
    date: boolean;
    course_code: boolean;
    expiration_date: boolean;
}

const FIELD_LABELS: Record<keyof FieldsConfig, string> = {
    student_name: "Nombre del participante",
    rut: "RUT / Pasaporte",
    company_name: "Nombre de empresa",
    company_rut: "RUT empresa",
    course_name: "Nombre del curso",
    hours: "Horas cronológicas",
    date: "Fecha de realización",
    course_code: "Código del curso",
    expiration_date: "Fecha de expiración",
};

export default function DiplomasAdminPage() {
    const router = useRouter();
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [backgroundUrl, setBackgroundUrl] = useState<string>("/cert-assets/metaverso-cert-bg.jpg");
    const [layoutConfig, setLayoutConfig] = useState<Partial<LayoutConfig>>({});
    const [fieldsConfig, setFieldsConfig] = useState<FieldsConfig>({
        student_name: true,
        rut: true,
        company_name: true,
        company_rut: true,
        course_name: true,
        hours: true,
        date: true,
        course_code: true,
        expiration_date: true,
    });
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [saved, setSaved] = useState(false);
    const [isPreviewing, setIsPreviewing] = useState(false);
    const [previewFile, setPreviewFile] = useState<File | null>(null);
    const [previewObjectUrl, setPreviewObjectUrl] = useState<string | null>(null);

    useEffect(() => {
        loadConfig();
        return () => {
            if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const loadConfig = async () => {
        setIsLoading(true);
        const { data, error } = await supabase
            .from("diploma_config")
            .select("*")
            .eq("id", DIPLOMA_CONFIG_ID)
            .maybeSingle();

        if (!error && data) {
            setBackgroundUrl(data.background_url || "/cert-assets/metaverso-cert-bg.jpg");
            if (data.fields_config) {
                const { layout, ...toggles } = data.fields_config;
                setFieldsConfig((prev) => ({ ...prev, ...toggles }));
                if (layout) setLayoutConfig(layout);
            }
        }
        setIsLoading(false);
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setPreviewFile(file);
        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        setPreviewObjectUrl(URL.createObjectURL(file));
    };

    const uploadBackground = async (): Promise<string | null> => {
        if (!previewFile) return backgroundUrl;
        setIsUploading(true);
        try {
            const ext = previewFile.name.split(".").pop();
            const path = `uploads/diplomas/metaverso-bg-${Date.now()}.${ext}`;
            const { error } = await supabase.storage
                .from("company-logos")
                .upload(path, previewFile, { upsert: true });

            if (error) throw error;

            const { data: { publicUrl } } = supabase.storage
                .from("company-logos")
                .getPublicUrl(path);

            return publicUrl;
        } catch (err: any) {
            alert("Error subiendo imagen: " + err.message);
            return null;
        } finally {
            setIsUploading(false);
        }
    };

    const handleSave = async () => {
        setIsSaving(true);
        try {
            let bgUrl = backgroundUrl;
            if (previewFile) {
                const uploaded = await uploadBackground();
                if (!uploaded) return;
                bgUrl = uploaded;
                setBackgroundUrl(bgUrl);
                setPreviewFile(null);
                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                    setPreviewObjectUrl(null);
                }
            }

            const { error } = await supabase
                .from("diploma_config")
                .upsert({
                    id: DIPLOMA_CONFIG_ID,
                    background_url: bgUrl,
                    fields_config: { ...fieldsConfig, layout: layoutConfig },
                    updated_at: new Date().toISOString(),
                });

            if (error) throw error;
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
        } catch (err: any) {
            alert("Error guardando configuración: " + err.message);
        } finally {
            setIsSaving(false);
        }
    };

    const handlePreview = async () => {
        setIsPreviewing(true);
        try {
            await generateMetaversoCert({
                studentName: "MAURICIO ALVAREZ RIVERA",
                rut: "17.452.318-5",
                companyName: "DEMO EMPRESA S.A.",
                companyRut: "76.135.878-2",
                courseName: "Trabajo en Altura - Teórico Práctico",
                courseCode: "VÉRTICE-174523185-TATP-03/2026-PR-1238092246",
                hours: "16",
                date: "27 de marzo de 2026",
                expirationDate: "27 de marzo de 2028",
                backgroundUrl: previewObjectUrl || backgroundUrl,
                fieldsConfig,
                layoutConfig,
            });
        } catch (err: any) {
            alert("Error generando previsualización: " + err.message);
        } finally {
            setIsPreviewing(false);
        }
    };

    const toggleField = (key: keyof FieldsConfig) => {
        setFieldsConfig((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    // Helpers for per-block layout editing
    const getL = (key: keyof LayoutConfig): number =>
        (layoutConfig as Record<string, number>)[key] ?? DEFAULT_LAYOUT[key];
    const setL = (key: keyof LayoutConfig, val: number) =>
        setLayoutConfig((prev) => ({ ...prev, [key]: val }));

    const LRow = ({
        label, yKey, sizeKey, yLabel = "Y (mm)", sizeLabel = "pt",
    }: { label: string; yKey: keyof LayoutConfig; sizeKey: keyof LayoutConfig; yLabel?: string; sizeLabel?: string }) => (
        <div className="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
            <span className="text-xs text-white/50 w-36 flex-shrink-0">{label}</span>
            <div className="flex items-center gap-2">
                <span className="text-[9px] text-white/30 uppercase tracking-widest">{yLabel}</span>
                <input
                    type="number" value={getL(yKey)} step={1}
                    onChange={(e) => setL(yKey, Number(e.target.value))}
                    className="w-16 bg-white/10 border border-white/10 rounded px-2 py-1 text-xs text-white text-center focus:outline-none focus:ring-1 focus:ring-brand"
                />
                <span className="text-[9px] text-white/30 uppercase tracking-widest ml-2">{sizeLabel}</span>
                <input
                    type="number" value={getL(sizeKey)} step={1} min={6} max={72}
                    onChange={(e) => setL(sizeKey, Number(e.target.value))}
                    className="w-14 bg-white/10 border border-white/10 rounded px-2 py-1 text-xs text-white text-center focus:outline-none focus:ring-1 focus:ring-brand"
                />
            </div>
        </div>
    );

    const displayBg = previewObjectUrl || backgroundUrl;

    if (isLoading) {
        return (
            <AdminSidebar title="Diplomas">
                <div className="min-h-screen flex items-center justify-center">
                    <Loader2 className="w-8 h-8 text-brand animate-spin" />
                </div>
            </AdminSidebar>
        );
    }

    return (
        <AdminSidebar title="Diplomas">
            <div className="min-h-screen bg-transparent text-white p-4 md:p-10 font-sans pt-20">
                <div className="max-w-5xl mx-auto space-y-10">

                    {/* Header */}
                    <header className="flex justify-between items-end">
                        <div className="space-y-1">
                            <div className="flex items-center gap-3">
                                <button
                                    onClick={() => router.push("/admin/metaverso")}
                                    className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all"
                                >
                                    <ArrowLeft className="w-4 h-4" />
                                </button>
                                <div>
                                    <h1 className="text-4xl font-black tracking-tight uppercase">
                                        Gestión de <span className="text-brand">Diplomas</span>
                                    </h1>
                                    <p className="text-white/40 font-bold uppercase tracking-widest text-[10px]">
                                        Certificado Metaverso — Configuración Global
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div className="flex gap-3">
                            <button
                                onClick={handlePreview}
                                disabled={isPreviewing}
                                className="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-5 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest transition-all disabled:opacity-40"
                            >
                                {isPreviewing ? <Loader2 className="w-4 h-4 animate-spin" /> : <Eye className="w-4 h-4" />}
                                Previsualizar PDF
                            </button>
                            <button
                                onClick={handleSave}
                                disabled={isSaving || isUploading}
                                className="flex items-center gap-2 bg-brand text-black px-6 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-brand/20 disabled:opacity-50"
                            >
                                {isSaving || isUploading
                                    ? <Loader2 className="w-4 h-4 animate-spin" />
                                    : saved
                                        ? <CheckCircle2 className="w-4 h-4" />
                                        : <Save className="w-4 h-4" />}
                                {saved ? "¡Guardado!" : "Guardar Configuración"}
                            </button>
                        </div>
                    </header>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">

                        {/* LEFT: Background upload + preview */}
                        <div className="space-y-6">
                            <div className="glass p-6 space-y-4 border-brand/20">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-sm font-black uppercase tracking-widest text-brand flex items-center gap-2">
                                        <ImageIcon className="w-4 h-4" /> Fondo del Diploma
                                    </h3>
                                    <button
                                        onClick={() => fileInputRef.current?.click()}
                                        className="flex items-center gap-2 text-[10px] font-black uppercase bg-white/5 hover:bg-white/10 px-3 py-2 rounded-lg transition-all"
                                    >
                                        <Upload className="w-3 h-3" /> Subir Imagen
                                    </button>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png"
                                        className="hidden"
                                        onChange={handleFileSelect}
                                    />
                                </div>

                                {/* Background preview */}
                                <div className="relative bg-black/40 rounded-xl overflow-hidden border border-white/10" style={{ aspectRatio: "210/297" }}>
                                    {displayBg ? (
                                        // eslint-disable-next-line @next/next/no-img-element
                                        <img
                                            src={displayBg}
                                            alt="Fondo diploma"
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex flex-col items-center justify-center text-white/20">
                                            <ImageIcon className="w-10 h-10 mb-2" />
                                            <p className="text-xs font-bold uppercase">Sin fondo cargado</p>
                                        </div>
                                    )}

                                    {/* Overlay showing active fields */}
                                    <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none px-6 py-16 gap-1">
                                        {/* Just show sample text positions when no background */}
                                    </div>

                                    {previewFile && (
                                        <div className="absolute top-2 right-2 bg-brand text-black text-[9px] font-black uppercase px-2 py-1 rounded-lg">
                                            Nueva imagen — sin guardar
                                        </div>
                                    )}
                                </div>

                                <p className="text-[10px] text-white/30 leading-relaxed">
                                    Sube el fondo en JPG o PNG (A4, 210 × 297 mm). 
                                    El fondo debe incluir logo, firma, sello y código QR. 
                                    El sistema solo agrega los datos del participante sobre el fondo.
                                </p>
                            </div>

                            {/* URL display */}
                            {backgroundUrl && !backgroundUrl.startsWith("/") && (
                                <div className="glass p-4 border-white/5">
                                    <p className="text-[9px] text-white/30 uppercase font-bold tracking-widest mb-1">URL actual</p>
                                    <p className="text-[10px] text-brand/70 break-all font-mono">{backgroundUrl}</p>
                                </div>
                            )}

                            {/* Info card — moved to left column */}
                            <div className="glass p-5 border-blue-500/20 space-y-2">
                                <h4 className="text-[10px] font-black uppercase tracking-widest text-blue-400">¿Cómo funciona?</h4>
                                <ul className="space-y-1.5 text-[10px] text-white/40 leading-relaxed">
                                    <li>• Esta configuración es <strong className="text-white/60">global</strong> para todos los cursos del Metaverso.</li>
                                    <li>• Para emitir un diploma, activa <strong className="text-white/60">Cert. Aprobación</strong> en la Gestión de Cursos de cada empresa.</li>
                                    <li>• El botón de descarga aparece automáticamente al alumno al finalizar el curso.</li>
                                    <li>• El fondo debe cargarse en JPG o PNG tamaño A4 (210 × 297 mm).</li>
                                    <li>• Las <strong className="text-white/60">horas</strong> del curso se configuran en la ficha de cada curso, en el campo <strong className="text-white/60">Horas Cronológicas</strong>.</li>
                                </ul>
                            </div>
                        </div>

                        {/* RIGHT: Fields configuration */}
                        <div className="space-y-6">
                            <div className="glass p-6 space-y-5 border-brand/20">
                                <h3 className="text-sm font-black uppercase tracking-widest text-brand flex items-center gap-2">
                                    <Award className="w-4 h-4" /> Campos del Certificado
                                </h3>
                                <p className="text-[10px] text-white/40 leading-relaxed">
                                    Activa o desactiva los campos que aparecerán en el diploma. 
                                    Los campos desactivados no se imprimirán en el PDF.
                                </p>

                                <div className="space-y-2">
                                    {(Object.keys(FIELD_LABELS) as (keyof FieldsConfig)[]).map((key) => (
                                        <motion.div
                                            key={key}
                                            layout
                                            className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                                                fieldsConfig[key]
                                                    ? "bg-brand/10 border-brand/30"
                                                    : "bg-white/5 border-white/5 hover:border-white/10"
                                            }`}
                                            onClick={() => toggleField(key)}
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className={`w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors ${
                                                    fieldsConfig[key] ? "bg-brand border-brand" : "border-white/20"
                                                }`}>
                                                    {fieldsConfig[key] && (
                                                        <svg className="w-2.5 h-2.5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={4}>
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    )}
                                                </div>
                                                <span className="text-sm font-bold">{FIELD_LABELS[key]}</span>
                                            </div>
                                            <span className={`text-[9px] font-black uppercase ${fieldsConfig[key] ? "text-brand" : "text-white/30"}`}>
                                                {fieldsConfig[key] ? "Activo" : "Inactivo"}
                                            </span>
                                        </motion.div>
                                    ))}
                                </div>
                            </div>

                            {/* Per-block layout config */}
                            <div className="glass p-6 space-y-5 border-brand/20">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-sm font-black uppercase tracking-widest text-brand flex items-center gap-2">
                                        <RefreshCw className="w-4 h-4" /> Posición y Tamaños
                                    </h3>
                                    <button
                                        onClick={() => setLayoutConfig({})}
                                        className="text-[9px] text-white/30 hover:text-brand transition uppercase tracking-widest"
                                    >
                                        Restablecer
                                    </button>
                                </div>
                                <p className="text-[10px] text-white/40 leading-relaxed">
                                    Ajusta la posición Y (mm desde arriba del A4) y el tamaño de fuente (pt) por bloque. 
                                    Genera una previsualización para verificar cambios.
                                </p>

                                {/* Block 1: Student */}
                                <div>
                                    <p className="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Bloque 1 — Participante</p>
                                    <LRow label="Nombre" yKey="student_name_y" sizeKey="student_name_size" />
                                    <LRow label="RUT" yKey="rut_y" sizeKey="rut_size" />
                                </div>

                                {/* Block 2: Company */}
                                <div>
                                    <p className="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Bloque 2 — Empresa</p>
                                    <LRow label="Nombre empresa" yKey="company_name_y" sizeKey="company_name_size" />
                                    <LRow label="RUT empresa" yKey="company_rut_y" sizeKey="company_rut_size" />
                                </div>

                                {/* Block 3: Course */}
                                <div>
                                    <p className="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Bloque 3 — Curso</p>
                                    <LRow label='"Ha realizado..."' yKey="ha_realizado_y" sizeKey="ha_realizado_size" />
                                    <LRow label="Nombre del curso" yKey="course_name_y" sizeKey="course_name_size" />
                                </div>

                                {/* Block 4: Hours / Dates / Course code (relative gap) */}
                                <div>
                                    <p className="text-[9px] font-black uppercase tracking-widest text-white/30 mb-1">Bloque 4 — Duración / Fecha</p>
                                    <LRow label="Horas" yKey="hours_gap" sizeKey="hours_size" yLabel="Gap (mm)" />
                                    <LRow label="Fecha (con horas)" yKey="date_gap" sizeKey="date_size" yLabel="Gap (mm)" />
                                    <LRow label="Fecha (sin horas)" yKey="date_gap_no_hours" sizeKey="date_size" yLabel="Gap (mm)" sizeLabel="—" />
                                    <LRow label="Código curso (gap)" yKey="course_code_gap" sizeKey="course_code_size" yLabel="Gap (mm)" />
                                    <LRow label="Expiración (gap)" yKey="expiration_date_gap" sizeKey="expiration_date_size" yLabel="Gap (mm)" />
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </AdminSidebar>
    );
}
