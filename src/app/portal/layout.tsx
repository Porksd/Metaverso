export default function PortalLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="min-h-screen text-white font-sans selection:bg-brand selection:text-black">
            {children}
        </div>
    );
}
