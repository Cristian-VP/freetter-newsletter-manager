import { type PageProps } from "@/types";
import { usePage } from "@inertiajs/react";
import {
  Bold,
  Camera,
  ImagePlus,
  Italic,
  Link2,
  List,
  ListOrdered,
  Loader2,
  Quote,
  Strikethrough,
  X,
} from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import { optimizeImage } from "@/lib/image-optimizer";

interface CreateNoteModalProps {
  isOpen: boolean;
  workspaceId: string | null;
  initialQuote?: {
    postId: string;
    authorName: string;
    text: string;
    postUrl: string;
  } | null;
  onClose: () => void;
  onPublished: (payload: { postId: string | null }) => void;
}

type SelectionToolbarState = {
  isVisible: boolean;
  left: number;
  top: number;
};

type ActiveFormatsState = {
  bold: boolean;
  italic: boolean;
  strike: boolean;
  unorderedList: boolean;
  orderedList: boolean;
  quote: boolean;
  link: boolean;
};

const MAX_IMAGES = 8;
const INLINE_ALLOWED_TAGS = new Set(["A", "B", "BR", "DEL", "EM", "I", "S", "STRIKE", "STRONG", "U"]);
const EMPTY_ACTIVE_FORMATS: ActiveFormatsState = {
  bold: false,
  italic: false,
  strike: false,
  unorderedList: false,
  orderedList: false,
  quote: false,
  link: false,
};

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

function getXsrfTokenFromCookie(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

  return match ? decodeURIComponent(match[1]) : "";
}

function normalizeLink(url: string): string {
  const trimmedUrl = url.trim();
  if (trimmedUrl === "") {
    return "";
  }

  if (/^(https?:|mailto:)/i.test(trimmedUrl)) {
    return trimmedUrl;
  }

  return `https://${trimmedUrl}`;
}

