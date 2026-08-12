"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { ShieldAlert, Filter, Search, RefreshCw } from "lucide-react";
import AdminSidebar from "@/components/AdminSidebar";
import { supabase } from "@/lib/supabase";
import { resolveAdminRole } from "@/lib/adminAuth";

type SecurityEvent = {
  id: string;
  created_at: string;
  event_type: string;
  action: string;
  actor_email: string | null;
  target_email: string | null;
  role_at_time: string | null;
  allowed: boolean;
  reason: string | null;
  ip_address: string | null;
  geo_country: string | null;
  geo_region: string | null;
  geo_city: string | null;
  geo_comuna: string | null;
};

type SecurityRecipient = {
  id: string;
  email: string;
  active: boolean;
  created_at: string;
};

export default function SecurityEventsPage() {
  const router = useRouter();
  const [isAuthorized, setIsAuthorized] = useState<boolean | null>(null);
  const [loading, setLoading] = useState(true);
  const [events, setEvents] = useState<SecurityEvent[]>([]);
  const [recipients, setRecipients] = useState<SecurityRecipient[]>([]);
  const [query, setQuery] = useState("");
  const [typeFilter, setTypeFilter] = useState("all");
  const [resultFilter, setResultFilter] = useState("all");
  const [recipientEmail, setRecipientEmail] = useState("");
  const [recipientsLoading, setRecipientsLoading] = useState(false);
  const [sendingReport, setSendingReport] = useState(false);

  const fetchEvents = async () => {
    setLoading(true);
    const { data, error } = await supabase
      .from("security_access_events")
      .select("id,created_at,event_type,action,actor_email,target_email,role_at_time,allowed,reason,ip_address,geo_country,geo_region,geo_city,geo_comuna")
      .order("created_at", { ascending: false })
      .limit(500);

    if (error) {
      console.error("Error loading security_access_events:", error);
      setEvents([]);
      setLoading(false);
      return;
    }

    setEvents((data || []) as SecurityEvent[]);
    setLoading(false);
  };

  const fetchRecipients = async () => {
    setRecipientsLoading(true);
    const { data, error } = await supabase
      .from("security_report_recipients")
      .select("id,email,active,created_at")
      .order("email", { ascending: true });

    if (error) {
      console.error("Error loading security_report_recipients:", error);
      setRecipients([]);
      setRecipientsLoading(false);
      return;
    }

    setRecipients((data || []) as SecurityRecipient[]);
    setRecipientsLoading(false);
  };

  const addRecipient = async () => {
    const normalized = recipientEmail.trim().toLowerCase();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized);
    if (!valid) {
      alert("Ingresa un email válido.");
      return;
    }

    const { error } = await supabase
      .from("security_report_recipients")
      .upsert([{ email: normalized, active: true }], { onConflict: "email" });

    if (error) {
      alert(`No se pudo guardar destinatario: ${error.message}`);
      return;
    }

    setRecipientEmail("");
    await fetchRecipients();
  };

  const toggleRecipient = async (row: SecurityRecipient) => {
    const { error } = await supabase
      .from("security_report_recipients")
      .update({ active: !row.active })
      .eq("id", row.id);

    if (error) {
      alert(`No se pudo actualizar destinatario: ${error.message}`);
      return;
    }

    await fetchRecipients();
  };

  const deleteRecipient = async (row: SecurityRecipient) => {
    if (!confirm(`¿Eliminar destinatario ${row.email}?`)) return;

    const { error } = await supabase
      .from("security_report_recipients")
      .delete()
      .eq("id", row.id);

    if (error) {
      alert(`No se pudo eliminar destinatario: ${error.message}`);
      return;
    }

    await fetchRecipients();
  };

  const sendReportNow = async () => {
    try {
      setSendingReport(true);

      const {
        data: { session }
      } = await supabase.auth.getSession();

      const accessToken = session?.access_token;
      if (!accessToken) {
        throw new Error("Sesion invalida. Inicia sesion nuevamente.");
      }

      const res = await fetch("/api/security/reports/daily", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${accessToken}`
        },
        body: JSON.stringify({})
      });

      const payload = await res.json().catch(() => null);
      if (!res.ok || !payload?.ok) {
        throw new Error(payload?.error || "No se pudo enviar el informe.");
      }

      const sentCount = Array.isArray(payload?.result?.recipients) ? payload.result.recipients.length : 0;
      alert(`Informe enviado correctamente a ${sentCount} destinatario(s).`);
    } catch (error: any) {
      alert(`Error enviando informe: ${error?.message || "Error desconocido."}`);
    } finally {
      setSendingReport(false);
    }
  };

  useEffect(() => {
    const checkAuth = async () => {
      const {
        data: { session }
      } = await supabase.auth.getSession();

      if (!session) {
        router.push("/admin/metaverso/login?returnUrl=/admin/metaverso/seguridad");
        return;
      }

      const email = session.user.email?.toLowerCase();
      const { role } = await resolveAdminRole(supabase, email, "/admin/metaverso/seguridad");

      if (role !== "superadmin") {
        setIsAuthorized(false);
        return;
      }

      setIsAuthorized(true);
      await Promise.all([fetchEvents(), fetchRecipients()]);
    };

    checkAuth();
  }, [router]);

  const eventTypes = useMemo(() => {
    return Array.from(new Set(events.map((item) => item.event_type))).sort();
  }, [events]);

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase();

    return events.filter((item) => {
      if (typeFilter !== "all" && item.event_type !== typeFilter) return false;
      if (resultFilter === "allowed" && item.allowed !== true) return false;
      if (resultFilter === "denied" && item.allowed !== false) return false;

      if (!needle) return true;

      const haystack = [
        item.event_type,
        item.action,
        item.actor_email || "",
        item.target_email || "",
        item.reason || "",
        item.ip_address || "",
        item.geo_country || "",
        item.geo_region || "",
        item.geo_city || "",
        item.geo_comuna || ""
      ]
        .join(" ")
        .toLowerCase();

      return haystack.includes(needle);
    });
  }, [events, query, typeFilter, resultFilter]);

  if (isAuthorized === null) {
    return (
      <div className="min-h-screen bg-black flex items-center justify-center">
        <div className="text-brand font-black animate-pulse uppercase tracking-widest text-xs">Verificando credenciales...</div>
      </div>
    );
  }

  if (isAuthorized === false) {
    return (
      <div className="min-h-screen bg-black flex flex-col items-center justify-center p-8 text-center space-y-6">
        <ShieldAlert className="w-20 h-20 text-red-500" />
        <h1 className="text-4xl font-black italic tracking-tighter uppercase text-white">Acceso Restringido</h1>
        <p className="text-white/40 max-w-md uppercase text-[10px] font-bold">Solo usuarios SUPERADMIN pueden revisar seguridad.</p>
        <button
          onClick={() => router.push("/admin/metaverso")}
          className="bg-white text-black px-8 py-4 rounded-xl font-black uppercase text-xs"
        >
          Regresar al Panel
        </button>
      </div>
    );
  }

  return (
    <AdminSidebar title="Registro de Seguridad">
      <div className="min-h-screen bg-[#060606] text-white p-4 md:p-8 font-sans pt-20">
        <div className="max-w-7xl mx-auto space-y-6">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 className="text-3xl md:text-4xl font-black uppercase tracking-tighter">Eventos de Seguridad</h1>
              <p className="text-white/45 text-sm">Ingresos y cambios con trazabilidad de IP y ubicación.</p>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={sendReportNow}
                disabled={sendingReport}
                className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand text-black disabled:opacity-60 text-xs uppercase font-black tracking-widest"
              >
                {sendingReport ? "Enviando..." : "Enviar Informe Ahora"}
              </button>
              <button
                onClick={fetchEvents}
                className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 text-xs uppercase font-black tracking-widest"
              >
                <RefreshCw className="w-4 h-4" /> Actualizar
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div className="md:col-span-2 relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/30" />
              <input
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Buscar por actor, acción, IP o comuna..."
                className="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 pl-9 pr-3 text-sm"
              />
            </div>
            <div className="relative">
              <Filter className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/30" />
              <select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 pl-9 pr-3 text-sm"
              >
                <option value="all">Todos los tipos</option>
                {eventTypes.map((eventType) => (
                  <option key={eventType} value={eventType}>{eventType}</option>
                ))}
              </select>
            </div>
            <div>
              <select
                value={resultFilter}
                onChange={(e) => setResultFilter(e.target.value)}
                className="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 px-3 text-sm"
              >
                <option value="all">Permitidos y denegados</option>
                <option value="allowed">Solo permitidos</option>
                <option value="denied">Solo denegados</option>
              </select>
            </div>
          </div>

          <div className="rounded-2xl border border-white/10 overflow-hidden bg-black/40">
            <div className="overflow-x-auto">
              <table className="w-full min-w-[980px] text-sm">
                <thead className="bg-white/5 text-white/60 uppercase text-[10px] tracking-widest">
                  <tr>
                    <th className="text-left px-4 py-3">Fecha</th>
                    <th className="text-left px-4 py-3">Tipo</th>
                    <th className="text-left px-4 py-3">Acción</th>
                    <th className="text-left px-4 py-3">Actor</th>
                    <th className="text-left px-4 py-3">Resultado</th>
                    <th className="text-left px-4 py-3">IP</th>
                    <th className="text-left px-4 py-3">Comuna/Ciudad</th>
                    <th className="text-left px-4 py-3">Motivo</th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr>
                      <td colSpan={8} className="px-4 py-8 text-center text-white/50 uppercase text-xs">Cargando eventos...</td>
                    </tr>
                  ) : filtered.length === 0 ? (
                    <tr>
                      <td colSpan={8} className="px-4 py-8 text-center text-white/50 uppercase text-xs">No hay eventos para los filtros seleccionados.</td>
                    </tr>
                  ) : (
                    filtered.map((item) => (
                      <tr key={item.id} className="border-t border-white/5">
                        <td className="px-4 py-3 text-white/80">{new Date(item.created_at).toLocaleString("es-CL")}</td>
                        <td className="px-4 py-3 text-white/80">{item.event_type}</td>
                        <td className="px-4 py-3 text-white/80">{item.action}</td>
                        <td className="px-4 py-3 text-white/80">{item.actor_email || "-"}</td>
                        <td className="px-4 py-3">
                          <span className={`px-2 py-1 rounded-full text-[10px] font-black uppercase ${item.allowed ? "bg-green-500/20 text-green-400" : "bg-red-500/20 text-red-400"}`}>
                            {item.allowed ? "Permitido" : "Denegado"}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-white/80">{item.ip_address || "-"}</td>
                        <td className="px-4 py-3 text-white/80">{item.geo_comuna || item.geo_city || "-"}</td>
                        <td className="px-4 py-3 text-white/60">{item.reason || "-"}</td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          <div className="rounded-2xl border border-white/10 bg-black/40 p-4 md:p-5 space-y-4">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
              <div>
                <h2 className="text-xl font-black uppercase tracking-tight">Destinatarios del Informe</h2>
                <p className="text-white/45 text-xs uppercase tracking-wider">Correos que reciben el reporte diario de seguridad.</p>
              </div>
              <div className="flex items-center gap-2 w-full md:w-auto">
                <input
                  value={recipientEmail}
                  onChange={(e) => setRecipientEmail(e.target.value)}
                  placeholder="correo@empresa.com"
                  className="w-full md:w-72 bg-white/5 border border-white/10 rounded-xl py-2.5 px-3 text-sm"
                />
                <button
                  onClick={addRecipient}
                  className="px-4 py-2.5 rounded-xl bg-brand text-black text-xs uppercase font-black tracking-widest"
                >
                  Agregar
                </button>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full min-w-[720px] text-sm">
                <thead className="bg-white/5 text-white/60 uppercase text-[10px] tracking-widest">
                  <tr>
                    <th className="text-left px-3 py-2">Email</th>
                    <th className="text-left px-3 py-2">Estado</th>
                    <th className="text-left px-3 py-2">Creado</th>
                    <th className="text-left px-3 py-2">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {recipientsLoading ? (
                    <tr>
                      <td colSpan={4} className="px-3 py-6 text-center text-white/50 uppercase text-xs">Cargando destinatarios...</td>
                    </tr>
                  ) : recipients.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="px-3 py-6 text-center text-white/50 uppercase text-xs">No hay destinatarios configurados.</td>
                    </tr>
                  ) : (
                    recipients.map((row) => (
                      <tr key={row.id} className="border-t border-white/5">
                        <td className="px-3 py-2 text-white/85">{row.email}</td>
                        <td className="px-3 py-2">
                          <span className={`px-2 py-1 rounded-full text-[10px] font-black uppercase ${row.active ? "bg-green-500/20 text-green-400" : "bg-white/10 text-white/60"}`}>
                            {row.active ? "Activo" : "Inactivo"}
                          </span>
                        </td>
                        <td className="px-3 py-2 text-white/70">{new Date(row.created_at).toLocaleString("es-CL")}</td>
                        <td className="px-3 py-2 flex items-center gap-2">
                          <button
                            onClick={() => toggleRecipient(row)}
                            className="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-[10px] uppercase font-black tracking-wider"
                          >
                            {row.active ? "Desactivar" : "Activar"}
                          </button>
                          <button
                            onClick={() => deleteRecipient(row)}
                            className="px-3 py-1.5 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-300 text-[10px] uppercase font-black tracking-wider"
                          >
                            Eliminar
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          <p className="text-xs text-white/40 uppercase tracking-wider">
            Total visible: {filtered.length} evento(s)
          </p>
        </div>
      </div>
    </AdminSidebar>
  );
}
