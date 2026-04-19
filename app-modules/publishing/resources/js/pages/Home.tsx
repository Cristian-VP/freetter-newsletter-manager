import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { Head, router } from "@inertiajs/react";
import { ChevronLeft, ChevronRight, Heart, Share2 } from "lucide-react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { CreateNoteModal } from "../components/create-note-modal";

type FeedPost = {
  id: string;
  author: {
    id: string | null;
    name: string | null;
    avatar_url: string | null;
  };
  published_at: string | null;
  published_relative: string | null;
  content: {
    title: string;
    excerpt: string;
  };
  media: Array<{
    id: string;
    url: string;
    mime_type: string;
  }>;
  metrics: {
    likes_count: number;
    liked_by_me: boolean;
  };
};

type HomePageProps = {
  posts: FeedPost[];
  workspace_id: string | null;
  feed: {
    has_more: boolean;
    next_cursor: string | null;
  };
};

type PostState = {
  likedByMe: boolean;
  likesCount: number;
  currentMediaIndex: number;
};

const HEART_ACTIVE = "text-zinc-900";
const HEART_IDLE = "text-zinc-500";

function getCsrfToken(): string {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

  return token ?? "";
}

function getXsrfTokenFromCookie(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

  return match ? decodeURIComponent(match[1]) : "";
}

async function likePost(postId: string): Promise<void> {
  await fetch("/community/likes", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": getCsrfToken(),
      "X-XSRF-TOKEN": getXsrfTokenFromCookie(),
    },
    body: JSON.stringify({ post_id: postId }),
    credentials: "same-origin",
  });
}

async function unlikePost(postId: string): Promise<void> {
  await fetch("/community/likes", {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": getCsrfToken(),
      "X-XSRF-TOKEN": getXsrfTokenFromCookie(),
    },
    body: JSON.stringify({ post_id: postId }),
    credentials: "same-origin",
  });
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

