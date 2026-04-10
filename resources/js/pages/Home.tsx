import { Head, usePage, Link } from '@inertiajs/react';
import { type PageProps } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { UserMenuContent } from "@/components/user-menu-content";
import { useInitials } from "@/hooks/use-initials";

export default function Home() {
    const { auth } = usePage<PageProps>().props;
    const getInitials = useInitials();

    return (
        <div className="flex h-screen flex-col overflow-hidden bg-white font-sans text-zinc-900">
            <Head title="Home - Freetter" />

            {/* Header Simplificado para el Feed */}
            <header className="flex h-20 shrink-0 items-center justify-between border-b border-zinc-200 px-6 lg:px-12 bg-white">
                <Link href="/home" className="satoshi-bold text-4xl tracking-tight text-zinc-900 hover:text-black">
                    Freetter
                </Link>

                <div className="flex items-center">
                    <DropdownMenu>
                        <DropdownMenuTrigger className="focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 rounded-full">
                            <Avatar className="h-10 w-10 border border-zinc-200">
                                <AvatarImage src={auth.user?.avatar} alt={auth.user?.name} />
                                <AvatarFallback className="bg-zinc-200 text-zinc-600 font-medium text-sm text-center">
                                    {auth.user?.name ? getInitials(auth.user.name) : ""}
                                </AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56 mt-2">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <main className="flex flex-1 items-center justify-center bg-zinc-50/30 px-6">
                <h1 className="text-center text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">
                    Welcome, {auth.user?.name}
                </h1>
            </main>
        </div>
    );
}
