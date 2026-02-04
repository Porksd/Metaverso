import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!;

// Cliente para el navegador (con RLS)
export const supabase = createClient(supabaseUrl, supabaseAnonKey);

// Cliente para el servidor (bypass RLS con service role).
// Only create this client in a server environment to avoid multiple GoTrueClient instances
// in the browser context. When used on the client, `supabaseAdmin` will be null.
export const supabaseAdmin = (typeof window === 'undefined' && process.env.SUPABASE_SERVICE_ROLE_KEY)
  ? createClient(supabaseUrl, process.env.SUPABASE_SERVICE_ROLE_KEY, {
      auth: { autoRefreshToken: false, persistSession: false }
    })
  : null;
