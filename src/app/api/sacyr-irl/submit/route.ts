// POST /api/sacyr-irl/submit
// Student submits a completed IRL form.
import { NextRequest, NextResponse } from "next/server";
import { supabaseAdmin } from "@/lib/supabase";

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const {
      assignment_id,
      student_id,
      form_id,
      motivo,
      induccion_data,
      respuestas_parte1,
      riesgos_identificados,
      imagen_riesgo_1,
      imagen_medidas_1,
      imagen_riesgo_2,
      imagen_medidas_2,
      student_signature_url,
      student_name,
      student_rut,
      relator_signature_url,
      relator_name,
      relator_role,
    } = body;

    if (!assignment_id || !student_id || !form_id || !motivo) {
      return NextResponse.json({ error: "Faltan campos obligatorios" }, { status: 400 });
    }

    const admin = supabaseAdmin;
    if (!admin) return NextResponse.json({ error: "Error de configuración del servidor" }, { status: 500 });

    // Verify the assignment exists and belongs to this student
    const { data: assignment, error: assignErr } = await admin
      .from("sacyr_irl_assignments")
      .select("id, status, student_id")
      .eq("id", assignment_id)
      .single();

    if (assignErr || !assignment) {
      return NextResponse.json({ error: "Asignación no encontrada" }, { status: 404 });
    }
    if (assignment.student_id !== student_id) {
      return NextResponse.json({ error: "Esta asignación no pertenece a este alumno" }, { status: 403 });
    }
    if (assignment.status === "completed") {
      return NextResponse.json({ error: "Este formulario ya fue completado" }, { status: 409 });
    }

    // Insert response
    const { error: respErr } = await admin.from("sacyr_irl_responses").insert({
      assignment_id,
      student_id,
      form_id,
      motivo,
      induccion_data: induccion_data || {},
      respuestas_parte1: respuestas_parte1 || {},
      riesgos_identificados: riesgos_identificados || [],
      imagen_riesgo_1: imagen_riesgo_1 || null,
      imagen_medidas_1: imagen_medidas_1 || null,
      imagen_riesgo_2: imagen_riesgo_2 || null,
      imagen_medidas_2: imagen_medidas_2 || null,
      student_signature_url: student_signature_url || null,
      student_name: student_name || "",
      student_rut: student_rut || "",
      relator_signature_url: relator_signature_url || null,
      relator_name: relator_name || null,
      relator_role: relator_role || null,
      completed_at: new Date().toISOString(),
    });

    if (respErr) return NextResponse.json({ error: respErr.message }, { status: 500 });

    // Mark assignment as completed
    const { error: updateErr } = await admin
      .from("sacyr_irl_assignments")
      .update({ status: "completed", completed_at: new Date().toISOString() })
      .eq("id", assignment_id);

    if (updateErr) return NextResponse.json({ error: updateErr.message }, { status: 500 });

    return NextResponse.json({ success: true });
  } catch (err: any) {
    return NextResponse.json({ error: err.message || "Error interno" }, { status: 500 });
  }
}
