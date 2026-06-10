import UserHomeLayout from "@/layouts/user-home-layout";
import { Head, router } from "@inertiajs/react";
import {
  AlertTriangle,
  Bookmark,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  Copy,
  Flag,
  MoreHorizontal,
  Share2,
  UserMinus,
  UserPlus,
  UserX,
} from "lucide-react";
import { type ReactNode, useCallback, useEffect, useMemo, useRef, useState } from "react";
import { CreateNoteModal } from "../components/create-note-modal";
import { PostCommentsPanel } from "../components/post-comments-panel";

type FeedPost = {
  id: string;
  author: {
    id: string | null;
    name: string | null;
    avatar_url: string | null;
  };
  workspace_id: string;
  published_at: string | null;
  published_relative: string | null;
  post_url: string;
  content: {
    blocks: Array<{
      type?: string;
      data?: {
        html?: string;
        text?: string;
        quote?: string;
        style?: string;
        items?: string[];
        src?: string;
        alt?: string;
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
    reposts_count: number;
    reposted_by_me: boolean;
    bookmarked_by_me: boolean;
    subscribed_to_workspace: boolean;
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
  repostedByMe: boolean;
  repostsCount: number;
  bookmarkedByMe: boolean;
  subscribedToWorkspace: boolean;
};

function sanitizeRichHtml(html: string): string {
  const parser = new DOMParser();
  const documentNode = parser.parseFromString(`<div>${html}</div>`, "text/html");
  const root = documentNode.body.firstElementChild as HTMLDivElement | null;

  if (!root) {
    return "";
  }

  const allowed = new Set(["A", "B", "BR", "DEL", "EM", "I", "S", "STRIKE", "STRONG", "U"]);

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
        <blockquote key={`quote-${index}`} className="border-l-3 border-zinc-300 pl-3 text-zinc-700">
          <span dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(html) }} />
        </blockquote>
      );
    }

    if (blockType === "image") {
      const src = block.data?.src?.trim() || "";
      if (src === "") {
        return null;
      }

      return (
        <div key={`image-${index}`} className="overflow-hidden rounded-2xl border border-zinc-200 bg-[#F7F4ED]">
          <img
            src={src}
            alt={block.data?.alt?.trim() || "Imagen de la newsletter"}
            className="block h-auto w-full"
            loading="lazy"
            decoding="async"
          />
        </div>
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

async function postJson(url: string, body: Record<string, string>): Promise<Response> {
  return fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": getCsrfToken(),
      "X-XSRF-TOKEN": getXsrfTokenFromCookie(),
    },
    body: JSON.stringify(body),
    credentials: "same-origin",
  });
}

