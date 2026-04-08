import { Head, Link } from '@inertiajs/react';

export default function Landing() {
    return (
        <>
            <Head title="Freetter - Human stories & ideas" />

            {/* Contenedor principal sin scroll (h-screen y flex-col) */}
            <div className="flex h-screen flex-col overflow-hidden bg-[#F7F4ED] font-sans text-zinc-900">
                {/* Navbar estilo Medium */}
                <header className="flex h-20 shrink-0 items-center justify-between border-b border-black/10 px-6 lg:px-12">
                    <div className="font-serif text-3xl font-bold tracking-tight">
                        Freetter
                    </div>
                    <nav className="flex items-center gap-6 text-sm font-medium">
                        <Link href="#" className="hidden text-zinc-600 hover:text-black sm:block">
                            Our story
                        </Link>
                        <Link href="#" className="hidden text-zinc-600 hover:text-black sm:block">
                            Membership
                        </Link>
                        <Link href="#" className="hidden text-zinc-600 hover:text-black sm:block">
                            Write
                        </Link>
                        <Link href="#" className="text-zinc-600 hover:text-black">
                            Sign in
                        </Link>
                        <Link
                            href="#"
                            className="rounded-full bg-black px-4 py-2.5 text-white transition hover:bg-zinc-800"
                        >
                            Get started
                        </Link>
                    </nav>
                </header>

                {/* Hero Section sin scroll - Ocupa el espacio sobrante */}
                <main className="flex flex-1 items-center px-6 lg:px-12 relative overflow-hidden">
                    <div className="z-10 w-full max-w-3xl">
                        <h1 className="mb-6 font-serif text-6xl tracking-tighter sm:text-7xl lg:text-[100px] lg:leading-[0.95]">
                            Human
                            <br />
                            stories & ideas
                        </h1>
                        <p className="mb-10 text-xl tracking-tight text-zinc-700 sm:text-2xl">
                            A place to read, write, and deepen your understanding
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
                         <div className="absolute right-32 top-32 h-[300px] w-[300px] rounded-full bg-[#1A8917] blur-3xl opacity-40"></div>
                         <div className="absolute right-64 bottom-32 h-[400px] w-[400px] rounded-full bg-[#1A8917] blur-3xl opacity-20"></div>
                    </div>
                </main>

                {/* Footer sencillo adherido al final */}
                <footer className="flex shrink-0 items-center justify-center gap-4 border-t border-black/10 px-6 py-4 text-xs text-zinc-500 bg-[#F7F4ED] z-20">
                    <Link href="#" className="hover:text-zinc-800 transition">Help</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Status</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">About</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Careers</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Press</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Blog</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Privacy</Link>
                    <Link href="#" className="hover:text-zinc-800 transition">Terms</Link>
                    <Link href="#" className="hidden sm:inline hover:text-zinc-800 transition">Text to speech</Link>
                </footer>
            </div>
        </>
    );
}
