const fs = require('fs');
const path = require('path');
const { createClient } = require('@supabase/supabase-js');

const envCandidates = ['.env.local', 'App/.env.local'];
for (const relPath of envCandidates) {
  const fullPath = path.resolve(process.cwd(), relPath);
  if (fs.existsSync(fullPath)) {
    require('dotenv').config({ path: fullPath });
    break;
  }
}

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const serviceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !serviceRoleKey) {
  console.error('Missing Supabase credentials (NEXT_PUBLIC_SUPABASE_URL / SUPABASE_SERVICE_ROLE_KEY)');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, serviceRoleKey);
const recoveryPassword = process.env.CRITICAL_ADMIN_RECOVERY_PASSWORD || 'Metaverso!2026#Admin';

function parseCriticalAdminsFromEnv() {
  const raw = process.env.CRITICAL_ADMINS_JSON || '[]';
  try {
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed
      .filter((row) => row && typeof row.email === 'string' && typeof row.role === 'string')
      .map((row) => ({
        email: row.email.toLowerCase().trim(),
        role: row.role,
        permissions: row.permissions && typeof row.permissions === 'object' ? row.permissions : {}
      }));
  } catch (error) {
    console.error('Invalid CRITICAL_ADMINS_JSON:', error.message);
    return [];
  }
}

const BLOCKED_RECOVERY_EMAILS = [
  'm.poblete.m@gmail.com',
  'soporte@lobus.cl'
];

const CRITICAL_ADMINS = parseCriticalAdminsFromEnv();

async function listAllAuthUsers() {
  const users = [];
  let page = 1;
  const perPage = 200;

  while (true) {
    const { data, error } = await supabase.auth.admin.listUsers({ page, perPage });
    if (error) throw error;

    const batch = data?.users || [];
    if (batch.length === 0) break;

    users.push(...batch);
    if (batch.length < perPage) break;
    page += 1;
  }

  return users;
}

async function ensureCriticalAdmins() {
  if (process.env.ENABLE_CRITICAL_ADMIN_RESTORE !== 'true') {
    throw new Error('Automatic restore disabled. Set ENABLE_CRITICAL_ADMIN_RESTORE=true to run.');
  }

  if (CRITICAL_ADMINS.length === 0) {
    throw new Error('No critical admins configured. Define CRITICAL_ADMINS_JSON first.');
  }

  const blockedTargets = CRITICAL_ADMINS.filter((admin) => BLOCKED_RECOVERY_EMAILS.includes(admin.email));
  if (blockedTargets.length > 0) {
    throw new Error(`Blocked recovery targets detected: ${blockedTargets.map((a) => a.email).join(', ')}`);
  }

  const summary = {
    profilesUpserted: 0,
    authAlreadyPresent: 0,
    authCreated: 0,
    authCreateFailed: []
  };

  for (const admin of CRITICAL_ADMINS) {
    const { error } = await supabase
      .from('admin_profiles')
      .upsert(
        {
          email: admin.email.toLowerCase(),
          role: admin.role,
          permissions: admin.permissions
        },
        { onConflict: 'email' }
      );

    if (error) {
      throw new Error(`admin_profiles upsert failed for ${admin.email}: ${error.message}`);
    }

    summary.profilesUpserted += 1;
  }

  const authUsers = await listAllAuthUsers();

  for (const admin of CRITICAL_ADMINS) {
    const exists = authUsers.some((u) => (u.email || '').toLowerCase() === admin.email.toLowerCase());

    if (exists) {
      summary.authAlreadyPresent += 1;
      continue;
    }

    const { data, error } = await supabase.auth.admin.createUser({
      email: admin.email.toLowerCase(),
      password: recoveryPassword,
      email_confirm: true,
      user_metadata: {
        restored_by: 'ensure_critical_admins',
        role_hint: admin.role
      }
    });

    if (error) {
      summary.authCreateFailed.push({ email: admin.email, error: error.message });
      continue;
    }

    summary.authCreated += 1;
    console.log(`AUTH user restored: ${admin.email} (${data?.user?.id || 'no-id'})`);
  }

  return summary;
}

ensureCriticalAdmins()
  .then((summary) => {
    console.log('CRITICAL_ADMINS_SUMMARY');
    console.log(JSON.stringify(summary, null, 2));

    if (summary.authCreateFailed.length > 0) {
      process.exit(2);
    }
  })
  .catch((err) => {
    console.error('ensure_critical_admins failed:', err.message);
    process.exit(1);
  });
