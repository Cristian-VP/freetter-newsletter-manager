import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { X } from 'lucide-react';
import { useIsMobile } from '@/hooks/use-mobile';

interface AuthModalProps {
    isOpen: boolean;
    onClose: () => void;
    initialMode?: 'signin' | 'signup';
}

export function AuthModal({ isOpen, onClose, initialMode = 'signin' }: AuthModalProps) {
    const [mode, setMode] = useState<'signin' | 'signup'>(initialMode);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const isMobile = useIsMobile();

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    // Evitar scroll cuando el modal está abierto
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            setSuccessMessage(null);
            document.body.style.overflow = 'unset';
        }
        return () => {
            document.body.style.overflow = 'unset';
        };
    }, [isOpen]);

    if (!isOpen) return null;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // The endpoint will handle sending the magic link
        const endpoint = mode === 'signin' ? '/login' : '/register';
        post(endpoint, {
            preserveScroll: true,
            onSuccess: () => {
                setSuccessMessage('Te hemos enviado un enlace al correo. Revisa tu bandeja de entrada.');
            }
        });
    };

    return (
        <div
            className="fixed inset-x-0 bottom-0 top-20 z-50 flex items-center justify-center bg-[#0f0f0f] transition-all md:inset-0 md:bg-black/40 md:px-4 md:backdrop-blur-sm"
            onClick={onClose}
        >
            <div
                className="relative flex h-full w-full max-w-md flex-col justify-center overflow-y-auto bg-[#0f0f0f] px-8 py-12 pb-32 text-[#F7F4ED] md:h-auto md:rounded-3xl md:border md:border-zinc-800 md:pb-12 md:shadow-2xl md:overflow-visible"
                onClick={(e) => e.stopPropagation()}
            >
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={onClose}
                    className="absolute left-6 top-6 hidden h-8 w-8 rounded-full border-[#F7F4ED]/20 bg-transparent text-[#F7F4ED] transition hover:bg-[#F7F4ED]/10 hover:text-white md:flex"
                    aria-label="Close modal"
                >
                    <X className="h-4 w-4" />
                </Button>

                <div className="flex flex-col mb-10 w-full">
                    <h2 className="text-3xl font-sans tracking-tight text-center">
                        {mode === 'signin' ? 'Sign in to Freetter' : 'Sign up for Freetter'}
                    </h2>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                    <div className="flex flex-col gap-2">
                        <Input
                            type="email"
                            placeholder="Email address"
                            value={data.email}
                            onChange={(e) => {
                                setSuccessMessage(null);
                                setData('email', e.target.value);
                            }}
                            className="bg-transparent border-[#F7F4ED]/30 text-[#F7F4ED] placeholder:text-[#F7F4ED]/50 h-12 rounded-lg focus-visible:ring-[#F7F4ED]/50"
                            required
                        />
                        {errors.email && <span className="text-red-400 text-sm mt-1">{errors.email}</span>}
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full h-12 rounded-full bg-[#F7F4ED] text-base font-medium text-[#0f0f0f] hover:bg-[#F7F4ED]/90 md:w-auto md:self-end md:px-8"
                    >
                        Continue
                    </Button>

                    {successMessage && (
                        <p className="rounded-lg border border-emerald-300/30 bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200">
                            {successMessage}
                        </p>
                    )}
                </form>

                <div className="mt-8 text-center text-sm font-medium tracking-wide">
                    {mode === 'signin' ? (
                        <p className="text-[#F7F4ED]/70">
                            First time here?{' '}
                            <button
                                type="button"
                                onClick={() => setMode('signup')}
                                className="text-[#F7F4ED] hover:underline"
                            >
                                Create an account
                            </button>
                        </p>
                    ) : (
                        <p className="text-[#F7F4ED]/70">
                            Already have an account?{' '}
                            <button
                                type="button"
                                onClick={() => setMode('signin')}
                                className="text-[#F7F4ED] hover:underline"
                            >
                                Sign in
                            </button>
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
