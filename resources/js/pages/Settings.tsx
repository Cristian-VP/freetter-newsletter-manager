import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { Head } from "@inertiajs/react";

export default function Settings() {
  return (
    <AuthenticatedHomeLayout>
      <Head title="Settings - Freetter" />

      <section className="mx-auto flex w-full max-w-3xl flex-col gap-4">
        <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
          <h1 className="text-3xl satoshi-bold tracking-tight text-zinc-900 sm:text-4xl">Settings</h1>
          <p className="mt-3 text-zinc-600">Aqui podras configurar tu cuenta.</p>
        </div>
      </section>
    </AuthenticatedHomeLayout>
  );
}
