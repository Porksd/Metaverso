export type AdminRole = 'superadmin' | 'administrador' | 'editor';

function parseEmailList(rawValue: string | undefined): string[] {
  return (rawValue || '')
    .split(',')
    .map((email) => email.trim().toLowerCase())
    .filter(Boolean);
}

// Bootstrap list intended only for emergency recovery. Keep empty by default.
const bootstrapSuperAdmins = parseEmailList(
  process.env.NEXT_PUBLIC_BOOTSTRAP_SUPER_ADMIN_EMAILS || process.env.BOOTSTRAP_SUPER_ADMIN_EMAILS
);

// Optional bootstrap list for administrador fallback. Keep empty by default.
const bootstrapAdministradores = parseEmailList(
  process.env.NEXT_PUBLIC_BOOTSTRAP_ADMINISTRADOR_EMAILS || process.env.BOOTSTRAP_ADMINISTRADOR_EMAILS
);

export const SUPER_ADMIN_EMAILS = bootstrapSuperAdmins;
export const ADMINISTRADOR_EMAILS = bootstrapAdministradores;

type ResolveRoleResult = {
  role: AdminRole | null;
  source: 'fallback' | 'admin_profiles' | null;
};

export async function resolveAdminRole(
  supabaseClient: any,
  email: string | null | undefined,
  context = 'admin-auth'
): Promise<ResolveRoleResult> {
  const normalizedEmail = (email || '').toLowerCase().trim();
  if (!normalizedEmail) return { role: null, source: null };

  // Fast path for known superadmins to avoid unnecessary DB calls/policy failures.
  if (SUPER_ADMIN_EMAILS.includes(normalizedEmail)) {
    return { role: 'superadmin', source: 'fallback' };
  }
  // Fast path for known administradores
  if (ADMINISTRADOR_EMAILS.includes(normalizedEmail)) {
    return { role: 'administrador', source: 'fallback' };
  }

  const { data: profile, error } = await supabaseClient
    .from('admin_profiles')
    .select('role')
    .eq('email', normalizedEmail)
    .maybeSingle();

  if (error) {
    console.warn(`No se pudo verificar admin_profiles en ${context}:`, error.message);
    return { role: null, source: null };
  }

  if (profile?.role === 'superadmin' || profile?.role === 'administrador' || profile?.role === 'editor') {
    return { role: profile.role as AdminRole, source: 'admin_profiles' };
  }

  return { role: null, source: null };
}
