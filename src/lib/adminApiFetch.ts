import { supabase } from "@/lib/supabase";

export async function adminApiFetch(input: RequestInfo | URL, init: RequestInit = {}) {
  const {
    data: { session },
    error
  } = await supabase.auth.getSession();

  if (error) {
    throw new Error(`No se pudo verificar la sesión administrativa: ${error.message}`);
  }
  if (!session?.access_token) {
    throw new Error("La sesión administrativa expiró. Inicia sesión nuevamente.");
  }

  const headers = new Headers(init.headers);
  headers.set("Authorization", `Bearer ${session.access_token}`);
  return fetch(input, { ...init, headers });
}
