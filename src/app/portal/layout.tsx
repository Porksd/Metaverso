export default function PortalLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="min-h-screen bg-[#050505] text-white font-sans selection:bg-brand selection:text-black">
            {children}
        </div>
    );
}
