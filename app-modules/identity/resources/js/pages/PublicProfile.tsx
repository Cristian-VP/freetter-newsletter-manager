import UserHomeLayout from "@/layouts/user-home-layout";
import { type PageProps } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import { ArrowLeft, X } from "lucide-react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useInitials } from "@/hooks/use-initials";

// ─── Types ────────────────────────────────────────────────────────────────────

type PublicProfileData = {
  id: string;
  name: string;
  handle: string | null;
  bio: string | null;
  avatar_url: string | null;
  workspace_id: string | null;
};

type NewsletterItem = {
  id: string;
  title: string;
  slug: string;
  published_at: string | null;
  view_url: string;
};

// ─── Component ────────────────────────────────────────────────────────────────

export default function PublicProfile() {
  const { profile } = usePage<PageProps & { profile: PublicProfileData }>().props;
  const getInitials = useInitials();

  const [newsletters, setNewsletters] = useState<NewsletterItem[]>([]);
  const [isLoadingNewsletters, setIsLoadingNewsletters] = useState(false);
  const [errorNewsletters, setErrorNewsletters] = useState<string | null>(null);
  const [selectedNewsletterId, setSelectedNewsletterId] = useState<string | null>(null);


  const autoOpenedRef = useRef(false);
  const handleSlug = profile.handle ? profile.handle.replace(/^@/, "") : "";

  // ─── Data Loaders ──────────────────────────────────────────────────────────

  const loadNewsletters = useCallback(async (): Promise<void> => {
    if (!handleSlug) return;
    setIsLoadingNewsletters(true);
    setErrorNewsletters(null);
    try {
      const response = await fetch(`/u/${handleSlug}/newsletters`, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      });
      if (!response.ok) {
        throw new Error("No pudimos cargar las newsletters.");
      }
      const payload = (await response.json()) as { data: { newsletters: NewsletterItem[] } };
      setNewsletters(Array.isArray(payload.data.newsletters) ? payload.data.newsletters : []);
    } catch (e) {
      setErrorNewsletters(e instanceof Error ? e.message : "Error al cargar newsletters.");
    } finally {
      setIsLoadingNewsletters(false);
    }
  }, [handleSlug]);

  useEffect(() => {
    void loadNewsletters();
  }, [loadNewsletters]);

  // Auto-open newsletter from URL ?newsletter= query param
  useEffect(() => {
    if (autoOpenedRef.current || isLoadingNewsletters || newsletters.length === 0) return;
    const params = new URLSearchParams(window.location.search);
    const newsletterId = params.get("newsletter");
    if (newsletterId && newsletters.some((n) => n.id === newsletterId)) {
      autoOpenedRef.current = true;
      setSelectedNewsletterId(newsletterId);
    }
  }, [isLoadingNewsletters, newsletters]);

  useEffect(() => {
    document.body.style.overflow = selectedNewsletterId === null ? "unset" : "hidden";
    return () => { document.body.style.overflow = "unset"; };
  }, [selectedNewsletterId]);

  const selectedNewsletter = useMemo(
    () => newsletters.find((n) => n.id === selectedNewsletterId) ?? null,
    [newsletters, selectedNewsletterId],
  );

  // ─── Avatar helpers ────────────────────────────────────────────────────────

  const avatarUrl = profile.avatar_url;
  const initials = getInitials(profile.name);

  // ─── Render ────────────────────────────────────────────────────────────────

  return (
    <UserHomeLayout>
      <Head title={`${profile.name} — Perfil`} />

      {selectedNewsletterId !== null && selectedNewsletter !== null ? (
        <PublicNewsletterDetailOverlay
          newsletter={selectedNewsletter}
          handleSlug={handleSlug}
          onClose={() => setSelectedNewsletterId(null)}
        />
      ) : null}

      <section className="mx-auto flex w-full max-w-190 flex-col md:max-w-205">

        {/* ── Profile Header ────────────────────────────────────────────────── */}
        <header className="flex flex-row items-center gap-5 px-4 pt-4 pb-6 md:px-0 md:items-start md:gap-8">

          <div className="shrink-0">
            <div className="h-20 w-20 md:h-32 md:w-32 overflow-hidden rounded-full ring-2 ring-zinc-200">
              {avatarUrl ? (
                <img src={avatarUrl} alt={profile.name} className="h-full w-full object-cover" />
              ) : (
                <div className="flex h-full w-full items-center justify-center bg-zinc-200 text-2xl md:text-4xl satoshi-bold text-zinc-700">
                  {initials}
                </div>
              )}
            </div>
          </div>

          <div className="flex flex-col gap-3 md:gap-2 w-full">
            <div className="flex flex-col md:gap-1">
              <h1 className="text-lg md:text-2xl satoshi-bold text-zinc-900">{profile.name}</h1>
              {profile.handle && (
                <span className="text-sm text-zinc-500">
                  {profile.handle}
                </span>
              )}
              {profile.bio && (
                <p className="text-sm text-zinc-700 mt-2 max-w-xl leading-relaxed">
                  {profile.bio}
                </p>
              )}
            </div>
          </div>
        </header>

        {/* ── Newsletters Grid ──────────────────────────────────────────────── */}
        <div className="pt-1">
          <PublicNewslettersGrid
            newsletters={newsletters}
            isLoading={isLoadingNewsletters}
            error={errorNewsletters}
            onRetry={loadNewsletters}
            onSelect={(id) => setSelectedNewsletterId(id)}
          />
        </div>
      </section>
    </UserHomeLayout>
  );
}

