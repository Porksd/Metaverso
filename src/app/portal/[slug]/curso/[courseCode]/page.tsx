"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { Lock, ArrowLeft, LogIn, UserPlus, Building2, Globe, Info, ChevronDown, Search, CheckCircle2 } from "lucide-react";
import { motion } from "framer-motion";
import SignatureCanvas from "@/components/SignatureCanvas";

// ── Chilean RUT Validator ──
function cleanRut(rut: string): string {
    return rut.replace(/[.\-\s]/g, '').toUpperCase();
}

function formatRut(rut: string): string {
    const clean = cleanRut(rut);
    if (clean.length < 2) return clean;
    const body = clean.slice(0, -1);
    const dv = clean.slice(-1);
    // Add dots every 3 digits from right
    const formatted = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return `${formatted}-${dv}`;
}

function validateRut(rut: string): boolean {
    const clean = cleanRut(rut);
    if (clean.length < 2) return false;
    const body = clean.slice(0, -1);
    const dvGiven = clean.slice(-1);
    if (!/^\d+$/.test(body)) return false;
    
    let sum = 0;
    let multiplier = 2;
    for (let i = body.length - 1; i >= 0; i--) {
        sum += parseInt(body[i]) * multiplier;
        multiplier = multiplier === 7 ? 2 : multiplier + 1;
    }
    const remainder = sum % 11;
    const dvExpected = remainder === 0 ? '0' : remainder === 1 ? 'K' : String(11 - remainder);
    return dvGiven === dvExpected;
}

