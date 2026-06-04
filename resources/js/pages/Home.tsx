import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { type PageProps } from "@/types";
import { Head, usePage } from "@inertiajs/react";

export default function Home() {
    const { auth } = usePage<PageProps>().props;

    return (
        <AuthenticatedHomeLayout>
            <Head title="Home " />

            <section className="mx-auto flex w-full max-w-3xl flex-col gap-4">
                <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
                    <h1 className="text-3xl satoshi-bold tracking-tight text-zinc-900 sm:text-4xl">
                        Welcome, {auth.user?.name}
                    </h1>
                    <p className="mt-3 text-zinc-600">
                        Este contenedor representa el feed principal autenticado. La navegación ya responde con toolbar inferior en mobile y sidebar lateral en desktop.
                    </p>
                </div>

                <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
                    <p className="text-sm uppercase tracking-[0.2em] text-zinc-500">Home</p>
                    <p className="mt-2 text-zinc-700">Aquí se renderizarán los cards/posts del timeline.</p>
                </div>
            </section>
        </AuthenticatedHomeLayout>
    );
}
