// POST /api/sacyr-irl/assign
// Admin assigns one or more IRL forms to a specific student.
import { NextRequest, NextResponse } from "next/server";
import { supabaseAdmin } from "@/lib/supabase";
import { SACYR_COMPANY_ID, SACYR_IRL_FORMS } from "@/lib/sacyrIrlData";

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const { student_id, form_slugs, assigned_by, company_id } = body as {
      student_id: string;
      form_slugs: string[];
      assigned_by?: string;
      company_id: string;
    };

    if (!student_id || !form_slugs?.length || !company_id) {
      return NextResponse.json({ error: "student_id, form_slugs y company_id son requeridos" }, { status: 400 });
    }

    if (company_id !== SACYR_COMPANY_ID) {
      return NextResponse.json({ error: "Este sistema IRL es exclusivo para Sacyr" }, { status: 403 });
    }

    const admin = supabaseAdmin;
    if (!admin) return NextResponse.json({ error: "Error de configuración del servidor" }, { status: 500 });

    // Auto-upsert any missing form records from the TypeScript source of truth
    const formsToUpsert = SACYR_IRL_FORMS.filter(f => form_slugs.includes(f.slug)).map(f => ({
      slug: f.slug,
      cargo_name: f.cargo_name,
      area: f.area,
      descripcion_puesto: f.descripcion_puesto,
      tareas: f.tareas,
      lugares_trabajo: f.lugares_trabajo,
      herramientas: f.herramientas,
      orden_aseo: f.orden_aseo,
      preguntas: f.preguntas,
      is_active: true,
    }));
    if (formsToUpsert.length > 0) {
      await admin.from("sacyr_irl_forms").upsert(formsToUpsert, { onConflict: "slug", ignoreDuplicates: false });
    }

    // Get form IDs from slugs (now guaranteed to exist)
    const { data: forms, error: formsErr } = await admin
      .from("sacyr_irl_forms")
      .select("id, slug")
      .in("slug", form_slugs)
      .eq("is_active", true);

    if (formsErr || !forms?.length) {
      return NextResponse.json({ error: "No se pudieron crear los formularios: " + formsErr?.message }, { status: 500 });
    }

    // Check which forms the student has already completed (to prevent re-assignment)
    const { data: existing } = await admin
      .from("sacyr_irl_assignments")
      .select("form_id, status")
      .eq("student_id", student_id);

    const completedFormIds = new Set(
      (existing || []).filter((a: any) => a.status === "completed").map((a: any) => a.form_id)
    );
    const pendingFormIds = new Set(
      (existing || []).filter((a: any) => a.status === "pending").map((a: any) => a.form_id)
    );

    const toInsert = forms
      .filter((f: any) => !completedFormIds.has(f.id) && !pendingFormIds.has(f.id))
      .map((f: any) => ({
        student_id,
        form_id: f.id,
        company_id,
        assigned_by: assigned_by || null,
        status: "pending",
      }));

    if (toInsert.length === 0) {
      return NextResponse.json({ assigned: 0, message: "No hay formularios nuevos para asignar (ya completados o pendientes)" });
    }

    const { error: insertErr } = await admin.from("sacyr_irl_assignments").insert(toInsert);
    if (insertErr) {
      return NextResponse.json({ error: insertErr.message }, { status: 500 });
    }

    return NextResponse.json({ assigned: toInsert.length, skipped: forms.length - toInsert.length });
  } catch (err: any) {
    return NextResponse.json({ error: err.message || "Error interno" }, { status: 500 });
  }
}

// DELETE /api/sacyr-irl/assign?student_id=X&form_id=Y
// Removes a PENDING assignment (completed ones cannot be removed)
export async function DELETE(req: NextRequest) {
  try {
    const { searchParams } = new URL(req.url);
    const student_id = searchParams.get("student_id");
    const form_id = searchParams.get("form_id");

    if (!student_id || !form_id) {
      return NextResponse.json({ error: "student_id y form_id son requeridos" }, { status: 400 });
    }

    const admin = supabaseAdmin;
    if (!admin) return NextResponse.json({ error: "Error de configuración del servidor" }, { status: 500 });

    const { error } = await admin
      .from("sacyr_irl_assignments")
      .delete()
      .eq("student_id", student_id)
      .eq("form_id", form_id)
      .eq("status", "pending"); // Only pending can be removed

    if (error) return NextResponse.json({ error: error.message }, { status: 500 });
    return NextResponse.json({ success: true });
  } catch (err: any) {
    return NextResponse.json({ error: err.message || "Error interno" }, { status: 500 });
  }
}