export default function CourseAuthPage() {
    const params = useParams();
    const router = useRouter();
    const slug = params.slug as string;
    const courseCode = params.courseCode as string;

    const [loading, setLoading] = useState(true);
    const [company, setCompany] = useState<any>(null);
    const [course, setCourse] = useState<any>(null);
    const [companyRoles, setCompanyRoles] = useState<any[]>([]);
    const [selectedRoleDesc, setSelectedRoleDesc] = useState<string | null>(null);
    const [lang, setLang] = useState<'es' | 'ht'>('es');
    const [idType, setIdType] = useState<'rut' | 'passport'>('rut');
    const [rutError, setRutError] = useState<string | null>(null);
    const [companiesList, setCompaniesList] = useState<any[]>([]);
    const [empresaInput, setEmpresaInput] = useState('');
    const [empresaDropdownOpen, setEmpresaDropdownOpen] = useState(false);
    const [showJobInfoModal, setShowJobInfoModal] = useState(false);
    const [hasReadJobInfo, setHasReadJobInfo] = useState(false);
    
    const [authMode, setAuthMode] = useState<'login' | 'register'>('login');
    
    // Login State
    const [loginData, setLoginData] = useState({ email: '', password: '' });
    
    // Register State
    const [regStep, setRegStep] = useState(1);
    const [regData, setRegData] = useState({
        first_name: '', last_name: '', email: '', password: '', 
        rut: '', passport: '', gender: '', age: '', position: '', role_id: '', language: 'es'
    });
    const [signatureUrl, setSignatureUrl] = useState<string | null>(null);

    const [error, setError] = useState<string | null>(null);
    const [actionLoading, setActionLoading] = useState(false);

    // i18n labels
    const t = {
        es: {
            login: 'Ingresar', register: 'Registrarse', email: 'Email Corporativo', password: 'Contraseña',
            loginBtn: 'Iniciar Sesión', verifying: 'Verificando...', name: 'Nombre', surname: 'Apellido',
            rut: 'RUT', cargo: 'Cargo', gender: 'Género', age: 'Edad', select: 'Seleccione',
            passport: 'Pasaporte',
            male: 'Masculino', female: 'Femenino', other: 'Otro', language: 'Idioma',
            empresa: 'Empresa Colaboradora', empresaPh: 'Escribe para buscar o agregar...',
            continueSign: 'Continuar a Firma', required: 'Completa los campos obligatorios',
            invalidRut: 'RUT inválido. Verifica el número.',
            digitalSign: 'Firma Digital', signDesc: 'Dibuja tu firma para aceptar el consentimiento de datos.',
            back: 'Volver', finish: 'Finalizar Registro', registering: 'Registrando...',
            backPortal: 'Volver al Portal',
            restricted: 'Acceso Restringido', open: 'Inscripción Abierta',
            restrictedDesc: 'Este curso requiere que tu cuenta haya sido creada previamente por el administrador.',
            openDesc: 'Puedes iniciar sesión con tu cuenta existente o crear una nueva para comenzar inmediatamente.',
            passMin: 'La contraseña debe tener al menos 6 caracteres',
            signRequired: 'Firma requerida.',
        },
        ht: {
            login: 'Konekte', register: 'Enskri', email: 'Imèl Antrepriz', password: 'Modpas',
            loginBtn: 'Konekte', verifying: 'Verifikasyon...', name: 'Non', surname: 'Siyati',
            rut: 'RUT', cargo: 'Pòs', gender: 'Sèks', age: 'Laj', select: 'Chwazi',
            passport: 'Paspò',
            male: 'Gason', female: 'Fi', other: 'Lòt', language: 'Lang',
            empresa: 'Antrepriz Kolaboratè', empresaPh: 'Ekri pou chèche oswa ajoute...',
            continueSign: 'Kontinye nan Siyati', required: 'Ranpli tout chan obligatwa yo',
            invalidRut: 'RUT envalid. Verifye nimewo a.',
            digitalSign: 'Siyati Dijital', signDesc: 'Desine siyati ou pou aksepte konsantman done yo.',
            back: 'Retounen', finish: 'Fini Enskripsyon', registering: 'Anrejistreman...',
            backPortal: 'Retounen nan Pòtal la',
            restricted: 'Aksè Restren', open: 'Enskripsyon Ouvè',
            restrictedDesc: 'Kou sa a egzije ke kont ou te kreye pa administratè a.',
            openDesc: 'Ou ka konekte ak kont ou oswa kreye yon nouvo pou kòmanse imedyatman.',
            passMin: 'Modpas la dwe gen omwen 6 karaktè',
            signRequired: 'Siyati obligatwa.',
        }
    }[lang];

    useEffect(() => {
        const init = async () => {
            try {
                // 1. Fetch Company
                const { data: comp } = await supabase.from('companies').select('*').eq('slug', slug).single();
                if (!comp) throw new Error("Empresa inválida.");
                setCompany(comp);

                // 2. Fetch Course
                const { data: crs } = await supabase.from('courses').select('*').eq('code', courseCode).single();
                if (!crs) throw new Error("Curso no encontrado.");

                // Check Registration Mode from Relationship (company_courses)
                const { data: assignment } = await supabase
                    .from('company_courses')
                    .select('registration_mode')
                    .eq('company_id', comp.id)
                    .eq('course_id', crs.id)
                    .single();

                // Respect assignment mode first, fallback to course global mode (if legacy), default to open
                const trueMode = assignment?.registration_mode || crs.registration_mode || 'open';
                crs.registration_mode = trueMode;

                setCourse(crs);

                // 3. Fetch ONLY assigned and visible company roles for this company
                const { data: assignedRoles, error: assignErr } = await supabase
                    .from('role_company_assignments')
                    .select('role_id, company_roles(*)')
                    .eq('company_id', comp.id)
                    .eq('is_visible', true);
                
                if (assignErr || !assignedRoles || assignedRoles.length === 0) {
                    // Fallback to basic visibility: roles of this company or global roles
                    const { data: allRoles } = await supabase
                        .from('company_roles')
                        .select('id, name, name_ht, description, description_ht, company_id, active')
                        .or(`company_id.eq.${comp.id},company_id.is.null`)
                        .eq('active', true) // Only active roles
                        .order('name');
                    setCompanyRoles(allRoles || []);
                } else {
                    const filteredRoles = (assignedRoles || [])
                        .map((ar: any) => ar.company_roles)
                        .filter((r: any) => r && r.active !== false); // Strictly filter out inactive
                    setCompanyRoles(filteredRoles || []);
                }

                // 4b. Fetch companies_list for Empresa autocomplete
                const { data: clData } = await supabase
                    .from('companies_list')
                    .select('*')
                    .order('name_es');
                setCompaniesList(clData || []);

                // 4. Set Auth Mode
                if (trueMode === 'restricted') {
                    setAuthMode('login');
                } else {
                    setAuthMode('login');
                }

            } catch (err: any) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };
        init();
    }, [slug, courseCode]);

    // Close empresa dropdown on click outside
    useEffect(() => {
        const handler = (e: MouseEvent) => {
            const target = e.target as HTMLElement;
            if (!target.closest('.empresa-autocomplete')) setEmpresaDropdownOpen(false);
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading(true);
        setError(null);
        try {
            // Intentar login manual consultando la tabla de estudiantes
            // Buscamos coincidencia exacta de email/rut y password
            const { data: student, error: studentError } = await supabase
                .from('students')
                .select('*')
                .eq('password', loginData.password)
                .or(`email.eq.${loginData.email},rut.eq.${loginData.email}`)
                .maybeSingle();

            if (studentError || !student) {
                console.error("Login detail error:", studentError);
                setError(lang === 'es' ? 'Credenciales inválidas. Verifica tu email/RUT y contraseña.' : 'Kredansyèl envalid. Verifye imèl/RUT ou ak modpas ou.');
                setActionLoading(false);
                return;
            }

            // Si llegamos aquí, las credenciales son válidas. 
            // Proceder con el login del estudiante...
            localStorage.setItem('student_session', JSON.stringify(student));

            // Verificar Inscripción (Enrollment)
            const { data: enrollment } = await supabase
                .from('enrollments')
                .select('*')
                .eq('student_id', student.id)
                .eq('course_id', course.id)
                .single();

            if (course.registration_mode === 'restricted' && !enrollment) {
                throw new Error("⛔ No estás inscrito en este curso. Contacta a tu administrador para solicitar acceso.");
            }

            if (!enrollment) {
                // Si es 'open' y no tiene inscripción, lo inscribimos automáticamente
                console.log("Inscripción automática para curso abierto...");
                await supabase.from('enrollments').insert({
                    student_id: student.id,
                    course_id: course.id,
                    status: 'not_started',
                    progress: 0
                });
            }

            localStorage.setItem('user', JSON.stringify(student));
            window.location.href = '/admin/empresa/alumnos/cursos';
            
        } catch (err: any) {
            setError(err.message);
        } finally {
            setActionLoading(false);
        }
    };

    const handleRegister = async () => {
        if (!signatureUrl) return setError(t.signRequired);
        setActionLoading(true);
        try {
            // If empresa was typed and doesn't exist, create it in companies_list
            const trimmedEmpresa = empresaInput.trim();
            if (trimmedEmpresa) {
                const exists = companiesList.some(c => c.name_es?.toLowerCase() === trimmedEmpresa.toLowerCase());
                if (!exists) {
                    const code = trimmedEmpresa.toUpperCase().replace(/\s+/g, '_');
                    await supabase.from('companies_list').insert({ name_es: trimmedEmpresa, code });
                }
            }

            // Store the clean RUT (without dots/dash) in the DB
            const cleanedRut = idType === 'rut' ? cleanRut(regData.rut) : null;
            
            // Clean up empty strings for optional fields to avoid type errors
            const ageVal = regData.age && regData.age.trim() !== '' ? parseInt(regData.age) : null;
            const genderVal = regData.gender === '' ? null : regData.gender;
            const roleVal = regData.role_id === '' ? null : regData.role_id;
            
            const res = await fetch('/api/students/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ...regData,
                    rut: cleanedRut,
                    passport: idType === 'passport' ? regData.passport : null,
                    language: lang,
                    company_name: trimmedEmpresa || company.name, 
                    client_id: company.id,
                    role_id: roleVal || null,
                    position: regData.position || null,
                    age: ageVal,
                    gender: genderVal,
                    digital_signature_url: signatureUrl
                })
            });
            
            const result = await res.json();
            if (!res.ok) throw new Error(result.error);

            alert("Registro exitoso. Iniciando sesión...");
            
            // Auto-login
            const { data: loginRes, error: loginErr } = await supabase.auth.signInWithPassword({
                email: regData.email,
                password: regData.password
            });
            
            if (loginErr) throw loginErr;
            
            // Fetch student profile again to save to localstorage
            localStorage.setItem('user', JSON.stringify(result.student));
            
            // Create Enrollment for this course automatically
            // This is a nice-to-have: if they register via a course link, enroll them immediately!
            if (course) {
                await supabase.from('enrollments').insert({
                    student_id: result.student.id,
                    course_id: course.id,
                    status: 'not_started',
                    progress: 0
                });
            }

            window.location.href = '/admin/empresa/alumnos/cursos';

        } catch (err: any) {
            setError(err.message);
        } finally {
            setActionLoading(false);
        }
    };

    if (loading) return <div className="min-h-screen bg-[#050505] flex items-center justify-center text-white/20">Cargando...</div>;
    if (!company || !course) return <div className="min-h-screen bg-[#050505] flex items-center justify-center text-red-500">Recurso no disponible</div>;

    const isRestricted = course.registration_mode === 'restricted';

    // Helper to check field visibility
    const isFieldVisible = (field: string) => {
        if (!company?.user_registration_config) return true; // Default to visible if no config
        return company.user_registration_config[field]?.visible !== false;
    };

    const handleRutInput = (value: string) => {
        // Auto-format as user types
        const clean = cleanRut(value);
        if (clean.length <= 10) {
            const formatted = clean.length >= 2 ? formatRut(clean) : clean;
            setRegData({...regData, rut: formatted});
            if (clean.length >= 8) {
                setRutError(validateRut(clean) ? null : (t.invalidRut));
            } else {
                setRutError(null);
            }
        }
    };

    const switchLang = (newLang: 'es' | 'ht') => {
        setLang(newLang);
        setRegData({...regData, language: newLang});
        // Update tooltip if a role is selected
        if (regData.role_id) {
            const role = companyRoles.find(r => r.id === regData.role_id);
            const desc = newLang === 'ht' ? (role?.description_ht || role?.description) : role?.description;
            setSelectedRoleDesc(desc || null);
        }
    };

    return (
        <div className="min-h-screen flex flex-col md:flex-row bg-[#050505]">
            {/* Left Panel: Context */}
            <div className="w-full md:w-1/2 p-10 flex flex-col relative overflow-hidden bg-[#0a0a0a]">
                <div className="absolute inset-0 z-0">
                    <div className="absolute top-[-20%] left-[-20%] w-[80%] h-[80%] bg-brand/5 blur-[120px] rounded-full" />
                </div>
                
                <div className="relative z-10 flex-1 flex flex-col justify-center max-w-lg mx-auto w-full">
                    <button onClick={() => router.back()} className="flex items-center gap-2 text-white/40 hover:text-white mb-10 transition-colors">
                        <ArrowLeft className="w-4 h-4" /> {t.backPortal}
                    </button>

                    <div className="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mb-6">
                        {company.logo_url ? <img src={company.logo_url} className="w-12 h-12 object-contain" /> : <Building2 className="w-8 h-8 text-white/40" />}
                    </div>

                    <h1 className="text-4xl font-black uppercase text-white mb-2">{course.name}</h1>
                    <p className="text-white/60 text-lg leading-relaxed mb-8">{course.description || (lang === 'ht' ? 'Konekte pou jwenn kontni a.' : 'Inicia sesión para acceder al contenido.')}</p>

                    <div className="p-6 rounded-2xl bg-white/5 border border-white/10">
                        <div className="flex items-center gap-3 mb-2">
                            <div className={`w-2 h-2 rounded-full ${isRestricted ? 'bg-red-500' : 'bg-green-500'}`} />
                            <span className="text-xs font-black uppercase tracking-widest text-white/40">
                                {isRestricted ? t.restricted : t.open}
                            </span>
                        </div>
                        <p className="text-sm text-white/60">
                            {isRestricted ? t.restrictedDesc : t.openDesc}
                        </p>
                    </div>
                </div>
            </div>

            {/* Right Panel: Auth Forms */}
            <div className="w-full md:w-1/2 bg-black border-l border-white/5 flex items-center justify-center p-6 md:p-12 relative">
                <div className="w-full max-w-md space-y-6">
                    
                    {/* Language Switcher — always visible */}
                    <div className="flex items-center justify-end gap-2">
                        <Globe className="w-3.5 h-3.5 text-white/30" />
                        <button
                            type="button"
                            onClick={() => switchLang('es')}
                            className={`px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all ${lang === 'es' ? 'bg-brand text-black' : 'bg-white/5 text-white/50 hover:text-white'}`}
                        >
                            Español
                        </button>
                        <button
                            type="button"
                            onClick={() => switchLang('ht')}
                            className={`px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all ${lang === 'ht' ? 'bg-brand text-black' : 'bg-white/5 text-white/50 hover:text-white'}`}
                        >
                            Kreyòl
                        </button>
                    </div>

                    {/* Switcher Login/Register (Only if not restricted) */}
                    {!isRestricted && (
                        <div className="flex p-1 bg-white/5 rounded-xl">
                            <button
                                onClick={() => setAuthMode('login')}
                                className={`flex-1 flex items-center justify-center gap-2 py-3 rounded-lg text-sm font-black uppercase tracking-widest transition-all ${authMode === 'login' ? 'bg-brand text-black shadow-lg shadow-brand/20' : 'text-white/40 hover:text-white'}`}
                            >
                                <LogIn className="w-4 h-4" /> {t.login}
                            </button>
                            <button
                                onClick={() => setAuthMode('register')}
                                className={`flex-1 flex items-center justify-center gap-2 py-3 rounded-lg text-sm font-black uppercase tracking-widest transition-all ${authMode === 'register' ? 'bg-brand text-black shadow-lg shadow-brand/20' : 'text-white/40 hover:text-white'}`}
                            >
                                <UserPlus className="w-4 h-4" /> {t.register}
                            </button>
                        </div>
                    )}

                    {error && (
                        <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-500 text-sm rounded-xl font-bold">
                            {error}
                        </div>
                    )}

                    {authMode === 'login' ? (
                        <form onSubmit={handleLogin} className="space-y-6">
                            <div className="space-y-2">
                                <label className="text-xs font-black uppercase text-white/40 tracking-widest">{t.email}</label>
                                <input 
                                    type="email" 
                                    required
                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand outline-none transition-colors"
                                    value={loginData.email}
                                    onChange={e => setLoginData({...loginData, email: e.target.value})}
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-xs font-black uppercase text-white/40 tracking-widest">{t.password}</label>
                                <input 
                                    type="password" 
                                    required
                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand outline-none transition-colors"
                                    value={loginData.password}
                                    onChange={e => setLoginData({...loginData, password: e.target.value})}
                                />
                            </div>
                            <button 
                                disabled={actionLoading}
                                className="w-full py-4 bg-white text-black font-black uppercase tracking-widest rounded-xl hover:scale-[1.02] active:scale-95 transition-all text-xs"
                            >
                                {actionLoading ? t.verifying : t.loginBtn}
                            </button>
                        </form>
                    ) : (
                        <div className="space-y-6">
                            {regStep === 1 && (
                                <div className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.name}</label>
                                            <input type="text" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm" placeholder="Juan" required 
                                                value={regData.first_name} onChange={e => setRegData({...regData, first_name: e.target.value})} />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.surname}</label>
                                            <input type="text" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm" placeholder="Pérez" required 
                                                value={regData.last_name} onChange={e => setRegData({...regData, last_name: e.target.value})} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.email}</label>
                                            <input type="email" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm" required 
                                                value={regData.email} onChange={e => setRegData({...regData, email: e.target.value})} />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.password}</label>
                                            <input 
                                                type="password" 
                                                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm" 
                                                required 
                                                minLength={6}
                                                title={t.passMin}
                                                pattern=".{6,}"
                                                onInvalid={(e) => (e.target as HTMLInputElement).setCustomValidity(t.passMin)}
                                                onInput={(e) => (e.target as HTMLInputElement).setCustomValidity('')}
                                                value={regData.password} onChange={e => setRegData({...regData, password: e.target.value})} />
                                        </div>
                                    </div>

                                    {/* Empresa autocomplete */}
                                    {isFieldVisible('company_collab') && (
                                    <div className="space-y-1 relative empresa-autocomplete">
                                        <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.empresa}</label>
                                        <div className="relative">
                                            <input 
                                                type="text" 
                                                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm pr-10"
                                                placeholder={t.empresaPh}
                                                value={empresaInput} 
                                                onChange={e => {
                                                    setEmpresaInput(e.target.value);
                                                    setEmpresaDropdownOpen(true);
                                                }}
                                                onFocus={() => setEmpresaDropdownOpen(true)}
                                            />
                                            <Search className="w-4 h-4 text-white/30 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                        </div>
                                        {empresaDropdownOpen && empresaInput.length > 0 && (
                                            <div className="absolute z-50 w-full mt-1 bg-[#1a1a1a] border border-white/10 rounded-xl max-h-48 overflow-y-auto shadow-xl">
                                                {companiesList
                                                    .filter(c => c.name_es?.toLowerCase().includes(empresaInput.toLowerCase()))
                                                    .map(c => (
                                                        <button
                                                            key={c.id}
                                                            type="button"
                                                            className="w-full text-left px-4 py-2.5 text-sm text-white hover:bg-white/10 transition-colors first:rounded-t-xl last:rounded-b-xl"
                                                            onClick={() => {
                                                                setEmpresaInput(c.name_es);
                                                                setEmpresaDropdownOpen(false);
                                                            }}
                                                        >
                                                            <Building2 className="w-3.5 h-3.5 inline mr-2 text-white/40" />{c.name_es}
                                                        </button>
                                                    ))}
                                                {companiesList.filter(c => c.name_es?.toLowerCase().includes(empresaInput.toLowerCase())).length === 0 && (
                                                    <button
                                                        type="button"
                                                        className="w-full text-left px-4 py-2.5 text-sm text-brand hover:bg-brand/10 transition-colors rounded-xl"
                                                        onClick={() => setEmpresaDropdownOpen(false)}
                                                    >
                                                        <span className="text-white/40">+</span> {lang === 'ht' ? 'Ajoute' : 'Agregar'}: <span className="font-bold">{empresaInput}</span>
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                    )}
                                    
                                    {/* ID Type + Cargo row */}
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <div className="flex justify-between items-center mb-1">
                                                <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">
                                                    {idType === 'rut' ? t.rut : t.passport}
                                                </label>
                                                <div className="flex gap-1 bg-white/5 p-0.5 rounded-lg">
                                                    <button 
                                                        type="button"
                                                        onClick={() => setIdType('rut')}
                                                        className={`px-2 py-0.5 text-[8px] font-black rounded ${idType === 'rut' ? 'bg-brand text-black' : 'text-white/40'}`}
                                                    >RUT</button>
                                                    <button 
                                                        type="button"
                                                        onClick={() => setIdType('passport')}
                                                        className={`px-2 py-0.5 text-[8px] font-black rounded ${idType === 'passport' ? 'bg-brand text-black' : 'text-white/40'}`}
                                                    >PAS</button>
                                                </div>
                                            </div>
                                            {idType === 'rut' ? (
                                                <>
                                                    <input 
                                                        type="text" 
                                                        className={`w-full bg-white/5 border rounded-xl px-4 py-3 text-white text-sm ${rutError ? 'border-red-500/50' : 'border-white/10'}`}
                                                        placeholder="12.345.678-9"
                                                        required 
                                                        value={regData.rut} 
                                                        onChange={e => handleRutInput(e.target.value)} 
                                                    />
                                                    {rutError && (
                                                        <p className="text-[10px] text-red-400 font-bold">{rutError}</p>
                                                    )}
                                                </>
                                            ) : (
                                                <input 
                                                    type="text" 
                                                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm"
                                                    placeholder="A1234567"
                                                    required 
                                                    value={regData.passport} 
                                                    onChange={e => setRegData({...regData, passport: e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '')})} 
                                                />
                                            )}
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.cargo}</label>
                                            {isFieldVisible('job_position') ? (
                                                <div className="relative">
                                                    <select 
                                                        className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm appearance-none pr-10" 
                                                        style={{ color: regData.role_id ? '#FFFFFF' : '#9CA3AF' }}
                                                        value={regData.role_id} 
                                                        onChange={e => {
                                                            const roleId = e.target.value;
                                                            const role = companyRoles.find(r => r.id === roleId);
                                                            setRegData({...regData, role_id: roleId, position: role?.name || ''});
                                                            const desc = lang === 'ht' 
                                                                ? (role?.description_ht || role?.description) 
                                                                : role?.description;
                                                            setSelectedRoleDesc(desc || null);
                                                            setHasReadJobInfo(false); // Reset acceptance when role changes
                                                            if (desc) {
                                                                setShowJobInfoModal(true); // Open modal automatically when role has description
                                                            }
                                                        }}
                                                    >
                                                        <option value="" style={{ background: '#1a1a1a', color: '#9CA3AF' }}>{t.select}</option>
                                                        {companyRoles.map(role => (
                                                            <option key={role.id} value={role.id} style={{ background: '#1a1a1a', color: '#FFFFFF' }}>
                                                                {lang === 'ht' ? (role.name_ht || role.name) : role.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <ChevronDown className="w-4 h-4 text-white/30 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                                </div>
                                            ) : (
                                                <div className="bg-white/5 border border-dashed border-white/10 rounded-xl px-4 py-3 text-sm text-white/20 italic">
                                                    {lang === 'ht' ? 'Otomatik asiyen' : 'Asignado Automáticamente'}
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Tooltip trigger button instead of full width tooltip */}
                                    {selectedRoleDesc && (
                                        <button 
                                            type="button"
                                            onClick={() => setShowJobInfoModal(true)}
                                            className={`w-full flex items-center justify-between p-3 rounded-xl border transition-all ${
                                                hasReadJobInfo 
                                                    ? 'bg-brand/10 border-brand/30 text-brand' 
                                                    : 'bg-orange-500/10 border-orange-500/30 text-orange-400 animate-pulse'
                                            }`}
                                        >
                                            <div className="flex items-center gap-2">
                                                <Info className="w-4 h-4" />
                                                <span className="text-[10px] font-black uppercase tracking-widest">
                                                    {lang === 'ht' ? 'Enfòmasyon sou Risk' : 'Información de Riesgos'}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-[10px] font-bold">
                                                    {hasReadJobInfo 
                                                        ? (lang === 'ht' ? 'AKSEPTE' : 'ACEPTADO') 
                                                        : (lang === 'ht' ? 'LI KOUNYE A' : 'LEER AHORA')}
                                                </span>
                                                {hasReadJobInfo ? (
                                                    <CheckCircle2 className="w-4 h-4" />
                                                ) : (
                                                    <ChevronDown className="w-4 h-4 rotate-[-90deg]" />
                                                )}
                                            </div>
                                        </button>
                                    )}

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.gender}</label>
                                            {isFieldVisible('gender') ? (
                                                <div className="relative">
                                                    <select 
                                                        className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm appearance-none pr-10" 
                                                        style={{ color: regData.gender ? '#FFFFFF' : '#9CA3AF' }}
                                                        value={regData.gender} 
                                                        onChange={e => setRegData({...regData, gender: e.target.value})}
                                                    >
                                                        <option value="" style={{ background: '#1a1a1a', color: '#9CA3AF' }}>{t.select}</option>
                                                        <option value="Masculino" style={{ background: '#1a1a1a', color: '#FFFFFF' }}>{t.male}</option>
                                                        <option value="Femenino" style={{ background: '#1a1a1a', color: '#FFFFFF' }}>{t.female}</option>
                                                        <option value="Otro" style={{ background: '#1a1a1a', color: '#FFFFFF' }}>{t.other}</option>
                                                    </select>
                                                    <ChevronDown className="w-4 h-4 text-white/30 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                                </div>
                                            ) : (
                                                <div className="bg-white/5 border border-dashed border-white/10 rounded-xl px-4 py-3 text-sm text-white/20 italic">
                                                    -
                                                </div>
                                            )}
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.age}</label>
                                            {isFieldVisible('age') ? (
                                                <input type="number" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm"
                                                    value={regData.age} onChange={e => setRegData({...regData, age: e.target.value})} />
                                            ) : (
                                                <div className="bg-white/5 border border-dashed border-white/10 rounded-xl px-4 py-3 text-sm text-white/20 italic">
                                                    -
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <button onClick={() => {
                                        const hasId = idType === 'rut' ? regData.rut : regData.passport;
                                        if(!regData.email || !regData.password || !hasId) {
                                            setError(t.required); return;
                                        }
                                        // Validate RUT before proceeding
                                        if (idType === 'rut' && !validateRut(regData.rut)) {
                                            setRutError(t.invalidRut);
                                            setError(t.invalidRut);
                                            return;
                                        }
                                        // Specific check for Job Info acceptance
                                        if (selectedRoleDesc && !hasReadJobInfo) {
                                            setError(lang === 'ht' ? 'Ou dwe li epi aksepte enfòmasyon sou pòs la anvan.' : 'Debes leer y aceptar la información del cargo antes de continuar.');
                                            setShowJobInfoModal(true);
                                            return;
                                        }
                                        setError(null);
                                        setRegStep(2);
                                    }} className="w-full mt-4 py-4 bg-brand text-black font-black uppercase tracking-widest rounded-xl hover:scale-[1.02] transition-all text-xs">
                                        {t.continueSign}
                                    </button>
                                </div>
                            )}

                            {regStep === 2 && (
                                <div className="space-y-6">
                                    <div className="text-center">
                                        <h3 className="text-xl font-black text-white">{t.digitalSign}</h3>
                                        <p className="text-xs text-white/40">{t.signDesc}</p>
                                    </div>

                                    <SignatureCanvas onSave={setSignatureUrl} />

                                    <div className="flex gap-4">
                                        <button onClick={() => setRegStep(1)} className="flex-1 py-3 bg-white/10 rounded-xl text-xs font-bold uppercase">{t.back}</button>
                                        <button onClick={handleRegister} className="flex-1 py-3 bg-brand text-black rounded-xl text-xs font-black uppercase" disabled={actionLoading}>
                                            {actionLoading ? t.registering : t.finish}
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
            {/* Job Info Modal */}
            {showJobInfoModal && selectedRoleDesc && (
                <div className="fixed inset-0 z-[200] bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
                    <motion.div 
                        initial={{ opacity: 0, scale: 0.9 }}
                        animate={{ opacity: 1, scale: 1 }}
                        className="glass max-w-2xl w-full flex flex-col max-h-[90vh] border-white/10"
                    >
                        {/* Header */}
                        <div className="p-6 border-b border-white/5 flex items-center justify-between">
                            <h3 className="text-xl font-black uppercase tracking-tight text-white flex items-center gap-2">
                                <Info className="w-5 h-5 text-brand" />
                                {lang === 'ht' ? 'Enfòmasyon sou Pòs' : 'Información del Cargo'}
                            </h3>
                            <div className="text-[10px] font-black uppercase text-brand/60 px-2 py-1 bg-brand/10 border border-brand/20 rounded">
                                {companyRoles.find(r => r.id === regData.role_id)?.name}
                            </div>
                        </div>

                        {/* Content */}
                        <div className="p-8 overflow-y-auto space-y-6 custom-scrollbar">
                            <div 
                                className="prose prose-invert prose-brand max-w-none text-white/80 leading-relaxed"
                                dangerouslySetInnerHTML={{ __html: selectedRoleDesc }}
                            />

                            <div className="p-4 bg-white/5 border border-white/10 rounded-2xl space-y-3">
                                <p className="text-[11px] text-white/60 leading-relaxed italic">
                                    {lang === 'ht' 
                                        ? "Konfòm ak sa ki prevwa nan Dekrè Nº 44, Tit II, paragraf 4, atik 15 nan “ENFÒME RISK TRAVAY (IRL)”. Se poutèt sa, moun ki siyen anba a; deklare li konnen risk ki genyen nan travay l ap fè yo, mezi prevansyon li dwe respekte ak swiv imedyatman, nan fè travay li atravè metòd travay kòrèk ak ansekirite."
                                        : "En cumplimiento a lo dispuesto en el Decreto N° 44, título II, párrafo 4, articulo 15 en “INFORMAR LOS RIESGOS LABORALES (IRL)”. Por tanto, el abajo firmante; declara conocer los riesgos que conllevan las labores que ejecuta, las medidas preventivas que debe respetar y cumplir de manera inmediata, ejecutando sus labores por medio de métodos de trabajos correctos y seguros."
                                    }
                                </p>
                            </div>
                        </div>

                        {/* Footer / Accept */}
                        <div className="p-6 border-t border-white/5 bg-white/[0.02] space-y-4">
                            <label className="flex items-start gap-3 cursor-pointer group">
                                <div className={`mt-0.5 w-5 h-5 rounded border-2 transition-all flex items-center justify-center ${hasReadJobInfo ? 'bg-brand border-brand' : 'border-white/20 group-hover:border-brand/50'}`}>
                                    {hasReadJobInfo && <LogIn className="w-3 h-3 text-black" />}
                                    <input 
                                        type="checkbox" 
                                        className="hidden" 
                                        checked={hasReadJobInfo} 
                                        onChange={e => setHasReadJobInfo(e.target.checked)} 
                                    />
                                </div>
                                <span className={`text-sm font-bold transition-colors ${hasReadJobInfo ? 'text-white' : 'text-white/40'}`}>
                                    {lang === 'ht' ? 'Mwen li epi mwen aksepte enfòmasyon an' : 'He leído y acepto la información'}
                                </span>
                            </label>

                            <button 
                                onClick={() => setShowJobInfoModal(false)}
                                disabled={!hasReadJobInfo}
                                className={`w-full py-4 rounded-xl font-black uppercase tracking-widest text-xs transition-all ${
                                    hasReadJobInfo 
                                        ? 'bg-brand text-black shadow-[0_0_20px_rgba(49,210,45,0.2)] hover:scale-[1.02]' 
                                        : 'bg-white/5 text-white/20 cursor-not-allowed'
                                }`}
                            >
                                {lang === 'ht' ? 'Fèmen' : 'Cerrar'}
                            </button>
                        </div>
                    </motion.div>
                </div>
            )}        </div>
    );
}
