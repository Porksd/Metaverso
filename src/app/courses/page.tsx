"use client";

import { useEffect, useState } from "react";
import { supabase } from "@/lib/supabase";
import Link from "next/link";
import { BookOpen, Clock, CheckCircle2, Lock } from "lucide-react";

interface Enrollment {
  id: string;
  status: string;
  best_score: number | null;
  completed_at: string | null;
  course: {
    id: string;
    title: string;
    description: string;
    duration: string;
    thumbnail_url: string;
  };
}

export default function StudentCoursesPage() {
  const [enrollments, setEnrollments] = useState<Enrollment[]>([]);
  const [loading, setLoading] = useState(true);
  const [studentData, setStudentData] = useState<any>(null);

  useEffect(() => {
    loadStudentCourses();
  }, []);

  const loadStudentCourses = async () => {
    try {
      // Get current user
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) {
        window.location.href = "/login";
        return;
      }

      // Get student data
      const { data: student } = await supabase
        .from("students")
        .select("*")
        .eq("auth_user_id", user.id)
        .single();

      if (student) {
        setStudentData(student);

        // Get enrollments with course data
        const { data: enrollmentData, error } = await supabase
          .from("enrollments")
          .select(`
            id,
            status,
            best_score,
            completed_at,
            course:courses(
              id,
              title,
              description,
              duration,
              thumbnail_url
            )
          `)
          .eq("student_id", student.id);

        if (error) {
          console.error("Error loading courses:", error);
        } else {
          const formattedData = (enrollmentData as any[] || []).map(e => ({
            ...e,
            course: Array.isArray(e.course) ? e.course[0] : e.course
          }));
          setEnrollments(formattedData);
        }
      }
    } catch (error) {
      console.error("Error:", error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#0A0A0A]">
        <div className="text-white text-center">
          <div className="animate-spin w-12 h-12 border-4 border-brand border-t-transparent rounded-full mx-auto mb-4"></div>
          <p>Cargando tus cursos...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#0A0A0A] text-white">
      {/* Header */}
      <header className="glass border-b border-white/10 p-6">
        <div className="max-w-7xl mx-auto">
          <h1 className="text-3xl font-black mb-2">Mis Cursos</h1>
          {studentData && (
            <p className="text-white/60">
              Bienvenido, {studentData.first_name} {studentData.last_name}
            </p>
          )}
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto p-6">
        {enrollments.length === 0 ? (
          <div className="text-center py-20">
            <BookOpen className="w-16 h-16 text-white/20 mx-auto mb-4" />
            <h2 className="text-2xl font-bold mb-2 text-white/60">
              No tienes cursos asignados
            </h2>
            <p className="text-white/40">
              Contacta a tu capacitador para que te asigne un curso
            </p>
          </div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {enrollments.map((enrollment) => {
              const course = enrollment.course as any;
              const isCompleted = enrollment.status === "completed";
              const inProgress = enrollment.status === "in_progress";
              const notStarted = enrollment.status === "not_started";

              return (
                <Link
                  key={enrollment.id}
                  href={`/courses/${course.id}`}
                  className="group glass rounded-3xl overflow-hidden border border-white/10 hover:border-brand/50 transition-all hover:scale-[1.02]"
                >
                  {/* Thumbnail */}
                  <div className="relative aspect-video bg-black/50 flex items-center justify-center">
                    {course.thumbnail_url ? (
                      <img
                        src={course.thumbnail_url}
                        alt={course.title}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <BookOpen className="w-16 h-16 text-white/20" />
                    )}
                    
                    {/* Status Badge */}
                    <div className="absolute top-4 right-4">
                      {isCompleted && (
                        <div className="bg-brand text-black px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                          <CheckCircle2 className="w-3 h-3" />
                          Completado
                        </div>
                      )}
                      {inProgress && (
                        <div className="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                          <Clock className="w-3 h-3" />
                          En Progreso
                        </div>
                      )}
                      {notStarted && (
                        <div className="bg-white/10 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                          <Lock className="w-3 h-3" />
                          Nuevo
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Course Info */}
                  <div className="p-6">
                    <h3 className="text-xl font-bold mb-2 group-hover:text-brand transition-colors">
                      {course.title}
                    </h3>
                    {course.description && (
                      <p className="text-sm text-white/60 mb-4 line-clamp-2">
                        {course.description}
                      </p>
                    )}

                    <div className="flex items-center justify-between text-xs text-white/40">
                      {course.duration && (
                        <span className="flex items-center gap-1">
                          <Clock className="w-3 h-3" />
                          {course.duration}
                        </span>
                      )}
                      {enrollment.best_score !== null && (
                        <span className="text-brand font-bold">
                          {enrollment.best_score}%
                        </span>
                      )}
                    </div>

                    <button className="w-full mt-4 px-4 py-3 bg-brand text-black font-bold rounded-xl group-hover:scale-105 transition-transform">
                      {isCompleted ? "Revisar Curso" : notStarted ? "Comenzar Curso" : "Continuar"}
                    </button>
                  </div>
                </Link>
              );
            })}
          </div>
        )}
      </main>
    </div>
  );
}
