// POST /api/sacyr-irl/reset
// Admin resets a completed IRL assignment back to pending (deletes response, resets status).
import { NextRequest, NextResponse } from "next/server";
import { supabaseAdmin } from "@/lib/supabase";

export async function POST(req: NextRequest) {
  try {
    const { assignment_id } = await req.json();
    if (!assignment_id) return NextResponse.json({ error: "assignment_id requerido" }, { status: 400 });

    const admin = supabaseAdmin;
    if (!admin) return NextResponse.json({ error: "Error de configuración" }, { status: 500 });

    // Delete the response (cascades because of FK ON DELETE CASCADE in schema)
    await admin.from("sacyr_irl_responses").delete().eq("assignment_id", assignment_id);

    // Reset assignment status to pending
    const { error } = await admin
      .from("sacyr_irl_assignments")
      .update({ status: "pending", completed_at: null })
      .eq("id", assignment_id);

    if (error) return NextResponse.json({ error: error.message }, { status: 500 });
    return NextResponse.json({ success: true });
  } catch (e: any) {
    return NextResponse.json({ error: e.message || "Error interno" }, { status: 500 });
  }
}
