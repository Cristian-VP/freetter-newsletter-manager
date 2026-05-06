import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { Head } from "@inertiajs/react";

export default function NewsletterPreview() {
  return (
    <AuthenticatedHomeLayout>
      <Head title="Preview Newsletter" />

      <section className="mx-auto flex w-full max-w-190 flex-col gap-4 md:max-w-205">
        <div className="rounded-4xl bg-white p-8 shadow-[0_10px_35px_rgba(15,23,42,0.08)]">
          <p className="text-sm uppercase tracking-[0.2em] text-zinc-500">Preview newsletter</p>
          <h1 className="mt-2 text-3xl satoshi-bold tracking-tight text-zinc-900 sm:text-4xl">En construccion</h1>
          <p className="mt-3 text-zinc-600">Esta vista sera el preview de newsletter despues de publicar.</p>
        </div>
      </section>
    </AuthenticatedHomeLayout>
  );
}
