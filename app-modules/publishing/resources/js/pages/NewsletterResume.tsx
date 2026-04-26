import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { Head, Link } from "@inertiajs/react";

export default function NewsletterResume() {
  return (
    <AuthenticatedHomeLayout>
      <Head title="Resumen de Newsletters" />

      <section className="mx-auto flex w-full max-w-3xl flex-col gap-4">
        <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
          <p className="text-sm uppercase tracking-[0.2em] text-zinc-500">Crear Newsletter</p>
          <h1 className="mt-2 text-3xl satoshi-bold tracking-tight text-zinc-900 sm:text-4xl">En construccion</h1>
          <p className="mt-3 text-zinc-600">Aqui se mostrara el resumen editorial con newsletters en borrador, programadas y publicadas.</p>

          <div className="mt-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
            <p className="text-sm font-semibold text-zinc-900">Proximamente</p>
            <p className="mt-1 text-sm text-zinc-600">Filtros por estado, contadores por columna y acceso rapido a cada newsletter.</p>
          </div>

          <Link
            href="/newsletters/create"
            prefetch
            className="mt-6 inline-flex items-center justify-center rounded-2xl bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-zinc-700"
          >
            Ir al newsletter builder
          </Link>
        </div>
      </section>
    </AuthenticatedHomeLayout>
  );
}
