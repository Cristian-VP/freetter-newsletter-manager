import { type PageProps } from "@/types";
import { usePage } from "@inertiajs/react";
import { Camera, ImagePlus, Loader2, X } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";

interface CreateNoteModalProps {
  isOpen: boolean;
  workspaceId: string | null;
  onClose: () => void;
  onPublished: () => void;
}

const MAX_IMAGES = 8;

function initialsFromName(name: string | null | undefined): string {
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

function sanitizeBody(body: string): string {
  return body
    .replace(/\r\n/g, "\n")
    .split("\n")
    .map((line) => line.trim())
    .filter(Boolean)
    .join("\n");
}

function getXsrfTokenFromCookie(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

  return match ? decodeURIComponent(match[1]) : "";
}

export function CreateNoteModal({ isOpen, workspaceId, onClose, onPublished }: CreateNoteModalProps) {
  const { auth } = usePage<PageProps>().props;
  const galleryInputRef = useRef<HTMLInputElement | null>(null);
  const cameraInputRef = useRef<HTMLInputElement | null>(null);
  const carouselRef = useRef<HTMLDivElement | null>(null);
  const [body, setBody] = useState("");
  const [images, setImages] = useState<File[]>([]);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [activeImageIndex, setActiveImageIndex] = useState(0);

  const canPublish = useMemo(() => {
    return sanitizeBody(body).length > 0 && !isSubmitting;
  }, [body, isSubmitting]);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    const { body } = document;
    const previousOverflow = body.style.overflow;
    body.style.overflow = "hidden";

    return () => {
      body.style.overflow = previousOverflow;
    };
  }, [isOpen]);

  const imagePreviews = useMemo(() => {
    return images.map((file) => ({
      file,
      previewUrl: URL.createObjectURL(file),
    }));
  }, [images]);

  useEffect(() => {
    return () => {
      imagePreviews.forEach((item) => {
        URL.revokeObjectURL(item.previewUrl);
      });
    };
  }, [imagePreviews]);

  const handleAddFiles = (fileList: FileList | null) => {
    if (!fileList || fileList.length === 0) {
      return;
    }

    const incoming = Array.from(fileList).filter((file) => file.type.startsWith("image/"));
    if (incoming.length === 0) {
      return;
    }

    setImages((previous) => {
      const next = [...previous, ...incoming].slice(0, MAX_IMAGES);

      if (previous.length + incoming.length > MAX_IMAGES) {
        setErrorMessage(`Solo puedes añadir hasta ${MAX_IMAGES} imágenes por post.`);
      } else {
        setErrorMessage(null);
      }

      return next;
    });
  };

  const handleRemoveImage = (index: number) => {
    setImages((previous) => previous.filter((_, itemIndex) => itemIndex !== index));
    setActiveImageIndex((previous) => {
      if (previous === 0) {
        return 0;
      }

      if (index > previous) {
        return previous;
      }

      return previous - 1;
    });
  };

  const handleClose = () => {
    if (isSubmitting) {
      return;
    }

    setBody("");
    setImages([]);
    setActiveImageIndex(0);
    setErrorMessage(null);
    onClose();
  };

  const handleSubmit = async () => {
    if (!canPublish) {
      return;
    }

    if (!workspaceId) {
      setErrorMessage("No hay un workspace activo para publicar.");
      return;
    }

    const cleanedBody = sanitizeBody(body);
    const excerpt = cleanedBody.slice(0, 220);
    const firstLine = cleanedBody.split("\n")[0] ?? "";
    const title = firstLine.slice(0, 90) || "Nuevo post";

    const formData = new FormData();
    formData.append("title", title);
    formData.append("type", "note");
    formData.append("excerpt", excerpt);
    formData.append("publish_now", "1");
    formData.append("content", JSON.stringify({
      blocks: cleanedBody.split("\n").map((line) => ({
        type: "paragraph",
        data: {
          text: line,
        },
      })),
    }));

    images.forEach((image) => {
      formData.append("media[]", image);
    });

    setIsSubmitting(true);
    setErrorMessage(null);

    try {
      const response = await fetch(`/publishing/workspaces/${workspaceId}/posts`, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "",
          "X-XSRF-TOKEN": getXsrfTokenFromCookie(),
        },
        credentials: "same-origin",
        body: formData,
      });

      if (!response.ok) {
        setErrorMessage("No se pudo publicar el post. Revisa los campos e inténtalo de nuevo.");
        return;
      }

      setBody("");
      setImages([]);
      setActiveImageIndex(0);
      onPublished();
    } catch {
      setErrorMessage("No se pudo conectar con el servidor para publicar el post.");
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isOpen) {
    return null;
  }

  const handleCarouselScroll = () => {
    const carousel = carouselRef.current;
    if (!carousel) {
      return;
    }

    const children = Array.from(carousel.children) as HTMLDivElement[];
    if (children.length === 0) {
      setActiveImageIndex(0);
      return;
    }

    const scrollLeft = carousel.scrollLeft;
    let nearestIndex = 0;
    let nearestDistance = Number.POSITIVE_INFINITY;

    children.forEach((child, index) => {
      const distance = Math.abs(child.offsetLeft - scrollLeft);
      if (distance < nearestDistance) {
        nearestDistance = distance;
        nearestIndex = index;
      }
    });

    setActiveImageIndex(nearestIndex);
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/45 p-0 md:flex md:items-center md:justify-center md:p-4">
      <section className="flex h-full w-full flex-col bg-[#F7F4ED] md:h-auto md:max-h-[92vh] md:max-w-2xl md:rounded-3xl md:bg-white md:shadow-[0_30px_90px_rgba(15,23,42,0.25)]">
        <header className="flex items-center justify-between px-4 pt-4 md:border-b md:border-zinc-200 md:px-6 md:py-4">
          <button
            type="button"
            onClick={handleClose}
            className="inline-flex h-10 w-10 items-center justify-center rounded-full text-zinc-900 transition hover:bg-zinc-100"
            aria-label="Cerrar"
          >
            <X className="h-7 w-7" />
          </button>

          <button
            type="button"
            onClick={handleSubmit}
            disabled={!canPublish}
            className="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-900 px-4 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-zinc-300 md:hidden"
          >
            {isSubmitting ? <Loader2 className="h-4 w-4 animate-spin" /> : "Post"}
          </button>

          <div className="hidden md:block" aria-hidden="true" />
        </header>

        <div className="overflow-y-auto px-4 pb-4 pt-4 md:px-6 md:pt-5">
          <div className="flex items-start gap-3">
            {auth.user?.avatar ? (
              <img src={auth.user.avatar} alt={auth.user?.name} className="h-11 w-11 rounded-full object-cover" />
            ) : (
              <div className="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-700 text-lg font-semibold text-white">
                {initialsFromName(auth.user?.name)}
              </div>
            )}

            <textarea
              value={body}
              onChange={(event) => setBody(event.target.value)}
              placeholder="Escribe algo..."
              rows={4}
              className="min-h-24 w-full resize-none bg-transparent text-2xl leading-tight tracking-tight text-zinc-900 outline-none placeholder:text-zinc-400 md:min-h-26 md:text-[18px]"
            />
          </div>

          {images.length > 0 ? (
            <div
              ref={carouselRef}
              onScroll={handleCarouselScroll}
              className="mt-4 flex snap-x snap-mandatory gap-2 overflow-x-auto pb-1"
            >
              {imagePreviews.map((item, index) => (
                <div
                  key={`${item.file.name}-${index}`}
                  className={`relative shrink-0 snap-start overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-50 ${
                    index === activeImageIndex
                      ? "basis-[76%] md:basis-[48%]"
                      : index === activeImageIndex + 1
                        ? "basis-[24%] md:basis-[30%]"
                        : "basis-[22%] md:basis-[20%]"
                  }`}
                >
                  <img src={item.previewUrl} alt={item.file.name} className="h-40 w-full object-cover md:h-52" />
                  <button
                    type="button"
                    onClick={() => handleRemoveImage(index)}
                    className="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-black/75 text-white"
                    aria-label="Eliminar imagen"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              ))}
            </div>
          ) : null}

          <div className="mt-4 flex items-center gap-2 text-zinc-700">
            <button
              type="button"
              onClick={() => galleryInputRef.current?.click()}
              className="inline-flex h-10 items-center gap-2 rounded-xl px-3 text-sm font-medium transition hover:bg-zinc-100"
            >
              <ImagePlus className="h-5 w-5" />
              <span>Galería</span>
            </button>

            <button
              type="button"
              onClick={() => cameraInputRef.current?.click()}
              className="inline-flex h-10 items-center gap-2 rounded-xl px-3 text-sm font-medium transition hover:bg-zinc-100"
            >
              <Camera className="h-5 w-5" />
              <span>Cámara</span>
            </button>

            <span className="ml-auto text-xs text-zinc-500">
              {images.length}/{MAX_IMAGES}
            </span>
          </div>

          {errorMessage ? <p className="mt-3 text-sm text-red-600">{errorMessage}</p> : null}
        </div>

        <footer className="hidden items-center justify-between border-t border-zinc-200 px-6 py-4 md:flex">
          <p className="text-xs text-zinc-500"></p>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={handleClose}
              className="inline-flex h-10 items-center rounded-xl border border-zinc-300 px-4 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100"
            >
              Cancelar
            </button>
            <button
              type="button"
              onClick={handleSubmit}
              disabled={!canPublish}
              className="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-900 px-4 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-zinc-300"
            >
              {isSubmitting ? <Loader2 className="h-4 w-4 animate-spin" /> : "Post"}
            </button>
          </div>
        </footer>

        <input
          ref={galleryInputRef}
          type="file"
          accept="image/*"
          multiple
          className="hidden"
          onChange={(event) => {
            handleAddFiles(event.target.files);
            event.currentTarget.value = "";
          }}
        />
        <input
          ref={cameraInputRef}
          type="file"
          accept="image/*"
          capture="environment"
          className="hidden"
          onChange={(event) => {
            handleAddFiles(event.target.files);
            event.currentTarget.value = "";
          }}
        />
      </section>
    </div>
  );
}
