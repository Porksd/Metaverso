const { createClient } = require("@supabase/supabase-js");
require("dotenv").config({ path: "App/.env.local" });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

const supabase = createClient(supabaseUrl, supabaseKey);

async function run() {
  const { data, error } = await supabase.rpc("get_schema_tables"); // Try a common way to list or just look at public table names
  
  // If rpc fails, let's try querying information_schema if enabled, or just listing from common knowledge of this project.
  // Actually, let's just try 'clients' or similar if 'alumnos' failed.
  // But wait, the user asked for 'alumnos'. Maybe it is in a different schema?
  
  const { data: tables, error: e2 } = await supabase.from('clients').select('id, name').limit(1);
  console.log("Try clients:", e2 ? e2.message : "Success");

  const { data: schema, error: e3 } = await supabase.from('profiles').select('id').limit(1);
  console.log("Try profiles:", e3 ? e3.message : "Success");
}
run();
