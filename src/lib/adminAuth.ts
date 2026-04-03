export type AdminRole = 'superadmin' | 'editor';

export const SUPER_ADMIN_EMAILS = [
  'apacheco@lobus.cl',
  'porksde@gmail.com',
  'm.poblete.m@gmail.com',
  'soporte@lobus.cl',
  'apacheco@metaversotec.com'
];

export const EDITOR_EMAILS = ['admin@metaversotec.com'];

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

  // Fast path for known admins to avoid unnecessary DB calls/policy failures.
  if (SUPER_ADMIN_EMAILS.includes(normalizedEmail)) {
    return { role: 'superadmin', source: 'fallback' };
  }
  if (EDITOR_EMAILS.includes(normalizedEmail)) {
    return { role: 'editor', source: 'fallback' };
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

  if (profile?.role === 'superadmin' || profile?.role === 'editor') {
    return { role: profile.role, source: 'admin_profiles' };
  }

  return { role: null, source: null };
}
