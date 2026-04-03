import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!;

// Cliente para el navegador (con RLS)
export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
  auth: {
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: true,
    storageKey: 'metaversotec-auth'
  }
});

// Cliente para el servidor (bypass RLS con service role).
// Only create this client in a server environment to avoid multiple GoTrueClient instances
// in the browser context. When used on the client, `supabaseAdmin` will be null.
export const supabaseAdmin = (typeof window === 'undefined' && process.env.SUPABASE_SERVICE_ROLE_KEY)
  ? createClient(supabaseUrl, process.env.SUPABASE_SERVICE_ROLE_KEY, {
      auth: { autoRefreshToken: false, persistSession: false }
    })
  : null;

if (typeof window !== 'undefined') {
  supabase.auth.getSession().then(async ({ error }) => {
    if (!error) return;

    const message = error.message?.toLowerCase() || '';
    const isInvalidRefresh = message.includes('invalid refresh token') || message.includes('refresh token not found');

    if (isInvalidRefresh) {
      // Clean only local browser session to recover from corrupted/revoked token state.
      await supabase.auth.signOut({ scope: 'local' });
      localStorage.removeItem('metaversotec-auth');
      localStorage.removeItem('metaversotec-auth-code-verifier');
    }
  });
}
