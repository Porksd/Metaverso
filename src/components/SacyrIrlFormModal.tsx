"use client";

import { useState } from "react";
import { X, ChevronRight, ChevronLeft, Check, AlertCircle, Shield } from "lucide-react";
import SignatureCanvas from "@/components/SignatureCanvas";
import { supabase } from "@/lib/supabase";
import { SACYR_IRL_FORMS } from "@/lib/sacyrIrlData";
import { generateSacyrIrlPdf, type InduccionData } from "@/lib/generateSacyrIrlPdf";

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

const POLITICAS = [
  { key: "prevencion", label: "Política de Prevención" },
  { key: "alcohol",    label: "Política Alcohol, tabaco y drogas" },
  { key: "vial",       label: "Política Seguridad Vial" },
  { key: "inclusion",  label: "Política Inclusión" },
];

const CONTENIDOS = [
  { key: "mipero",          label: "Matriz de Identificación de peligros, evaluación de riesgos y oportunidades (MIPERO asociada al cargo):", desc: "mipero_desc" },
  { key: "erpt",            label: "Evaluación de Riesgos de Puesto y Lugar de Trabajo (ERPT y ERLT asociada al cargo):", desc: "erpt_desc" },
  { key: "restriccion",     label: "Restricción médica (describir motivo):", desc: "restriccion_desc" },
  { key: "sensible",        label: "Persona especialmente sensible o vulnerable (describir motivo):", desc: "sensible_desc" },
  { key: "plan_gestion",    label: "Plan de Gestión de Prevención y actividades asociadas." },
  { key: "salud",           label: "Salud ocupacional: Protocolos MINSAL." },
  { key: "ptos",            label: "Procedimiento PTOS relativo a su cargo." },
  { key: "plan_emergencias",label: "Medidas contenidas en el Plan de emergencias, contingencias y/o desastre del centro." },
  { key: "ind_vial",        label: "Inducción de Seguridad Vial." },
  { key: "epp_uso",         label: "Elementos de protección personal (cuidado y uso correcto)." },
  { key: "comite",          label: "Comité Paritario de Higiene y Seguridad (constitución y funcionamiento)." },
  { key: "req_legales",     label: "Requisitos Legales / Otros:", desc: "req_otros_desc" },
  { key: "prod_quimicos",   label: "Productos químicos." },
];

const COMPRENSION_ITEMS = [
  { key: "plan_emergencias", label: "Plan de Emergencias, contingencias y/o Desastres" },
  { key: "plan_gestion",     label: "Plan de Gestión de la Prevención" },
  { key: "mipero",           label: "Matriz de Identificación de Peligros (MIPERO)" },
  { key: "erpt",             label: "Evaluación de Riesgos (ERPT y ERLT) *" },
  { key: "riohs",            label: "RIOHS" },
  { key: "protocolos",       label: "Protocolos del Cliente" },
  { key: "ptos",             label: "Procedimiento Teletrabajo (PTOS)" },
  { key: "calor",            label: "Estándar de Calor Extremo y Altas Temperatura" },
];

function CB({ checked, onChange, label }: { checked: boolean; onChange: (v: boolean) => void; label: string }) {
  return (
    <label className="flex items-start gap-2.5 cursor-pointer select-none">
      <div
        onClick={() => onChange(!checked)}
        className={`w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all cursor-pointer ${checked ? "border-brand bg-brand" : "border-white/30 bg-white/5"}`}
      >
        {checked && <Check className="w-2.5 h-2.5 text-black" />}
      </div>
      <span className="text-sm text-white/80 leading-snug">{label}</span>
    </label>
  );
}

