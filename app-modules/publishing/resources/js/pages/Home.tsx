import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout";
import { Head, router } from "@inertiajs/react";
import { ChevronDown, ChevronLeft, ChevronRight, ChevronUp, Heart, Share2 } from "lucide-react";
import { type ReactNode, useCallback, useEffect, useMemo, useRef, useState } from "react";
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
    blocks: Array<{
      type?: string;
      data?: {
        html?: string;
        text?: string;
        quote?: string;
        style?: string;
        items?: string[];
      };
    }>;
    plain_text: string;
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
};

const HEART_ACTIVE = "text-zinc-900";
const HEART_IDLE = "text-zinc-500";

function sanitizeRichHtml(html: string): string {
  const parser = new DOMParser();
  const documentNode = parser.parseFromString(`<div>${html}</div>`, "text/html");
  const root = documentNode.body.firstElementChild as HTMLDivElement | null;

  if (!root) {
    return "";
  }

  const allowed = new Set(["A", "B", "BR", "EM", "I", "STRONG", "U"]);

  Array.from(root.querySelectorAll("*")).forEach((element) => {
    const tagName = element.tagName.toUpperCase();

    if (!allowed.has(tagName)) {
      const parent = element.parentNode;
      if (!parent) {
        return;
      }

      while (element.firstChild) {
        parent.insertBefore(element.firstChild, element);
      }
      parent.removeChild(element);
      return;
    }

    Array.from(element.attributes).forEach((attribute) => {
      if (tagName === "A" && attribute.name === "href") {
        return;
      }

      element.removeAttribute(attribute.name);
    });

    if (tagName === "A") {
      const href = element.getAttribute("href") ?? "";
      if (!/^(https?:|mailto:)/i.test(href)) {
        const parent = element.parentNode;
        if (!parent) {
          return;
        }

        while (element.firstChild) {
          parent.insertBefore(element.firstChild, element);
        }
        parent.removeChild(element);
        return;
      }

      element.setAttribute("target", "_blank");
      element.setAttribute("rel", "noopener noreferrer");
    }
  });

  return root.innerHTML;
}

