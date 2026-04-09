import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Landing() {
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
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
                    className="flex h-20 shrink-0 items-stretch justify-between border-b border-black"
                    onClick={() => isMobileMenuOpen && setIsMobileMenuOpen(false)}
                >
                    <div className="flex items-center px-6 lg:px-12">
                        <div className="font-serif text-3xl font-bold tracking-tight">
                            Freetter
                        </div>
                    </div>

                    <div className="flex items-center">
                        <nav className="flex h-full items-center gap-3 px-4 text-sm font-medium lg:gap-6 lg:px-12 pl-0 md:px-6">
                            <Link href="#" className="hidden text-zinc-600 hover:text-black lg:block">
                                Our story
                            </Link>
                            <Link href="#" className="hidden text-zinc-600 hover:text-black lg:block">
                                Membership
                            </Link>
                            <Link href="#" className="hidden text-zinc-600 hover:text-black lg:block">
                                Write
                            </Link>
                            <Link href="#" className="hidden text-zinc-600 hover:text-black lg:block">
                                Sign in
                            </Link>
                            <Link href="#" className="rounded-full bg-black px-3 py-2 text-xs text-white transition hover:bg-zinc-800 sm:px-4 sm:py-2 shrink-0">
                                Get started
                            </Link>
                        </nav>
                        <div className="flex h-full lg:hidden border-l border-zinc-200">
                            <button
                                type="button"
                                aria-label={isMobileMenuOpen ? "Close menu" : "Open main menu"}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setIsMobileMenuOpen(!isMobileMenuOpen);
                                }}
                                className={`flex h-full w-20 items-center justify-center transition-colors ${
                                    isMobileMenuOpen
                                        ? 'bg-[#0f0f0f] text-white hover:bg-black'
                                        : 'text-zinc-900 hover:bg-black/5'
                                }`}
                            >
                                <span className="sr-only">{isMobileMenuOpen ? 'Close menu' : 'Open main menu'}</span>
                                {isMobileMenuOpen ? (
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true" className="size-8">
                                        <path d="M6 18L18 6M6 6l12 12" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                ) : (
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true" className="size-8">
                                        <path d="M4 9h16" strokeLinecap="round" />
                                        <path d="M4 15h16" strokeLinecap="round" />
                                    </svg>
                                )}
                            </button>
                        </div>
                    </div>
                </header>

                {/* Menú móvil full screen */}
                {isMobileMenuOpen && (
                    <div className="absolute inset-x-0 bottom-0 top-20 z-50 flex flex-col bg-[#0f0f0f] text-[#F7F4ED] lg:hidden">
                        <div className="flex-1 overflow-y-auto px-6 py-22">
                            <nav className="flex flex-col gap-6 text-2xl">
                                {/* Ponemos href="/" para Our story y lo marca clarito en la landing */}
                                <Link href="/" className={getMobileLinkClass('/')}>Our story</Link>
                                <Link href="/membership" className={getMobileLinkClass('/membership')}>Membership</Link>
                                <Link href="/write" className={getMobileLinkClass('/write')}>Write</Link>
                                <Link href="/login" className={getMobileLinkClass('/login')}>Sign in</Link>
                            </nav>
                        </div>
                        <div className="flex justify-between px-6 py-6 text-xs text-zinc-500 font-medium tracking-wide">
                            <span>Freetter</span>
                        </div>
                    </div>
                )}

                <main className="flex flex-1 items-center px-6 lg:px-12 relative overflow-hidden">
                    <div className="z-10 w-full max-w-3xl">
                        <h1 className="mb-6 font-serif text-7xl tracking-tighter sm:text-8xl lg:text-[100px] lg:leading-[0.95]">
                            Put on your voice
                        </h1>
                        <p className="mb-10 text-xl tracking-tight text-zinc-700 sm:text-2xl">
                            A place to read, write, and discover stories.
                        </p>
                        <Link
                            href="#"
                            className="inline-block rounded-full bg-black px-8 py-3 text-lg font-medium text-white transition hover:bg-zinc-800"
                        >
                            Start reading
                        </Link>
                    </div>

                    {/* Decoración abstracta simulando las ilustraciones de la derecha */}
                    <div className="pointer-events-none absolute right-0 top-0 h-full w-1/2 flex items-center justify-end overflow-hidden opacity-10 xl:opacity-100 mix-blend-multiply">
                         {/* Podemos colocar aquí gráficos SVG o imágenes más adelante. Usamos formas de Tailwind como placeholder */}
                         <div className="absolute right-32 top-32 h-75 w-75 rounded-full bg-[#1A8917] blur-3xl opacity-40"></div>
                         <div className="absolute right-64 bottom-32 h-100 w-100 rounded-full bg-[#1A8917] blur-3xl opacity-20"></div>
                    </div>
                </main>

                <footer className="flex shrink-0 items-center justify-center md:justify-start gap-5  md:gap-4 p-4 md:px-6 md:py-4 text-xs sm:text-sm text-[#F7F4ED] md:text-zinc-700 bg-[#0f0f0f] md:bg-[#F7F4ED] z-20">
                    <Link href="#" className="hover:text-zinc-800 transition">About</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Privacy</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Terms</Link>
                </footer>
            </div>
        </>
    );
}
