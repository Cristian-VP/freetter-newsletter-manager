import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { type PageProps } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { ArrowLeft, Bookmark, X } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useInitials } from "@/hooks/use-initials";

// ─── Types ────────────────────────────────────────────────────────────────────

type ProfileStats = {
  published_count: number;
  followers_count: number;
  following_count: number;
  bookmarks_count: number;
};

type ProfileData = {
  id: string;
  name: string;
  handle: string | null;
  bio: string | null;
  avatar_url: string | null;
  workspace_id: string | null;
  stats: ProfileStats;
};

type NewsletterItem = {
  id: string;
  title: string;
  slug: string;
  published_at: string | null;
  view_url: string;
};

type BookmarkItem = {
  bookmark_id: string;
  post_id: string;
  title: string;
  preview_text: string;
  author: {
    id: string | null;
    name: string | null;
    avatar_url: string | null;
  };
};

type SelectedNewsletter = NewsletterItem & {
  body_paragraphs?: string[];
};

type SubscriptionDetailResponse = {
  data: {
    subscriptions: Array<{
      id: string;
      newsletter: { id: string; title: string; body_paragraphs: string[] };
      owner: { id: string | null; name: string | null; avatar_url: string | null };
      published_at: string | null;
      preview_text: string;
      cover_image_url: string | null;
      image_urls: string[];
    }>;
  };
};

type TabKey = "newsletters" | "marcadores";

// ─── Component ────────────────────────────────────────────────────────────────

