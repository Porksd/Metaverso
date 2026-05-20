const { createClient } = require("@supabase/supabase-js");
require("dotenv").config({ path: "App/.env.local" });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

async function run() {
  const { data, error } = await supabase.from('clients').select('*').limit(1);
  if (data && data.length > 0) {
    console.log("Columnas en clients:", Object.keys(data[0]));
  } else {
    console.log("No se encontraron registros en clients o error:", error);
  }
}
run();
