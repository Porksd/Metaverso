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

const CRITICAL_ADMINS = [
  {
    email: 'admin@metaversotec.com',
    role: 'administrador',
    permissions: { export_excel: false, delete_courses: true }
  },
  {
    email: 'apacheco@metaversotec.com',
    role: 'superadmin',
    permissions: { all: true }
  },
  {
    email: 'porksde@gmail.com',
    role: 'superadmin',
    permissions: { all: true }
  },
  {
    email: 'soporte@lobus.cl',
    role: 'superadmin',
    permissions: { all: true }
  },
  {
    email: 'm.poblete.m@gmail.com',
    role: 'superadmin',
    permissions: { all: true }
  }
];

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