export default function Profile() {
  const { auth, profile } = usePage<PageProps & { profile: ProfileData }>().props;
  const getInitials = useInitials();

  const [activeTab, setActiveTab] = useState<TabKey>("newsletters");
  const [newsletters, setNewsletters] = useState<NewsletterItem[]>([]);
  const [bookmarks, setBookmarks] = useState<BookmarkItem[]>([]);
  const [isLoadingNewsletters, setIsLoadingNewsletters] = useState(false);
  const [isLoadingBookmarks, setIsLoadingBookmarks] = useState(false);
  const [errorNewsletters, setErrorNewsletters] = useState<string | null>(null);
  const [errorBookmarks, setErrorBookmarks] = useState<string | null>(null);

  // Newsletter detail viewer (inline, like Subscriptions page)
  const [selectedNewsletterId, setSelectedNewsletterId] = useState<string | null>(null);

  // ─── Data Loaders ──────────────────────────────────────────────────────────

  const loadNewsletters = useCallback(async (): Promise<void> => {
    setIsLoadingNewsletters(true);
    setErrorNewsletters(null);
    try {
      const response = await fetch("/identity/profile/newsletters", {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      });
      if (!response.ok) {
        throw new Error(response.status === 401 ? "Tu sesión expiró." : "No pudimos cargar tus newsletters.");
      }
      const payload = (await response.json()) as { data: { newsletters: NewsletterItem[] } };
      setNewsletters(Array.isArray(payload.data.newsletters) ? payload.data.newsletters : []);
    } catch (e) {
      setErrorNewsletters(e instanceof Error ? e.message : "Error al cargar newsletters.");
    } finally {
      setIsLoadingNewsletters(false);
    }
  }, []);

  const loadBookmarks = useCallback(async (): Promise<void> => {
    setIsLoadingBookmarks(true);
    setErrorBookmarks(null);
    try {
      const response = await fetch("/identity/profile/bookmarks", {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      });
      if (!response.ok) {
        throw new Error(response.status === 401 ? "Tu sesión expiró." : "No pudimos cargar tus marcadores.");
      }
      const payload = (await response.json()) as { data: { bookmarks: BookmarkItem[] } };
      setBookmarks(Array.isArray(payload.data.bookmarks) ? payload.data.bookmarks : []);
    } catch (e) {
      setErrorBookmarks(e instanceof Error ? e.message : "Error al cargar marcadores.");
    } finally {
      setIsLoadingBookmarks(false);
    }
  }, []);

  // Initial load
  useEffect(() => {
    void loadNewsletters();
  }, [loadNewsletters]);

  // Load bookmarks on first tab switch
  useEffect(() => {
    if (activeTab === "marcadores" && bookmarks.length === 0 && !isLoadingBookmarks && !errorBookmarks) {
      void loadBookmarks();
    }
  }, [activeTab, bookmarks.length, isLoadingBookmarks, errorBookmarks, loadBookmarks]);

  // Lock scroll when viewing a newsletter
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
    <AuthenticatedHomeLayout>
      <Head title={`${profile.name} — Perfil`} />

      {/* ── Newsletter Detail Overlay ─────────────────────────────────────── */}
      {selectedNewsletterId !== null && selectedNewsletter !== null ? (
        <NewsletterDetailOverlay
          newsletter={selectedNewsletter}
          onClose={() => setSelectedNewsletterId(null)}
        />
      ) : null}

      <section className="mx-auto flex w-full max-w-190 flex-col md:max-w-205">

        {/* ── Profile Header ────────────────────────────────────────────────── */}
        <header className="flex flex-row items-center gap-5 px-4 pt-4 pb-6 md:px-0 md:items-start md:gap-8">
          
          {/* Avatar Block (Left) */}
          <div className="shrink-0">
            <Link href="/profile/edit" aria-label="Editar foto de perfil" className="group relative block">
              <div className="h-20 w-20 md:h-32 md:w-32 overflow-hidden rounded-full ring-2 ring-zinc-200 transition-opacity group-hover:opacity-80">
                {avatarUrl ? (
                  <img src={avatarUrl} alt={profile.name} className="h-full w-full object-cover" />
                ) : (
                  <div className="flex h-full w-full items-center justify-center bg-zinc-200 text-2xl md:text-4xl satoshi-bold text-zinc-700">
                    {initials}
                  </div>
                )}
              </div>
            </Link>
          </div>

          {/* Info Block (Right) */}
          <div className="flex flex-col gap-3 md:gap-2 w-full">
            
            {/* Name & Subhandle */}
            <div className="flex flex-col md:gap-1">
              <h1 className="text-lg md:text-2xl satoshi-bold text-zinc-900">{profile.name}</h1>
              <span className="hidden md:inline-flex text-sm text-zinc-500 w-fit">
                {profile.handle ? (
                  profile.handle
                ) : (
                  <Link href="/profile/edit" className="hover:underline">@añadir_handle</Link>
                )}
              </span>
              {profile.bio && (
                <p className="text-sm text-zinc-700 mt-2 max-w-xl leading-relaxed">
                  {profile.bio}
                </p>
              )}
            </div>

            {/* Metrics Row */}
            <div className="flex flex-row gap-6 md:gap-6">
              <StatItem value={profile.stats.published_count} label="publicaciones" />
              <StatItem value={profile.stats.followers_count} label="seguidores" />
              <StatItem value={profile.stats.following_count} label="seguidos" />
            </div>

          </div>
        </header>

        {/* ── Tab Navigation ────────────────────────────────────────────────── */}
        <div className="border-b border-zinc-200">
          <nav className="flex" aria-label="Secciones del perfil">
            <TabButton
              id="tab-newsletters"
              label="newsletters"
              isActive={activeTab === "newsletters"}
              onClick={() => setActiveTab("newsletters")}
            />
            <TabButton
              id="tab-marcadores"
              label="marcadores"
              isActive={activeTab === "marcadores"}
              onClick={() => setActiveTab("marcadores")}
            />
          </nav>
        </div>

        {/* ── Tab Content ───────────────────────────────────────────────────── */}
        <div className="pt-1">
          {activeTab === "newsletters" ? (
            <NewslettersTab
              newsletters={newsletters}
              isLoading={isLoadingNewsletters}
              error={errorNewsletters}
              onRetry={loadNewsletters}
              onSelect={(id) => setSelectedNewsletterId(id)}
            />
          ) : (
            <BookmarksTab
              bookmarks={bookmarks}
              isLoading={isLoadingBookmarks}
              error={errorBookmarks}
              onRetry={loadBookmarks}
            />
          )}
        </div>
      </section>
    </AuthenticatedHomeLayout>
  );
}

