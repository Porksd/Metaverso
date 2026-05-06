export type AdminRole = 'superadmin' | 'administrador' | 'editor';

export const SUPER_ADMIN_EMAILS = [
  'apacheco@lobus.cl',
  'porksde@gmail.com',
  'm.poblete.m@gmail.com',
  'soporte@lobus.cl',
  'apacheco@metaversotec.com'
];

// Nota: admin@metaversotec.com ya no está en la lista hardcoded,
// su rol 'administrador' se gestiona desde la tabla admin_profiles en la DB.

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