// ─── Newsletters Grid ─────────────────────────────────────────────────────────

function PublicNewslettersGrid({
  newsletters,
  isLoading,
  error,
  onRetry,
  onSelect,
}: {
  newsletters: NewsletterItem[];
  isLoading: boolean;
  error: string | null;
  onRetry: () => void;
  onSelect: (id: string) => void;
}) {
  if (isLoading) {
    return (
      <div className="grid grid-cols-3 gap-px bg-zinc-200">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="aspect-square animate-pulse bg-zinc-100" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="py-16 text-center">
        <p className="text-sm text-zinc-500">{error}</p>
        <button
          type="button"
          onClick={onRetry}
          className="mt-4 text-sm satoshi-medium text-zinc-900 underline underline-offset-2"
        >
          Reintentar
        </button>
      </div>
    );
  }

  if (newsletters.length === 0) {
    return (
      <div className="py-20 text-center">
        <p className="text-sm text-zinc-500">Este usuario aún no ha publicado ninguna newsletter.</p>
      </div>
    );
  }

  return (
    <div
      className="grid grid-cols-3 gap-px bg-zinc-200"
      role="list"
      aria-label="Newsletters publicadas"
    >
      {newsletters.map((newsletter) => (
        <button
          key={newsletter.id}
          type="button"
          role="listitem"
          onClick={() => onSelect(newsletter.id)}
          className="group relative flex aspect-square w-full cursor-pointer flex-col justify-start overflow-hidden p-4 text-left transition-colors duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-zinc-900 bg-[#F7F4ED] hover:bg-zinc-900"
          aria-label={newsletter.title}
        >
          <span
            className="w-full font-sans leading-[0.95] tracking-tighter text-zinc-900 transition-colors duration-300 group-hover:text-white"
            style={{
              fontSize: "clamp(1.5rem, 4vw, 2.5rem)",
              fontWeight: 800,
              wordBreak: "break-word",
              hyphens: "auto"
            }}
            lang="es"
          >
            {newsletter.title}
          </span>
        </button>
      ))}
    </div>
  );
}

// ─── Newsletter Detail Overlay ────────────────────────────────────────────────