function sanitizeInlineHtml(html: string): string {
  const parser = new DOMParser();
  const documentNode = parser.parseFromString(`<div>${html}</div>`, "text/html");
  const root = documentNode.body.firstElementChild as HTMLDivElement | null;

  if (!root) {
    return "";
  }

  const elements = Array.from(root.querySelectorAll("*"));
  elements.forEach((element) => {
    const tagName = element.tagName.toUpperCase();

    if (!INLINE_ALLOWED_TAGS.has(tagName)) {
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
      const normalizedHref = normalizeLink(element.getAttribute("href") ?? "");
      if (normalizedHref === "") {
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

      element.setAttribute("href", normalizedHref);
      element.setAttribute("target", "_blank");
      element.setAttribute("rel", "noopener noreferrer");
    }
  });

  return root.innerHTML.trim();
}

function extractPlainTextFromHtml(html: string): string {
  const parser = new DOMParser();
  const documentNode = parser.parseFromString(`<div>${html}</div>`, "text/html");
  const root = documentNode.body.firstElementChild as HTMLDivElement | null;

  if (!root) {
    return "";
  }

  const text = root.textContent ?? "";

  return text.replace(/\s+/g, " ").trim();
}

function escapeHtml(value: string): string {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function buildPostBlocksFromHtml(html: string): Array<Record<string, unknown>> {
  const parser = new DOMParser();
  const documentNode = parser.parseFromString(`<div>${html}</div>`, "text/html");
  const root = documentNode.body.firstElementChild as HTMLDivElement | null;

  if (!root) {
    return [];
  }

  const blocks: Array<Record<string, unknown>> = [];
  let paragraphBuffer = "";

  const flushParagraphBuffer = () => {
    const htmlValue = sanitizeInlineHtml(paragraphBuffer);
    const textValue = extractPlainTextFromHtml(paragraphBuffer);

    if (htmlValue.length === 0 && textValue.length === 0) {
      paragraphBuffer = "";
      return;
    }

    blocks.push({
      type: "paragraph",
      data: {
        text: textValue,
        html: htmlValue,
      },
    });

    paragraphBuffer = "";
  };

  Array.from(root.childNodes).forEach((node) => {
    if (node.nodeType === Node.TEXT_NODE) {
      const value = node.textContent ?? "";
      if (value.trim().length === 0) {
        return;
      }

      paragraphBuffer += escapeHtml(value);
      return;
    }

    if (!(node instanceof HTMLElement)) {
      return;
    }

    const tagName = node.tagName.toUpperCase();

    if (tagName === "UL" || tagName === "OL") {
      flushParagraphBuffer();

      const items = Array.from(node.querySelectorAll(":scope > li"))
        .map((item) => sanitizeInlineHtml(item.innerHTML))
        .filter((item) => item.length > 0);

      if (items.length > 0) {
        blocks.push({
          type: "list",
          data: {
            style: tagName === "OL" ? "ordered" : "unordered",
            items,
          },
        });
      }

      return;
    }

    if (tagName === "BLOCKQUOTE") {
      flushParagraphBuffer();

      const htmlValue = sanitizeInlineHtml(node.innerHTML);
      const textValue = extractPlainTextFromHtml(node.innerHTML);

      if (htmlValue.length === 0 && textValue.length === 0) {
        return;
      }

      blocks.push({
        type: "quote",
        data: {
          text: textValue,
          html: htmlValue,
        },
      });

      return;
    }

    if (tagName === "DIV" || tagName === "P") {
      const nestedQuote = node.querySelector(":scope > blockquote");
      if (nestedQuote) {
        flushParagraphBuffer();

        const htmlValue = sanitizeInlineHtml(nestedQuote.innerHTML);
        const textValue = extractPlainTextFromHtml(nestedQuote.innerHTML);

        if (htmlValue.length === 0 && textValue.length === 0) {
          return;
        }

        blocks.push({
          type: "quote",
          data: {
            text: textValue,
            html: htmlValue,
          },
        });

        return;
      }

      flushParagraphBuffer();

      const htmlValue = sanitizeInlineHtml(node.innerHTML);
      const textValue = extractPlainTextFromHtml(node.innerHTML);

      if (htmlValue.length === 0 && textValue.length === 0) {
        return;
      }

      blocks.push({
        type: "paragraph",
        data: {
          text: textValue,
          html: htmlValue,
        },
      });

      return;
    }

    paragraphBuffer += node.outerHTML;
  });

  flushParagraphBuffer();

  return blocks;
}

export function CreateNoteModal({ isOpen, workspaceId, initialQuote = null, onClose, onPublished }: CreateNoteModalProps) {
  const { auth } = usePage<PageProps>().props;
  const editorRef = useRef<HTMLDivElement | null>(null);
  const selectionToolbarRef = useRef<HTMLDivElement | null>(null);
  const linkComposerRef = useRef<HTMLDivElement | null>(null);
  const linkInputRef = useRef<HTMLInputElement | null>(null);
  const savedSelectionRangeRef = useRef<Range | null>(null);
  const galleryInputRef = useRef<HTMLInputElement | null>(null);
  const cameraInputRef = useRef<HTMLInputElement | null>(null);
  const carouselRef = useRef<HTMLDivElement | null>(null);
  const [editorHtml, setEditorHtml] = useState("");
  const [images, setImages] = useState<File[]>([]);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [activeImageIndex, setActiveImageIndex] = useState(0);
  const [selectionToolbar, setSelectionToolbar] = useState<SelectionToolbarState>({
    isVisible: false,
    left: 0,
    top: 0,
  });
  const [activeFormats, setActiveFormats] = useState<ActiveFormatsState>(EMPTY_ACTIVE_FORMATS);
  const [isLinkComposerOpen, setIsLinkComposerOpen] = useState(false);
  const [linkValue, setLinkValue] = useState("");

  const editorPlainText = useMemo(() => {
    return extractPlainTextFromHtml(editorHtml);
  }, [editorHtml]);

  const isEditorEmpty = editorPlainText.length === 0;

  const canPublish = useMemo(() => {
    return editorPlainText.length > 0 && !isSubmitting;
  }, [editorPlainText, isSubmitting]);

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

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    const findClosestTag = (node: Node | null, tagName: string, root: HTMLElement): HTMLElement | null => {
      let currentNode: Node | null = node;

      while (currentNode) {
        if (currentNode instanceof HTMLElement && currentNode.tagName.toUpperCase() === tagName) {
          return currentNode;
        }

        if (currentNode === root) {
          break;
        }

        currentNode = currentNode.parentNode;
      }

      return null;
    };

    const syncActiveFormats = (selection: Selection, editorElement: HTMLDivElement) => {
      const range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
      const anchorNode = range?.commonAncestorContainer ?? selection.anchorNode;
      const quoteTag = findClosestTag(anchorNode, "BLOCKQUOTE", editorElement);
      const linkTag = findClosestTag(anchorNode, "A", editorElement);
      const formatBlockValue = String(document.queryCommandValue("formatBlock") ?? "")
        .toLowerCase()
        .replace(/[<>]/g, "");

      setActiveFormats({
        bold: document.queryCommandState("bold"),
        italic: document.queryCommandState("italic"),
        strike: document.queryCommandState("strikeThrough"),
        unorderedList: document.queryCommandState("insertUnorderedList"),
        orderedList: document.queryCommandState("insertOrderedList"),
        quote: quoteTag !== null || formatBlockValue === "blockquote",
        link: linkTag !== null,
      });
    };

    const handleSelectionChange = () => {
      const editorElement = editorRef.current;
      if (!editorElement) {
        return;
      }

      const selection = window.getSelection();
      if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
        setSelectionToolbar((previous) => ({ ...previous, isVisible: false }));
        setActiveFormats(EMPTY_ACTIVE_FORMATS);
        return;
      }

      const range = selection.getRangeAt(0);
      if (!editorElement.contains(range.commonAncestorContainer)) {
        setSelectionToolbar((previous) => ({ ...previous, isVisible: false }));
        setActiveFormats(EMPTY_ACTIVE_FORMATS);
        return;
      }

      savedSelectionRangeRef.current = range.cloneRange();
      syncActiveFormats(selection, editorElement);

      const selectionRect = range.getClientRects()[0] ?? range.getBoundingClientRect();
      const editorRect = editorElement.getBoundingClientRect();
      const hasRect = selectionRect.width > 0 || selectionRect.height > 0;
      const rect = hasRect
        ? selectionRect
        : {
            left: editorRect.left + 24,
            width: 0,
            top: editorRect.top + 12,
            height: 0,
          };
      const toolbarWidth = 278;
      const left = Math.min(window.innerWidth - toolbarWidth - 12, Math.max(12, rect.left + rect.width / 2 - toolbarWidth / 2));
      const top = Math.max(12, rect.top - 58);

      setSelectionToolbar({
        isVisible: true,
        left,
        top,
      });
    };

    const handlePointerDown = (event: MouseEvent) => {
      const editorElement = editorRef.current;
      const toolbarElement = selectionToolbarRef.current;
      const linkComposerElement = linkComposerRef.current;
      const target = event.target as Node | null;

      if (!target) {
        return;
      }

      if (editorElement?.contains(target) || toolbarElement?.contains(target) || linkComposerElement?.contains(target)) {
        return;
      }

      setSelectionToolbar((previous) => ({ ...previous, isVisible: false }));
      setActiveFormats(EMPTY_ACTIVE_FORMATS);
      setIsLinkComposerOpen(false);
    };

    document.addEventListener("selectionchange", handleSelectionChange);
    document.addEventListener("mousedown", handlePointerDown);

    return () => {
      document.removeEventListener("selectionchange", handleSelectionChange);
      document.removeEventListener("mousedown", handlePointerDown);
    };
  }, [isOpen]);

  const syncEditorHtml = () => {
    const editorElement = editorRef.current;
    if (!editorElement) {
      setEditorHtml("");
      return;
    }

    const currentHtml = editorElement.innerHTML.trim();

    if (currentHtml === "<br>" || currentHtml === "<div><br></div>" || currentHtml === "&nbsp;") {
      editorElement.innerHTML = "";
      setEditorHtml("");
      return;
    }

    setEditorHtml(editorElement.innerHTML);
  };

  const applyEditorCommand = (command: string, value?: string) => {
    if (!editorRef.current) {
      return;
    }

    editorRef.current.focus();
    document.execCommand(command, false, value);
    syncEditorHtml();

    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) {
      setActiveFormats(EMPTY_ACTIVE_FORMATS);
      return;
    }

    const editorElement = editorRef.current;
    if (!editorElement || !editorElement.contains(selection.getRangeAt(0).commonAncestorContainer)) {
      setActiveFormats(EMPTY_ACTIVE_FORMATS);
      return;
    }

    document.dispatchEvent(new Event("selectionchange"));
  };

  const openLinkComposer = () => {
    const editorElement = editorRef.current;
    if (!editorElement) {
      return;
    }

    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
      return;
    }

    const range = selection.getRangeAt(0);
    if (!editorElement.contains(range.commonAncestorContainer)) {
      return;
    }

    savedSelectionRangeRef.current = range.cloneRange();
    setLinkValue("");
    setIsLinkComposerOpen(true);
  };

  const restoreSelection = (): boolean => {
    const range = savedSelectionRangeRef.current;
    if (!range) {
      return false;
    }

    const selection = window.getSelection();
    if (!selection) {
      return false;
    }

    selection.removeAllRanges();
    selection.addRange(range);

    return true;
  };

  const closeLinkComposer = () => {
    setIsLinkComposerOpen(false);
    setLinkValue("");
    savedSelectionRangeRef.current = null;
  };

  const handleCreateLink = () => {
    const normalizedUrl = normalizeLink(linkValue);
    if (normalizedUrl === "") {
      closeLinkComposer();
      return;
    }

    if (!restoreSelection()) {
      closeLinkComposer();
      return;
    }

    const editorElement = editorRef.current;
    if (!editorElement) {
      closeLinkComposer();
      return;
    }

    document.execCommand("createLink", false, normalizedUrl);
    syncEditorHtml();
    setActiveFormats((previous) => ({ ...previous, link: true }));
    closeLinkComposer();
  };

  const handleInsertQuote = () => {
    applyEditorCommand("formatBlock", "blockquote");
  };

  const handleAddFiles = async (fileList: FileList | null) => {
    if (!fileList || fileList.length === 0) {
      return;
    }

    const incoming = Array.from(fileList).filter((file) => file.type.startsWith("image/"));
    if (incoming.length === 0) {
      return;
    }

    // Optimizar las imágenes (máximo 2MB y resolución Full HD 1920px para mantener buena calidad en posts)
    const optimizedIncoming: File[] = [];
    for (const file of incoming) {
      const optimized = await optimizeImage(file, 2, 1920);
      if (optimized) {
        optimizedIncoming.push(optimized);
      }
    }

    setImages((previous) => {
      const next = [...previous, ...optimizedIncoming].slice(0, MAX_IMAGES);

      if (previous.length + optimizedIncoming.length > MAX_IMAGES) {
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

  const resetComposer = () => {
    if (editorRef.current) {
      editorRef.current.innerHTML = "";
    }

    setEditorHtml("");
    setImages([]);
    setActiveImageIndex(0);
    setErrorMessage(null);
    setSelectionToolbar({
      isVisible: false,
      left: 0,
      top: 0,
    });
    setActiveFormats(EMPTY_ACTIVE_FORMATS);
    closeLinkComposer();
  };

  const handleClose = () => {
    if (isSubmitting) {
      return;
    }

    resetComposer();
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

    const rawHtml = editorRef.current?.innerHTML ?? "";
    const blocks = buildPostBlocksFromHtml(rawHtml);
    const quoteBlocks =
      initialQuote && initialQuote.text.trim().length > 0
        ? [
            {
              type: "quote",
              data: {
                text: `${initialQuote.authorName}: ${initialQuote.text} ${initialQuote.postUrl}`,
                html: `<a href="${escapeHtml(initialQuote.postUrl)}">Post de ${escapeHtml(initialQuote.authorName)}</a><br>${escapeHtml(initialQuote.text)}`,
              },
            },
          ]
        : [];
    const allBlocks = [...blocks, ...quoteBlocks];
    const plainText = editorPlainText;

    if (plainText.length === 0 || allBlocks.length === 0) {
      setErrorMessage("Escribe contenido antes de publicar.");
      return;
    }

    const excerpt = plainText.slice(0, 220);
    const title = plainText.slice(0, 90) || "Nuevo post";

    const formData = new FormData();
    formData.append("title", title);
    formData.append("type", "note");
    formData.append("excerpt", excerpt);
    formData.append("publish_now", "1");
    formData.append(
      "content",
      JSON.stringify({
        blocks: allBlocks,
      })
    );

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

      const payload = (await response.json()) as {
        data?: {
          id?: string;
        };
      };

      resetComposer();
      onPublished({ postId: payload.data?.id ?? null });
    } catch {
      setErrorMessage("No se pudo conectar con el servidor para publicar el post.");
    } finally {
      setIsSubmitting(false);
    }
  };

  useEffect(() => {
    if (!isLinkComposerOpen) {
      return;
    }

    const timeout = window.setTimeout(() => {
      linkInputRef.current?.focus();
    }, 0);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [isLinkComposerOpen]);

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
      <section className="flex h-full w-full max-w-full flex-col overflow-hidden bg-[#F7F4ED] md:h-auto md:max-h-[92vh] md:max-w-185 md:rounded-3xl md:bg-white md:shadow-[0_30px_90px_rgba(15,23,42,0.25)]">
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
              <img src={auth.user!.avatar} alt={auth.user?.name} className="h-11 w-11 rounded-full object-cover" />
            ) : (
              <div className="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-700 text-lg font-semibold text-white">
                {initialsFromName(auth.user?.name)}
              </div>
            )}

            <div className="w-full min-w-0">
              <div className="relative">
                {isEditorEmpty ? (
                  <p className="pointer-events-none absolute left-0 top-0 text-[30px] leading-tight tracking-tight text-zinc-400 md:text-[20px]">
                    Escribe algo...
                  </p>
                ) : null}

                <div
                  ref={editorRef}
                  contentEditable
                  suppressContentEditableWarning
                  role="textbox"
                  aria-label="Editor de contenido"
                  onInput={syncEditorHtml}
                  onPaste={(event) => {
                    event.preventDefault();
                    const pastedText = event.clipboardData.getData("text/plain");
                    document.execCommand("insertText", false, pastedText);
                    syncEditorHtml();
                  }}
                  className="max-h-[42vh] min-h-24 w-full max-w-full overflow-y-auto wrap-anywhere bg-transparent text-[30px] leading-tight tracking-tight text-zinc-900 outline-none [&_a]:underline [&_a]:underline-offset-3 [&_blockquote]:my-2 [&_blockquote]:border-l-3 [&_blockquote]:border-zinc-300 [&_blockquote]:pl-3 [&_blockquote]:text-zinc-700 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6 md:min-h-26 md:text-[20px]"
                />

                {selectionToolbar.isVisible ? (
                  <div
                    ref={selectionToolbarRef}
                    className="fixed z-60 flex items-center gap-0.5 rounded-xl bg-zinc-900/96 p-1 text-white shadow-[0_10px_25px_rgba(0,0,0,0.28)]"
                    style={{
                      left: `${selectionToolbar.left}px`,
                      top: `${selectionToolbar.top}px`,
                    }}
                  >
                    <button
                      type="button"
                      onMouseDown={(event) => event.preventDefault()}
                      onClick={() => applyEditorCommand("bold")}
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg transition ${
                        activeFormats.bold ? "bg-white/20 text-white" : "text-white/90 hover:bg-white/12"
                      }`}
                      aria-label="Negrita"
                    >
                      <Bold className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onMouseDown={(event) => event.preventDefault()}
                      onClick={() => applyEditorCommand("italic")}
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg transition ${
                        activeFormats.italic ? "bg-white/20 text-white" : "text-white/90 hover:bg-white/12"
                      }`}
                      aria-label="Cursiva"
                    >
                      <Italic className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onMouseDown={(event) => event.preventDefault()}
                      onClick={() => applyEditorCommand("strikeThrough")}
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg transition ${
                        activeFormats.strike ? "bg-white/20 text-white" : "text-white/90 hover:bg-white/12"
                      }`}
                      aria-label="Tachado"
                    >
                      <Strikethrough className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onMouseDown={(event) => event.preventDefault()}
                      onClick={openLinkComposer}
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg transition ${
                        activeFormats.link ? "bg-white/20 text-white" : "text-white/90 hover:bg-white/12"
                      }`}
                      aria-label="Insertar enlace"
                    >
                      <Link2 className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onMouseDown={(event) => event.preventDefault()}
                      onClick={() => applyEditorCommand("insertUnorderedList")}
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg transition ${
                        activeFormats.unorderedList ? "bg-white/20 text-white" : "text-white/90 hover:bg-white/12"
                      }`}
                      aria-label="Lista con viñetas"
                    >
                      <List className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onMouseDown={(event) => event.preventDefault()}
                      onClick={() => applyEditorCommand("insertOrderedList")}
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg transition ${
                        activeFormats.orderedList ? "bg-white/20 text-white" : "text-white/90 hover:bg-white/12"
                      }`}
                      aria-label="Lista numerada"
                    >
                      <ListOrdered className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onMouseDown={(event) => event.preventDefault()}
                      onClick={handleInsertQuote}
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg transition ${
                        activeFormats.quote ? "bg-white/20 text-white" : "text-white/90 hover:bg-white/12"
                      }`}
                      aria-label="Insertar cita"
                    >
                      <Quote className="h-4 w-4" />
                    </button>
                  </div>
                ) : null}

                {isLinkComposerOpen ? (
                  <div
                    ref={linkComposerRef}
                    className="fixed z-70 w-76 max-w-[calc(100vw-24px)] rounded-xl border border-zinc-700 bg-zinc-900/98 p-2.5 text-white shadow-[0_14px_30px_rgba(0,0,0,0.35)]"
                    style={{
                      left: `${selectionToolbar.left}px`,
                      top: `${selectionToolbar.top + 46}px`,
                    }}
                  >
                    <p className="mb-2 text-xs font-medium text-zinc-300">Añadir enlace</p>
                    <input
                      ref={linkInputRef}
                      type="url"
                      value={linkValue}
                      onChange={(event) => setLinkValue(event.target.value)}
                      onKeyDown={(event) => {
                        if (event.key === "Enter") {
                          event.preventDefault();
                          handleCreateLink();
                        }

                        if (event.key === "Escape") {
                          event.preventDefault();
                          closeLinkComposer();
                        }
                      }}
                      placeholder="https://ejemplo.com"
                      className="h-9 w-full rounded-lg border border-zinc-600 bg-zinc-800 px-3 text-sm text-white outline-none ring-zinc-400 placeholder:text-zinc-400 focus:ring-2"
                    />
                    <div className="mt-2 flex items-center justify-end gap-2">
                      <button
                        type="button"
                        onClick={closeLinkComposer}
                        className="inline-flex h-8 items-center rounded-lg px-2.5 text-xs font-medium text-zinc-300 transition hover:bg-white/8"
                      >
                        Cancelar
                      </button>
                      <button
                        type="button"
                        onClick={handleCreateLink}
                        className="inline-flex h-8 items-center rounded-lg bg-white px-2.5 text-xs font-semibold text-zinc-900 transition hover:bg-zinc-100"
                      >
                        Aplicar
                      </button>
                    </div>
                  </div>
                ) : null}
              </div>

              {initialQuote ? (
                <div className="mt-3 rounded-2xl border border-zinc-300 bg-zinc-100 p-3 text-sm text-zinc-800">
                  <p className="text-xs font-semibold uppercase tracking-wide text-zinc-500">Citando</p>
                  <a
                    href={initialQuote.postUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-1 block line-clamp-1 font-semibold text-zinc-900 underline underline-offset-2"
                  >
                    Post de {initialQuote.authorName}
                  </a>
                  <p className="line-clamp-2 text-zinc-700">{initialQuote.text}</p>
                </div>
              ) : null}
            </div>
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

          <div className="mt-4 flex flex-wrap items-center gap-2 text-zinc-700">
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
