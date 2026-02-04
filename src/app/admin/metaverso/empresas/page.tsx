"use client";

import { useState, useEffect } from "react";
import { supabase } from "@/lib/supabase";
import { Building2, Plus, Edit, Trash2, Save, X, Upload } from "lucide-react";
import ContentUploader from "@/components/ContentUploader";

export default function CompaniesAdmin() {
    const [companies, setCompanies] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [editingCompany, setEditingCompany] = useState<any>(null);
    const [showModal, setShowModal] = useState(false);

    useEffect(() => {
        fetchCompanies();
    }, []);

    const fetchCompanies = async () => {
        setLoading(true);
        // Join companies_list (catalogo) with companies (configuración) if needed, 
        // or just manage companies table directly depending on architecture.
        // Assuming 'companies' table holds the configuration (signatures) and student access code?
        // Let's inspect, likely 'companies' is the detailed config and 'companies_list' is the dropdown source.

        // For now, let's look at 'companies' table which seems to be the main one used in login/certificate
        const { data } = await supabase.from('companies').select('*').order('created_at');
        if (data) setCompanies(data);
        setLoading(false);
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();

        const companyData = {
            name: editingCompany.name,
            rut: editingCompany.rut,
            // Add other fields as necessary
            signature_name_1: editingCompany.signature_name_1,
            signature_role_1: editingCompany.signature_role_1,
            signature_url_1: editingCompany.signature_url_1,
            signature_name_2: editingCompany.signature_name_2,
            signature_role_2: editingCompany.signature_role_2,
            signature_url_2: editingCompany.signature_url_2,
            signature_name_3: editingCompany.signature_name_3,
            signature_role_3: editingCompany.signature_role_3,
            signature_url_3: editingCompany.signature_url_3,
        };

        let error;
        if (editingCompany.id) {
            const { error: err } = await supabase
                .from('companies')
                .update(companyData)
                .eq('id', editingCompany.id);
            error = err;
        } else {
            const { error: err } = await supabase
                .from('companies')
                .insert(companyData);
            error = err;
        }

        if (error) {
            alert('Error guardando empresa');
            console.error(error);
        } else {
            setShowModal(false);
            fetchCompanies();
        }
    };

    return (
        <div className="p-8 text-white min-h-screen">
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-black">Gestión de Empresas</h1>
                    <p className="text-white/40">Configura logos y firmas para los certificados</p>
                </div>
                <button
                    onClick={() => { setEditingCompany({}); setShowModal(true); }}
                    className="bg-brand text-black px-4 py-2 rounded-xl font-bold flex items-center gap-2 hover:bg-white transition-colors"
                >
                    <Plus className="w-4 h-4" /> Nueva Empresa
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {companies.map(company => (
                    <div key={company.id} className="glass p-6 rounded-2xl border-white/10 hover:border-brand/30 transition-all group">
                        <div className="flex justify-between items-start mb-4">
                            <div className="p-3 bg-white/5 rounded-xl">
                                <Building2 className="w-8 h-8 text-brand" />
                            </div>
                            <div className="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    onClick={() => { setEditingCompany(company); setShowModal(true); }}
                                    className="p-2 hover:bg-white/10 rounded-lg text-blue-400"
                                >
                                    <Edit className="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <h3 className="text-xl font-bold mb-1">{company.name}</h3>
                        <p className="text-white/40 text-sm mb-4">RUT: {company.rut}</p>

                        <div className="space-y-2 border-t border-white/5 pt-4">
                            <div className="flex items-center gap-2 text-xs text-white/60">
                                <div className={`w-2 h-2 rounded-full ${company.signature_url_1 ? 'bg-green-500' : 'bg-red-500'}`} />
                                Firma 1: {company.signature_name_1 || 'No configurada'}
                            </div>
                            <div className="flex items-center gap-2 text-xs text-white/60">
                                <div className={`w-2 h-2 rounded-full ${company.signature_url_2 ? 'bg-green-500' : 'bg-red-500'}`} />
                                Firma 2: {company.signature_name_2 || 'No configurada'}
                            </div>
                            <div className="flex items-center gap-2 text-xs text-white/60">
                                <div className={`w-2 h-2 rounded-full ${company.signature_url_3 ? 'bg-green-500' : 'bg-red-500'}`} />
                                Firma 3: {company.signature_name_3 || 'No configurada'}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Modal de Edición */}
            {showModal && (
                <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="glass max-w-4xl w-full max-h-[90vh] overflow-y-auto rounded-3xl p-8 border-white/10">
                        <div className="flex justify-between items-center mb-8">
                            <h2 className="text-2xl font-bold">Configuración de Empresa</h2>
                            <button onClick={() => setShowModal(false)} className="p-2 hover:bg-white/10 rounded-full">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={handleSave} className="space-y-8">
                            {/* Datos Básicos */}
                            <section className="space-y-4">
                                <h3 className="text-sm font-black uppercase text-brand tracking-widest">Información General</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-bold mb-2">Nombre Empresa</label>
                                        <input
                                            value={editingCompany.name || ''}
                                            onChange={e => setEditingCompany({ ...editingCompany, name: e.target.value })}
                                            className="w-full bg-white/5 border border-white/10 p-3 rounded-xl focus:border-brand outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold mb-2">RUT Empresa</label>
                                        <input
                                            value={editingCompany.rut || ''}
                                            onChange={e => setEditingCompany({ ...editingCompany, rut: e.target.value })}
                                            className="w-full bg-white/5 border border-white/10 p-3 rounded-xl focus:border-brand outline-none"
                                        />
                                    </div>
                                </div>
                            </section>

                            {/* Firmas */}
                            <section className="space-y-6">
                                <h3 className="text-sm font-black uppercase text-brand tracking-widest">Firmas del Certificado</h3>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {[1, 2, 3].map(num => (
                                        <div key={num} className="bg-white/5 p-4 rounded-xl border border-white/5 space-y-3">
                                            <h4 className="font-bold text-center border-b border-white/10 pb-2">Firmante {num}</h4>

                                            <ContentUploader
                                                courseId={`company_${editingCompany.id || 'new'}`}
                                                sectionKey={`signature_${num}`}
                                                label="Imagen Firma (PNG)"
                                                accept="image/*"
                                                currentValue={editingCompany[`signature_url_${num}`]}
                                                onUploadComplete={(url) => setEditingCompany({ ...editingCompany, [`signature_url_${num}`]: url })}
                                            />

                                            <div>
                                                <label className="text-[10px] uppercase font-bold text-white/40">Nombre</label>
                                                <input
                                                    value={editingCompany[`signature_name_${num}`] || ''}
                                                    onChange={e => setEditingCompany({ ...editingCompany, [`signature_name_${num}`]: e.target.value })}
                                                    className="w-full bg-black/20 border border-white/5 p-2 rounded-lg text-sm"
                                                />
                                            </div>
                                            <div>
                                                <label className="text-[10px] uppercase font-bold text-white/40">Cargo</label>
                                                <input
                                                    value={editingCompany[`signature_role_${num}`] || ''}
                                                    onChange={e => setEditingCompany({ ...editingCompany, [`signature_role_${num}`]: e.target.value })}
                                                    className="w-full bg-black/20 border border-white/5 p-2 rounded-lg text-sm"
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </section>

                            <div className="flex justify-end gap-3 pt-4 border-t border-white/10">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-6 py-3 rounded-xl font-bold hover:bg-white/5 transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    className="px-6 py-3 bg-brand text-black rounded-xl font-bold hover:bg-white transition-colors"
                                >
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