function PublicNewsletterDetailOverlay({
  newsletter,
  handleSlug,
  onClose,
}: {
  newsletter: NewsletterItem;
  handleSlug: string;
  onClose: () => void;
}) {
  const [detail, setDetail] = useState<{
    body_paragraphs: string[];
    owner: { name: string | null; avatar_url: string | null };
    published_at: string | null;
    cover_image_url: string | null;
  } | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const getInitials = useInitials();

  useEffect(() => {
    const controller = new AbortController();

    const load = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const response = await fetch(`/u/${handleSlug}/newsletters/${newsletter.id}`, {
          headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
          credentials: "same-origin",
          signal: controller.signal,
        });
        if (!response.ok) throw new Error("No pudimos cargar el contenido.");
        const payload = (await response.json()) as {
          data: {
            newsletter: {
              body_paragraphs: string[];
              owner: { id: string | null; name: string | null; avatar_url: string | null };
              published_at: string | null;
              cover_image_url: string | null;
            };
          };
        };
        const n = payload.data.newsletter;
        setDetail({
          body_paragraphs: n.body_paragraphs,
          owner: n.owner,
          published_at: n.published_at,
          cover_image_url: n.cover_image_url,
        });
      } catch (e) {
        if ((e as Error).name !== "AbortError") {
          setError(e instanceof Error ? e.message : "Error al cargar.");
        }
      } finally {
        setIsLoading(false);
      }
    };

    void load();
    return () => controller.abort();
  }, [newsletter.id, handleSlug]);

  return (
    <div
      className="fixed inset-0 z-50 flex flex-col overflow-hidden bg-[#F7F4ED]"
      role="dialog"
      aria-modal="true"
      aria-label={newsletter.title}
    >
      <div className="sticky top-0 z-10 flex items-center justify-between border-b border-zinc-200 bg-[#F7F4ED] px-4 py-3 md:px-8">
        <button
          type="button"
          onClick={onClose}
          className="flex items-center gap-2 text-sm text-zinc-600 transition-colors hover:text-zinc-900"
          aria-label="Volver al perfil"
        >
          <ArrowLeft className="h-4 w-4" />
          <span className="satoshi-medium">Perfil</span>
        </button>
        <button
          type="button"
          onClick={onClose}
          className="rounded-full p-1.5 text-zinc-500 transition-colors hover:bg-zinc-200 hover:text-zinc-900"
          aria-label="Cerrar"
        >
          <X className="h-4 w-4" />
        </button>
      </div>

      <div className="flex-1 overflow-y-auto">
        <article className="mx-auto max-w-2xl px-4 py-8 md:px-0">
          <h1 className="text-3xl satoshi-bold leading-tight text-zinc-900 sm:text-4xl">
            {newsletter.title}
          </h1>

          {isLoading ? (
            <div className="mt-8 flex flex-col gap-4">
              {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className={`h-4 animate-pulse rounded bg-zinc-200 ${i % 3 === 2 ? "w-2/3" : "w-full"}`} />
              ))}
            </div>
          ) : error ? (
            <p className="mt-8 text-sm text-zinc-500">{error}</p>
          ) : detail ? (
            <>
              <div className="mt-5 flex items-center gap-3 border-b border-zinc-200 pb-5">
                <div className="h-9 w-9 shrink-0 overflow-hidden rounded-full bg-zinc-200">
                  {detail.owner.avatar_url ? (
                    <img
                      src={detail.owner.avatar_url}
                      alt={detail.owner.name ?? ""}
                      className="h-full w-full object-cover"
                    />
                  ) : (
                    <span className="flex h-full w-full items-center justify-center text-xs satoshi-medium text-zinc-600">
                      {detail.owner.name ? getInitials(detail.owner.name) : "?"}
                    </span>
                  )}
                </div>
                <div className="flex flex-col">
                  <span className="text-sm satoshi-medium text-zinc-900">
                    {detail.owner.name ?? "Autor"}
                  </span>
                  {detail.published_at ? (
                    <time className="text-xs text-zinc-500" dateTime={detail.published_at}>
                      {new Date(detail.published_at).toLocaleDateString("es-ES", {
                        year: "numeric",
                        month: "long",
                        day: "numeric",
                      })}
                    </time>
                  ) : null}
                </div>
              </div>

              {detail.cover_image_url ? (
                <div className="mt-6 overflow-hidden rounded-2xl">
                  <img
                    src={detail.cover_image_url}
                    alt=""
                    className="w-full object-cover"
                  />
                </div>
              ) : null}

              <div className="mt-6 flex flex-col gap-4">
                {detail.body_paragraphs.length > 0 ? (
                  detail.body_paragraphs.map((paragraph, i) => (
                    <p key={i} className="text-base leading-relaxed text-zinc-700">
                      {paragraph}
                    </p>
                  ))
                ) : (
                  <p className="text-sm text-zinc-500">El contenido de este newsletter no está disponible en la vista previa.</p>
                )}
              </div>
            </>
          ) : null}
        </article>
      </div>
    </div>
  );
}