// ─── Stat Item ────────────────────────────────────────────────────────────────

function StatItem({ value, label }: { value: number; label: string }) {
  return (
    <div className="flex flex-col items-start justify-center gap-0.5 md:flex-row md:gap-1.5 md:items-baseline md:justify-start">
      <span className="text-lg md:text-base satoshi-bold text-zinc-900">{value}</span>
      <span className="text-xs md:text-sm text-zinc-500">{label}</span>
    </div>
  );
}

// ─── Tab Button ───────────────────────────────────────────────────────────────

function TabButton({
  id,
  label,
  isActive,
  onClick,
}: {
  id: string;
  label: string;
  isActive: boolean;
  onClick: () => void;
}) {
  return (
    <button
      id={id}
      type="button"
      role="tab"
      aria-selected={isActive}
      onClick={onClick}
      className={[
        "px-4 py-3 text-sm satoshi-medium tracking-wide transition-colors",
        isActive
          ? "border-b-2 border-zinc-900 text-zinc-900"
          : "border-b-2 border-transparent text-zinc-500 hover:text-zinc-700",
      ].join(" ")}
    >
      {label}
    </button>
  );
}

// ─── Newsletters Tab ──────────────────────────────────────────────────────────

function NewslettersTab({
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
    return <NewslettersGridSkeleton />;
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
        <p className="text-sm text-zinc-500">Aún no has publicado ninguna newsletter.</p>
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
        <NewsletterGridCell
          key={newsletter.id}
          newsletter={newsletter}
          onSelect={onSelect}
        />
      ))}
    </div>
  );
}

// ─── Newsletter Grid Cell ─────────────────────────────────────────────────────

function NewsletterGridCell({
  newsletter,
  onSelect,
}: {
  newsletter: NewsletterItem;
  onSelect: (id: string) => void;
}) {
  return (
    <button
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
  );
}

// ─── Newsletters Grid Skeleton ────────────────────────────────────────────────

function NewslettersGridSkeleton() {
  return (
    <div className="grid grid-cols-3 gap-px bg-zinc-200">
      {Array.from({ length: 6 }).map((_, i) => (
        <div key={i} className="aspect-square animate-pulse bg-zinc-100" />
      ))}
    </div>
  );
}

// ─── Bookmarks Tab ────────────────────────────────────────────────────────────

