import { X } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";

type PostCommentAuthor = {
  id: string | null;
  name: string | null;
  avatar_url: string | null;
};

type PostCommentReply = {
  id: string;
  post_id: string;
  parent_id: string | null;
  content: string;
  created_at: string | null;
  created_relative: string;
  author: PostCommentAuthor;
};

type PostComment = {
  id: string;
  post_id: string;
  parent_id: string | null;
  content: string;
  created_at: string | null;
  created_relative: string;
  author: PostCommentAuthor;
  replies_count: number;
  replies: PostCommentReply[];
};

type PostHeader = {
  id: string;
  author: {
    id: string | null;
    name: string | null;
    avatar_url: string | null;
  };
  published_relative: string | null;
};

type Props = {
  isOpen: boolean;
  post: PostHeader | null;
  onClose: () => void;
  onNotify: (message: string, durationMs?: number) => void;
};

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

export function PostCommentsPanel({ isOpen, post, onClose, onNotify }: Props) {
  const [comments, setComments] = useState<PostComment[]>([]);
  const [expandedRepliesByCommentMap, setExpandedRepliesByCommentMap] = useState<Record<string, boolean>>({});
  const [replyingToCommentId, setReplyingToCommentId] = useState<string | null>(null);
  const [draft, setDraft] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [mobileSheetOffset, setMobileSheetOffset] = useState(0);
  const touchStartYRef = useRef<number | null>(null);

  const postId = post?.id ?? null;

  const commentsCount = useMemo(() => {
    return comments.reduce((total, comment) => total + 1 + comment.replies_count, 0);
  }, [comments]);

  const loadComments = async () => {
    if (!postId) {
      return;
    }

    setIsLoading(true);

    try {
      const response = await fetch(`/community/comments?post_id=${encodeURIComponent(postId)}`, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
      });

      if (!response.ok) {
        onNotify("No se pudieron cargar los comentarios", 1800);
        return;
      }

      const payload = (await response.json()) as {
        data?: {
          comments?: PostComment[];
        };
      };

      setComments(payload.data?.comments ?? []);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (!isOpen || !postId) {
      return;
    }

    void loadComments();
    const interval = window.setInterval(() => {
      void loadComments();
    }, 10000);

    return () => {
      window.clearInterval(interval);
    };
  }, [isOpen, postId]);

  useEffect(() => {
    if (!isOpen) {
      setDraft("");
      setReplyingToCommentId(null);
      setExpandedRepliesByCommentMap({});
      setMobileSheetOffset(0);
    }
  }, [isOpen]);

  if (!isOpen || !post) {
    return null;
  }

  const submitComment = async () => {
    const content = draft.trim();
    if (content.length === 0 || !postId) {
      return;
    }

    setIsSubmitting(true);

    try {
      const payload: Record<string, string> = {
        post_id: postId,
        content,
      };

      if (replyingToCommentId) {
        payload.parent_id = replyingToCommentId;
      }

      const response = await postJson("/community/comments", payload);

      if (!response.ok) {
        onNotify("No se pudo publicar el comentario", 2200);
        return;
      }

      setDraft("");
      setReplyingToCommentId(null);
      onNotify("Comentario publicado", 1300);
      await loadComments();
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-120" role="dialog" aria-modal="true">
      <button
        type="button"
        className="absolute inset-0 bg-black/45"
        onClick={onClose}
        aria-label="Cerrar"
      />

      <div
        className="absolute bottom-0 left-0 right-0 flex h-[88dvh] flex-col rounded-t-3xl bg-white shadow-[0_-12px_28px_rgba(0,0,0,0.18)] transition-transform md:bottom-auto md:left-1/2 md:right-auto md:top-1/2 md:h-[min(82vh,780px)] md:w-[min(92vw,880px)] md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-3xl md:shadow-[0_18px_46px_rgba(0,0,0,0.26)]"
        style={{ transform: mobileSheetOffset > 0 ? `translateY(${mobileSheetOffset}px)` : undefined }}
        onTouchStart={(event) => {
          touchStartYRef.current = event.touches[0]?.clientY ?? null;
        }}
        onTouchMove={(event) => {
          const startY = touchStartYRef.current;
          if (startY === null) {
            return;
          }

          const currentY = event.touches[0]?.clientY ?? startY;
          const delta = currentY - startY;

          if (delta > 0) {
            setMobileSheetOffset(Math.min(delta, 220));
          }
        }}
        onTouchEnd={() => {
          if (mobileSheetOffset > 130) {
            onClose();
            return;
          }

          setMobileSheetOffset(0);
          touchStartYRef.current = null;
        }}
      >
        <div className="flex items-center justify-between border-b border-zinc-200 px-4 py-3">
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-zinc-900">Comentarios</p>
            <p className="text-xs text-zinc-500">{commentsCount} en total</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="inline-flex h-8 w-8 items-center justify-center rounded-full text-zinc-700 transition hover:bg-zinc-100"
            aria-label="Cerrar comentarios"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <div className="border-b border-zinc-200 px-4 py-3">
          <div className="flex items-center gap-3">
            {post.author.avatar_url ? (
              <img
                src={post.author.avatar_url}
                alt={`Avatar de ${post.author.name ?? "usuario"}`}
                className="h-9 w-9 rounded-full object-cover"
              />
            ) : (
              <div className="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700">
                {initialsFromName(post.author.name)}
              </div>
            )}
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-zinc-900">{post.author.name ?? "Usuario"}</p>
              <p className="text-xs text-zinc-500">{post.published_relative ?? "ahora"}</p>
            </div>
          </div>
        </div>

        <div className="min-h-0 flex-1 overflow-y-auto px-4 py-3">
          {isLoading && comments.length === 0 ? <p className="text-sm text-zinc-500">Cargando comentarios...</p> : null}

          {!isLoading && comments.length === 0 ? <p className="text-sm text-zinc-500">Todavia no hay comentarios.</p> : null}

          <div className="space-y-3">
            {comments.map((comment) => {
              const repliesVisible = expandedRepliesByCommentMap[comment.id] ?? false;
              const canExpandReplies = comment.replies_count > 0;

              return (
                <div key={comment.id} className="rounded-2xl border border-zinc-200 bg-white p-3">
                  <div className="flex gap-2.5">
                    {comment.author.avatar_url ? (
                      <img
                        src={comment.author.avatar_url}
                        alt={`Avatar de ${comment.author.name ?? "usuario"}`}
                        className="h-8 w-8 rounded-full object-cover"
                      />
                    ) : (
                      <div className="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 text-[11px] font-semibold text-zinc-700">
                        {initialsFromName(comment.author.name)}
                      </div>
                    )}

                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <p className="truncate text-xs font-semibold text-zinc-900">{comment.author.name ?? "Usuario"}</p>
                        <span className="text-[11px] text-zinc-500">{comment.created_relative}</span>
                      </div>
                      <p className="mt-1 text-sm text-zinc-800">{comment.content}</p>
                      <button
                        type="button"
                        onClick={() => setReplyingToCommentId(comment.id)}
                        className={`mt-2 text-xs transition ${
                          replyingToCommentId === comment.id ? "font-semibold text-zinc-900" : "text-zinc-600 hover:text-zinc-900"
                        }`}
                      >
                        Responder
                      </button>

                      {canExpandReplies ? (
                        <div className="mt-2">
                          {!repliesVisible ? (
                            <button
                              type="button"
                              onClick={() => {
                                setExpandedRepliesByCommentMap((previous) => ({
                                  ...previous,
                                  [comment.id]: true,
                                }));
                              }}
                              className="text-xs text-zinc-600 hover:text-zinc-900"
                            >
                              Ver respuestas ({comment.replies_count})
                            </button>
                          ) : (
                            <div className="space-y-2 border-l border-zinc-200 pl-3">
                              {comment.replies.map((reply) => (
                                <div key={reply.id} className="rounded-xl bg-zinc-50 px-2.5 py-2">
                                  <div className="flex items-center gap-2">
                                    <p className="truncate text-xs font-semibold text-zinc-900">{reply.author.name ?? "Usuario"}</p>
                                    <span className="text-[11px] text-zinc-500">{reply.created_relative}</span>
                                  </div>
                                  <p className="mt-1 text-sm text-zinc-800">{reply.content}</p>
                                </div>
                              ))}
                            </div>
                          )}
                        </div>
                      ) : null}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        <div className="border-t border-zinc-200 bg-white px-4 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] pt-3 md:pb-3">
          {replyingToCommentId ? (
            <p className="mb-2 text-xs text-zinc-600">
              Respondiendo comentario.
              <button
                type="button"
                onClick={() => setReplyingToCommentId(null)}
                className="ml-1 font-semibold text-zinc-900"
              >
                Cancelar
              </button>
            </p>
          ) : null}

          <div className="flex items-center gap-2">
            <input
              type="text"
              value={draft}
              onChange={(event) => setDraft(event.target.value)}
              placeholder={replyingToCommentId ? "Escribe una respuesta..." : "Escribe un comentario..."}
              className="h-10 w-full rounded-full border border-zinc-200 px-3 text-sm text-zinc-900 outline-none focus:border-zinc-300"
              onKeyDown={(event) => {
                if (event.key === "Enter") {
                  event.preventDefault();
                  void submitComment();
                }
              }}
            />
            <button
              type="button"
              onClick={() => {
                void submitComment();
              }}
              disabled={isSubmitting || draft.trim().length === 0}
              className="rounded-full bg-zinc-900 px-3 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
            >
              Enviar
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
