/**
 * SCORM 1.2 API Driver for MetaversOtec LMS
 * This script provides the 'API' object required by SCORM 1.2 packages.
 */

export const initScormAPI = (supabase: any, student: any, enrollmentId: string, courseId?: string) => {
    if (typeof window === "undefined") return;

    const SCORM_DATA: { [key: string]: string } = {
        "cmi.core.student_id": student.rut || student.email || "unknown",
        "cmi.core.student_name": `${student.first_name || ""} ${student.last_name || ""}`,
        "cmi.core.lesson_status": "not attempted",
        "cmi.core.score.raw": "0",
        "cmi.suspend_data": "",
        "cmi.core.entry": "ab-initio",
    };

    (window as any).API = {
        LMSInitialize: (param: string) => {
            console.log("SCORM: LMSInitialize", param);
            return "true";
        },
        LMSFinish: (param: string) => {
            console.log("SCORM: LMSFinish", param);
            saveToSupabase();
            if ((window as any).API.onFinish) (window as any).API.onFinish();
            return "true";
        },
        LMSGetValue: (element: string) => {
            console.log("SCORM: LMSGetValue", element);
            return SCORM_DATA[element] || "";
        },
        LMSSetValue: (element: string, value: string) => {
            console.log("SCORM: LMSSetValue", element, "=", value);
            SCORM_DATA[element] = value;
            return "true";
        },
        LMSCommit: (param: string) => {
            console.log("SCORM: LMSCommit", param);
            saveToSupabase();
            if ((window as any).API.onSave) (window as any).API.onSave();
            return "true";
        },
        LMSGetLastError: () => "0",
        LMSGetErrorString: (errorCode: string) => "No error",
        LMSGetDiagnostic: (errorCode: string) => "No diagnostic info",
        onSave: null as any,
        onFinish: null as any
    };

    const saveToSupabase = async () => {
        try {
            if (!enrollmentId || enrollmentId === "dummy-id") {
                console.warn("SCORM: No valid enrollment ID, skipping save");
                return;
            }

            const rawScore = parseFloat(SCORM_DATA["cmi.core.score.raw"] || "0");
            const status = SCORM_DATA["cmi.core.lesson_status"];

            console.log("SCORM: Attempting to save progress", { enrollmentId, rawScore, status, courseId });

            // 1. Guardar en course_progress para tracking detallado
            const { data: progressData, error: progressError } = await supabase.from("course_progress").insert({
                enrollment_id: enrollmentId,
                module_type: "scorm",
                raw_score: rawScore,
                scaled_score: rawScore,
                completed_at: status === "completed" || status === "passed" ? new Date().toISOString() : null
            }).select();

            if (progressError) {
                console.error("SCORM: Error saving to course_progress:", progressError);
            } else {
                console.log("SCORM: Progress saved to course_progress:", progressData);
            }

            // NOTA: No actualizamos 'enrollments' directamente desde el driver para evitar conflictos
            // con la ponderación 80/20 que se calcula en el CoursePlayer.
            // El CoursePlayer recibirá el score vía callback 'onComplete' y actualizará el estado global.

            const logPayload = {
                enrollment_id: enrollmentId,
                course_id: courseId || null,
                attempt_number: 1,
                raw_data: SCORM_DATA,
                score: rawScore,
                interaction_type: status || "tracking"
            };

            console.log("SCORM: Activity log payload:", logPayload);

            // 3. Log de actividad
            const { error: logError } = await supabase.from("activity_logs").insert(logPayload);

            if (logError) {
                console.error("SCORM: Error saving activity log:", JSON.stringify(logError, null, 2));
            }

            console.log("SCORM: Save process completed", { rawScore, status });
        } catch (err) {
            console.error("Critical error in SCORM save:", err);
        }
    };

    return (window as any).API;
};
