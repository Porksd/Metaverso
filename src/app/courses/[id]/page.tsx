"use client";

import { useEffect, useState } from "react";
import { supabase } from "@/lib/supabase";
import { useParams, useRouter } from "next/navigation";
import CoursePlayer from "@/components/CoursePlayer";
import { ArrowLeft } from "lucide-react";

export default function StudentCoursePage() {
  const params = useParams();
  const router = useRouter();
  const courseId = params.id as string;
  const [studentId, setStudentId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    verifyAccess();
  }, [courseId]);

  const verifyAccess = async () => {
    try {
      // Get current user
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) {
        router.push("/login");
        return;
      }

      // Get student data
      const { data: student, error: studentError } = await supabase
        .from("students")
        .select("id")
        .eq("auth_user_id", user.id)
        .single();

      if (studentError || !student) {
        setError("No se encontró tu perfil de estudiante");
        setLoading(false);
        return;
      }

      // Verify enrollment
      const { data: enrollment, error: enrollmentError } = await supabase
        .from("enrollments")
        .select("*")
        .eq("student_id", student.id)
        .eq("course_id", courseId)
        .single();

      if (enrollmentError || !enrollment) {
        setError("No tienes acceso a este curso");
        setLoading(false);
        return;
      }

      // Update status to in_progress if not_started
      if (enrollment.status === "not_started") {
        await supabase
          .from("enrollments")
          .update({ status: "in_progress" })
          .eq("id", enrollment.id);
      }

      setStudentId(student.id);
      setLoading(false);
    } catch (err) {
      console.error("Error verifying access:", err);
      setError("Error al verificar el acceso");
      setLoading(false);
    }
  };

  const handleCourseComplete = async () => {
    // This will be called when the course is completed
    router.push("/courses");
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#0A0A0A]">
        <div className="text-white text-center">
          <div className="animate-spin w-12 h-12 border-4 border-brand border-t-transparent rounded-full mx-auto mb-4"></div>
          <p>Cargando curso...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#0A0A0A]">
        <div className="text-center">
          <div className="glass p-8 rounded-3xl border border-red-500/20 max-w-md">
            <h2 className="text-2xl font-bold text-red-500 mb-4">Error</h2>
            <p className="text-white/60 mb-6">{error}</p>
            <button
              onClick={() => router.push("/courses")}
              className="px-6 py-3 bg-brand text-black font-bold rounded-xl hover:scale-105 transition-transform flex items-center gap-2 mx-auto"
            >
              <ArrowLeft className="w-4 h-4" />
              Volver a Mis Cursos
            </button>
          </div>
        </div>
      </div>
    );
  }

  if (!studentId) {
    return null;
  }

  return (
    <CoursePlayer
      courseId={courseId}
      studentId={studentId}
      onComplete={handleCourseComplete}
      className="min-h-screen"
    />
  );
}
