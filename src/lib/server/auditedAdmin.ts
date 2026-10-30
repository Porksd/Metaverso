import { createClient, type SupabaseClient } from "@supabase/supabase-js";
import { resolveAdminRole } from "@/lib/adminAuth";

export async function createAuditedAdminClient(request: Request): Promise<SupabaseClient | null> {
  const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
  const serviceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
  const authorization = request.headers.get("authorization") || "";
  const accessToken = authorization.match(/^Bearer\s+(.+)$/i)?.[1];

  if (!supabaseUrl || !serviceRoleKey || !accessToken) return null;

  const verifier = createClient(supabaseUrl, serviceRoleKey, {
    auth: { autoRefreshToken: false, persistSession: false }
  });
  const { data, error } = await verifier.auth.getUser(accessToken);

  if (error || !data.user) return null;

  const normalizedEmail = data.user.email?.trim().toLowerCase() || "";
  const { role } = await resolveAdminRole(verifier, normalizedEmail, "audited-admin-client");
  if (!role) return null;

  return createClient(supabaseUrl, serviceRoleKey, {
    auth: { autoRefreshToken: false, persistSession: false },
    global: {
      headers: {
        "x-audit-actor-user-id": data.user.id,
        "x-audit-actor-email": normalizedEmail,
        "x-audit-app-role": role
      }
    }
  });
}
