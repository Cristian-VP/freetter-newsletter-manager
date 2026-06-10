import UserHomeLayout from "@/layouts/user-home-layout";
import { type PageProps } from "@/types";
import { Head, router, usePage } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import { useMemo } from "react";

type NewsletterPost = {
  id: string;
  workspace_id: string;
  title: string;
  status: string;
  excerpt: string | null;
  published_at: string | null;
  slug: string;
  workspace_name: string | null;
  body_text: string;
};

type NewsletterPreviewProps = {
  post: NewsletterPost | null;
};

export default function NewsletterPreview() {
  const page = usePage<PageProps & NewsletterPreviewProps>();
  const { post } = page.props;

  const formattedDate = useMemo(() => {
    if (!post?.published_at) return null;
    return new Date(post.published_at).toLocaleDateString("es-ES", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }, [post?.published_at]);

  return (
    <UserHomeLayout>
      <Head title={post ? post.title : "Preview Newsletter"} />

      <section className="mx-auto flex w-full max-w-190 flex-col gap-5 md:max-w-205">
        <div className="rounded-4xl border border-zinc-200/70 bg-white/95 p-5 shadow-[0_16px_48px_rgba(15,23,42,0.08)] sm:p-7">
          <div className="flex items-center justify-between gap-3">
            <button
              type="button"
              onClick={() => router.visit("/newsletters/resume")}
              className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-950"
              aria-label="Volver al resumen"
            >
              <ArrowLeft className="h-4 w-4" />
            </button>

            <div className="text-right">
              <p className="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">newsletters/preview</p>
              <h1 className="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">
                {post ? post.title : "Newsletter publicada"}
              </h1>
            </div>
          </div>

          {post ? (
            <>
              <div className="mt-6 flex items-center gap-3 border-b border-zinc-200 pb-5">
                <div className="flex flex-col">
                  <span className="text-sm font-medium text-zinc-900">
                    {post.workspace_name ?? "Autor"}
                  </span>
                  {formattedDate ? (
                    <time className="text-xs text-zinc-500" dateTime={post.published_at}>
                      {formattedDate}
                    </time>
                  ) : null}
                </div>
              </div>

              <div className="mt-6 flex flex-col gap-4">
                {post.body_text ? (
                  post.body_text.split("\n").filter(Boolean).map((paragraph, i) => (
                    <p key={i} className="text-base leading-relaxed text-zinc-700">
                      {paragraph}
                    </p>
                  ))
                ) : (
                  <p className="text-sm text-zinc-500">El contenido de esta newsletter no está disponible.</p>
                )}
              </div>
            </>
          ) : (
            <div className="mt-6 rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-5 text-sm text-zinc-600">
              Selecciona una newsletter publicada desde el resumen para ver su contenido.
            </div>
          )}
        </div>
      </section>
    </UserHomeLayout>
  );
}