function renderPostBlocks(blocks: FeedPost["content"]["blocks"]): ReactNode {
  if (blocks.length === 0) {
    return null;
  }

  return blocks.map((block, index) => {
    const blockType = block.type ?? "paragraph";

    if (blockType === "list") {
      const items = block.data?.items ?? [];
      if (items.length === 0) {
        return null;
      }

      const ListTag = block.data?.style === "ordered" ? "ol" : "ul";

      return (
        <ListTag
          key={`list-${index}`}
          className={block.data?.style === "ordered" ? "list-decimal pl-6" : "list-disc pl-6"}
        >
          {items.map((item, itemIndex) => (
            <li
              key={`item-${index}-${itemIndex}`}
              className="mb-1 wrap-break-word"
              dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(item) }}
            />
          ))}
        </ListTag>
      );
    }

    if (blockType === "quote") {
      const html = block.data?.html?.trim() || block.data?.text?.trim() || "";
      if (html === "") {
        return null;
      }

      return (
        <blockquote key={`quote-${index}`} className="border-l-3 border-zinc-400 pl-4 text-zinc-700 italic">
          <span dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(html) }} />
        </blockquote>
      );
    }

    const html = block.data?.html?.trim() || block.data?.text?.trim() || "";
    if (html === "") {
      return null;
    }

    return (
      <p
        key={`paragraph-${index}`}
        className="wrap-break-word"
        dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(html) }}
      />
    );
  });
}

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
  const loadMoreRef = useRef<HTMLDivElement | null>(null);
  const mediaRailByPostRef = useRef<Record<string, HTMLDivElement | null>>({});
  const [feedPosts, setFeedPosts] = useState<FeedPost[]>(posts);
  const [hasMorePosts, setHasMorePosts] = useState(feed.has_more);
  const [nextCursor, setNextCursor] = useState<string | null>(feed.next_cursor);
  const [isLoadingMore, setIsLoadingMore] = useState(false);

  const initialState = useMemo<Record<string, PostState>>(() => {
    return feedPosts.reduce<Record<string, PostState>>((acc, post) => {
      acc[post.id] = {
        likedByMe: post.metrics.liked_by_me,
        likesCount: post.metrics.likes_count,
      };

      return acc;
    }, {});
  }, [feedPosts]);

  const [postStateMap, setPostStateMap] = useState<Record<string, PostState>>(initialState);
  const [expandedPostMap, setExpandedPostMap] = useState<Record<string, boolean>>({});
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

  const toggleExpandedPost = (postId: string) => {
    setExpandedPostMap((previous) => ({
      ...previous,
      [postId]: !previous[postId],
    }));
  };

  const scrollMediaRail = (postId: string, direction: "next" | "prev") => {
    const rail = mediaRailByPostRef.current[postId];
    if (!rail) {
      return;
    }

    const offset = Math.round(rail.clientWidth * 0.74);
    rail.scrollBy({
      left: direction === "next" ? offset : -offset,
      behavior: "smooth",
    });
  };

  return (
    <AuthenticatedHomeLayout onCreateClick={() => setIsComposerOpen(true)}>
      <Head title="Home" />

      <section className="mx-auto w-full max-w-185 bg-[#F7F4ED]">
        {feedPosts.length === 0 ? (
          <div className="rounded-3xl border border-zinc-200 bg-white p-6 text-center shadow-[0_8px_20px_rgba(15,23,42,0.06)]">
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
          };

          const hasCarousel = post.media.length > 1;
          const shouldCollapseText = post.content.plain_text.length > 460 || post.content.blocks.length > 5;
          const isExpanded = expandedPostMap[post.id] ?? false;

          return (
            <article key={post.id} className="border-b-2 border-zinc-300/90 bg-[#F7F4ED] py-4 md:py-5">
              <div className="mx-auto w-full max-w-170 px-1 sm:px-2 md:px-0">
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

                <div className="mt-3">
                  <div
                    className={`relative space-y-2 text-[16px] leading-relaxed text-zinc-800 [&_a]:font-medium [&_a]:text-zinc-800 [&_a]:underline [&_a]:underline-offset-3 [&_a]:decoration-zinc-700 [&_a:hover]:text-zinc-900 ${
                      shouldCollapseText && !isExpanded ? "max-h-64 overflow-hidden" : ""
                    }`}
                  >
                    {renderPostBlocks(post.content.blocks)}
                    {shouldCollapseText && !isExpanded ? (
                      <div className="pointer-events-none absolute inset-x-0 bottom-0 h-14 bg-linear-to-t from-[#F7F4ED] to-transparent" />
                    ) : null}
                  </div>

                  {shouldCollapseText ? (
                    <button
                      type="button"
                      onClick={() => toggleExpandedPost(post.id)}
                      className="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-700 transition hover:text-zinc-900"
                    >
                      <span>{isExpanded ? "Ocultar" : "Ver mas"}</span>
                      {isExpanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                    </button>
                  ) : null}
                </div>

                {post.media.length > 0 ? (
                  <div className="mt-3">
                    {hasCarousel ? (
                      <div className="relative -mx-1 px-1 pb-1">
                        <div
                          ref={(node) => {
                            mediaRailByPostRef.current[post.id] = node;
                          }}
                          className="overflow-x-auto"
                        >
                          <div className="flex snap-x snap-mandatory gap-2.5">
                            {post.media.map((mediaItem, index) => {
                              const desktopWidthClass = post.media.length >= 3 ? "md:w-[42%]" : "md:w-[68%]";

                              return (
                                <div
                                  key={mediaItem.id}
                                  className={`w-[82%] shrink-0 snap-start overflow-hidden rounded-2xl border border-zinc-200 bg-[#F7F4ED] sm:w-[68%] ${desktopWidthClass}`}
                                >
                                  <img
                                    src={mediaItem.url}
                                    alt={`Imagen ${index + 1} del post ${post.content.plain_text.slice(0, 36) || "sin texto"}`}
                                    className="block h-auto w-full max-h-[72vh] object-contain md:max-h-140"
                                  />
                                </div>
                              );
                            })}
                          </div>
                        </div>

                        <button
                          type="button"
                          onClick={() => scrollMediaRail(post.id, "prev")}
                          className="absolute left-1 top-1/2 hidden h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/92 text-zinc-800 shadow-[0_2px_8px_rgba(0,0,0,0.18)] ring-1 ring-black/10 transition hover:bg-white md:inline-flex"
                          aria-label="Imagen anterior"
                        >
                          <ChevronLeft className="h-4 w-4" />
                        </button>
                        <button
                          type="button"
                          onClick={() => scrollMediaRail(post.id, "next")}
                          className="absolute right-1 top-1/2 hidden h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/92 text-zinc-800 shadow-[0_2px_8px_rgba(0,0,0,0.18)] ring-1 ring-black/10 transition hover:bg-white md:inline-flex"
                          aria-label="Imagen siguiente"
                        >
                          <ChevronRight className="h-4 w-4" />
                        </button>
                      </div>
                    ) : (
                      <div className="overflow-hidden rounded-2xl border border-zinc-200 bg-[#F7F4ED]">
                        <img
                          src={post.media[0]?.url}
                          alt={`Imagen del post ${post.content.plain_text.slice(0, 36) || "sin texto"}`}
                          className="block h-auto w-full max-h-[72vh] object-contain md:max-h-168"
                        />
                      </div>
                    )}
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
                    onClick={() =>
                      window.navigator.share?.({
                        title: post.content.plain_text.slice(0, 80) || "Post",
                      })
                    }
                    className="inline-flex items-center text-zinc-500 transition hover:text-zinc-900"
                    aria-label="Compartir"
                  >
                    <Share2 className="h-5 w-5" />
                  </button>
                </footer>
              </div>
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
