import UserHomeLayout from "@/layouts/user-home-layout";
import { type PageProps } from "@/types";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { Ellipsis } from "lucide-react";
import { type CSSProperties, useCallback, useEffect, useMemo, useState } from "react";

type NewsletterStatus = "draft" | "scheduled" | "published";

type NewsletterItem = {
  id: string;
  title: string;
  status: NewsletterStatus;
  excerpt: string | null;
  preview_text: string;
  created_at: string | null;
  published_at: string | null;
  updated_at: string | null;
  builder_url: string;
  publish_url: string;
};

type NewsletterResumeResponse = {
  data: {
    items: NewsletterItem[];
  };
};

type TabKey = "todos" | "borrador" | "programados" | "enviados";

const tabs: Array<{ key: TabKey; label: string }> = [
  { key: "todos", label: "todos" },
  { key: "borrador", label: "borrador" },
  { key: "programados", label: "programados" },
  { key: "enviados", label: "enviados" },
];

export default function NewsletterResume() {
  const { auth } = usePage<PageProps>().props;
  const [items, setItems] = useState<NewsletterItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [snackbar, setSnackbar] = useState<{ show: boolean; message: string; durationMs: number; canGoTop: boolean }>({
    show: false,
    message: "",
    durationMs: 1400,
    canGoTop: false,
  });
  const [selectedTab, setSelectedTab] = useState<TabKey>("todos");
  const [activeMenuId, setActiveMenuId] = useState<string | null>(null);

  const loadNewsletters = useCallback(async (): Promise<void> => {
    setIsLoading(true);
    setErrorMessage(null);

    try {
      const response = await fetch("/publishing/newsletters?status=all&per_page=30", {
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
      });

      if (!response.ok) {
        throw new Error(response.status === 401 ? "Tu sesión expiró." : "No pudimos cargar tus newsletters.");
      }

      const payload = (await response.json()) as NewsletterResumeResponse;
      setItems(Array.isArray(payload.data.items) ? payload.data.items : []);
    } catch (error) {
      setErrorMessage(error instanceof Error ? error.message : "No pudimos cargar tus newsletters.");
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadNewsletters();
  }, [loadNewsletters]);

  useEffect(() => {
    const closeMenu = () => setActiveMenuId(null);

    window.addEventListener("click", closeMenu);

    return () => {
      window.removeEventListener("click", closeMenu);
    };
  }, []);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const snackbarCode = params.get("snackbar");

    if (snackbarCode === "newsletter-cancelada") {
      setSnackbar({
        show: true,
        message: "Newsletter cancelada",
        durationMs: 1800,
        canGoTop: false,
      });

      params.delete("snackbar");
      const nextSearch = params.toString();
      const nextUrl = `${window.location.pathname}${nextSearch ? `?${nextSearch}` : ""}${window.location.hash}`;
      window.history.replaceState({}, "", nextUrl);
    } else if (snackbarCode === "newsletter-publicada" || snackbarCode === "newsletter-programada") {
      setSnackbar({
        show: true,
        message: snackbarCode === "newsletter-publicada" ? "Newsletter publicada" : "Newsletter programada",
        durationMs: 1800,
        canGoTop: false,
      });

      params.delete("snackbar");
      const nextSearch = params.toString();
      const nextUrl = `${window.location.pathname}${nextSearch ? `?${nextSearch}` : ""}${window.location.hash}`;
      window.history.replaceState({}, "", nextUrl);
    }
  }, []);

  useEffect(() => {
    if (!snackbar.show) {
      return;
    }

    const timeout = window.setTimeout(() => {
      setSnackbar((current) => ({ ...current, show: false }));
    }, snackbar.durationMs);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [snackbar.durationMs, snackbar.show]);

  const filteredItems = useMemo(() => {
    if (selectedTab === "todos") {
      return items.filter((item) => item.status !== "published");
    }

    if (selectedTab === "borrador") {
      return items.filter((item) => item.status === "draft");
    }

    if (selectedTab === "programados") {
      return items.filter((item) => item.status === "scheduled");
    }

    return items.filter((item) => item.status === "published");
  }, [items, selectedTab]);

  const draftCount = useMemo(() => items.filter((item) => item.status === "draft").length, [items]);
  const scheduledCount = useMemo(() => items.filter((item) => item.status === "scheduled").length, [items]);


  return (
    <UserHomeLayout>
      <Head title="Crear Newsletter" />

      <section className="mx-auto flex w-full max-w-190 flex-col gap-5 md:max-w-205">
        <header className="px-1 pt-1 md:px-0">
          <p className="text-xs font-semibold uppercase tracking-[0.26em] text-zinc-500">Resumen</p>


          <div className="mt-4 border-b border-zinc-200/80">
            <div className="no-scrollbar flex min-w-0 gap-5 overflow-x-auto pb-2">
              {tabs.map((tab) => {
                const isActive = selectedTab === tab.key;
                const badgeCount =
                  tab.key === "borrador" ? draftCount : tab.key === "programados" ? scheduledCount : 0;

                return (
                  <button
                    key={tab.key}
                    type="button"
                    onClick={() => setSelectedTab(tab.key)}
                    className={`relative flex shrink-0 items-center gap-2 border-b-2 pb-2 text-sm font-semibold uppercase tracking-[0.18em] transition ${
                      isActive
                        ? "border-zinc-900 text-zinc-900"
                        : "border-transparent text-zinc-500 hover:text-zinc-700"
                    }`}
                  >
                    <span>{tab.label}</span>

                    {badgeCount > 0 ? (
                      tab.key === "borrador" || tab.key === "programados" ? (
                        <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-black px-1.5 text-[10px] font-semibold text-white">
                          {badgeCount}
                        </span>
                      ) : null
                    ) : null}
                  </button>
                );
              })}
            </div>
          </div>
        </header>

        {isLoading ? <NewsletterResumeSkeleton /> : null}

        {!isLoading && errorMessage ? (
          <div className="rounded-[28px] border border-zinc-200/80 bg-white px-6 py-5 text-sm text-zinc-600 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
            {errorMessage}
          </div>
        ) : null}

        {!isLoading ? (
          <div className="flex flex-col gap-4">
            {!errorMessage ? (
              <div className="overflow-hidden bg-white md:rounded-4xl md:border md:border-zinc-200/80 md:shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
                {filteredItems.map((item) => (
                <article
                  key={item.id}
                  className="group border-b border-zinc-200/80 bg-white px-4 py-5 last:border-b-0 sm:px-6 sm:py-6 md:px-8 md:py-7"
                >
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-3">
                      {/* <span className="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Newsletter</span> */}
                    </div>

                    <div className="flex items-center gap-2">
                      <time
                        className="shrink-0 text-xs uppercase tracking-[0.18em] text-zinc-500"
                        dateTime={item.created_at ?? undefined}
                      >
                        {formatItemDate(item.created_at)}
                      </time>

                      <div className="relative">
                        <button
                          type="button"
                          onClick={(event) => {
                            event.stopPropagation();
                            setActiveMenuId((value) => (value === item.id ? null : item.id));
                          }}
                          className="inline-flex h-8 w-8 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                          aria-label="Abrir acciones"
                        >
                          <Ellipsis className="h-4 w-4" />
                        </button>

                        {activeMenuId === item.id ? (
                          <div
                            className="absolute right-0 top-9 z-10 min-w-40 rounded-xl border border-zinc-200 bg-white p-1 shadow-[0_18px_44px_rgba(15,23,42,0.16)]"
                            onClick={(event) => event.stopPropagation()}
                          >
                            {item.status === "published" ? (
                              <div className="px-3 py-2 text-sm text-zinc-500">
                                <span className="font-medium text-zinc-700">Publicada</span>
                                {item.published_at ? (
                                  <>{" "}{formatItemDate(item.published_at)}</>
                                ) : null}
                              </div>
                            ) : (
                              <button
                                type="button"
                                onClick={() => router.visit(`${item.publish_url}&from=resume`)}
                                className="flex w-full items-center justify-start rounded-lg px-3 py-2 text-sm font-medium text-zinc-900 transition hover:bg-zinc-100"
                              >
                                Publicar
                              </button>
                            )}
                          </div>
                        ) : null}
                      </div>
                    </div>
                  </div>

                  <button
                    type="button"
                    onClick={() => router.visit(item.builder_url)}
                    className="mt-4 block w-full text-left"
                  >
                    <h2 className="text-[1.15rem] font-semibold leading-tight tracking-tight text-black transition group-hover:text-zinc-700 sm:text-[1.35rem] md:text-[1.55rem]">
                      {item.title}
                    </h2>

                    <p
                      className="mt-3 max-w-[56ch] text-sm leading-6 text-zinc-600 sm:text-[0.95rem] md:text-[1rem]"
                      style={clampTextStyle}
                    >
                      {item.excerpt?.trim() || item.preview_text || "Sin preview disponible todavía."}
                    </p>
                  </button>
                </article>
                ))}

                {filteredItems.length === 0 ? (
                  <div className="px-6 py-8 text-sm text-zinc-600 sm:px-8">No hay newsletters para este estado.</div>
                ) : null}
              </div>
            ) : null}

            <div className="w-full md:flex md:justify-end">
              <Link
                href="/newsletters/create"
                prefetch
                className="inline-flex h-17 w-full items-center gap-3 rounded-[22px] border border-zinc-300 bg-transparent px-5 text-base font-semibold text-zinc-900 transition hover:border-transparent hover:bg-black hover:text-white md:h-15.5 md:w-[46%]"
              >
                {auth.user?.avatar ? (
                  <img src={auth.user!.avatar} alt={auth.user!.name ?? "Usuario"} className="h-10 w-10 rounded-full object-cover" />
                ) : (
                  <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white">
                    {initialsFromName(auth.user?.name ?? null)}
                  </span>
                )}
                <span>Nueva publicación</span>
              </Link>
            </div>
          </div>
        ) : null}
      </section>

      {snackbar.show ? (
        <button
          type="button"
          onClick={() => {
            if (snackbar.canGoTop) {
              window.scrollTo({ top: 0, behavior: "smooth" });
            }

            setSnackbar((current) => ({ ...current, show: false }));
          }}
          className="fixed bottom-20 left-1/2 z-90 inline-flex -translate-x-1/2 items-center gap-2 rounded-full bg-zinc-900 px-4 py-2 text-sm text-white shadow-[0_10px_22px_rgba(0,0,0,0.25)]"
        >
          {snackbar.message}
        </button>
      ) : null}
    </UserHomeLayout>
  );
}