async function deleteJson(url: string, body: Record<string, string>): Promise<Response> {
  return fetch(url, {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": getCsrfToken(),
      "X-XSRF-TOKEN": getXsrfTokenFromCookie(),
    },
    body: JSON.stringify(body),
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
  const menuContainerRef = useRef<HTMLDivElement | null>(null);
  const touchStartByPostRef = useRef<Record<string, number>>({});

  const [feedPosts, setFeedPosts] = useState<FeedPost[]>(posts);
  const [hasMorePosts, setHasMorePosts] = useState(feed.has_more);
  const [nextCursor, setNextCursor] = useState<string | null>(feed.next_cursor);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [expandedPostMap, setExpandedPostMap] = useState<Record<string, boolean>>({});
  const [mediaIndexByPostMap, setMediaIndexByPostMap] = useState<Record<string, number>>({});
  const [openMenuPostId, setOpenMenuPostId] = useState<string | null>(null);
  const [commentsPanelPostId, setCommentsPanelPostId] = useState<string | null>(null);
  const [isComposerOpen, setIsComposerOpen] = useState(() => {
    return new URLSearchParams(window.location.search).get("open") === "composer";
  });
  const [repostComposerPost, setRepostComposerPost] = useState<FeedPost | null>(null);
  const [isSubmittingReport, setIsSubmittingReport] = useState(false);
  const [reportModalState, setReportModalState] = useState<{
    postId: string;
    category: string;
    reason: string;
  } | null>(null);
  const [snackbar, setSnackbar] = useState<{ show: boolean; message: string; durationMs: number; canGoTop: boolean }>({
    show: false,
    message: "",
    durationMs: 1200,
    canGoTop: false,
  });

  const initialState = useMemo<Record<string, PostState>>(() => {
    return feedPosts.reduce<Record<string, PostState>>((acc, post) => {
      acc[post.id] = {
        repostedByMe: post.metrics.reposted_by_me,
        repostsCount: post.metrics.reposts_count,
        bookmarkedByMe: post.metrics.bookmarked_by_me,
        subscribedToWorkspace: post.metrics.subscribed_to_workspace,
      };

      return acc;
    }, {});
  }, [feedPosts]);

  const [postStateMap, setPostStateMap] = useState<Record<string, PostState>>(initialState);

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

  useEffect(() => {
    const closeMenuOnOutside = (event: MouseEvent) => {
      if (!menuContainerRef.current) {
        return;
      }

      if (!menuContainerRef.current.contains(event.target as Node)) {
        setOpenMenuPostId(null);
      }
    };

    document.addEventListener("mousedown", closeMenuOnOutside);

    return () => {
      document.removeEventListener("mousedown", closeMenuOnOutside);
    };
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

  useEffect(() => {
    if (isComposerOpen) {
      const params = new URLSearchParams(window.location.search);
      if (params.has("open")) {
        params.delete("open");
        const newUrl = `${window.location.pathname}${params.toString() ? `?${params}` : ""}`;
        window.history.replaceState({}, "", newUrl);
      }
    }
  }, []);

  const absolutePostUrl = useCallback((post: FeedPost): string => {
    return new URL(post.post_url, window.location.origin).toString();
  }, []);

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

  const toggleExpandedPost = (postId: string) => {
    setExpandedPostMap((previous) => ({
      ...previous,
      [postId]: !previous[postId],
    }));
  };

  const updatePostState = (postId: string, updater: (current: PostState) => PostState) => {
    setPostStateMap((previous) => {
      const current = previous[postId];
      if (!current) {
        return previous;
      }

      return {
        ...previous,
        [postId]: updater(current),
      };
    });
  };

  const removePostFromFeed = (postId: string) => {
    setFeedPosts((previous) => previous.filter((post) => post.id !== postId));
    setOpenMenuPostId(null);
    if (commentsPanelPostId === postId) {
      setCommentsPanelPostId(null);
    }
  };

  const syncFeedPostMetrics = useCallback((postId: string, updater: (post: FeedPost) => FeedPost) => {
    setFeedPosts((previous) => previous.map((post) => (post.id === postId ? updater(post) : post)));
  }, []);

  const handlePostContentClick = useCallback((event: React.MouseEvent<HTMLElement>) => {
    const target = event.target as HTMLElement | null;
    const anchor = target?.closest("a");
    if (!anchor) {
      return;
    }

    const href = anchor.getAttribute("href") ?? "";
    if (href === "") {
      return;
    }

    try {
      const url = new URL(href, window.location.origin);
      const hash = url.hash || "";

      if (url.origin !== window.location.origin || !hash.startsWith("#post-")) {
        return;
      }

      const targetPost = document.querySelector(hash);
      if (!(targetPost instanceof HTMLElement)) {
        return;
      }

      event.preventDefault();
      targetPost.scrollIntoView({ behavior: "smooth", block: "start" });
      window.history.replaceState(null, "", hash);
    } catch {
      // Keep default anchor behavior when URL parsing fails.
    }
  }, []);

  const goToPrevMedia = (postId: string) => {
    const post = feedPosts.find((candidate) => candidate.id === postId);
    if (!post || post.media.length < 2) {
      return;
    }

    setMediaIndexByPostMap((previous) => {
      const current = previous[postId] ?? 0;
      const nextIndex = Math.max(0, current - 1);

      return {
        ...previous,
        [postId]: nextIndex,
      };
    });
  };

  const goToNextMedia = (postId: string) => {
    const post = feedPosts.find((candidate) => candidate.id === postId);
    if (!post || post.media.length < 2) {
      return;
    }

    setMediaIndexByPostMap((previous) => {
      const current = previous[postId] ?? 0;
      const nextIndex = Math.min(post.media.length - 1, current + 1);

      return {
        ...previous,
        [postId]: nextIndex,
      };
    });
  };

  const setMediaIndex = (postId: string, index: number) => {
    setMediaIndexByPostMap((previous) => ({
      ...previous,
      [postId]: index,
    }));
  };

  const handleToggleBookmark = async (postId: string) => {
    const current = postStateMap[postId];
    if (!current) {
      return;
    }

    updatePostState(postId, (state) => ({
      ...state,
      bookmarkedByMe: !state.bookmarkedByMe,
    }));

    syncFeedPostMetrics(postId, (post) => ({
      ...post,
      metrics: {
        ...post.metrics,
        bookmarked_by_me: !post.metrics.bookmarked_by_me,
      },
    }));

    try {
      if (current.bookmarkedByMe) {
        const response = await deleteJson("/community/bookmarks", { post_id: postId });
        if (!response.ok) {
          throw new Error("bookmark remove failed");
        }
      } else {
        const response = await postJson("/community/bookmarks", { post_id: postId });
        if (!response.ok && response.status !== 409) {
          throw new Error("bookmark failed");
        }
      }

      setSnackbar({
        show: true,
        message: current.bookmarkedByMe ? "Marcador eliminado" : "Guardado en marcadores",
        durationMs: 1500,
        canGoTop: false,
      });
    } catch {
      updatePostState(postId, () => current);
      syncFeedPostMetrics(postId, (post) => ({
        ...post,
        metrics: {
          ...post.metrics,
          bookmarked_by_me: current.bookmarkedByMe,
        },
      }));

      setSnackbar({
        show: true,
        message: "No se pudo actualizar marcador",
        durationMs: 1800,
        canGoTop: false,
      });
    }
  };

  const commentsPanelPost = useMemo(() => {
    if (!commentsPanelPostId) {
      return null;
    }

    return feedPosts.find((post) => post.id === commentsPanelPostId) ?? null;
  }, [commentsPanelPostId, feedPosts]);

  const handleSubscribe = async (post: FeedPost) => {
    const current = postStateMap[post.id];
    if (!current) {
      setOpenMenuPostId(null);
      return;
    }

    try {
      const response = current.subscribedToWorkspace
        ? await deleteJson("/community/follows", {
            followed_workspace_id: post.workspace_id,
          })
        : await postJson("/community/follows", {
            followed_workspace_id: post.workspace_id,
          });

      if (!response.ok && response.status !== 409) {
        return;
      }

      const nextSubscribedState = response.status === 409 ? true : !current.subscribedToWorkspace;

      setPostStateMap((previous) => {
        const nextState = { ...previous };

        feedPosts.forEach((candidatePost) => {
          if (candidatePost.workspace_id !== post.workspace_id) {
            return;
          }

          const existing = nextState[candidatePost.id];
          if (!existing) {
            return;
          }

          nextState[candidatePost.id] = {
            ...existing,
            subscribedToWorkspace: nextSubscribedState,
          };
        });

        return nextState;
      });

      setSnackbar({
        show: true,
        message: nextSubscribedState ? "Suscripcion activada" : "Suscripcion cancelada",
        durationMs: 1500,
        canGoTop: false,
      });
    } finally {
      setOpenMenuPostId(null);
    }
  };

  const sharePost = async (post: FeedPost) => {
    const shareUrl = absolutePostUrl(post);

    try {
      if (window.navigator.share) {
        await window.navigator.share({
          title: post.author.name ? `Post de ${post.author.name}` : "Post",
          text: post.content.plain_text.slice(0, 120),
          url: shareUrl,
        });
      } else {
        await navigator.clipboard.writeText(shareUrl);
        window.open(shareUrl, "_blank", "noopener,noreferrer");
      }
    } catch {
      setSnackbar({
        show: true,
        message: "No se pudo abrir el menu de compartir",
        durationMs: 1800,
        canGoTop: false,
      });
    } finally {
      setOpenMenuPostId(null);
    }
  };

  const copyPostLink = async (post: FeedPost) => {
    const shareUrl = absolutePostUrl(post);

    try {
      await navigator.clipboard.writeText(shareUrl);
      setSnackbar({
        show: true,
        message: "Enlace copiado",
        durationMs: 1500,
        canGoTop: false,
      });
    } catch {
      setSnackbar({
        show: true,
        message: "No se pudo copiar el enlace",
        durationMs: 1800,
        canGoTop: false,
      });
    } finally {
      setOpenMenuPostId(null);
    }
  };

  const handleMuteUser = async (post: FeedPost) => {
    if (!post.author.id) {
      return;
    }

    const response = await postJson("/community/mutes", {
      target_user_id: post.author.id,
    });

    if (response.ok || response.status === 409) {
      removePostFromFeed(post.id);
    }
  };

  const handleBlockUser = async (post: FeedPost) => {
    if (!post.author.id) {
      return;
    }

    const response = await postJson("/community/blocks", {
      target_user_id: post.author.id,
    });

    if (response.ok || response.status === 409) {
      removePostFromFeed(post.id);
    }
  };

  const openReportModal = (post: FeedPost) => {
    setReportModalState({
      postId: post.id,
      category: "spam",
      reason: "",
    });
    setOpenMenuPostId(null);
  };

  const submitReport = async () => {
    if (!reportModalState || reportModalState.reason.trim().length < 6) {
      return;
    }

    setIsSubmittingReport(true);

    try {
      const response = await postJson("/community/reports", {
        post_id: reportModalState.postId,
        category: reportModalState.category,
        reason: reportModalState.reason.trim(),
      });

      if (response.ok) {
        setReportModalState(null);
        setSnackbar({
          show: true,
          message: "Reporte enviado",
          durationMs: 1400,
          canGoTop: false,
        });
      }
    } finally {
      setIsSubmittingReport(false);
    }
  };

  const openRepostComposer = (post: FeedPost) => {
    setRepostComposerPost(post);
    setIsComposerOpen(true);
  };

  const handlePublishFromComposer = ({ postId }: { postId: string | null }) => {
    const sourcePost = repostComposerPost;
    const wasScrolledDown = window.scrollY > 100;

    const complete = async () => {
      if (sourcePost) {
        try {
          const response = await postJson("/community/reposts", {
            post_id: sourcePost.id,
          });

          if (!response.ok && response.status !== 409) {
            throw new Error("repost failed");
          }

          updatePostState(sourcePost.id, (state) => ({
            ...state,
            repostedByMe: true,
            repostsCount: state.repostedByMe ? state.repostsCount : state.repostsCount + 1,
          }));

          syncFeedPostMetrics(sourcePost.id, (post) => ({
            ...post,
            metrics: {
              ...post.metrics,
              reposted_by_me: true,
              reposts_count: post.metrics.reposted_by_me ? post.metrics.reposts_count : post.metrics.reposts_count + 1,
            },
          }));
        } catch {
          // If repost event fails, keep existing state from backend on next reload.
        }
      }

      setIsComposerOpen(false);
      setRepostComposerPost(null);

      const scrollY = window.scrollY;

      router.reload({
        only: ["posts", "workspace_id", "feed"],
        onSuccess: () => {
          window.scrollTo({
            top: scrollY,
            behavior: "auto",
          });
        },
      });

      if (wasScrolledDown && postId) {
        setSnackbar({
          show: true,
          message: "Tu post se ha publicado",
          durationMs: 2800,
          canGoTop: true,
        });
      }
    };

    void complete();
  };

  return (
    <UserHomeLayout
      onCreateClick={() => {
        setRepostComposerPost(null);
        setIsComposerOpen(true);
      }}
    >
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
            repostedByMe: false,
            repostsCount: 0,
            bookmarkedByMe: false,
            subscribedToWorkspace: false,
          };

          const hasCarousel = post.media.length > 1;
          const shouldCollapseText = post.content.plain_text.length > 460 || post.content.blocks.length > 5;
          const isExpanded = expandedPostMap[post.id] ?? false;
          const currentMediaIndex = mediaIndexByPostMap[post.id] ?? 0;
          const currentMedia = post.media[currentMediaIndex] ?? null;
          const nextMedia = currentMediaIndex < post.media.length - 1 ? post.media[currentMediaIndex + 1] : null;

          return (
            <article key={post.id} id={`post-${post.id}`} className="border-b-2 border-zinc-300/90 bg-[#F7F4ED] py-4 md:py-5">
              <div className="mx-auto w-full max-w-170 px-1 sm:px-2 md:px-0">
                <header className="relative flex items-start justify-between gap-3" ref={openMenuPostId === post.id ? menuContainerRef : null}>
                  <div className="flex min-w-0 items-center gap-3">
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
                      <p className="truncate text-sm font-semibold text-zinc-900 md:hidden">{post.author.name ?? "Usuario"}</p>
                      <p className="text-xs text-zinc-500 md:hidden">{post.published_relative ?? "ahora"}</p>

                      <p className="hidden items-center gap-2 truncate text-sm text-zinc-600 md:flex">
                        <span className="max-w-62 truncate font-semibold text-zinc-900">{post.author.name ?? "Usuario"}</span>
                        <span className="text-zinc-400">·</span>
                        <span>{post.published_relative ?? "ahora"}</span>
                      </p>
                    </div>
                  </div>

                  <button
                    type="button"
                    onClick={() => setOpenMenuPostId((current) => (current === post.id ? null : post.id))}
                    className="inline-flex h-8 w-8 items-center justify-center rounded-full text-zinc-700 transition hover:bg-zinc-200/70"
                    aria-label="Mas opciones"
                  >
                    <MoreHorizontal className="h-5 w-5" />
                  </button>

                  {openMenuPostId === post.id ? (
                    <div className="absolute right-0 top-10 z-40 w-72 rounded-2xl bg-zinc-950 p-2 shadow-[0_16px_28px_rgba(0,0,0,0.32)]">
                      <div className="space-y-1 px-1 py-1">
                        <button
                          type="button"
                          onClick={() => handleSubscribe(post)}
                          className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-white transition hover:bg-zinc-800"
                        >
                          {postState.subscribedToWorkspace ? <UserMinus className="h-4 w-4" /> : <UserPlus className="h-4 w-4" />}
                          <span>
                            {postState.subscribedToWorkspace
                              ? `Cancelar suscripcion a ${post.author.name ?? "este perfil"}`
                              : `Suscribirse a ${post.author.name ?? "este perfil"}`}
                          </span>
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            void sharePost(post);
                          }}
                          className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-white transition hover:bg-zinc-800"
                        >
                          <Share2 className="h-4 w-4" />
                          <span>Compartir</span>
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            void copyPostLink(post);
                          }}
                          className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-white transition hover:bg-zinc-800"
                        >
                          <Copy className="h-4 w-4" />
                          <span>Copiar enlace</span>
                        </button>
                      </div>

                      <div className="my-1.5 flex justify-center">
                        <span className="h-px w-[88%] rounded-full bg-[#E6DCCB]/65" />
                      </div>

                      <div className="space-y-1 px-1 py-1">
                        <button
                          type="button"
                          onClick={() => {
                            void handleMuteUser(post);
                          }}
                          className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-zinc-800"
                        >
                          <UserMinus className="h-4 w-4" />
                          <span>Silenciar</span>
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            void handleBlockUser(post);
                          }}
                          className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-zinc-800"
                        >
                          <UserX className="h-4 w-4" />
                          <span>Bloquear</span>
                        </button>
                        <button
                          type="button"
                          onClick={() => openReportModal(post)}
                          className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-zinc-800"
                        >
                          <Flag className="h-4 w-4" />
                          <span>Reportar</span>
                        </button>
                      </div>
                    </div>
                  ) : null}
                </header>

                <div className="mt-3">
                  <div
                    className={`relative space-y-2 text-[16px] leading-relaxed text-zinc-800 [&_a]:font-medium [&_a]:text-zinc-800 [&_a]:underline [&_a]:underline-offset-3 [&_a]:decoration-zinc-700 [&_a:hover]:text-zinc-900 ${
                      shouldCollapseText && !isExpanded ? "max-h-64 overflow-hidden" : ""
                    }`}
                    onClick={handlePostContentClick}
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
                      <div className="space-y-2">
                        <div className="grid grid-cols-[2fr_1fr] gap-2.5">
                          <div
                            className="relative h-[56vw] min-h-64 max-h-[72vh] overflow-hidden rounded-2xl border border-zinc-200 bg-[#F7F4ED] md:h-128 md:max-h-128"
                            onTouchStart={(event) => {
                              touchStartByPostRef.current[post.id] = event.touches[0]?.clientX ?? 0;
                            }}
                            onTouchEnd={(event) => {
                              const startX = touchStartByPostRef.current[post.id] ?? 0;
                              const endX = event.changedTouches[0]?.clientX ?? startX;
                              const delta = endX - startX;

                              if (Math.abs(delta) < 24) {
                                return;
                              }

                              if (delta < 0) {
                                goToNextMedia(post.id);
                              } else {
                                goToPrevMedia(post.id);
                              }
                            }}
                          >
                            {currentMedia ? (
                              <img
                                src={currentMedia.url}
                                alt={`Imagen principal del post ${post.content.plain_text.slice(0, 36) || "sin texto"}`}
                                className="block h-full w-full object-cover"
                              />
                            ) : null}

                            <button
                              type="button"
                              onClick={() => goToPrevMedia(post.id)}
                              disabled={currentMediaIndex === 0}
                              className={`absolute left-1 top-1/2 hidden h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/92 text-zinc-800 shadow-[0_2px_8px_rgba(0,0,0,0.18)] ring-1 ring-black/10 transition md:inline-flex ${
                                currentMediaIndex === 0 ? "pointer-events-none opacity-0" : "hover:bg-white"
                              }`}
                              aria-label="Imagen anterior"
                            >
                              <ChevronLeft className="h-4 w-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => goToNextMedia(post.id)}
                              disabled={currentMediaIndex >= post.media.length - 1}
                              className={`absolute right-1 top-1/2 hidden h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/92 text-zinc-800 shadow-[0_2px_8px_rgba(0,0,0,0.18)] ring-1 ring-black/10 transition md:inline-flex ${
                                currentMediaIndex >= post.media.length - 1 ? "pointer-events-none opacity-0" : "hover:bg-white"
                              }`}
                              aria-label="Imagen siguiente"
                            >
                              <ChevronRight className="h-4 w-4" />
                            </button>
                          </div>

                          {nextMedia ? (
                            <div className="h-[56vw] min-h-64 max-h-[72vh] overflow-hidden rounded-2xl border border-zinc-200 bg-[#F7F4ED] md:h-128 md:max-h-128">
                              <img
                                src={nextMedia.url}
                                alt={`Vista previa de imagen ${currentMediaIndex + 2}`}
                                className="block h-full w-full object-cover"
                              />
                            </div>
                          ) : (
                            <div className="h-[56vw] min-h-64 max-h-[72vh] rounded-2xl border border-[#E6DCCB] bg-[#F7F4ED]/35 md:h-128 md:max-h-128" />
                          )}
                        </div>

                        <div className="flex items-center justify-center gap-1.5 md:hidden">
                          {post.media.map((mediaItem, dotIndex) => (
                            <button
                              key={mediaItem.id}
                              type="button"
                              onClick={() => setMediaIndex(post.id, dotIndex)}
                              className={`h-1.5 w-1.5 rounded-full transition ${
                                dotIndex === currentMediaIndex ? "bg-zinc-700" : "bg-zinc-400/65"
                              }`}
                              aria-label={`Ir a imagen ${dotIndex + 1}`}
                            />
                          ))}
                        </div>
                      </div>
                    ) : (
                      <div className="h-[56vw] min-h-64 max-h-[72vh] overflow-hidden rounded-2xl border border-zinc-200 bg-[#F7F4ED] md:h-168 md:max-h-168">
                        <img
                          src={post.media[0]?.url}
                          alt={`Imagen del post ${post.content.plain_text.slice(0, 36) || "sin texto"}`}
                          className="block h-full w-full object-cover"
                        />
                      </div>
                    )}
                  </div>
                ) : null}

                <footer className="mt-3 grid grid-cols-[1fr_auto] items-center gap-3 text-sm text-zinc-600">
                  <div className="flex items-center gap-4">
                    <button
                      type="button"
                      onClick={() => setCommentsPanelPostId(post.id)}
                      className="text-zinc-600 transition hover:text-zinc-900"
                    >
                      Responder
                    </button>
                    <button
                      type="button"
                      onClick={() => openRepostComposer(post)}
                      className={`inline-flex items-center gap-1 transition ${
                        postState.repostedByMe ? "text-zinc-900" : "text-zinc-600 hover:text-zinc-900"
                      }`}
                    >
                      <span>Repostear</span>
                      <span>{postState.repostsCount}</span>
                    </button>
                  </div>

                  <button
                    type="button"
                    onClick={() => {
                      void handleToggleBookmark(post.id);
                    }}
                      className={`inline-flex items-center rounded-full p-1 transition ${
                      postState.bookmarkedByMe ? "bg-zinc-900 text-white" : "text-zinc-600 hover:bg-zinc-200 hover:text-zinc-900"
                    }`}
                    aria-label="Añadir a marcador"
                  >
                    <Bookmark className="h-5 w-5" fill={postState.bookmarkedByMe ? "currentColor" : "none"} />
                  </button>
                </footer>
              </div>
            </article>
          );
        })}

        <div ref={loadMoreRef} className="h-8 w-full" aria-hidden="true" />

        {isLoadingMore ? <div className="px-4 pb-6 text-center text-sm text-zinc-500 md:px-0">Cargando mas posts...</div> : null}
      </section>

      <CreateNoteModal
        isOpen={isComposerOpen}
        workspaceId={workspace_id}
        initialQuote={
          repostComposerPost
            ? {
                postId: repostComposerPost.id,
                authorName: repostComposerPost.author.name ?? "Usuario",
                text: repostComposerPost.content.plain_text.slice(0, 160),
                postUrl: absolutePostUrl(repostComposerPost),
              }
            : null
        }
        onClose={() => {
          setIsComposerOpen(false);
          setRepostComposerPost(null);
        }}
        onPublished={handlePublishFromComposer}
      />

      <PostCommentsPanel
        isOpen={commentsPanelPost !== null}
        post={
          commentsPanelPost
            ? {
                id: commentsPanelPost.id,
                author: commentsPanelPost.author,
                published_relative: commentsPanelPost.published_relative,
              }
            : null
        }
        onClose={() => setCommentsPanelPostId(null)}
        onNotify={(message, durationMs = 1400) => {
          setSnackbar({
            show: true,
            message,
            durationMs,
            canGoTop: false,
          });
        }}
      />

      {reportModalState ? (
        <div className="fixed inset-0 z-80 flex items-center justify-center bg-black/50 px-4" role="dialog" aria-modal="true">
          <div className="w-full max-w-md rounded-2xl bg-[#111111] p-4 text-white shadow-[0_18px_40px_rgba(0,0,0,0.35)]">
            <div className="mb-3 flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-[#E6DCCB]" />
              <p className="text-base font-semibold">Reportar publicacion</p>
            </div>

            <label className="text-xs text-zinc-300">Categoria</label>
            <select
              value={reportModalState.category}
              onChange={(event) => {
                setReportModalState((current) =>
                  current
                    ? {
                        ...current,
                        category: event.target.value,
                      }
                    : null
                );
              }}
              className="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white outline-none"
            >
              <option value="spam">Spam</option>
              <option value="abuso">Abuso</option>
              <option value="contenido">Contenido sensible</option>
              <option value="otro">Otro</option>
            </select>

            <label className="mt-3 block text-xs text-zinc-300">Motivo</label>
            <textarea
              value={reportModalState.reason}
              onChange={(event) => {
                setReportModalState((current) =>
                  current
                    ? {
                        ...current,
                        reason: event.target.value,
                      }
                    : null
                );
              }}
              rows={4}
              placeholder="Cuentanos por que quieres reportar esta publicacion"
              className="mt-1 w-full resize-none rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white outline-none"
            />

            <div className="mt-4 flex justify-end gap-2">
              <button
                type="button"
                onClick={() => setReportModalState(null)}
                className="rounded-xl bg-zinc-700 px-4 py-2 text-sm text-white transition hover:bg-zinc-600"
              >
                Cancelar
              </button>
              <button
                type="button"
                disabled={isSubmittingReport || reportModalState.reason.trim().length < 6}
                onClick={() => {
                  void submitReport();
                }}
                className="rounded-xl bg-[#E6DCCB] px-4 py-2 text-sm font-semibold text-zinc-900 transition hover:bg-[#f0e6d6] disabled:cursor-not-allowed disabled:opacity-60"
              >
                Enviar reporte
              </button>
            </div>
          </div>
        </div>
      ) : null}

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
          {snackbar.canGoTop ? <ChevronUp className="h-4 w-4" /> : null}
          {snackbar.message}
        </button>
      ) : null}
    </UserHomeLayout>
  );
}
