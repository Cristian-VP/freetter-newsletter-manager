import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AuthModal } from '../components/auth-modal';
import { Logotipo } from '@/components/logotipo';

export default function OurStory() {
    const [isAuthOpen, setIsAuthOpen] = useState(false);
    const [authMode, setAuthMode] = useState<'signin' | 'signup'>('signin');

    return (
        <>
            <Head title="Freetter - Our Story" />
            
            <div className="min-h-screen bg-[#242424] text-[#F9F9F9] selection:bg-[#F9F9F9] selection:text-[#242424] font-serif">
                {/* Header */}
                <header className="flex h-20 items-center justify-between px-6 lg:px-12 border-b border-white/10 font-sans">
                    <Link href="/" className="hover:opacity-80 transition-opacity">
                        <Logotipo className="h-8 w-auto text-white" />
                    </Link>
                    
                    <div className="flex items-center gap-4 text-sm font-medium">
                        <button
                            onClick={() => { setAuthMode('signin'); setIsAuthOpen(true); }}
                            className="rounded-full border border-white/30 px-4 py-1.5 text-white transition hover:border-white"
                        >
                            Sign in
                        </button>
                        <button
                            onClick={() => { setAuthMode('signup'); setIsAuthOpen(true); }}
                            className="rounded-full bg-white px-4 py-1.5 text-black transition hover:bg-white/90"
                        >
                            Sign up
                        </button>
                    </div>
                </header>

                {/* Content */}
                <main className="px-6 py-20 lg:px-12 lg:py-32 max-w-4xl text-left">
                    <h1 className="mb-16 text-5xl md:text-7xl lg:text-[100px] tracking-tight leading-[1.05] font-serif text-white max-w-2xl">
                        Everyone has a story to tell
                    </h1>
                    
                    <div className="space-y-8 text-lg md:text-xl lg:text-2xl leading-relaxed text-[#D1D1D1] max-w-2xl font-serif">
                        <p>
                            Freetter is an open-source home for human stories, ideas, and sustainable newsletters. Here, any creator, social collective, or organization can share knowledge and wisdom with the world—without the barrier of paywalls, extractive commissions, or opaque algorithms. The digital landscape is increasingly dominated by closed, profit-driven platforms; Freetter is a community-governed alternative. It's transparent, collaborative, and designed to give you full ownership over your audience and your work.
                        </p>
                        
                        <p>
                            <span className="bg-white/20 text-white">Ultimately, our goal is to empower creators while preserving the freedom and sustainability of the web.</span>
                        </p>
                        
                        <p>
                            We believe that the tools we use to communicate matter. In a world where platforms lock in your data and monetize your community, we're building a system that rewards transparency, collaboration, and environmental awareness. Freetter incorporates a carbon tracking system to estimate and visualize the environmental impact of every newsletter sent, promoting a more conscious digital ecosystem. It is a space for meaningful communication rather than surface-level metrics.
                        </p>
                        
                        <p>
                            Every day, writers, activists, developers, and independent journalists choose open-source tools to connect with their audience. They write about their passions, their causes, and their projects, knowing their content remains entirely theirs.
                        </p>
                        
                        <p>
                            Instead of taking a cut from your donations or selling your data, Freetter operates as a 100% free, AGPLv3-licensed platform driven by a radical commitment to open knowledge. If you believe in a fairer, more transparent web, start reading. Dive deeper into stories that matter, support the creators directly without intermediaries, and then—write your story.
                        </p>
                    </div>
                </main>
                
                {/* Footer simple */}
                <footer className="flex items-center justify-between px-6 py-8 lg:px-12 text-sm bg-white font-sans text-black">
                    <Link href="/">
                        <Logotipo className="h-6 w-auto text-black" />
                    </Link>
                    <div className="flex gap-4 font-medium text-zinc-600">
                        <Link href="#" className="hover:text-black transition">About</Link>
                        <Link href="#" className="hover:text-black transition">Terms</Link>
                        <Link href="#" className="hover:text-black transition">Privacy</Link>
                    </div>
                </footer>
                
                <AuthModal isOpen={isAuthOpen} onClose={() => setIsAuthOpen(false)} initialMode={authMode} />
            </div>
        </>
    );
}
