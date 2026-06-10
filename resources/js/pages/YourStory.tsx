import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AuthModal } from '../components/auth-modal';
import { Logotipo } from '@/components/logotipo';

export default function YourStory() {
    const [isAuthOpen, setIsAuthOpen] = useState(false);
    const [authMode, setAuthMode] = useState<'signin' | 'signup'>('signin');

    return (
        <>
            <Head title="Freetter - Your Story" />
            
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
                        Your story is worth telling
                    </h1>
                    
                    <div className="space-y-8 text-lg md:text-xl lg:text-2xl leading-relaxed text-[#D1D1D1] max-w-2xl font-serif">
                        <p>
                            We often believe that our thoughts aren't profound enough, or that someone else has already said it better. But the truth is, your perspective is entirely unique. The experiences you've lived, the challenges you've overcome, and the niche you obsess over are exactly what someone else is searching for right now.
                        </p>
                        
                        <p>
                            <span className="bg-white/20 text-white">Whether you're an industry expert, a hobbyist, or just someone observing the world—there is an audience waiting for your voice.</span>
                        </p>
                        
                        <p>
                            Freetter is built to help you share that voice without friction. When you write here, you are not working for an algorithm. You are building a direct, unfiltered connection with your readers. No paywalls blocking your content. No corporate interests deciding who sees your work.
                        </p>

                        <div className="space-y-4 py-6">
                            <h3 className="text-white text-3xl font-medium">Why tell your story on Freetter?</h3>
                            <ul className="list-disc pl-6 space-y-4 text-xl">
                                <li><strong>100% Free & Independent:</strong> Keep everything you earn. We don't take a cut from your donations, and the platform is open source (AGPLv3).</li>
                                <li><strong>You own your audience:</strong> Easily import and export your subscriber list at any time. Your readers are yours, not ours.</li>
                                <li><strong>Collaborate freely:</strong> Want to co-author? Invite your friends or team with our fine-grained multi-role workspace system.</li>
                                <li><strong>Write mindfully:</strong> Our unique Carbon Tracking system lets you see the environmental impact of your newsletters, promoting sustainable digital habits.</li>
                            </ul>
                        </div>
                        
                        <p>
                            Don't wait until you feel "ready." Start with a draft. Start with an idea. The hardest part is writing the first sentence. We've built the canvas—now it's your turn to paint.
                        </p>
                        
                        <div className="pt-8 font-sans">
                            <button
                                onClick={() => { setAuthMode('signup'); setIsAuthOpen(true); }}
                                className="inline-flex items-center justify-center rounded-full bg-white px-8 py-3 text-lg font-medium text-black transition hover:bg-zinc-200"
                            >
                                Start your journey
                            </button>
                        </div>
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