export default function Home({ posts, workspace_id, feed }: HomePageProps) {
  const touchStartByPostRef = useRef<Record<string, number | null>>({});
  const loadMoreRef = useRef<HTMLDivElement | null>(null);
  const [feedPosts, setFeedPosts] = useState<FeedPost[]>(posts);
  const [hasMorePosts, setHasMorePosts] = useState(feed.has_more);
  const [nextCursor, setNextCursor] = useState<string | null>(feed.next_cursor);
  const [isLoadingMore, setIsLoadingMore] = useState(false);

  const initialState = useMemo<Record<string, PostState>>(() => {
    return feedPosts.reduce<Record<string, PostState>>((acc, post) => {
      acc[post.id] = {
        likedByMe: post.metrics.liked_by_me,
        likesCount: post.metrics.likes_count,
        currentMediaIndex: 0,
      };

      return acc;
    }, {});
  }, [feedPosts]);

  const [postStateMap, setPostStateMap] = useState<Record<string, PostState>>(initialState);
  const [isComposerOpen, setIsComposerOpen] = useState(false);

  useEffect(() => {
    setFeedPosts(posts);
  }, [posts]);

  useEffect(() => {
    setHasMorePosts(feed.has_more);
    setNextCursor(feed.next_cursor);
  }, [feed]);

  useEffect(() => {
    setPostStateMap(initialState);
  }, [initialState]);

  const loadMorePosts = useCallback(async () => {
    if (isLoadingMore || !hasMorePosts || !nextCursor) {
      return;
    }

    setIsLoadingMore(true);

    try {
      const response = await fetch(`/publishing/feed?cursor=${encodeURIComponent(nextCursor)}`, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
      });

      if (!response.ok) {
        return;
      }

      const payload = (await response.json()) as {
        data?: {
          posts?: FeedPost[];
          has_more?: boolean;
          next_cursor?: string | null;
        };
      };

      const newPosts = payload.data?.posts ?? [];

      setFeedPosts((previous) => {
        const knownIds = new Set(previous.map((post) => post.id));
        const filtered = newPosts.filter((post) => !knownIds.has(post.id));

        return [...previous, ...filtered];
      });

      setHasMorePosts(payload.data?.has_more ?? false);
      setNextCursor(payload.data?.next_cursor ?? null);
    } finally {
      setIsLoadingMore(false);
    }
  }, [hasMorePosts, isLoadingMore, nextCursor]);

  useEffect(() => {
    const sentinel = loadMoreRef.current;
    if (!sentinel) {
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          void loadMorePosts();
        }
      },
      {
        root: null,
        rootMargin: "220px",
        threshold: 0,
      }
    );

    observer.observe(sentinel);

    return () => {
      observer.disconnect();
    };
  }, [loadMorePosts]);

  const handleToggleLike = async (postId: string) => {
    const currentState = postStateMap[postId];
    if (!currentState) {
      return;
    }

    const nextLiked = !currentState.likedByMe;
    const nextCount = nextLiked
      ? currentState.likesCount + 1
      : Math.max(0, currentState.likesCount - 1);

    setPostStateMap((prev) => ({
      ...prev,
      [postId]: {
        ...prev[postId],
        likedByMe: nextLiked,
        likesCount: nextCount,
      },
    }));

    try {
      if (nextLiked) {
        await likePost(postId);
      } else {
        await unlikePost(postId);
      }
    } catch {
      setPostStateMap((prev) => ({
        ...prev,
        [postId]: currentState,
      }));
    }
  };

  const setCurrentMediaIndex = (postId: string, index: number) => {
    setPostStateMap((prev) => ({
      ...prev,
      [postId]: {
        ...prev[postId],
        currentMediaIndex: index,
      },
    }));
  };

  const goToNextMedia = (postId: string, totalMedia: number) => {
    if (totalMedia <= 1) {
      return;
    }

    setPostStateMap((prev) => {
      const current = prev[postId];
      if (!current) {
        return prev;
      }

      return {
        ...prev,
        [postId]: {
          ...current,
          currentMediaIndex: (current.currentMediaIndex + 1) % totalMedia,
        },
      };
    });
  };

  const goToPreviousMedia = (postId: string, totalMedia: number) => {
    if (totalMedia <= 1) {
      return;
    }

    setPostStateMap((prev) => {
      const current = prev[postId];
      if (!current) {
        return prev;
      }

      return {
        ...prev,
        [postId]: {
          ...current,
          currentMediaIndex: (current.currentMediaIndex - 1 + totalMedia) % totalMedia,
        },
      };
    });
  };

  const handleTouchStart = (postId: string, clientX: number) => {
    touchStartByPostRef.current[postId] = clientX;
  };

  const handleTouchEnd = (postId: string, clientX: number, totalMedia: number) => {
    const startX = touchStartByPostRef.current[postId];
    touchStartByPostRef.current[postId] = null;

    if (startX === null || startX === undefined || totalMedia <= 1) {
      return;
    }

    const deltaX = clientX - startX;
    const swipeThreshold = 40;

    if (deltaX <= -swipeThreshold) {
      goToNextMedia(postId, totalMedia);
    }

    if (deltaX >= swipeThreshold) {
      goToPreviousMedia(postId, totalMedia);
    }
  };

  return (
    <AuthenticatedHomeLayout onCreateClick={() => setIsComposerOpen(true)}>
      <Head title="Home" />

      <section className="-mx-3 w-[calc(100%+1.5rem)] bg-[#F7F4ED] md:mx-auto md:w-full md:max-w-190">
        {feedPosts.length === 0 ? (
          <div className="mx-3 rounded-3xl border border-zinc-200 bg-white p-6 text-center shadow-[0_8px_20px_rgba(15,23,42,0.06)] md:mx-0">
            <p className="text-base font-semibold tracking-tight text-zinc-900">No hay posts publicados todavía</p>
            <p className="mt-2 text-sm text-zinc-500">
              Cuando otros usuarios publiquen notas, aparecerán aquí en orden de publicación más reciente.
            </p>
          </div>
        ) : null}

        {feedPosts.map((post) => {
          const postState = postStateMap[post.id] ?? {
            likedByMe: false,
            likesCount: 0,
            currentMediaIndex: 0,
          };

          const hasCarousel = post.media.length > 1;
          const currentMedia = post.media[postState.currentMediaIndex];

          return (
            <article key={post.id} className="relative bg-[#F7F4ED] py-4">
              <div className="mx-auto w-full max-w-170 px-3 md:px-4">
                <header className="flex items-center gap-3">
                  {post.author.avatar_url ? (
                    <img
                      src={post.author.avatar_url}
                      alt={`Avatar de ${post.author.name ?? "usuario"}`}
                      className="h-10 w-10 rounded-full object-cover"
                    />
                  ) : (
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700">
                      {initialsFromName(post.author.name)}
                    </div>
                  )}

                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-zinc-900">{post.author.name ?? "Usuario"}</p>
                    <p className="text-xs text-zinc-500">{post.published_relative ?? "Ahora"}</p>
                  </div>
                </header>

                <div className="mt-3 space-y-2">
                  <h2 className="text-base font-semibold tracking-tight text-zinc-900">{post.content.title}</h2>
                  <p className="whitespace-pre-line text-[15px] leading-relaxed text-zinc-700">{post.content.excerpt}</p>
                </div>

                {currentMedia ? (
                  <div className="mt-3">
                    <div
                      className="relative -mx-3 aspect-[4/5] w-[calc(100%+1.5rem)] overflow-hidden bg-zinc-100 md:mx-0 md:w-full md:rounded-2xl md:border md:border-zinc-200"
                      onTouchStart={(event) => handleTouchStart(post.id, event.changedTouches[0]?.clientX ?? 0)}
                      onTouchEnd={(event) =>
                        handleTouchEnd(post.id, event.changedTouches[0]?.clientX ?? 0, post.media.length)
                      }
                    >
                      <img
                        src={currentMedia.url}
                        alt={`Imagen del post ${post.content.title}`}
                        className="h-full w-full object-cover"
                      />

                      {hasCarousel ? (
                        <>
                          <button
                            type="button"
                            onClick={() => goToPreviousMedia(post.id, post.media.length)}
                            className="absolute left-2 top-1/2 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-800 shadow-[0_2px_8px_rgba(0,0,0,0.18)] ring-1 ring-black/10 transition hover:bg-white md:inline-flex"
                            aria-label="Imagen anterior"
                          >
                            <ChevronLeft className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => goToNextMedia(post.id, post.media.length)}
                            className="absolute right-2 top-1/2 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-zinc-800 shadow-[0_2px_8px_rgba(0,0,0,0.18)] ring-1 ring-black/10 transition hover:bg-white md:inline-flex"
                            aria-label="Imagen siguiente"
                          >
                            <ChevronRight className="h-4 w-4" />
                          </button>
                        </>
                      ) : null}
                    </div>

                    {hasCarousel ? (
                      <div className="mt-2 flex items-center justify-center gap-1.5">
                        {post.media.map((mediaItem, index) => (
                          <button
                            key={mediaItem.id}
                            type="button"
                            onClick={() => setCurrentMediaIndex(post.id, index)}
                            className={`h-2 w-2 rounded-full transition ${
                              index === postState.currentMediaIndex ? "bg-zinc-900" : "bg-zinc-300"
                            }`}
                            aria-label={`Ir a imagen ${index + 1}`}
                          />
                        ))}
                      </div>
                    ) : null}
                  </div>
                ) : null}

                <footer className="mt-3 flex items-center justify-between text-sm text-zinc-500">
                  <button
                    type="button"
                    onClick={() => handleToggleLike(post.id)}
                    className="inline-flex items-center gap-2 transition"
                  >
                    <Heart
                      className={`h-5 w-5 ${postState.likedByMe ? HEART_ACTIVE : HEART_IDLE}`}
                      fill={postState.likedByMe ? "currentColor" : "none"}
                    />
                    <span className={postState.likedByMe ? "text-zinc-900" : "text-zinc-500"}>
                      {postState.likesCount}
                    </span>
                  </button>

                  <button
                    type="button"
                    onClick={() => window.navigator.share?.({ title: post.content.title })}
                    className="inline-flex items-center text-zinc-500 transition hover:text-zinc-900"
                    aria-label="Compartir"
                  >
                    <Share2 className="h-5 w-5" />
                  </button>
                </footer>
              </div>

              <div className="absolute bottom-0 left-1/2 h-0.5 w-screen -translate-x-1/2 bg-zinc-300 md:w-full" />
            </article>
          );
        })}

        <div ref={loadMoreRef} className="h-8 w-full" aria-hidden="true" />

        {isLoadingMore ? (
          <div className="px-4 pb-6 text-center text-sm text-zinc-500 md:px-0">Cargando mas posts...</div>
        ) : null}
      </section>

      <CreateNoteModal
        isOpen={isComposerOpen}
        workspaceId={workspace_id}
        onClose={() => setIsComposerOpen(false)}
        onPublished={() => {
          setIsComposerOpen(false);
          router.reload({ only: ["posts", "workspace_id", "feed"] });
        }}
      />
    </AuthenticatedHomeLayout>
  );
}