function NewsletterResumeSkeleton(): React.JSX.Element {
  return (
    <div className="overflow-hidden md:rounded-4xl md:border md:border-zinc-200/80 md:bg-white md:shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
      {Array.from({ length: 3 }, (_, index) => (
        <div
          key={`newsletter-skeleton-${index}`}
          className="border-b border-zinc-200/80 bg-white px-4 py-5 last:border-b-0 sm:px-6 sm:py-6 md:px-8 md:py-7"
        >
          <div className="flex items-center justify-between gap-4">
            <div className="h-3 w-24 animate-pulse rounded-full bg-zinc-200" />
            <div className="h-3 w-20 animate-pulse rounded-full bg-zinc-200" />
          </div>

          <div className="mt-4 space-y-3">
            <div className="h-6 w-[70%] animate-pulse rounded-full bg-zinc-200" />
            <div className="space-y-2">
              <div className="h-3 w-full animate-pulse rounded-full bg-zinc-200" />
              <div className="h-3 w-[90%] animate-pulse rounded-full bg-zinc-200" />
              <div className="h-3 w-[76%] animate-pulse rounded-full bg-zinc-200" />
              <div className="h-3 w-[58%] animate-pulse rounded-full bg-zinc-200" />
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

function formatItemDate(value: string | null): string {
  if (!value) {
    return "Sin fecha";
  }

  return new Intl.DateTimeFormat("es-ES", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(new Date(value));
}

function readCsrfToken(): string {
  const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  return token ?? "";
}

function initialsFromName(name: string | null): string {
  if (!name) {
    return "?";
  }

  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join("");
}

const clampTextStyle: CSSProperties = {
  display: "-webkit-box",
  WebkitBoxOrient: "vertical",
  WebkitLineClamp: 4,
  overflow: "hidden",
};
