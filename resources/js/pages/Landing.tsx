import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { useIsMobile } from '../hooks/use-mobile';
import { AuthModal } from '../components/auth-modal';

export default function Landing() {
    const isMobile = useIsMobile();
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [isAuthOpen, setIsAuthOpen] = useState(false);
    const [authMode, setAuthMode] = useState<'signin' | 'signup'>('signin');
    const { url } = usePage();
    // Helper para determinar si el link está activo
    const getMobileLinkClass = (path: string) => {
        // En la landing (/) consideraremos activo 'Our story' si así lo configuras, o puedes mapearlo exacto
        const isActive = url === path;

        // Activo: color clarito (#F7F4ED completo). Inactivo: crema oscurecido/opaco (text-[#F7F4ED]/40)
        return `border-b border-zinc-800 pb-4 tracking-tight transition-colors ${
            isActive ? 'text-[#F7F4ED]' : 'text-[#F7F4ED]/40 hover:text-[#F7F4ED]'
        }`;
    };

    return (
        <>
            <Head title="Freetter - Put on your voice" />

            {/* Contenedor principal sin scroll (h-screen y flex-col) */}
            <div className="flex h-screen flex-col overflow-hidden bg-[#F7F4ED] font-sans text-zinc-900">
                {/* Navbar estilo Medium */}
                <header
                        className="
                            flex h-20
                            shrink-0
                            items-stretch
                            justify-between
                            border-b
                            border-black
                        "
                        onClick={() => {
                            if (isMobileMenuOpen) setIsMobileMenuOpen(false);
                            if (isAuthOpen) setIsAuthOpen(false);
                        }}
                    >

                    <div className="flex items-center px-6 lg:px-12">
                        <Link href="/" className="satoshi-bold text-4xl tracking-tight">
                            Freetter
                        </Link>
                    </div>

                    <div className="flex items-center">
                        <nav className="
                                flex
                                satoshi-medium
                                h-full
                                items-center
                                gap-3 px-4
                                text-sm
                                lg:gap-6
                                lg:px-12
                                pl-0
                                md:px-6
                            ">
                            <Link href="#" className="hidden text-zinc-600 hover:text-black lg:block">
                                Our story
                            </Link>
                            <Link href="#" className="hidden text-zinc-600 hover:text-black lg:block">
                                Membership
                            </Link>
                            <Link href="#" className="hidden text-zinc-600 hover:text-black lg:block">
                                Write
                            </Link>
                            <button
                                onClick={(e) => { e.stopPropagation(); setAuthMode('signin'); setIsAuthOpen(true); }}
                                className="hidden text-zinc-600 hover:text-black lg:block"
                            >
                                Sign in
                            </button>
                            <button
                                onClick={(e) => { e.stopPropagation(); setAuthMode('signup'); setIsAuthOpen(true); }}
                                className="
                                    hidden md:rounded-full
                                    text-zinc-200
                                    md:bg-black transition
                                    hover:bg-zinc-800
                                    hover:text-zinc-100
                                    md:px-4 md:py-2
                                    md:block
                                    shrink-0
                                ">
                                Get started
                            </button>
                        </nav>

                        <div className="flex h-full lg:hidden ">
                            <button
                                type="button"
                                aria-label={isMobileMenuOpen ? "Close menu" : "Open main menu"}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setIsAuthOpen(false);
                                    setIsMobileMenuOpen(!isMobileMenuOpen);
                                }}
                                className={`flex h-full w-20 items-center justify-center transition-colors ${
                                    isMobileMenuOpen
                                        ? 'bg-[#0f0f0f] text-white hover:bg-black'
                                        : 'text-zinc-900 hover:bg-black/5'
                                }`}>

                                <span className="sr-only">{isMobileMenuOpen ? 'Close menu' : 'Open main menu'}</span>

                                {isMobileMenuOpen ? (
                                    <svg viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.5"
                                        aria-hidden="true"
                                        className="size-8
                                    ">
                                        <path d="M6 18L18 6M6 6l12 12" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                ) : (
                                    <svg viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.6"
                                        aria-hidden="true"
                                        className="size-8
                                    ">
                                        <path d="M1 9h22" strokeLinecap="round" />
                                        <path d="M1 15h22" strokeLinecap="round" />
                                    </svg>
                                )}
                            </button>
                        </div>
                    </div>
                </header>

                {/* Menú móvil full screen */}
                {isMobileMenuOpen && (
                    <div className="
                            absolute
                            inset-x-0
                            bottom-0
                            top-20
                            z-50
                            flex
                            flex-col
                            bg-[#0f0f0f]
                            text-[#F7F4ED]
                            lg:hidden
                        ">

                        <div className="flex-1 overflow-y-auto px-6 py-22">
                            <nav className="flex flex-col gap-6 text-2xl">
                                {/* Ponemos href="/" para Our story y lo marca clarito en la landing */}
                                <Link href="/" className={getMobileLinkClass('/')}>Our story</Link>
                                <Link href="/membership" className={getMobileLinkClass('/membership')}>Membership</Link>
                                <Link href="/write" className={getMobileLinkClass('/write')}>Write</Link>
                            </nav>
                        </div>

                        <div className="flex justify-between px-6 py-6 text-xs text-zinc-500 font-medium tracking-wide">
                            <span>Freetter</span>
                        </div>
                    </div>
                )}

                <main className="flex flex-1 items-center px-6 lg:px-12 relative overflow-hidden">
                    <div className="z-10 w-full max-w-3xl">
                        <h1 className="mb-6 font-sans text-7xl tracking-tighter sm:text-8xl lg:text-[100px] lg:leading-[0.95]">
                            Put on flame your thoughts
                        </h1>
                        <p className="mb-10 satoshi-medium text-xl tracking-tight text-zinc-700 sm:text-2xl">
                            A place to read, write, and discover newsletters
                        </p>
                        <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setAuthMode(isMobile ? "signup" : "signin");
                                    setIsAuthOpen(true);
                                }}
                                className="
                                inline-block
                                rounded-lg
                                bg-black
                                px-8
                                py-3
                                text-lg
                                satoshi-regular
                                text-white
                                transition
                                hover:bg-zinc-800
                            ">
                            {isMobile ? "Get started" : "Start reading"}
                        </button>
                    </div>
                </main>

                <footer className="
                        flex
                        satoshi-light
                        shrink-0
                        items-center
                        justify-center
                        md:justify-start
                        gap-5
                        md:gap-4
                        p-4 md:px-6
                        md:py-4
                        text-xs
                        sm:text-sm
                        text-[#F7F4ED]
                        md:text-zinc-700
                        bg-[#0f0f0f]
                        md:bg-[#F7F4ED] z-20
                    ">
                    <Link href="#" className="hover:text-zinc-900 transition ">About</Link>
                    <Link href="#" className="hover:text-zinc-900 transition">Privacy</Link>
                    <Link href="#" className="hover:text-zinc-900 transition">Terms</Link>
                </footer>
                <AuthModal isOpen={isAuthOpen} onClose={() => setIsAuthOpen(false)} initialMode={authMode} />
            </div>
        </>
    );
}
