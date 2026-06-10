import UserHomeLayout from "@/layouts/user-home-layout";
import { Head } from "@inertiajs/react";
import { ArrowLeft, X } from "lucide-react";
import { type CSSProperties, useCallback, useEffect, useMemo, useState } from "react";

type SubscriptionItem = {
  id: string;
  newsletter: {
    id: string;
    title: string;
    body_paragraphs: string[];
  };
  workspace: {
    id: string | null;
    name: string | null;
    slug: string | null;
  };
  owner: {
    id: string | null;
    name: string | null;
    avatar_url: string | null;
  };
  published_at: string | null;
  preview_text: string;
  cover_image_url: string | null;
  image_urls: string[];
};

type SubscriptionsResponse = {
  data: {
    subscriptions: SubscriptionItem[];
  };
};

export default function Subscriptions() {
  const [subscriptions, setSubscriptions] = useState<SubscriptionItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState<string | null>(null);

  const loadSubscriptions = useCallback(async (): Promise<void> => {
    setIsLoading(true);
    setErrorMessage(null);

    try {
      const response = await fetch("/community/subscriptions", {
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
      });

      if (!response.ok) {
        throw new Error(response.status === 401 ? "Tu sesión expiró." : "No pudimos cargar tus subscripciones.");
      }

      const payload = (await response.json()) as SubscriptionsResponse;
      setSubscriptions(Array.isArray(payload.data.subscriptions) ? payload.data.subscriptions : []);
    } catch (error) {
      setErrorMessage(error instanceof Error ? error.message : "No pudimos cargar tus subscripciones.");
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadSubscriptions();
  }, [loadSubscriptions]);

  const selectedSubscription = useMemo(
    () => subscriptions.find((subscription) => subscription.id === selectedSubscriptionId) ?? null,
    [selectedSubscriptionId, subscriptions],
  );

  useEffect(() => {
    document.body.style.overflow = selectedSubscriptionId === null ? "unset" : "hidden";

    return () => {
      document.body.style.overflow = "unset";
    };
  }, [selectedSubscriptionId]);

  useEffect(() => {
    if (selectedSubscriptionId !== null && selectedSubscription === null) {
      setSelectedSubscriptionId(null);
    }
  }, [selectedSubscription, selectedSubscriptionId]);

  return (
    <UserHomeLayout>
      <Head title="Subscripciones" />

      <section className="mx-auto flex w-full max-w-190 flex-col gap-5 md:max-w-205">
        <header className="px-1 pt-1 md:px-0">
          <p className="text-xs font-semibold uppercase tracking-[0.26em] text-zinc-500">Subscripciones</p>
          <h1 className="mt-2 text-3xl satoshi-bold tracking-tight text-zinc-900 sm:text-4xl">Newsletters</h1>
        </header>

        {isLoading ? <SubscriptionsSkeleton /> : null}

        {!isLoading && errorMessage ? (
          <div className="rounded-[28px] border border-zinc-200/80 bg-white px-6 py-5 text-sm text-zinc-600 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
            {errorMessage}
          </div>
        ) : null}

        {!isLoading && !errorMessage && subscriptions.length === 0 ? (
          <div className="rounded-[28px] border border-zinc-200/80 bg-white px-6 py-5 text-sm text-zinc-600 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
            Todavía no hay newsletters para mostrar.
          </div>
        ) : null}

        {!isLoading && !errorMessage && subscriptions.length > 0 ? (
          <div className="overflow-hidden bg-white md:rounded-4xl md:border md:border-zinc-200/80 md:shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
            {subscriptions.map((subscription) => (
              <button
                key={subscription.id}
                type="button"
                onClick={() => setSelectedSubscriptionId(subscription.id)}
                className="group block w-full border-b border-zinc-200/80 bg-white px-4 py-5 text-left transition last:border-b-0 hover:bg-[#FCFBF8] sm:px-6 sm:py-6 md:px-8 md:py-7"
              >
                <div className="flex items-start justify-between gap-4">
                  <div className="flex min-w-0 items-center gap-3">
                    <OwnerAvatar owner={subscription.owner} />

                    <div className="min-w-0">
                      <p className="truncate text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">
                        {subscription.owner.name ?? "Usuario"}
                      </p>
                    </div>
                  </div>

                  <time
                    className="shrink-0 text-xs uppercase tracking-[0.18em] text-zinc-500"
                    dateTime={subscription.published_at ?? undefined}
                  >
                    {subscription.published_at ? formatPublishedAt(subscription.published_at) : "Publicado"}
                  </time>
                </div>

                <div className="mt-4 grid grid-cols-[minmax(0,1fr)_84px] items-start gap-4 sm:grid-cols-[minmax(0,1fr)_104px] md:grid-cols-[minmax(0,1fr)_168px] md:gap-6">
                  <div className="min-w-0 space-y-3">
                    <h2 className="text-[1.15rem] font-semibold leading-tight tracking-tight text-zinc-900 transition group-hover:text-zinc-700 sm:text-[1.35rem] md:text-[1.55rem]">
                      {subscription.newsletter.title}
                    </h2>

                    <p
                      className="max-w-[52ch] text-sm leading-6 text-zinc-600 sm:text-[0.95rem] md:text-[1rem]"
                      style={clampTextStyle}
                    >
                      {subscription.preview_text || "Sin preview disponible todavía."}
                    </p>
                  </div>

                  <NewsletterThumbnail imageUrl={subscription.cover_image_url} title={subscription.newsletter.title} />
                </div>
              </button>
            ))}
          </div>
        ) : null}
      </section>

      {selectedSubscription ? (
        <NewsletterOverlay subscription={selectedSubscription} onClose={() => setSelectedSubscriptionId(null)} />
      ) : null}
    </UserHomeLayout>
  );
}

function SubscriptionsSkeleton(): React.JSX.Element {
  return (
    <div className="overflow-hidden md:rounded-4xl md:border md:border-zinc-200/80 md:bg-white md:shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
      {Array.from({ length: 3 }, (_, index) => (
        <div
          key={`subscription-skeleton-${index}`}
          className="border-b border-zinc-200/80 bg-white px-4 py-5 last:border-b-0 sm:px-6 sm:py-6 md:px-8 md:py-7"
        >
          <div className="flex items-start justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="h-11 w-11 animate-pulse rounded-full bg-zinc-200" />
              <div className="h-3 w-28 animate-pulse rounded-full bg-zinc-200" />
            </div>

            <div className="h-3 w-24 animate-pulse rounded-full bg-zinc-200" />
          </div>

          <div className="mt-4 grid grid-cols-[minmax(0,1fr)_84px] gap-4 sm:grid-cols-[minmax(0,1fr)_104px] md:grid-cols-[minmax(0,1fr)_168px] md:gap-6">
            <div className="space-y-3">
              <div className="h-6 w-[78%] animate-pulse rounded-full bg-zinc-200" />
              <div className="space-y-2">
                <div className="h-3 w-full animate-pulse rounded-full bg-zinc-200" />
                <div className="h-3 w-[92%] animate-pulse rounded-full bg-zinc-200" />
                <div className="h-3 w-[72%] animate-pulse rounded-full bg-zinc-200" />
                <div className="h-3 w-[54%] animate-pulse rounded-full bg-zinc-200" />
              </div>
            </div>

            <div className="aspect-square rounded-3xl bg-zinc-100" />
          </div>
        </div>
      ))}
    </div>
  );
}

function OwnerAvatar({ owner }: { owner: SubscriptionItem["owner"] }): React.JSX.Element {
  const initials = getInitials(owner.name);

  if (owner.avatar_url) {
    return <img src={owner.avatar_url} alt={owner.name ?? "Usuario"} className="h-11 w-11 rounded-full object-cover" />;
  }

  return (
    <div className="flex h-11 w-11 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white shadow-sm">
      {initials}
    </div>
  );
}

function NewsletterThumbnail({ imageUrl, title }: { imageUrl: string | null; title: string }): React.JSX.Element {
  if (imageUrl) {
    return (
      <div className="aspect-square overflow-hidden rounded-3xl bg-zinc-100 md:rounded-3xl">
        <img src={imageUrl} alt={title} className="h-full w-full object-cover" />
      </div>
    );
  }

  return <div className="aspect-square rounded-3xl border border-transparent bg-transparent md:rounded-3xl" aria-hidden="true" />;
}

function NewsletterOverlay({
  subscription,
  onClose,
}: {
  subscription: SubscriptionItem;
  onClose: () => void;
}): React.JSX.Element {
  return (
    <div className="fixed inset-0 z-50 bg-[#F7F4ED] md:left-60 md:right-0 md:top-4 md:bottom-4 md:bg-transparent md:px-4">
      <div className="flex h-full w-full flex-col overflow-hidden bg-white md:rounded-4xl md:border md:border-zinc-200/80 md:shadow-[0_24px_80px_rgba(15,23,42,0.18)]">
        <div className="flex items-center justify-between border-b border-zinc-200/70 px-4 py-4 sm:px-6 md:px-8">
          <button
            type="button"
            onClick={onClose}
            className="inline-flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-900 transition hover:bg-zinc-200 md:hidden"
            aria-label="Volver a subscripciones"
          >
            <ArrowLeft className="h-4 w-4" />
            Atrás
          </button>

          <button
            type="button"
            onClick={onClose}
            className="hidden h-10 w-10 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-900 transition hover:bg-zinc-100 md:inline-flex"
            aria-label="Cerrar newsletter"
          >
            <X className="h-5 w-5" />
          </button>

          <div className="hidden md:block" aria-hidden="true" />
        </div>

        <div className="flex-1 overflow-y-auto px-4 pb-10 pt-6 sm:px-6 md:px-14 md:pb-12 md:pt-10">
          <article className="mx-auto flex w-full max-w-190 flex-col gap-7">
            <div className="flex items-start justify-between gap-4">
              <div className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">
                  {subscription.owner.name ?? "Usuario"}
                </p>
                <h2 className="mt-3 text-[2rem] satoshi-bold tracking-tight text-zinc-900 sm:text-[2.5rem] md:text-[3.15rem]">
                  {subscription.newsletter.title}
                </h2>
              </div>

              <OwnerAvatar owner={subscription.owner} />
            </div>

            <div className="flex items-center gap-3 text-sm text-zinc-500">
              <span className="font-medium uppercase tracking-[0.2em] text-zinc-700">{subscription.owner.name ?? "Usuario"}</span>
              <span>·</span>
              <time dateTime={subscription.published_at ?? undefined}>
                {subscription.published_at ? formatPublishedAt(subscription.published_at) : "Publicado"}
              </time>
            </div>

            <div className="border-t border-zinc-200/80 pt-7">
              <div className="space-y-6 text-[1.07rem] leading-8 text-zinc-800 sm:text-[1.16rem] md:text-[1.2rem]">
                {(subscription.newsletter.body_paragraphs.length > 0
                  ? subscription.newsletter.body_paragraphs
                  : [subscription.preview_text]
                ).map((paragraph, index) => (
                  <p key={`${subscription.id}-paragraph-${index}`}>{paragraph}</p>
                ))}
              </div>

              {subscription.cover_image_url ? (
                <div className="mt-8 overflow-hidden rounded-[28px] bg-zinc-100">
                  <img src={subscription.cover_image_url} alt={subscription.newsletter.title} className="h-auto w-full object-cover" />
                </div>
              ) : null}

              {subscription.image_urls.length > 1 ? (
                <div className="mt-8 grid gap-4 sm:grid-cols-2">
                  {subscription.image_urls.slice(1).map((imageUrl, index) => (
                    <div key={`${subscription.id}-image-${index}`} className="overflow-hidden rounded-3xl bg-zinc-100">
                      <img src={imageUrl} alt={subscription.newsletter.title} className="h-full w-full object-cover" />
                    </div>
                  ))}
                </div>
              ) : null}
            </div>
          </article>
        </div>
      </div>
    </div>
  );
}

function getInitials(name: string | null): string {
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

function formatPublishedAt(value: string): string {
  return new Intl.DateTimeFormat("es-ES", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(new Date(value));
}

const clampTextStyle: CSSProperties = {
  display: "-webkit-box",
  WebkitBoxOrient: "vertical",
  WebkitLineClamp: 4,
  overflow: "hidden",
};
