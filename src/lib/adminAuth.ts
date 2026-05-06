export type AdminRole = 'superadmin' | 'administrador' | 'editor';

export const SUPER_ADMIN_EMAILS = [
  'apacheco@lobus.cl',
  'porksde@gmail.com',
  'm.poblete.m@gmail.com',
  'soporte@lobus.cl',
  'apacheco@metaversotec.com'
];

// Fallback para el rol Administrador (puede eliminarse cursos, no puede exportar Excel)
export const ADMINISTRADOR_EMAILS = ['admin@metaversotec.com'];

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
