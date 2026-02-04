import { redirect } from 'next/navigation';

export default function RootLanding() {
    redirect('/admin');
}

      {/* Background Aesthetics - ULTRA PREMUM */}
      <div className="fixed inset-0 z-0 text-[#31D22D]">
        <div className="absolute top-[-20%] left-[-10%] w-[70%] h-[70%] bg-current opacity-10 rounded-full blur-[160px] animate-pulse pointer-events-none" />
        <div className="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-current opacity-5 rounded-full blur-[140px] pointer-events-none" />
        <div className="absolute top-[30%] right-[10%] w-[40%] h-[40%] bg-blue-500/5 rounded-full blur-[120px] pointer-events-none" />
      </div>

      {/* Matrix-like Pulse Points */}
      <div className="fixed inset-0 z-0 opacity-[0.05] pointer-events-none">
        <div className="absolute top-1/4 left-1/4 w-1 h-1 bg-brand rounded-full animate-ping" />
        <div className="absolute top-3/4 right-1/3 w-1 h-1 bg-brand rounded-full animate-ping [animation-delay:1s]" />
        <div className="absolute top-1/2 right-1/4 w-1 h-1 bg-blue-400 rounded-full animate-ping [animation-delay:2s]" />
      </div>

      {/* Grid Pattern Overlay */}
      <div className="fixed inset-0 z-0 opacity-[0.03] pointer-events-none"
        style={{ backgroundImage: 'radial-gradient(circle, #31D22D 1px, transparent 1px)', backgroundSize: '50px 50px' }} />

      <div className="max-w-6xl w-full space-y-16 relative z-10">

        <header className="text-center space-y-8">
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            className="flex items-center gap-4 justify-center"
          >
            <div className="flex items-center justify-center gap-3 text-brand text-[10px] font-black uppercase tracking-[0.5em] bg-brand/10 px-8 py-3 rounded-full border border-brand/20 backdrop-blur-xl shadow-[0_0_30px_rgba(49,210,45,0.1)]">
              <ShieldCheck className="w-4 h-4 animate-pulse" /> Ecosistema Metaverso Otec
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 0.2 }}
            className="space-y-4"
          >
            <h1 className="text-4xl md:text-6xl font-black tracking-tighter leading-tight uppercase">
              Metaverso <span className="text-brand">Otec</span>
            </h1>
            <div className="h-1 w-24 bg-brand mx-auto mt-6 rounded-full shadow-[0_0_30px_#31D22D]" />
            <p className="text-white/20 text-xs font-black uppercase tracking-[0.4em] mt-4">Administrador de cursos</p>
          </motion.div>

          <p className="text-white/40 text-lg font-medium max-w-2xl mx-auto leading-relaxed tracking-wide">
            Arquitectura centralizada para la gestión de activos educativos y capital humano de clase mundial.
          </p>
        </header>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-10 px-4">
          <Link href="/admin/metaverso?target=empresas" className="group">
            <motion.div
              whileHover={{ y: -12, scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className="glass p-14 h-full border-white/5 hover:border-brand/40 transition-all duration-500 space-y-10 relative overflow-hidden group-hover:shadow-[0_30px_80px_rgba(49,210,45,0.15)] bg-white/[0.02]"
            >
              <div className="absolute -top-12 -right-12 p-8 opacity-5 group-hover:opacity-10 transition-all duration-700 rotate-12 group-hover:rotate-0">
                <Building2 className="w-56 h-56" />
              </div>

              <div className="w-24 h-24 rounded-[2rem] bg-brand/10 flex items-center justify-center border border-brand/20 shadow-inner group-hover:bg-brand/20 transition-colors">
                <Building2 className="w-12 h-12 text-brand" />
              </div>

              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <h3 className="text-3xl font-black uppercase tracking-tighter">Empresas</h3>
                  <Globe className="w-5 h-5 text-white/10 group-hover:text-brand transition-colors" />
                </div>
                <p className="text-white/40 text-sm leading-relaxed font-medium">Gestión de firmas digitales, control de cupos corporativos y analíticas de cumplimiento legal en tiempo real.</p>
              </div>

              <div className="flex items-center gap-3 text-brand text-[11px] font-black uppercase tracking-[0.3em] pt-6 opacity-60 group-hover:opacity-100 transition-opacity">
                Acceder Protocolo <ArrowRight className="w-4 h-4 group-hover:translate-x-2 transition-transform" />
              </div>
            </motion.div>
          </Link>

          <Link href="/admin/cursos" className="group">
            <motion.div
              whileHover={{ y: -12, scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className="glass p-14 h-full border-white/5 hover:border-brand/40 transition-all duration-500 space-y-10 relative overflow-hidden group-hover:shadow-[0_30px_80px_rgba(49,210,45,0.15)] bg-white/[0.02]"
            >
              <div className="absolute -top-12 -right-12 p-8 opacity-5 group-hover:opacity-10 transition-all duration-700 -rotate-12 group-hover:rotate-0">
                <BookOpen className="w-56 h-56" />
              </div>

              <div className="w-24 h-24 rounded-[2rem] bg-brand/10 flex items-center justify-center border border-brand/20 shadow-inner group-hover:bg-brand/20 transition-colors">
                <BookOpen className="w-12 h-12 text-brand" />
              </div>

              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <h3 className="text-4xl font-black uppercase tracking-tighter">Catálogo</h3>
                  <Cpu className="w-5 h-5 text-white/10 group-hover:text-brand transition-colors" />
                </div>
                <p className="text-white/40 text-sm leading-relaxed font-medium">Definición técnica de currículum, despliegue de paquetes SCORM e ingeniería de evaluación interactiva.</p>
              </div>

              <div className="flex items-center gap-3 text-brand text-[11px] font-black uppercase tracking-[0.3em] pt-6 opacity-60 group-hover:opacity-100 transition-opacity">
                Abrir Ingeniería <ArrowRight className="w-4 h-4 group-hover:translate-x-2 transition-transform" />
              </div>
            </motion.div>
          </Link>
        </div>

        <footer className="text-center space-y-8 pt-16">
          <div className="flex justify-center gap-12">
            <div className="flex items-center gap-3 text-[10px] font-black text-white/20 tracking-[0.4em] uppercase">
              <Zap className="w-4 h-4 text-brand" /> System: Stable
            </div>
            <div className="flex items-center gap-3 text-[10px] font-black text-white/20 tracking-[0.4em] uppercase">
              <Terminal className="w-4 h-4 text-brand" /> Build: 2.0.4r
            </div>
          </div>
          <div className="h-px w-full max-w-xs bg-gradient-to-r from-transparent via-white/10 to-transparent mx-auto" />
          <p className="text-white/10 text-[10px] font-black uppercase tracking-[0.6em]">© 2026 Metaverso Otec S.A. - Neural Infrastructure</p>
        </footer>
      </div>
    </div>
  );
}