export default function SacyrIrlFormModal({
  assignmentId, formSlug, studentId, studentName, studentRut,
  jobName, companyName, relatorSignatureUrl, relatorName, relatorRole,
  onComplete, onClose,
}: Props) {
  const form = SACYR_IRL_FORMS.find(f => f.slug === formSlug);

  type Step = "info" | "induccion" | "quiz" | "workshop" | "sign";
  const STEPS: Step[] = ["info", "induccion", "quiz", "workshop", "sign"];
  const STEP_LABELS = ["Datos", "Inducción", "Test", "Taller", "Firma"];
  const [step, setStep] = useState<Step>("info");
  const stepIdx = STEPS.indexOf(step);

  // ── State ────────────────────────────────────────────────────────────────
  const [motivo, setMotivo] = useState<"nueva_incorporacion" | "cambio_proceso" | "nuevas_actividades" | "">("");

  const [politicas, setPoliticas]           = useState<Record<string, boolean>>({});
  const [contenidos, setContenidos]         = useState<Record<string, boolean>>({});
  const [contenidosDesc, setContenidosDesc] = useState<Record<string, string>>({});
  const [productosQuimicos, setProdQuim]    = useState(Array(4).fill(null).map(() => ({ tipo: "", riesgos: "", medidas: "" })));
  const [equipos, setEquipos]               = useState(Array(4).fill(null).map(() => ({ nombre: "", marca: "", modelo: "" })));
  const [eppTipo, setEppTipo]               = useState("");
  const [capacitacion, setCapacitacion]     = useState(Array(5).fill(null).map(() => ({ riesgo: "", accion: "" })));
  const [comprension, setComprension]       = useState<Record<string, boolean>>({});

  const [respParte1, setRespParte1]         = useState<Record<number, number>>({});
  const [riesgos, setRiesgos]               = useState(Array(5).fill(null).map(() => ({ riesgo: "", medidas: "" })));
  const [imgRiesgo1, setImgRiesgo1]         = useState("");
  const [imgMedidas1, setImgMedidas1]       = useState("");
  const [imgRiesgo2, setImgRiesgo2]         = useState("");
  const [imgMedidas2, setImgMedidas2]       = useState("");

  const [studentSignature, setStudentSignature] = useState<string | null>(null);
  const [savedSignatureUrl, setSavedSignatureUrl] = useState<string | null>(null);
  const [loadingSig, setLoadingSig] = useState(false);
  const [imageExpanded, setImageExpanded] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError]   = useState<string | null>(null);

  if (!form) return null;

  const canNext = () => {
    if (step === "info") return motivo !== "";
    if (step === "quiz") return Object.keys(respParte1).length === form.preguntas.length;
    if (step === "workshop") return riesgos.some(r => r.riesgo.trim() !== "");
    if (step === "sign") return !!(studentSignature || savedSignatureUrl);
    return true;
  };

  // Load saved signature when entering sign step
  const handleStepChange = async (newStep: Step) => {
    if (newStep === "sign" && !savedSignatureUrl && !loadingSig) {
      setLoadingSig(true);
      const { data } = await supabase.from("students").select("digital_signature_url").eq("id", studentId).single();
      if (data?.digital_signature_url) {
        setSavedSignatureUrl(data.digital_signature_url);
        setStudentSignature(data.digital_signature_url);
      }
      setLoadingSig(false);
    }
    setStep(newStep);
  };

  const buildInduccionData = (): InduccionData => ({
    politicas: politicas as any,
    contenidos: { ...contenidos, ...contenidosDesc } as any,
    productos_quimicos: productosQuimicos.filter(p => p.tipo.trim()),
    equipos_maquinarias: equipos.filter(e => e.nombre.trim()),
    epp_tipo: eppTipo,
    capacitacion: capacitacion.filter(c => c.riesgo.trim() || c.accion.trim()),
    comprension: comprension as any,
  });

  const handleSubmit = async () => {
    if (!studentSignature) { setError("Debes firmar para enviar el formulario."); return; }
    setSaving(true);
    setError(null);
    try {
      const { data: stu } = await supabase.from("students").select("digital_signature_url").eq("id", studentId).single();
      let finalSigUrl = stu?.digital_signature_url || null;
      if (!finalSigUrl && studentSignature) {
        const blob = await (await fetch(studentSignature)).blob();
        const path = `signatures/${studentId}_${Date.now()}.png`;
        const { data: up } = await supabase.storage.from("company-logos").upload(path, blob, { upsert: true });
        if (up) {
          const { data: { publicUrl } } = supabase.storage.from("company-logos").getPublicUrl(path);
          finalSigUrl = publicUrl;
          await supabase.from("students").update({ digital_signature_url: finalSigUrl }).eq("id", studentId);
        }
      }
      const induccionData = buildInduccionData();
      const res = await fetch("/api/sacyr-irl/submit", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          assignment_id: assignmentId, student_id: studentId, form_id: form.slug, motivo,
          induccion_data: induccionData,
          respuestas_parte1: respParte1,
          riesgos_identificados: riesgos.filter(r => r.riesgo.trim()),
          imagen_riesgo_1: imgRiesgo1, imagen_medidas_1: imgMedidas1,
          imagen_riesgo_2: imgRiesgo2, imagen_medidas_2: imgMedidas2,
          student_signature_url: finalSigUrl || studentSignature,
          student_name: studentName, student_rut: studentRut,
          relator_signature_url: relatorSignatureUrl || null,
          relator_name: relatorName || null, relator_role: relatorRole || null,
        }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Error al guardar");
      await generateSacyrIrlPdf({
        form, studentName, studentRut,
        jobName: jobName || form.cargo_name,
        companyName: companyName || "Sacyr",
        motivo: motivo as any,
        induccion: induccionData,
        respuestas_parte1: respParte1,
        riesgos_identificados: riesgos.filter(r => r.riesgo.trim()),
        imagen_riesgo_1: imgRiesgo1, imagen_medidas_1: imgMedidas1,
        imagen_riesgo_2: imgRiesgo2, imagen_medidas_2: imgMedidas2,
        studentSignatureUrl: finalSigUrl || studentSignature,
        relatorSignatureUrl: relatorSignatureUrl || null,
        relatorName: relatorName || null, relatorRole: relatorRole || null,
      });
      onComplete();
    } catch (e: any) {
      setError(e.message || "Error al enviar");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[200] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-[#0f1117] border border-white/10 rounded-2xl w-full max-w-2xl max-h-[93vh] overflow-y-auto">

        <div className="sticky top-0 z-10 bg-[#0f1117] border-b border-white/10 px-6 py-4 flex items-center justify-between">
          <div>
            <p className="text-[10px] font-black uppercase text-brand tracking-widest">IRL Sacyr</p>
            <h2 className="text-lg font-black text-white">{form.cargo_name}</h2>
          </div>
          <button onClick={onClose} className="p-2 hover:bg-white/10 rounded-xl"><X className="w-5 h-5 text-white/60" /></button>
        </div>

        <div className="flex gap-1 px-6 pt-4">
          {STEP_LABELS.map((label, idx) => (
            <div key={idx} className="flex-1 flex flex-col items-center gap-1">
              <div className={`h-1.5 w-full rounded-full transition-all ${idx <= stepIdx ? "bg-brand" : "bg-white/10"}`} />
              <span className={`text-[9px] font-bold uppercase ${idx <= stepIdx ? "text-brand" : "text-white/20"}`}>{label}</span>
            </div>
          ))}
        </div>

        <div className="px-6 py-5 space-y-5">

          {step === "info" && (
            <div className="space-y-5">
              <div className="bg-white/5 border border-white/10 rounded-xl p-4 grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                {([["Trabajador", studentName], ["RUT", studentRut], ["Cargo", jobName || form.cargo_name], ["Empresa", companyName || "Sacyr"]] as [string,string][]).map(([k, v]) => (
                  <><span key={k+"k"} className="font-bold text-white/40 text-xs uppercase">{k}</span><span key={k+"v"} className="text-white/80">{v}</span></>
                ))}
              </div>
              <div>
                <p className="text-white font-bold mb-2 text-sm">Descripción del cargo</p>
                <p className="text-white/60 text-sm leading-relaxed">{form.descripcion_puesto}</p>
              </div>
              {form.tareas.length > 0 && (
                <div>
                  <p className="text-white font-bold mb-2 text-sm">Tareas</p>
                  <ul className="space-y-1">{form.tareas.map((t, i) => <li key={i} className="text-white/60 text-xs flex gap-2"><span className="text-brand mt-0.5">•</span>{t}</li>)}</ul>
                </div>
              )}
              {form.lugares_trabajo.length > 0 && (
                <div>
                  <p className="text-white font-bold mb-2 text-sm">Lugares de Trabajo</p>
                  <ul className="space-y-1">{form.lugares_trabajo.map((l, i) => <li key={i} className="text-white/60 text-xs flex gap-2"><span className="text-brand mt-0.5">•</span>{l}</li>)}</ul>
                </div>
              )}
              {form.herramientas.length > 0 && (
                <div>
                  <p className="text-white font-bold mb-2 text-sm">Herramientas y Equipos</p>
                  <ul className="space-y-1">{form.herramientas.map((h, i) => <li key={i} className="text-white/60 text-xs flex gap-2"><span className="text-brand mt-0.5">•</span>{h}</li>)}</ul>
                </div>
              )}
              {form.orden_aseo.length > 0 && (
                <div>
                  <p className="text-white font-bold mb-2 text-sm">Condiciones de orden y aseo</p>
                  <ul className="space-y-1">{form.orden_aseo.map((o, i) => <li key={i} className="text-white/60 text-xs flex gap-2"><span className="text-brand mt-0.5">•</span>{o}</li>)}</ul>
                </div>
              )}
              <div>
                <p className="text-white font-bold mb-3">Motivo de la IRL <span className="text-brand">*</span></p>
                <div className="space-y-2">
                  {([
                    { key: "nueva_incorporacion", label: "Nueva incorporación de Persona Trabajadora" },
                    { key: "cambio_proceso",       label: "Cambios en el proceso de trabajo o puesto de trabajo" },
                    { key: "nuevas_actividades",   label: "Nuevas actividades" },
                  ] as {key: string, label: string}[]).map(({ key, label }) => (
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

          {step === "induccion" && (
            <div className="space-y-6">
              {/* Políticas */}
              <div>
                <p className="text-white font-black text-sm mb-1 flex items-center gap-2">
                  <Shield className="w-4 h-4 text-brand" />
                  Recibe la Inducción por parte del Dpto. SST Sacyr Chile S.A.
                </p>
                <p className="text-white/40 text-xs mb-3">Confirma las políticas que fueron entregadas.</p>
                <div className="space-y-2">
                  {POLITICAS.map(({ key, label }) => (
                    <CB key={key} checked={!!politicas[key]} onChange={v => setPoliticas(p => ({ ...p, [key]: v }))} label={label} />
                  ))}
                </div>
              </div>

              {/* Contenidos */}
              <div>
                <p className="text-white font-black text-sm mb-1">Contenidos y materias informadas</p>
                <p className="text-white/40 text-xs mb-3">Marca todo lo que fue cubierto en la inducción.</p>
                <div className="space-y-3">
                  {CONTENIDOS.map(({ key, label, desc }) => (
                    <div key={key} className="space-y-1">
                      <CB checked={!!contenidos[key]} onChange={v => setContenidos(p => ({ ...p, [key]: v }))} label={label} />
                      {desc && contenidos[key] && (
                        <input type="text" placeholder="Describir..."
                          value={contenidosDesc[desc] || ""}
                          onChange={e => setContenidosDesc(p => ({ ...p, [desc]: e.target.value }))}
                          className="ml-6 w-[calc(100%-1.5rem)] bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 focus:border-brand focus:outline-none" />
                      )}
                    </div>
                  ))}
                </div>
              </div>

              {/* Productos químicos */}
              {contenidos["prod_quimicos"] && (
                <div>
                  <p className="text-white font-bold text-sm mb-2">Productos químicos</p>
                  <div className="space-y-2">
                    {productosQuimicos.map((row, i) => (
                      <div key={i} className="grid grid-cols-3 gap-2">
                        {(["tipo", "riesgos", "medidas"] as const).map((f, fi) => (
                          <input key={fi} type="text" placeholder={["Tipo de producto", "Riesgos asociados", "Medidas de control"][fi]}
                            value={row[f]} onChange={e => { const n = [...productosQuimicos]; n[i] = { ...n[i], [f]: e.target.value }; setProdQuim(n); }}
                            className="bg-white/5 border border-white/10 rounded-lg p-2 text-xs text-white placeholder:text-white/30 focus:border-brand focus:outline-none" />
                        ))}
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Equipos */}
              <div>
                <p className="text-white font-bold text-sm mb-2">Equipos y/o maquinarias</p>
                <div className="space-y-2">
                  {equipos.map((row, i) => (
                    <div key={i} className="grid grid-cols-3 gap-2">
                      {(["nombre", "marca", "modelo"] as const).map((f, fi) => (
                        <input key={fi} type="text" placeholder={["Nombre", "Marca", "Modelo"][fi]}
                          value={row[f]} onChange={e => { const n = [...equipos]; n[i] = { ...n[i], [f]: e.target.value }; setEquipos(n); }}
                          className="bg-white/5 border border-white/10 rounded-lg p-2 text-xs text-white placeholder:text-white/30 focus:border-brand focus:outline-none" />
                      ))}
                    </div>
                  ))}
                </div>
              </div>

              {/* EPP */}
              <div>
                <p className="text-white font-bold text-sm mb-2">Tipo de EPPs a utilizar</p>
                <textarea value={eppTipo} onChange={e => setEppTipo(e.target.value)} rows={2}
                  placeholder="Describir los elementos de protección personal a usar..."
                  className="w-full bg-white/5 border border-white/10 rounded-xl p-3 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
              </div>

              {/* Capacitación */}
              <div>
                <p className="text-white font-bold text-sm mb-2">Capacitación y formación recibida</p>
                <div className="space-y-2">
                  {capacitacion.map((row, i) => (
                    <div key={i} className="grid grid-cols-2 gap-2">
                      <input type="text" placeholder="Riesgo" value={row.riesgo}
                        onChange={e => { const n = [...capacitacion]; n[i] = { ...n[i], riesgo: e.target.value }; setCapacitacion(n); }}
                        className="bg-white/5 border border-white/10 rounded-lg p-2 text-xs text-white placeholder:text-white/30 focus:border-brand focus:outline-none" />
                      <input type="text" placeholder="Nombre acción formativa" value={row.accion}
                        onChange={e => { const n = [...capacitacion]; n[i] = { ...n[i], accion: e.target.value }; setCapacitacion(n); }}
                        className="bg-white/5 border border-white/10 rounded-lg p-2 text-xs text-white placeholder:text-white/30 focus:border-brand focus:outline-none" />
                    </div>
                  ))}
                </div>
                <p className="text-white/30 text-xs mt-2">Consideración: capacitaciones que permitan a la persona trabajadora reconocer y gestionar los riesgos presentes en su entorno de trabajo.</p>
              </div>

              {/* Comprensión */}
              <div className="bg-blue-900/15 border border-blue-500/25 rounded-xl p-4">
                <p className="text-white font-black text-sm mb-3">
                  El trabajador manifiesta haber comprendido la inducción, explicaciones aclaratorias y documentación al respecto, dando por conocida y comprendida la existencia de:
                </p>
                <div className="grid grid-cols-2 gap-y-2.5 gap-x-4">
                  {COMPRENSION_ITEMS.map(({ key, label }) => (
                    <CB key={key} checked={!!comprension[key]} onChange={v => setComprension(p => ({ ...p, [key]: v }))} label={label} />
                  ))}
                </div>
                <p className="text-white/30 text-xs mt-3">* Herramientas que derivan del análisis MIPERO en las cuales se expresan los riesgos en el puesto de trabajo y lugar de trabajo, así como sus medidas de control, deben ir adjuntos al presente documento según cada área o sección de trabajo y cargo.</p>
              </div>
            </div>
          )}

          {step === "quiz" && (
            <div className="space-y-6">
              <div className="bg-orange-900/20 border border-orange-500/25 rounded-xl p-3 text-sm text-orange-200">
                <strong>TEST EVALUACIÓN INDUCCIÓN</strong> — Cada pregunta vale 1 punto. Aprobación: 80%.
              </div>
              <p className="text-white/50 text-xs">Primera Parte: Preguntas de alternativas, existe sólo una respuesta correcta (marque con una X).</p>
              {form.preguntas.map((q, qi) => (
                <div key={qi} className="space-y-2 scroll-mt-4" id={`q-${qi}`}>
                  <p className="text-white font-bold text-sm">{qi + 1}. {q.pregunta}</p>
                  <div className="space-y-1.5">
                    {q.opciones.map((opt, oi) => (
                      <label key={oi}
                        onClick={e => {
                          e.preventDefault();
                          setRespParte1(prev => ({ ...prev, [qi]: oi }));
                        }}
                        className={`flex items-start gap-3 p-2.5 rounded-xl border cursor-pointer transition-all text-sm ${respParte1[qi] === oi ? "border-brand bg-brand/10 text-white" : "border-white/10 bg-white/5 text-white/70 hover:bg-white/8"}`}>
                        <div className={`w-4 h-4 rounded border-2 mt-0.5 flex-shrink-0 flex items-center justify-center ${respParte1[qi] === oi ? "border-brand bg-brand" : "border-white/30"}`}>
                          {respParte1[qi] === oi && <Check className="w-2.5 h-2.5 text-black" />}
                        </div>
                        <span>{["a)", "b)", "c)", "d)"][oi]} {opt}</span>
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}

          {step === "workshop" && (
            <div className="space-y-5">
              <p className="text-white/50 text-xs">Segunda Parte: Taller de Aplicación.</p>
              <div>
                <p className="text-white font-bold mb-1 text-sm">Identifica 5 posibles riesgos y sus medidas de control</p>
                <div className="space-y-3">
                  {riesgos.map((r, i) => (
                    <div key={i} className="bg-white/5 border border-white/10 rounded-xl p-3 space-y-2">
                      <p className="text-xs font-bold text-white/50 uppercase">Riesgo {i + 1}</p>
                      <textarea placeholder="Describe el riesgo..." value={r.riesgo} rows={2}
                        onChange={e => { const n = [...riesgos]; n[i] = { ...n[i], riesgo: e.target.value }; setRiesgos(n); }}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                      <textarea placeholder="Medidas de control..." value={r.medidas} rows={2}
                        onChange={e => { const n = [...riesgos]; n[i] = { ...n[i], medidas: e.target.value }; setRiesgos(n); }}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                    </div>
                  ))}
                </div>
              </div>
              <div>
                <p className="text-white font-bold mb-2 text-sm">Análisis de imagen</p>
                <button
                  type="button"
                  onClick={() => setImageExpanded(e => !e)}
                  className="w-full flex items-center justify-between p-3 bg-white/5 border border-white/10 rounded-xl mb-2 text-sm text-white/70 hover:bg-white/8 transition-all"
                >
                  <span>{imageExpanded ? 'Ocultar imagen' : 'Ver imagen completa ↑'}</span>
                  <span className="text-xs text-brand">{imageExpanded ? 'Colapsar' : 'Expandir'}</span>
                </button>
                {imageExpanded && (
                  <div className="rounded-xl overflow-hidden border border-white/10 mb-3">
                    <img src="/cert-assets/sacyr-irl-header.png" alt="Escena de obra" className="w-full object-contain" />
                  </div>
                )}
                <p className="text-white/50 text-xs mb-3">Observe la imagen y analice situaciones de riesgo. Indique 2 con al menos 1 medida de control por cada una.</p>
                <div className="grid grid-cols-2 gap-3">
                  {[0, 1].map(idx => (
                    <div key={idx} className="space-y-2">
                      <p className="text-xs font-bold text-white/60">Riesgo {idx + 1}</p>
                      <textarea placeholder="Descripción del riesgo..." value={idx === 0 ? imgRiesgo1 : imgRiesgo2} rows={3}
                        onChange={e => idx === 0 ? setImgRiesgo1(e.target.value) : setImgRiesgo2(e.target.value)}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                      <p className="text-xs font-bold text-white/60">Medidas de control</p>
                      <textarea placeholder="Medidas..." value={idx === 0 ? imgMedidas1 : imgMedidas2} rows={3}
                        onChange={e => idx === 0 ? setImgMedidas1(e.target.value) : setImgMedidas2(e.target.value)}
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-2 text-sm text-white placeholder:text-white/30 resize-none focus:border-brand focus:outline-none" />
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {step === "sign" && (
            <div className="space-y-4">
              <div className="bg-blue-900/20 border border-blue-500/30 rounded-xl p-4 text-sm text-blue-200 leading-relaxed">
                En cumplimiento al Decreto N° 44, declaro haber recibido información sobre los riesgos laborales de mi cargo y me comprometo a cumplir las medidas preventivas establecidas.
              </div>
              {loadingSig ? (
                <p className="text-white/40 text-sm">Verificando firma guardada…</p>
              ) : savedSignatureUrl ? (
                <div className="space-y-3">
                  <p className="text-white font-bold">Firma registrada</p>
                  <div className="bg-white rounded-xl p-3 flex justify-center">
                    <img src={savedSignatureUrl} alt="Tu firma" className="max-h-24 object-contain" />
                  </div>
                  <div className="flex items-center gap-2 text-green-400 text-sm"><Check className="w-4 h-4" /> Firma guardada previamente — se usará en este IRL.</div>
                  <button type="button" onClick={() => { setSavedSignatureUrl(null); setStudentSignature(null); }} className="text-xs text-white/40 underline">
                    Firmar nuevamente
                  </button>
                </div>
              ) : (
                <div>
                  <p className="text-white font-bold mb-3">Firma del trabajador</p>
                  <SignatureCanvas onSave={sig => setStudentSignature(sig)} />
                  {studentSignature && <div className="mt-2 flex items-center gap-2 text-green-400 text-sm"><Check className="w-4 h-4" /> Firma registrada</div>}
                </div>
              )}
              {error && (
                <div className="flex items-center gap-2 text-red-400 text-sm bg-red-900/20 border border-red-500/30 rounded-xl p-3">
                  <AlertCircle className="w-4 h-4 flex-shrink-0" />{error}
                </div>
              )}
            </div>
          )}
        </div>

        <div className="sticky bottom-0 bg-[#0f1117] border-t border-white/10 px-6 py-4 flex gap-3">
          {stepIdx > 0 && (
            <button onClick={() => handleStepChange(STEPS[stepIdx - 1])}
              className="flex items-center gap-2 px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white/70 hover:bg-white/10 text-sm font-bold">
              <ChevronLeft className="w-4 h-4" /> Anterior
            </button>
          )}
          <button
            onClick={() => stepIdx === STEPS.length - 1 ? handleSubmit() : handleStepChange(STEPS[stepIdx + 1])}
            disabled={!canNext() || saving}
            className="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-brand text-black rounded-xl font-black uppercase text-sm hover:bg-white transition-all disabled:opacity-40 disabled:cursor-not-allowed"
          >
            {saving ? "Guardando..." : stepIdx === STEPS.length - 1
              ? <><Check className="w-4 h-4" /> Enviar y Descargar PDF</>
              : <>Siguiente <ChevronRight className="w-4 h-4" /></>}
          </button>
        </div>
      </div>
    </div>
  );
}
