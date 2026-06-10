import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AuthModal } from '../components/auth-modal';
import { Logotipo } from '@/components/logotipo';

export default function Membership() {
    const [isAuthOpen, setIsAuthOpen] = useState(false);
    const [authMode, setAuthMode] = useState<'signin' | 'signup'>('signin');

    return (
        <>
            <Head title="Freetter - Membership" />

            <div className="min-h-screen bg-white text-black selection:bg-black selection:text-white font-sans">
                
                {/* Hero Section */}
                <div className="flex flex-col min-h-screen md:min-h-[80vh] border-b border-black">
                    {/* Pink Background */}
                    <div className="flex-1 bg-[#F9E5F9] flex flex-col">
                        <header className="flex h-20 items-center justify-between px-6 lg:px-12">
                            <Link href="/" className="hover:opacity-80 transition-opacity">
                                <Logotipo className="h-8 w-auto text-black" />
                            </Link>
                            
                            <div className="flex items-center gap-4 text-sm font-medium">
                                <button
                                    onClick={() => { setAuthMode('signin'); setIsAuthOpen(true); }}
                                    className="rounded-full border border-black px-4 py-1.5 text-black transition hover:bg-black hover:text-white"
                                >
                                    Sign in
                                </button>
                                <button
                                    onClick={() => { setAuthMode('signup'); setIsAuthOpen(true); }}
                                    className="rounded-full bg-black px-4 py-1.5 text-white transition hover:bg-black/90"
                                >
                                    Sign up
                                </button>
                            </div>
                        </header>

                        <div className="flex-1 flex flex-col justify-center px-6 py-20 lg:px-12">
                            <h1 className="text-6xl md:text-[80px] lg:text-[100px] leading-[1] font-serif tracking-tight mb-8">
                                Support<br />independent<br />voices
                            </h1>
                            
                            <p className="text-xl md:text-2xl text-zinc-700 max-w-lg mb-10 leading-relaxed font-sans">
                                Become a supporter to fund great writers, sustain an open-source platform, and join a global community of people who care about transparent, high-quality storytelling.
                            </p>

                            <div className="flex flex-wrap gap-4">
                                <button
                                    onClick={() => { setAuthMode('signup'); setIsAuthOpen(true); }}
                                    className="rounded-full bg-black px-6 py-3 text-white font-medium hover:bg-zinc-800 transition"
                                >
                                    Get started
                                </button>
                                <a
                                    href="#plans"
                                    className="rounded-full border border-black px-6 py-3 text-black font-medium hover:bg-black/5 transition"
                                >
                                    View plans
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Why Membership Section */}
                <section className="py-24 px-6 lg:px-12 max-w-7xl mx-auto border-b border-black">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-24">
                        <div className="md:col-span-5">
                            <h2 className="text-5xl md:text-7xl font-serif tracking-tight sticky top-24">
                                Why<br />membership?
                            </h2>
                        </div>
                        <div className="md:col-span-7 space-y-16">
                            <div>
                                <h3 className="text-3xl md:text-4xl font-serif mb-4">Reward creators</h3>
                                <p className="text-lg text-zinc-700 leading-relaxed">
                                    Your contribution directly supports the writers, editors, and teams who make Freetter a vibrant, inclusive home for human stories. A portion of your donation goes directly to the writers you read and interact with.
                                </p>
                            </div>

                            <div>
                                <h3 className="text-3xl md:text-4xl font-serif mb-4">Support open source</h3>
                                <p className="text-lg text-zinc-700 leading-relaxed">
                                    Freetter is 100% free and AGPLv3 licensed. Your support pays for servers and development, keeping the platform free of paywalls, invasive ads, and corporate algorithms.
                                </p>
                            </div>

                            <div>
                                <h3 className="text-3xl md:text-4xl font-serif mb-4">Conscious reading</h3>
                                <p className="text-lg text-zinc-700 leading-relaxed">
                                    Help us maintain our unique Carbon Tracking infrastructure. By supporting us, you promote a sustainable digital ecosystem that is mindful of its environmental footprint.
                                </p>
                            </div>

                            <div>
                                <h3 className="text-3xl md:text-4xl font-serif mb-4">Elevate your writing</h3>
                                <p className="text-lg text-zinc-700 leading-relaxed">
                                    Contribute to a community that values deep conversation over superficial metrics. Collaborate in workspaces with fine-grained roles and powerful, distraction-free publishing tools.
                                </p>
                            </div>

                            <div>
                                <h3 className="text-3xl md:text-4xl font-serif mb-4">Support a mission that matters</h3>
                                <p className="text-lg text-zinc-700 leading-relaxed">
                                    Supporters are creating a world where original, human-crafted stories thrive. As a community-supported platform, quality comes first, not clickbait.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Membership Plans Section */}
                <section id="plans" className="py-24 px-6 lg:px-12 max-w-7xl mx-auto border-b border-black">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-24">
                        <div className="md:col-span-5">
                            <h2 className="text-5xl md:text-7xl font-serif tracking-tight sticky top-24">
                                Support<br />plans
                            </h2>
                        </div>
                        <div className="md:col-span-7">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                {/* Plan 1 */}
                                <div className="border border-black p-8 flex flex-col h-full">
                                    <div className="text-[#FBBF24] text-2xl mb-4">✦</div>
                                    <h3 className="text-2xl font-sans font-bold mb-1">Platform Supporter</h3>
                                    <p className="text-zinc-600 mb-8 font-serif">5 $/month or 50 $/year</p>
                                    
                                    <button 
                                        onClick={() => { setAuthMode('signup'); setIsAuthOpen(true); }}
                                        className="w-full rounded-full bg-[#16A34A] text-white py-3 font-medium hover:bg-[#15803d] transition mb-8"
                                    >
                                        Get started
                                    </button>

                                    <ul className="space-y-4 text-sm text-zinc-700 flex-1">
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Help pay for server costs
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Keep Freetter 100% free
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Fund Carbon tracking infrastructure
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Support open source development
                                        </li>
                                    </ul>
                                </div>

                                {/* Plan 2 */}
                                <div className="border border-black p-8 flex flex-col h-full relative overflow-hidden">
                                    <div className="text-[#FBBF24] text-2xl mb-4">♥</div>
                                    <h3 className="text-2xl font-sans font-bold mb-1">Creator Patron</h3>
                                    <p className="text-zinc-600 mb-8 font-serif">Custom amount per creator</p>
                                    
                                    <button 
                                        onClick={() => { setAuthMode('signup'); setIsAuthOpen(true); }}
                                        className="w-full rounded-full bg-[#16A34A] text-white py-3 font-medium hover:bg-[#15803d] transition mb-8"
                                    >
                                        Get started
                                    </button>

                                    <ul className="space-y-4 text-sm text-zinc-700 flex-1">
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Direct donation to writers
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> 0% platform commission
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Support independent journalism
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Direct connection with creators
                                        </li>
                                        <li className="flex items-start gap-3">
                                            <span className="text-[#16A34A]">✓</span> Exclusive supporter badges
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Bottom Banner */}
                <section className="bg-[#F9E5F9] py-24 px-6 text-center border-b border-black">
                    <h2 className="text-5xl md:text-7xl lg:text-[90px] font-serif tracking-tight mb-10 max-w-5xl mx-auto leading-tight">
                        Unlock a world of transparent storytelling
                    </h2>
                    <button 
                        onClick={() => { setAuthMode('signup'); setIsAuthOpen(true); }}
                        className="rounded-full bg-black text-white px-8 py-4 text-lg font-medium hover:bg-zinc-800 transition"
                    >
                        Get started
                    </button>
                </section>

                {/* Footer simple */}
                <footer className="flex flex-col md:flex-row items-center justify-between px-6 py-8 lg:px-12 text-sm bg-white font-sans text-black">
                    <Link href="/" className="mb-4 md:mb-0">
                        <Logotipo className="h-7 w-auto text-black" />
                    </Link>
                    <div className="flex gap-4 font-medium text-zinc-600">
                        <Link href="#" className="hover:text-black transition underline decoration-transparent hover:decoration-black underline-offset-4">About</Link>
                        <Link href="#" className="hover:text-black transition underline decoration-transparent hover:decoration-black underline-offset-4">Terms</Link>
                        <Link href="#" className="hover:text-black transition underline decoration-transparent hover:decoration-black underline-offset-4">Privacy</Link>
                        <Link href="#" className="hover:text-black transition underline decoration-transparent hover:decoration-black underline-offset-4">Help</Link>
                    </div>
                </footer>
                
                <AuthModal isOpen={isAuthOpen} onClose={() => setIsAuthOpen(false)} initialMode={authMode} />
            </div>
        </>
    );
}
