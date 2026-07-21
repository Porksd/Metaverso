"use client";

import { useState } from "react";
import { X, ChevronRight, ChevronLeft, Check, AlertCircle } from "lucide-react";
import SignatureCanvas from "@/components/SignatureCanvas";
import { supabase } from "@/lib/supabase";
import { SACYR_IRL_FORMS, type SacyrIrlFormData } from "@/lib/sacyrIrlData";
import { generateSacyrIrlPdf } from "@/lib/generateSacyrIrlPdf";

interface Props {
  assignmentId: string;
  formSlug: string;
  studentId: string;
  studentName: string;
  studentRut: string;
  jobName?: string;
  companyName?: string;
  relatorSignatureUrl?: string | null;
  relatorName?: string | null;
  relatorRole?: string | null;
  onComplete: () => void;
  onClose: () => void;
}

export default function SacyrIrlFormModal({
  assignmentId,
  formSlug,
  studentId,
  studentName,
  studentRut,
  jobName,
  companyName,
  relatorSignatureUrl,
  relatorName,
  relatorRole,
  onComplete,
  onClose,
}: Props) {
  const form = SACYR_IRL_FORMS.find(f => f.slug === formSlug);

  const [step, setStep] = useState<"info" | "quiz" | "workshop" | "sign">("info");
  const [motivo, setMotivo] = useState<"nueva_incorporacion" | "cambio_proceso" | "nuevas_actividades" | "">("");
  const [respParte1, setRespParte1] = useState<Record<number, number>>({});
  const [riesgos, setRiesgos] = useState<{ riesgo: string; medidas: string }[]>(
    Array(5).fill(null).map(() => ({ riesgo: "", medidas: "" }))
  );
  const [imgRiesgo1, setImgRiesgo1] = useState("");
  const [imgMedidas1, setImgMedidas1] = useState("");
  const [imgRiesgo2, setImgRiesgo2] = useState("");
  const [imgMedidas2, setImgMedidas2] = useState("");
  const [studentSignature, setStudentSignature] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!form) return null;

  const steps = ["info", "quiz", "workshop", "sign"];
  const stepIdx = steps.indexOf(step);
  const isLastStep = step === "sign";

  const canNext = () => {
    if (step === "info") return motivo !== "";
    if (step === "quiz") return Object.keys(respParte1).length === form.preguntas.length;
    if (step === "workshop") return riesgos.some(r => r.riesgo.trim() !== "");
    return true;
  };

  const handleSubmit = async () => {
    if (!studentSignature) { setError("Debes firmar para enviar el formulario."); return; }
    setSaving(true);
    setError(null);

    try {
      // Save signature to student profile if not present
      const { data: stu } = await supabase
        .from("students")
        .select("digital_signature_url")
        .eq("id", studentId)
        .single();

      let finalSigUrl = stu?.digital_signature_url || null;

      if (!finalSigUrl && studentSignature) {
        // Upload signature
        const blob = await (await fetch(studentSignature)).blob();
        const path = `signatures/${studentId}_${Date.now()}.png`;
        const { data: uploadData } = await supabase.storage
          .from("company-logos")
          .upload(path, blob, { upsert: true });
        if (uploadData) {
          const { data: { publicUrl } } = supabase.storage.from("company-logos").getPublicUrl(path);
          finalSigUrl = publicUrl;
          await supabase.from("students").update({ digital_signature_url: finalSigUrl }).eq("id", studentId);
        }
      }

      // Submit to API
      const res = await fetch("/api/sacyr-irl/submit", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          assignment_id: assignmentId,
          student_id: studentId,
          form_id: form.slug, // Will be resolved by API via slug lookup if needed
          motivo,
          respuestas_parte1: respParte1,
          riesgos_identificados: riesgos.filter(r => r.riesgo.trim()),
          imagen_riesgo_1: imgRiesgo1,
          imagen_medidas_1: imgMedidas1,
          imagen_riesgo_2: imgRiesgo2,
          imagen_medidas_2: imgMedidas2,
          student_signature_url: finalSigUrl || studentSignature,
          student_name: studentName,
          student_rut: studentRut,
          relator_signature_url: relatorSignatureUrl || null,
          relator_name: relatorName || null,
          relator_role: relatorRole || null,
        }),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Error al guardar");

      // Generate PDF
      await generateSacyrIrlPdf({
        form,
        studentName,
        studentRut,
        jobName: jobName || form.cargo_name,
        companyName: companyName || "Sacyr",
        motivo: motivo as any,
        respuestas_parte1: respParte1,
        riesgos_identificados: riesgos.filter(r => r.riesgo.trim()),
        imagen_riesgo_1: imgRiesgo1,
        imagen_medidas_1: imgMedidas1,
        imagen_riesgo_2: imgRiesgo2,
        imagen_medidas_2: imgMedidas2,
        studentSignatureUrl: finalSigUrl || studentSignature,
        relatorSignatureUrl: relatorSignatureUrl || null,
        relatorName: relatorName || null,
        relatorRole: relatorRole || null,
      });

      onComplete();
    } catch (e: any) {
      setError(e.message || "Error al enviar el formulario");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[200] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-[#0f1117] border border-white/10 rounded-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
        {/* Header */}
        <div className="sticky top-0 z-10 bg-[#0f1117] border-b border-white/10 px-6 py-4 flex items-center justify-between">
          <div>
            <p className="text-[10px] font-black uppercase text-brand tracking-widest">IRL Sacyr</p>
            <h2 className="text-lg font-black text-white">{form.cargo_name}</h2>
          </div>
          <button onClick={onClose} className="p-2 hover:bg-white/10 rounded-xl transition-colors">
            <X className="w-5 h-5 text-white/60" />
          </button>
        </div>

        {/* Step indicator */}
        <div className="flex gap-1 px-6 pt-4">
          {["Datos e IRL", "Cuestionario", "Taller", "Firma"].map((label, idx) => (
            <div key={idx} className={`flex-1 h-1.5 rounded-full transition-all ${idx <= stepIdx ? "bg-brand" : "bg-white/10"}`} />
          ))}
        </div>

        <div className="px-6 py-5 space-y-5">

          {/* ── STEP 1: Info + Motivo ─────────────────────────────────────── */}
          {step === "info" && (
            <div className="space-y-4">
              <div className="bg-white/5 border border-white/10 rounded-xl p-4 space-y-2 text-sm">
                <div className="grid grid-cols-2 gap-x-4 gap-y-1.5 text-white/70">
                  <span className="font-bold text-white/40 text-xs uppercase">Trabajador</span>
                  <span>{studentName}</span>
                  <span className="font-bold text-white/40 text-xs uppercase">RUT</span>
                  <span>{studentRut}</span>
                  <span className="font-bold text-white/40 text-xs uppercase">Cargo</span>
                  <span>{jobName || form.cargo_name}</span>
                  <span className="font-bold text-white/40 text-xs uppercase">Empresa</span>
                  <span>{companyName || "Sacyr"}</span>
                </div>
              </div>

              <div>
                <p className="text-white font-bold mb-3">Descripción del cargo</p>
                <p className="text-white/60 text-sm leading-relaxed">{form.descripcion_puesto}</p>
              </div>

              {form.tareas.length > 0 && (
                <div>
                  <p className="text-white font-bold mb-2 text-sm">Tareas que realizas</p>
                  <ul className="space-y-1">
                    {form.tareas.map((t, i) => (
                      <li key={i} className="text-white/60 text-xs flex gap-2"><span className="text-brand mt-0.5">•</span>{t}</li>
                    ))}
                  </ul>
                </div>
              )}

              <div>
                <p className="text-white font-bold mb-3">Motivo de la IRL <span className="text-brand">*</span></p>
                <div className="space-y-2">
                  {[
                    { key: "nueva_incorporacion", label: "Nueva incorporación de Persona Trabajadora" },
                    { key: "cambio_proceso", label: "Cambios en el proceso de trabajo o puesto de trabajo" },
                    { key: "nuevas_actividades", label: "Nuevas actividades" },
                  ].map(({ key, label }) => (
                    <label key={key} className={`flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all ${motivo === key ? "border-brand bg-brand/10" : "border-white/10 bg-white/5 hover:bg-white/8"}`}>
                      <div className={`w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 ${motivo === key ? "border-brand bg-brand" : "border-white/30"}`}>
                        {motivo === key && <Check className="w-2.5 h-2.5 text-black" />}
                      </div>
                      <span className="text-sm text-white/80">{label}</span>
                      <input type="radio" name="motivo" value={key} checked={motivo === key} onChange={() => setMotivo(key as any)} className="sr-only" />
                    </label>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* ── STEP 2: Quiz ─────────────────────────────────────────────── */}
          {step === "quiz" && (
            <div className="space-y-6">
              <p className="text-white/60 text-sm">Responde las siguientes preguntas marcando la alternativa correcta.</p>
              {form.preguntas.map((q, qi) => (
                <div key={qi} className="space-y-2">
                  <p className="text-white font-bold text-sm">{qi + 1}. {q.pregunta}</p>
                  <div className="space-y-1.5">
                    {q.opciones.map((opt, oi) => (
                      <label key={oi} className={`flex items-start gap-3 p-2.5 rounded-xl border cursor-pointer transition-all text-sm ${respParte1[qi] === oi ? "border-brand bg-brand/10 text-white" : "border-white/10 bg-white/5 text-white/70 hover:bg-white/8"}`}>
                        <div className={`w-4 h-4 rounded border-2 mt-0.5 flex items-center justify-center flex-shrink-0 ${respParte1[qi] === oi ? "border-brand bg-brand" : "border-white/30"}`}>
                          {respParte1[qi] === oi && <Check className="w-2.5 h-2.5 text-black" />}
                        </div>
                        {opt}
                        <input type="radio" name={`q${qi}`} checked={respParte1[qi] === oi} onChange={() => setRespParte1(prev => ({ ...prev, [qi]: oi }))} className="sr-only" />
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* ── STEP 3: Workshop ─────────────────────────────────────────── */}
          {step === "workshop" && (
            <div className="space-y-5">
              <div>
                <p className="text-white font-bold mb-1">Identifica 5 riesgos en tu puesto de trabajo</p>
                <p className="text-white/50 text-xs mb-4">Para cada riesgo, describe también las medidas de control correspondientes.</p>
                <div className="space-y-3">
                  {riesgos.map((r, i) => (
                    <div key={i} className="bg-white/5 border border-white/10 rounded-xl p-3 space-y-2">
                      <p className="text-xs font-bold text-white/50 uppercase">Riesgo {i + 1}</p>
                      <textarea
                        placeholder="Describe el riesgo..."
                        value={r.riesgo}
                        onChange={e => {
                          const next = [...riesgos]; next[i] = { ...next[i], riesgo: e.target.value }; setRiesgos(next);
                        }}
                        rows={2}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none"
                      />
                      <textarea
                        placeholder="Medidas de control..."
                        value={r.medidas}
                        onChange={e => {
                          const next = [...riesgos]; next[i] = { ...next[i], medidas: e.target.value }; setRiesgos(next);
                        }}
                        rows={2}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none"
                      />
                    </div>
                  ))}
                </div>
              </div>

              <div>
                <p className="text-white font-bold mb-2">Análisis de imagen</p>
                <div className="rounded-xl overflow-hidden border border-white/10 mb-3">
                  <img src="/cert-assets/sacyr-irl-header.png" alt="Escena de obra" className="w-full max-h-56 object-cover" />
                </div>
                <p className="text-white/50 text-xs mb-3">Observa la imagen e identifica 2 situaciones de riesgo con sus medidas de control.</p>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-2">
                    <p className="text-xs font-bold text-white/60">Riesgo 1</p>
                    <textarea value={imgRiesgo1} onChange={e => setImgRiesgo1(e.target.value)} rows={3} placeholder="Descripción del riesgo..." className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                    <p className="text-xs font-bold text-white/60">Medidas de control</p>
                    <textarea value={imgMedidas1} onChange={e => setImgMedidas1(e.target.value)} rows={3} placeholder="Medidas..." className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                  </div>
                  <div className="space-y-2">
                    <p className="text-xs font-bold text-white/60">Riesgo 2</p>
                    <textarea value={imgRiesgo2} onChange={e => setImgRiesgo2(e.target.value)} rows={3} placeholder="Descripción del riesgo..." className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                    <p className="text-xs font-bold text-white/60">Medidas de control</p>
                    <textarea value={imgMedidas2} onChange={e => setImgMedidas2(e.target.value)} rows={3} placeholder="Medidas..." className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ── STEP 4: Signature ────────────────────────────────────────── */}
          {step === "sign" && (
            <div className="space-y-4">
              <div className="bg-blue-900/20 border border-blue-500/30 rounded-xl p-4 text-sm text-blue-200 leading-relaxed">
                En cumplimiento al Decreto N° 44, declaro haber recibido información sobre los riesgos laborales de mi cargo y me comprometo a cumplir las medidas preventivas establecidas.
              </div>
              <div>
                <p className="text-white font-bold mb-3">Firma del trabajador</p>
                <SignatureCanvas
                  onSave={sig => setStudentSignature(sig)}
                  isSaving={saving}
                />
                {studentSignature && (
                  <div className="mt-2 flex items-center gap-2 text-green-400 text-sm">
                    <Check className="w-4 h-4" />
                    Firma registrada
                  </div>
                )}
              </div>
              {error && (
                <div className="flex items-center gap-2 text-red-400 text-sm bg-red-900/20 border border-red-500/30 rounded-xl p-3">
                  <AlertCircle className="w-4 h-4 flex-shrink-0" />
                  {error}
                </div>
              )}
            </div>
          )}

        </div>

        {/* Footer navigation */}
        <div className="sticky bottom-0 bg-[#0f1117] border-t border-white/10 px-6 py-4 flex gap-3">
          {stepIdx > 0 && (
            <button
              onClick={() => setStep(steps[stepIdx - 1] as any)}
              className="flex items-center gap-2 px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white/70 hover:bg-white/10 transition-all text-sm font-bold"
            >
              <ChevronLeft className="w-4 h-4" /> Anterior
            </button>
          )}
          <button
            onClick={() => {
              if (isLastStep) { handleSubmit(); }
              else { setStep(steps[stepIdx + 1] as any); }
            }}
            disabled={!canNext() || saving}
            className="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-brand text-black rounded-xl font-black uppercase text-sm hover:bg-white transition-all disabled:opacity-40 disabled:cursor-not-allowed"
          >
            {saving ? "Guardando..." : isLastStep ? (
              <><Check className="w-4 h-4" /> Enviar y Descargar PDF</>
            ) : (
              <>Siguiente <ChevronRight className="w-4 h-4" /></>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