function BookmarksTab({
  bookmarks,
  isLoading,
  error,
  onRetry,
}: {
  bookmarks: BookmarkItem[];
  isLoading: boolean;
  error: string | null;
  onRetry: () => void;
}) {
  if (isLoading) {
    return <BookmarksSkeleton />;
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

  if (bookmarks.length === 0) {
    return (
      <div className="flex flex-col items-center py-20 gap-3">
        <Bookmark className="h-8 w-8 text-zinc-300" />
        <p className="text-sm text-zinc-500">No tienes marcadores guardados.</p>
      </div>
    );
  }

  return (
    <ul className="flex flex-col gap-3 py-3" aria-label="Marcadores guardados">
      {bookmarks.map((bookmark) => (
        <BookmarkCard key={bookmark.bookmark_id} bookmark={bookmark} />
      ))}
    </ul>
  );
}

// ─── Bookmark Card ────────────────────────────────────────────────────────────

function BookmarkCard({ bookmark }: { bookmark: BookmarkItem }) {
  const getInitials = useInitials();
  const authorName = bookmark.author.name ?? "";
  const initials = authorName ? getInitials(authorName) : "?";

  return (
    <li className="overflow-hidden rounded-3xl bg-white shadow-[0_2px_12px_rgba(15,23,42,0.06)] transition-shadow hover:shadow-[0_4px_20px_rgba(15,23,42,0.10)]">
      {/* Card header: avatar + author */}
      <div className="flex items-center gap-3 px-5 pt-5 pb-3">
        <div className="h-8 w-8 shrink-0 overflow-hidden rounded-full bg-zinc-200">
          {bookmark.author.avatar_url ? (
            <img
              src={bookmark.author.avatar_url}
              alt={authorName}
              className="h-full w-full object-cover"
            />
          ) : (
            <span className="flex h-full w-full items-center justify-center text-[10px] satoshi-medium text-zinc-600">
              {initials}
            </span>
          )}
        </div>
        <span className="text-sm satoshi-medium text-zinc-700">{authorName || "Usuario"}</span>
      </div>

      {/* Card body: title + preview */}
      <div className="px-5 pb-5">
        <p className="text-sm satoshi-bold text-zinc-900 leading-snug">{bookmark.title}</p>
        {bookmark.preview_text ? (
          <p className="mt-1.5 line-clamp-2 text-sm text-zinc-500 leading-relaxed">
            {bookmark.preview_text}
          </p>
        ) : null}
      </div>
    </li>
  );
}

// ─── Bookmarks Skeleton ───────────────────────────────────────────────────────

function BookmarksSkeleton() {
  return (
    <ul className="flex flex-col gap-3 py-3">
      {Array.from({ length: 3 }).map((_, i) => (
        <li key={i} className="overflow-hidden rounded-3xl bg-white shadow-[0_2px_12px_rgba(15,23,42,0.06)]">
          <div className="flex items-center gap-3 px-5 pt-5 pb-3">
            <div className="h-8 w-8 animate-pulse rounded-full bg-zinc-200" />
            <div className="h-3 w-28 animate-pulse rounded bg-zinc-200" />
          </div>
          <div className="px-5 pb-5 flex flex-col gap-2">
            <div className="h-3.5 w-3/4 animate-pulse rounded bg-zinc-200" />
            <div className="h-3 w-full animate-pulse rounded bg-zinc-100" />
            <div className="h-3 w-2/3 animate-pulse rounded bg-zinc-100" />
          </div>
        </li>
      ))}
    </ul>
  );
}

// ─── Newsletter Detail Overlay ────────────────────────────────────────────────

function NewsletterDetailOverlay({
  newsletter,
  onClose,
}: {
  newsletter: NewsletterItem;
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
        const response = await fetch(`/identity/profile/newsletters/${newsletter.id}`, {
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
  }, [newsletter.id]);

  return (
    <div
      className="fixed inset-0 z-50 flex flex-col overflow-hidden bg-[#F7F4ED]"
      role="dialog"
      aria-modal="true"
      aria-label={newsletter.title}
    >
      {/* Sticky header */}
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

      {/* Scrollable body */}
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
              {/* Author + date */}
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

              {/* Cover image */}
              {detail.cover_image_url ? (
                <div className="mt-6 overflow-hidden rounded-2xl">
                  <img
                    src={detail.cover_image_url}
                    alt=""
                    className="w-full object-cover"
                  />
                </div>
              ) : null}

              {/* Body */}
              <div className="mt-6 flex flex-col gap-4">
                {detail.body_paragraphs.length > 0 ? (
                  detail.body_paragraphs.map((paragraph, i) => (
                    <p key={i} className="text-base leading-relaxed text-zinc-700">
                      {paragraph}
                    </p>
                  ))
                ) : (
                  <p className="text-sm text-zinc-500">El contenido de esta newsletter no está disponible en la vista previa.</p>
                )}
              </div>
            </>
          ) : null}
        </article>
      </div>
    </div>
  );
}
