"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { supabase } from "@/lib/supabase";
import { Globe, Building2, Briefcase, Info } from "lucide-react";

export default function StudentRegister() {
    const router = useRouter();
    const [loading, setLoading] = useState(false);
    const [showJobDescription, setShowJobDescription] = useState(false);
    const [selectedJobDescription, setSelectedJobDescription] = useState({ title: "", description: "" });
    const [showOtherCompany, setShowOtherCompany] = useState(false);

    const [formData, setFormData] = useState({
        language: "es",
        first_name: "",
        last_name: "",
        email: "",
        gender: "",
        age: "",
        company: "",
        other_company: "",
        rut: "",
        job_position: "",
        client_id: "c7fd2d19-c6a8-4ea0-b9fa-11082eaacac7" // Sacyr
    });

    const [companies, setCompanies] = useState<any[]>([]);
    const [jobPositions, setJobPositions] = useState<any[]>([]);

    useEffect(() => {
        loadCompanies();
        loadJobPositions();
    }, []);

    const loadCompanies = async () => {
        const { data } = await supabase
            .from('companies_list')
            .select('*')
            .eq('active', true)
            .order('code');
        if (data) setCompanies(data);
    };

    const loadJobPositions = async () => {
        const { data } = await supabase
            .from('job_positions')
            .select('*')
            .eq('active', true)
            .order('code');
        if (data) setJobPositions(data);
    };

    const t = (key: string) => {
        const translations: any = {
            es: {
                title: "Registro de Alumno",
                subtitle: "Curso de Trabajo en Altura",
                language: "Idioma",
                firstName: "Nombre",
                lastName: "Apellido",
                email: "Correo Electrónico",
                gender: "Género",
                genderMale: "Masculino",
                genderFemale: "Femenino",
                genderOther: "Otro",
                age: "Edad",
                company: "Empresa",
                selectCompany: "Seleccione su empresa",
                otherCompany: "Especifique el nombre de su empresa",
                rutPassport: "RUT o Pasaporte",
                jobPosition: "Cargo",
                selectJob: "Seleccione su cargo",
                register: "Registrarse",
                alreadyHaveAccount: "¿Ya tienes cuenta?",
                login: "Iniciar Sesión",
                fillAllFields: "Por favor complete todos los campos obligatorios",
                registering: "Registrando..."
            },
            ht: {
                title: "Enskripsyon Elèv",
                subtitle: "Kou Travay nan Wotè",
                language: "Lang",
                firstName: "Non",
                lastName: "Siyati",
                email: "Imèl",
                gender: "Sèks",
                genderMale: "Gason",
                genderFemale: "Fi",
                genderOther: "Lòt",
                age: "Laj",
                company: "Konpayi",
                selectCompany: "Chwazi konpayi ou",
                otherCompany: "Espesifye non konpayi ou",
                rutPassport: "RUT oswa Paspò",
                jobPosition: "Travay",
                selectJob: "Chwazi travay ou",
                register: "Enskri",
                alreadyHaveAccount: "Ou gen yon kont deja?",
                login: "Konekte",
                fillAllFields: "Tanpri ranpli tout chan obligatwa yo",
                registering: "Ap enskri..."
            }
        };
        return translations[formData.language]?.[key] || key;
    };

    const handleCompanyChange = (value: string) => {
        setFormData({ ...formData, company: value });
        setShowOtherCompany(value === 'OTRA');
    };

    const handleJobChange = async (code: string) => {
        setFormData({ ...formData, job_position: code });

        // Show job description popup
        const job = jobPositions.find(j => j.code === code);
        if (job) {
            const lang = formData.language;
            setSelectedJobDescription({
                title: lang === 'ht' ? job.name_ht || job.name_es : job.name_es,
                description: lang === 'ht' ? job.description_ht || job.description_es : job.description_es
            });
            if (job.description_es || job.description_ht) {
                setShowJobDescription(true);
            }
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        // Validation
        if (!formData.first_name || !formData.last_name || !formData.email ||
            !formData.gender || !formData.age || !formData.company || !formData.rut || !formData.job_position) {
            alert(t('fillAllFields'));
            return;
        }

        if (formData.company === 'OTRA' && !formData.other_company) {
            alert(t('fillAllFields'));
            return;
        }

        setLoading(true);

        try {
            const companyName = formData.company === 'OTRA' ? formData.other_company : formData.company;

            const { data, error } = await supabase
                .from('students')
                .insert({
                    language: formData.language,
                    first_name: formData.first_name,
                    last_name: formData.last_name,
                    email: formData.email,
                    gender: formData.gender,
                    age: parseInt(formData.age),
                    company_name: companyName,
                    rut: formData.rut,
                    job_position: formData.job_position,
                    client_id: formData.client_id
                })
                .select()
                .single();

            if (error) throw error;

            // Store in localStorage and redirect to login
            alert('Registro exitoso. Por favor inicia sesión.');
            router.push('/admin/empresa/alumnos/login');
        } catch (error: any) {
            console.error('Registration error:', error);
            alert('Error al registrar: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black flex items-center justify-center p-4">
            <div className="glass max-w-4xl w-full p-8 rounded-3xl border-white/10">
                {/* Header */}
                <div className="text-center mb-8">
                    <h1 className="text-4xl font-black mb-2">{t('title')}</h1>
                    <p className="text-white/60 text-sm uppercase tracking-wider">{t('subtitle')}</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Language Selector */}
                    <div>
                        <label className="block text-xs text-white/40 uppercase font-bold mb-2">
                            <Globe className="w-4 h-4 inline mr-2" />
                            {t('language')}
                        </label>
                        <select
                            value={formData.language}
                            onChange={(e) => setFormData({ ...formData, language: e.target.value })}
                            className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-brand focus:outline-none"
                            style={{ colorScheme: 'dark' }}
                        >
                            <option value="es" className="bg-neutral-900 text-white">Español</option>
                            <option value="ht" className="bg-neutral-900 text-white">Kreyòl Ayisyen</option>
                        </select>
                    </div>

                    {/* Name Fields */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs text-white/40 uppercase font-bold mb-2">{t('firstName')}</label>
                            <input
                                type="text"
                                value={formData.first_name}
                                onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                                className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-xs text-white/40 uppercase font-bold mb-2">{t('lastName')}</label>
                            <input
                                type="text"
                                value={formData.last_name}
                                onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                                className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                                required
                            />
                        </div>
                    </div>

                    {/* Email */}
                    <div>
                        <label className="block text-xs text-white/40 uppercase font-bold mb-2">{t('email')}</label>
                        <input
                            type="email"
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                            className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                            required
                        />
                    </div>

                    {/* Gender and Age */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs text-white/40 uppercase font-bold mb-2">{t('gender')}</label>
                            <select
                                value={formData.gender}
                                onChange={(e) => setFormData({ ...formData, gender: e.target.value })}
                                className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-brand focus:outline-none"
                                style={{ colorScheme: 'dark' }}
                                required
                            >
                                <option value="" className="bg-neutral-900 text-white">-</option>
                                <option value="Masculino" className="bg-neutral-900 text-white">{t('genderMale')}</option>
                                <option value="Femenino" className="bg-neutral-900 text-white">{t('genderFemale')}</option>
                                <option value="Otro" className="bg-neutral-900 text-white">{t('genderOther')}</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs text-white/40 uppercase font-bold mb-2">{t('age')}</label>
                            <input
                                type="number"
                                value={formData.age}
                                onChange={(e) => setFormData({ ...formData, age: e.target.value })}
                                className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                                required
                                min="1"
                            />
                        </div>
                    </div>

                    {/* Company */}
                    <div>
                        <label className="block text-xs text-white/40 uppercase font-bold mb-2">
                            <Building2 className="w-4 h-4 inline mr-2" />
                            {t('company')}
                        </label>
                        <select
                            value={formData.company}
                            onChange={(e) => handleCompanyChange(e.target.value)}
                            className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-brand focus:outline-none"
                            style={{ colorScheme: 'dark' }}
                            required
                        >
                            <option value="" className="bg-neutral-900 text-white">{t('selectCompany')}</option>
                            {companies.map(c => (
                                <option key={c.code} value={c.code} className="bg-neutral-900 text-white">
                                    {formData.language === 'ht' ? c.name_ht || c.name_es : c.name_es}
                                </option>
                            ))}
                        </select>
                        {showOtherCompany && (
                            <input
                                type="text"
                                value={formData.other_company}
                                onChange={(e) => setFormData({ ...formData, other_company: e.target.value })}
                                placeholder={t('otherCompany')}
                                className="w-full p-3 mt-2 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                                required
                            />
                        )}
                    </div>

                    {/* RUT/Passport */}
                    <div>
                        <label className="block text-xs text-white/40 uppercase font-bold mb-2">{t('rutPassport')}</label>
                        <input
                            type="text"
                            value={formData.rut}
                            onChange={(e) => setFormData({ ...formData, rut: e.target.value })}
                            className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:border-brand focus:outline-none"
                            required
                        />
                    </div>

                    {/* Job Position */}
                    <div>
                        <label className="block text-xs text-white/40 uppercase font-bold mb-2">
                            <Briefcase className="w-4 h-4 inline mr-2" />
                            {t('jobPosition')}
                        </label>
                        <select
                            value={formData.job_position}
                            onChange={(e) => handleJobChange(e.target.value)}
                            className="w-full p-3 bg-white/5 border border-white/10 rounded-xl text-white focus:border-brand focus:outline-none"
                            style={{ colorScheme: 'dark' }}
                            required
                        >
                            <option value="" className="bg-neutral-900 text-white">{t('selectJob')}</option>
                            {jobPositions.map(j => (
                                <option key={j.code} value={j.code} className="bg-neutral-900 text-white">
                                    {formData.language === 'ht' ? j.name_ht || j.name_es : j.name_es}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Submit Button */}
                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full py-4 bg-brand text-black rounded-xl font-bold uppercase text-sm hover:bg-white transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {loading ? t('registering') : t('register')}
                    </button>

                    {/* Login Link */}
                    <div className="text-center">
                        <p className="text-white/60 text-sm">
                            {t('alreadyHaveAccount')}{' '}
                            <a href="/admin/empresa/alumnos/login" className="text-brand hover:underline">
                                {t('login')}
                            </a>
                        </p>
                    </div>
                </form>
            </div>

            {/* Job Description Modal */}
            {showJobDescription && (
                <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4" onClick={() => setShowJobDescription(false)}>
                    <div className="glass max-w-2xl w-full p-8 rounded-3xl border-white/10" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-start gap-4 mb-4">
                            <Info className="w-6 h-6 text-brand flex-shrink-0 mt-1" />
                            <div>
                                <h3 className="text-2xl font-black mb-2">{selectedJobDescription.title}</h3>
                                <p className="text-white/80 whitespace-pre-wrap">{selectedJobDescription.description}</p>
                            </div>
                        </div>
                        <button
                            onClick={() => setShowJobDescription(false)}
                            className="w-full py-3 bg-white/10 rounded-xl font-bold uppercase text-sm hover:bg-white/20 transition-all"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
