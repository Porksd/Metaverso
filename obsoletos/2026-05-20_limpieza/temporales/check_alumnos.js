const { createClient } = require("@supabase/supabase-js");
require("dotenv").config({ path: "App/.env.local" });

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

const supabase = createClient(supabaseUrl, supabaseKey);

const names = [
  "Nicolas Abarca",
  "Juan Abarca",
  "Erika Aros",
  "Victor Manuel Riquelme Acuna",
  "Gabriel Adan Villarroel Gutierrez",
  "Yunier Garcia Daudinot",
  "Domingo Luis Fuentes Caceres"
];

async function run() {
  const { data, error } = await supabase
    .from("alumnos")
    .select("id, nombre, company_name, digital_signature_url")
    .in("nombre", names);

  if (error) {
    console.error(error);
    return;
  }

  const result = data.map(al => ({
    id: al.id,
    nombre: al.nombre,
    company: al.company_name,
    has_sig: al.digital_signature_url ? "si" : "no",
    len: al.digital_signature_url ? al.digital_signature_url.length : 0,
    prefix: al.digital_signature_url ? al.digital_signature_url.substring(0, 40) : ""
  }));

  console.table(result);
}

run();
