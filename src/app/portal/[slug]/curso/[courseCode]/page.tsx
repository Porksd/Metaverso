"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { Lock, ArrowLeft, LogIn, UserPlus, Building2, Globe, Info, ChevronDown, Search } from "lucide-react";
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
    const [rutError, setRutError] = useState<string | null>(null);
    const [companiesList, setCompaniesList] = useState<any[]>([]);
    const [empresaInput, setEmpresaInput] = useState('');
    const [empresaDropdownOpen, setEmpresaDropdownOpen] = useState(false);
    
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
            male: 'Masculino', female: 'Femenino', other: 'Otro', language: 'Idioma',
            empresa: 'Empresa', empresaPh: 'Escribe para buscar o agregar...',
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
            male: 'Gason', female: 'Fi', other: 'Lòt', language: 'Lang',
            empresa: 'Antrepriz', empresaPh: 'Ekri pou chèche oswa ajoute...',
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

                // 3. Fetch ALL company roles for Cargo selector
                // Global roles + company-specific roles (dedup by name, company ones take priority)
                const { data: allRoles } = await supabase
                    .from('company_roles')
                    .select('id, name, name_ht, description, description_ht, company_id')
                    .order('name');
                setCompanyRoles(allRoles || []);

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
            // El administrador crea estudiantes directamente en la tabla 'students' sin crear registro en auth.users
            const { data: student, error: studentError } = await supabase
                .from('students')
                .select('*')
                .or(`email.eq.${loginData.email},rut.eq.${loginData.email}`)
                .eq('password', loginData.password)
                .single();

            if (studentError || !student) {
                throw new Error("Credenciales inválidas. Verifica tu email/RUT y contraseña.");
            }

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
            const cleanedRut = cleanRut(regData.rut);
            const res = await fetch('/api/students/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ...regData,
                    rut: cleanedRut,
                    language: lang,
                    company_name: trimmedEmpresa || company.name, 
                    client_id: company.id,
                    role_id: regData.role_id || null,
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
                                    
                                    {/* RUT + Cargo row */}
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.rut}</label>
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
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.cargo}</label>
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
                                        </div>
                                    </div>

                                    {/* Tooltip full width — below RUT+Cargo row, arrow points to cargo */}
                                    {selectedRoleDesc && (
                                        <div className="relative bg-brand/10 border border-brand/20 rounded-xl p-3">
                                            {/* Arrow pointing up-right toward Cargo column */}
                                            <div className="absolute -top-2 right-[25%] w-0 h-0 border-l-[8px] border-l-transparent border-r-[8px] border-r-transparent border-b-[8px] border-b-brand/20" />
                                            <div className="flex items-start gap-2.5">
                                                <Info className="w-4 h-4 text-brand mt-0.5 shrink-0" />
                                                <div>
                                                    <p className="text-[10px] font-black text-brand/60 uppercase tracking-wider mb-0.5">
                                                        {companyRoles.find(r => r.id === regData.role_id)?.name || t.cargo}
                                                    </p>
                                                    <p className="text-[12px] text-brand/80 leading-relaxed">{selectedRoleDesc}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.gender}</label>
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
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-black uppercase text-white/40 tracking-widest">{t.age}</label>
                                            <input type="number" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm"
                                                value={regData.age} onChange={e => setRegData({...regData, age: e.target.value})} />
                                        </div>
                                    </div>

                                    <button onClick={() => {
                                        if(!regData.email || !regData.password || !regData.rut) {
                                            setError(t.required); return;
                                        }
                                        // Validate RUT before proceeding
                                        if (!validateRut(regData.rut)) {
                                            setRutError(t.invalidRut);
                                            setError(t.invalidRut);
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
        </div>
    );
}
