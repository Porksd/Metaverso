"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { Filter, History, RefreshCw, Search, ShieldAlert } from "lucide-react";
import AdminSidebar from "@/components/AdminSidebar";
import { supabase } from "@/lib/supabase";
import { resolveAdminRole } from "@/lib/adminAuth";

type AuditEntry = {
  id: number;
  occurred_at: string;
  schema_name: string;
  table_name: string;
  operation: "INSERT" | "UPDATE" | "DELETE";
  actor_user_id: string | null;
  actor_email: string | null;
  actor_app_role: string | null;
  jwt_role: string | null;
  database_role: string;
  row_pk: Record<string, unknown>;
  old_data: Record<string, unknown> | null;
  new_data: Record<string, unknown> | null;
};

const operationLabels: Record<AuditEntry["operation"], string> = {
  INSERT: "Creación",
  UPDATE: "Cambio",
  DELETE: "Eliminación"
};

export default function DataAuditPage() {
  const router = useRouter();
  const [isAuthorized, setIsAuthorized] = useState<boolean | null>(null);
  const [loading, setLoading] = useState(true);
  const [entries, setEntries] = useState<AuditEntry[]>([]);
  const [errorMessage, setErrorMessage] = useState("");
  const [query, setQuery] = useState("");
  const [operationFilter, setOperationFilter] = useState("all");

  const fetchEntries = async () => {
    setLoading(true);
    setErrorMessage("");
    const { data, error } = await supabase
      .from("data_change_audit")
      .select("id,occurred_at,schema_name,table_name,operation,actor_user_id,actor_email,actor_app_role,jwt_role,database_role,row_pk,old_data,new_data")
      .order("occurred_at", { ascending: false })
      .limit(500);

    if (error) {
      console.error("Error loading data_change_audit:", error);
      setErrorMessage(`No se pudo cargar el registro: ${error.message}`);
      setEntries([]);
    } else {
      setEntries((data || []) as AuditEntry[]);
    }
    setLoading(false);
  };

  useEffect(() => {
    const checkAuth = async () => {
      const {
        data: { session }
      } = await supabase.auth.getSession();

      if (!session) {
        router.push("/admin/metaverso/login?returnUrl=/admin/metaverso/auditoria");
        return;
      }

      const { role } = await resolveAdminRole(
        supabase,
        session.user.email,
        "/admin/metaverso/auditoria"
      );
      if (role !== "superadmin") {
        setIsAuthorized(false);
        setLoading(false);
        return;
      }

      setIsAuthorized(true);
      await fetchEntries();
    };

    void checkAuth();
  }, [router]);

  const filteredEntries = useMemo(() => {
    const needle = query.trim().toLowerCase();
    return entries.filter((entry) => {
      if (operationFilter !== "all" && entry.operation !== operationFilter) return false;
      if (!needle) return true;
      return [
        entry.schema_name,
        entry.table_name,
        entry.operation,
        entry.actor_email || "",
        entry.actor_user_id || "",
        entry.actor_app_role || "",
        entry.jwt_role || "",
        entry.database_role,
        JSON.stringify(entry.row_pk),
        JSON.stringify(entry.old_data),
        JSON.stringify(entry.new_data)
      ].join(" ").toLowerCase().includes(needle);
    });
  }, [entries, operationFilter, query]);

  if (isAuthorized === null) {
    return (
      <div className="min-h-screen bg-black flex items-center justify-center">
        <div className="text-brand font-black animate-pulse uppercase tracking-widest text-xs">
          Verificando credenciales...
        </div>
      </div>
    );
  }

  if (isAuthorized === false) {
    return (
      <div className="min-h-screen bg-black flex flex-col items-center justify-center p-8 text-center space-y-6">
        <ShieldAlert className="w-20 h-20 text-red-500" />
        <h1 className="text-4xl font-black italic tracking-tighter uppercase text-white">Acceso restringido</h1>
        <p className="text-white/40 max-w-md uppercase text-[10px] font-bold">
          Solo usuarios SUPERADMIN pueden revisar el historial de cambios.
        </p>
        <button
          onClick={() => router.push("/admin/metaverso")}
          className="bg-white text-black px-8 py-4 rounded-xl font-black uppercase text-xs"
        >
          Regresar al panel
        </button>
      </div>
    );
  }

  return (
    <AdminSidebar title="Auditoría de Datos">
      <div className="min-h-screen bg-[#060606] text-white p-4 md:p-8 font-sans pt-20">
        <div className="max-w-7xl mx-auto space-y-6">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 className="text-3xl md:text-4xl font-black uppercase tracking-tighter">Cambios y eliminaciones</h1>
              <p className="text-white/45 text-sm">
                Registro de inserciones, modificaciones y eliminaciones. Solo se muestran los 500 eventos más recientes.
              </p>
            </div>
            <button
              onClick={() => void fetchEntries()}
              disabled={loading}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 disabled:opacity-50 text-xs uppercase font-black tracking-widest"
            >
              <RefreshCw className={`w-4 h-4 ${loading ? "animate-spin" : ""}`} /> Actualizar
            </button>
          </div>

          <div className="rounded-xl border border-amber-400/20 bg-amber-400/5 p-4 text-sm text-amber-100/80">
            Los cambios realizados directamente con una sesión de usuario muestran su correo e identificador.
            Las operaciones de servidor sin identidad de usuario verificada aparecen con el rol de base de datos,
            por ejemplo <strong>service_role</strong>; no se atribuyen a una persona por suposición.
            El registro comienza al aplicar la migración y no reconstruye cambios anteriores.
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div className="md:col-span-3 relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/30" />
              <input
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Buscar por tabla, actor, identificador o valor..."
                className="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 pl-9 pr-3 text-sm"
              />
            </div>
            <div className="relative">
              <Filter className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/30" />
              <select
                value={operationFilter}
                onChange={(event) => setOperationFilter(event.target.value)}
                className="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 pl-9 pr-3 text-sm"
              >
                <option value="all">Todas las acciones</option>
                <option value="INSERT">Creaciones</option>
                <option value="UPDATE">Cambios</option>
                <option value="DELETE">Eliminaciones</option>
              </select>
            </div>
          </div>

          {errorMessage && (
            <div role="alert" className="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200">
              {errorMessage}
            </div>
          )}

          <div className="overflow-x-auto rounded-2xl border border-white/10 bg-white/[0.02]">
            <table className="w-full min-w-[900px] text-left text-sm">
              <thead className="bg-white/5 text-[10px] uppercase tracking-widest text-white/45">
                <tr>
                  <th className="p-4">Fecha</th>
                  <th className="p-4">Acción</th>
                  <th className="p-4">Tabla / registro</th>
                  <th className="p-4">Actor confirmado</th>
                  <th className="p-4">Detalle</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {loading ? (
                  <tr><td className="p-6 text-white/50" colSpan={5}>Cargando historial...</td></tr>
                ) : filteredEntries.length === 0 ? (
                  <tr>
                    <td className="p-6 text-white/50" colSpan={5}>
                      {errorMessage ? "No se pudo consultar el historial." : "No hay cambios que coincidan con la búsqueda."}
                    </td>
                  </tr>
                ) : filteredEntries.map((entry) => (
                  <tr key={entry.id} className="align-top hover:bg-white/[0.03]">
                    <td className="p-4 whitespace-nowrap text-white/70">
                      {new Date(entry.occurred_at).toLocaleString("es-CL")}
                    </td>
                    <td className="p-4">
                      <span className={`rounded-full px-2.5 py-1 text-[10px] font-black uppercase ${
                        entry.operation === "DELETE"
                          ? "bg-red-500/15 text-red-300"
                          : entry.operation === "INSERT"
                            ? "bg-emerald-500/15 text-emerald-300"
                            : "bg-sky-500/15 text-sky-300"
                      }`}>
                        {operationLabels[entry.operation]}
                      </span>
                    </td>
                    <td className="p-4">
                      <div className="font-bold">{entry.schema_name}.{entry.table_name}</div>
                      <code className="text-xs text-white/45">{JSON.stringify(entry.row_pk)}</code>
                    </td>
                    <td className="p-4">
                      {entry.actor_email ? (
                        <>
                          <div className="font-semibold">{entry.actor_email}</div>
                          {entry.actor_app_role && <div className="text-xs text-white/45">{entry.actor_app_role}</div>}
                          {entry.actor_user_id && <code className="text-[10px] text-white/35">{entry.actor_user_id}</code>}
                        </>
                      ) : (
                        <div className="text-amber-200/80">
                          Sin usuario verificado
                          <div className="text-xs text-white/45">{entry.jwt_role || entry.database_role}</div>
                        </div>
                      )}
                    </td>
                    <td className="p-4">
                      <details className="max-w-xl">
                        <summary className="cursor-pointer text-brand hover:text-white">
                          Ver valores anteriores y nuevos
                        </summary>
                        <div className="mt-3 grid gap-3">
                          {entry.old_data && (
                            <div>
                              <div className="mb-1 text-[10px] uppercase tracking-widest text-white/40">Anterior</div>
                              <pre className="max-h-72 overflow-auto rounded-lg bg-black/50 p-3 text-xs whitespace-pre-wrap break-all">
                                {JSON.stringify(entry.old_data, null, 2)}
                              </pre>
                            </div>
                          )}
                          {entry.new_data && (
                            <div>
                              <div className="mb-1 text-[10px] uppercase tracking-widest text-white/40">Nuevo</div>
                              <pre className="max-h-72 overflow-auto rounded-lg bg-black/50 p-3 text-xs whitespace-pre-wrap break-all">
                                {JSON.stringify(entry.new_data, null, 2)}
                              </pre>
                            </div>
                          )}
                        </div>
                      </details>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <p className="flex items-center gap-2 text-xs text-white/35">
            <History className="h-4 w-4" />
            {filteredEntries.length} eventos cargados · Se omiten valores de campos sensibles como contraseñas y tokens.
          </p>
        </div>
      </div>
    </AdminSidebar>
  );
}
