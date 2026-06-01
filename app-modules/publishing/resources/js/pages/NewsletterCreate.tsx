import { Head, router, usePage } from "@inertiajs/react"
import { EditorContent, EditorContext, useEditor } from "@tiptap/react"
import { Image } from "@tiptap/extension-image"
import { Link } from "@tiptap/extension-link"
import { ImageUploadNode } from "@/components/tiptap-node/image-upload-node/image-upload-node-extension"
import StarterKit from "@tiptap/starter-kit"
import { Highlight } from "@tiptap/extension-highlight"
import { Subscript } from "@tiptap/extension-subscript"
import { Superscript } from "@tiptap/extension-superscript"
import { TaskItem, TaskList } from "@tiptap/extension-list"
import { TextAlign } from "@tiptap/extension-text-align"
import { Typography } from "@tiptap/extension-typography"
import { Underline } from "@tiptap/extension-underline"
import { useCallback, useEffect, useMemo, useState } from "react"
import { handleImageUpload, MAX_FILE_SIZE } from "@/lib/tiptap-utils"
import { createImagePastePlugin } from "@/lib/tiptap-paste-handler"
import {
  ArrowLeft,
  CalendarClock,
  ChevronDown,
  ChevronUp,
  EllipsisVertical,
  Send,
  Trash2,
} from "lucide-react"

import { Button } from "@/components/tiptap-ui-primitive/button"
import { Input } from "@/components/ui/input"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/tiptap-ui-primitive/dropdown-menu"
// title will be handled as an in-editor heading (WYSIWYG)
import { Toolbar, ToolbarGroup } from "@/components/tiptap-ui-primitive/toolbar"
import { BlockquoteButton } from "@/components/tiptap-ui/blockquote-button"
import { HeadingDropdownMenu } from "@/components/tiptap-ui/heading-dropdown-menu"
import { LinkPopover } from "@/components/tiptap-ui/link-popover"
import { ListDropdownMenu } from "@/components/tiptap-ui/list-dropdown-menu"
import { MarkButton } from "@/components/tiptap-ui/mark-button"
import { TextAlignButton } from "@/components/tiptap-ui/text-align-button"
import { UndoRedoButton } from "@/components/tiptap-ui/undo-redo-button"
import { ImageUploadButton } from "@/components/tiptap-ui/image-upload-button"
import { type PageProps } from "@/types"
import "@/components/tiptap-node/blockquote-node/blockquote-node.scss"
import "@/components/tiptap-node/heading-node/heading-node.scss"
import "@/components/tiptap-node/image-node/image-node.scss"
import "@/components/tiptap-node/list-node/list-node.scss"
import "@/components/tiptap-node/paragraph-node/paragraph-node.scss"
import "@/components/tiptap-ui/link-popover/link-popover.scss"

type NewsletterBlock = {
  type?: string
  data?: {
    html?: string
    text?: string
    items?: string[]
    style?: string
    src?: string
    alt?: string
    href?: string
  }
}

type NewsletterPost = {
  id: string
  workspace_id: string
  title: string
  status: string
  content: {
    blocks?: NewsletterBlock[]
  } | Record<string, unknown> | null
  excerpt: string | null
  published_at: string | null
  slug: string
}

type NewsletterCreateProps = {
  workspace_id: string | null
  post: NewsletterPost | null
}

type BuilderDialog = "back" | "schedule" | "delete-step-1" | "delete-step-2" | null

type ActionState = {
  title: string
  message: string
  confirmLabel: string
  cancelLabel: string
}

const EMPTY_DOC = {
  type: "doc",
  content: [{ type: "paragraph" }],
}

function blocksToEditorContent(blocks: NewsletterBlock[]): Record<string, unknown> {
  const content = blocks.flatMap((block): Array<Record<string, unknown>> => {
    const blockType = block.type ?? "paragraph"
    const data = block.data ?? {}

    if (blockType === "heading") {
      const text = data.text?.trim() ?? ""
      return text.length > 0
        ? [{ type: "heading", attrs: { level: 1 }, content: [{ type: "text", text }] }]
        : []
    }

    if (blockType === "list") {
      const items = Array.isArray(data.items) ? data.items : []
      const style = data.style === "ordered" ? "orderedList" : "bulletList"

      if (items.length === 0) {
        return []
      }

      return [
        {
          type: style,
          content: items.map((item) => ({ type: "listItem", content: [{ type: "paragraph", content: [{ type: "text", text: item }] }] })),
        },
      ]
    }

    if (blockType === "quote") {
      const text = data.text?.trim() || data.html?.trim() || ""
      return text.length > 0
        ? [{ type: "blockquote", content: [{ type: "paragraph", content: [{ type: "text", text }] }] }]
        : []
    }

    if (blockType === "image") {
      const src = data.src?.trim() ?? ""
      if (src.length === 0) {
        return []
      }

      return [
        {
          type: "image",
          attrs: {
            src,
            alt: data.alt?.trim() || null,
            title: null,
          },
        },
      ]
    }

    if (blockType === "link") {
      const text = data.text?.trim() || data.html?.trim() || ""
      const href = data.href?.trim() ?? ""

      return text.length > 0 && href.length > 0
        ? [{ type: "paragraph", content: [{ type: "text", text, marks: [{ type: "link", attrs: { href, target: "_blank", rel: "noopener noreferrer" } }] }] }]
        : []
    }

    const text = data.text?.trim() || data.html?.trim() || ""
    return text.length > 0
      ? [{ type: "paragraph", content: [{ type: "text", text }] }]
      : []
  })

  return {
    type: "doc",
    content: content.length > 0 ? content : EMPTY_DOC.content,
  }
}

function buildEditorContent(content: NewsletterPost["content"]): any {
  if (!content || typeof content !== "object") {
    return EMPTY_DOC
  }

  if ((content as { type?: string }).type === "doc" && Array.isArray((content as { content?: unknown[] }).content)) {
    return content
  }

  const blocks = (content as { blocks?: NewsletterBlock[] }).blocks
  if (Array.isArray(blocks) && blocks.length > 0) {
    return blocksToEditorContent(blocks)
  }

  return EMPTY_DOC
}

function formatDateTimeForInput(value?: string | null): string {
  const date = value ? new Date(value) : new Date(Date.now() + 60 * 60 * 1000)
  const offset = date.getTimezoneOffset()
  const localDate = new Date(date.getTime() - offset * 60 * 1000)

  return localDate.toISOString().slice(0, 16)
}

function getCsrfToken(): string {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")

  return token ?? ""
}

function getXsrfTokenFromCookie(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/)

  return match ? decodeURIComponent(match[1]) : ""
}

async function sendJson(
  url: string,
  method: "POST" | "DELETE",
  body: Record<string, unknown>
): Promise<Response> {
  return fetch(url, {
    method,
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": getCsrfToken(),
      "X-XSRF-TOKEN": getXsrfTokenFromCookie(),
    },
    body: JSON.stringify(body),
    credentials: "same-origin",
  })
}

function statusLabel(status: string): string {
  if (status === "published") {
    return "Publicado"
  }

  if (status === "scheduled") {
    return "Programado"
  }

  return "Borrador"
}

function actionStateForDialog(dialog: BuilderDialog): ActionState {
  if (dialog === "schedule") {
    return {
      title: "Programar newsletter",
      message: "Elige la fecha y hora para publicar esta newsletter automáticamente.",
      confirmLabel: "Programar",
      cancelLabel: "Cancelar",
    }
  }

  if (dialog === "delete-step-2") {
    return {
      title: "Eliminar newsletter",
      message: "Esta acción eliminará la newsletter de forma definitiva. No podrás recuperarla.",
      confirmLabel: "Eliminar definitivamente",
      cancelLabel: "Cancelar",
    }
  }

  return {
    title: "Confirmar eliminación",
    message: "Primero revisa la acción. Si continúas, pasarás a la confirmación final antes de borrar el contenido.",
    confirmLabel: "Continuar",
    cancelLabel: "Cancelar",
  }
}

function NewsletterDialog({
  open,
  title,
  message,
  confirmLabel,
  cancelLabel,
  onCancel,
  onConfirm,
  children,
}: {
  open: boolean
  title: string
  message: string
  confirmLabel: string
  cancelLabel: string
  onCancel: () => void
  onConfirm: () => void
  children?: React.ReactNode
}) {
  if (!open) {
    return null
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 px-4 py-6 backdrop-blur-[1px]">
      <div className="w-full max-w-lg rounded-[28px] border border-stone-200 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.2)]">
        <h2 className="text-xl font-semibold tracking-tight text-stone-950">{title}</h2>
        <p className="mt-2 text-sm leading-6 text-stone-600">{message}</p>

        {children ? <div className="mt-5">{children}</div> : null}

        <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <Button type="button" variant="ghost" onClick={onCancel} className="sm:min-w-32">
            {cancelLabel}
          </Button>
          <Button type="button" variant="primary" onClick={onConfirm} className="sm:min-w-40">
            {confirmLabel}
          </Button>
        </div>
      </div>
    </div>
  )
}

export default function NewsletterCreate() {
  const page = usePage<PageProps & NewsletterCreateProps>()
  const { workspace_id: workspaceId, post: existingPost } = page.props

  const [title, setTitle] = useState(existingPost?.title ?? "")
  const [postId, setPostId] = useState(existingPost?.id ?? null)
  const [status, setStatus] = useState(existingPost?.status ?? "draft")
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [dialog, setDialog] = useState<BuilderDialog>(null)
  const [scheduleAt, setScheduleAt] = useState(formatDateTimeForInput(existingPost?.published_at))
  const [lastSavedSnapshot, setLastSavedSnapshot] = useState<string | null>(null)
  const [isToolbarOpen, setIsToolbarOpen] = useState(false)
  const [hasFeatureImage, setHasFeatureImage] = useState(false)
  const [snackbar, setSnackbar] = useState<{ show: boolean; message: string; durationMs: number; canGoTop: boolean }>({
    show: false,
    message: "",
    durationMs: 1400,
    canGoTop: false,
  })

  const editorContent = useMemo(() => buildEditorContent(existingPost?.content ?? null), [existingPost])

  const editor = useEditor({
    immediatelyRender: false,
    content: editorContent,
    extensions: [
      StarterKit.configure({
        horizontalRule: false,
      }),
        Link.configure({
          openOnClick: false,
          autolink: true,
          linkOnPaste: true,
          protocols: ["http", "https", "mailto"],
        }),
      Image.configure({
        allowBase64: false,
      }),
      ImageUploadNode.configure({
        accept: "image/*",
        maxSize: MAX_FILE_SIZE,
        limit: 1,
        upload: handleImageUpload,
        onError: (error: Error) => console.error("Upload failed:", error),
      }),
      TextAlign.configure({ types: ["heading", "paragraph"] }),
      TaskList,
      TaskItem.configure({ nested: true }),
      Highlight.configure({ multicolor: true }),
      Underline,
      Typography,
      Superscript,
      Subscript,
      createImagePastePlugin(handleImageUpload),
    ],
    editorProps: {
      attributes: {
        class:
          "tiptap min-h-[calc(100vh-16rem)] pb-34 text-[1.06rem] leading-8 text-zinc-900 focus:outline-none",
      },
    },
  })

  const showSnackbar = useCallback((message: string, durationMs = 1400, canGoTop = false) => {
    setSnackbar({ show: true, message, durationMs, canGoTop })
  }, [])

  const contentBlocks = useCallback(() => {
    if (!editor) {
      return EMPTY_DOC
    }

    return editor.getJSON()
  }, [editor])

  const serializeSnapshot = useCallback((): string => {
    return JSON.stringify({
      title: title.trim(),
      blocks: contentBlocks(),
    })
  }, [contentBlocks, title])

  const hasUnsavedChanges = useMemo(() => {
    if (!editor || lastSavedSnapshot === null) {
      return false
    }

    return serializeSnapshot() !== lastSavedSnapshot
  }, [editor, lastSavedSnapshot, serializeSnapshot])

  useEffect(() => {
    if (!snackbar.show) {
      return
    }

    const timeout = window.setTimeout(() => {
      setSnackbar((current) => ({ ...current, show: false }))
    }, snackbar.durationMs)

    return () => {
      window.clearTimeout(timeout)
    }
  }, [snackbar.durationMs, snackbar.show])

  useEffect(() => {
    if (!editor || lastSavedSnapshot !== null) {
      return
    }

    setLastSavedSnapshot(serializeSnapshot())
  }, [editor, lastSavedSnapshot, serializeSnapshot])

  useEffect(() => {
    if (!editor) {
      return
    }

    const syncFeatureImageState = () => {
      let foundImage = false

      editor.state.doc.descendants((node) => {
        if (node.type.name === "image") {
          foundImage = true
          return false
        }

        return !foundImage
      })

      setHasFeatureImage(foundImage)
    }

    syncFeatureImageState()

    editor.on("update", syncFeatureImageState)

    return () => {
      editor.off("update", syncFeatureImageState)
    }
  }, [editor])

  useEffect(() => {
    if (!editor) {
      return
    }

    const handleSelectionUpdate = () => {
      if (!editor.state.selection.empty) {
        setIsToolbarOpen(true)
      }
    }

    editor.on("selectionUpdate", handleSelectionUpdate)

    return () => {
      editor.off("selectionUpdate", handleSelectionUpdate)
    }
  }, [editor])

  // Ensure the first node is a heading used as the title (WYSIWYG), and sync it with `title` state
  useEffect(() => {
    if (!editor) return

    const ensureTitleNode = () => {
      const first = editor.state.doc.firstChild
      if (!first || first.type.name !== "heading") {
        // Insert heading at the start with current title (may be empty)
        editor
          .chain()
          .focus()
          .insertContentAt(0, [
            {
              type: "heading",
              attrs: { level: 1 },
              content: title ? [{ type: "text", text: title }] : [],
            },
            { type: "paragraph" },
          ])
          .run()
      }
    }

    const handleUpdate = () => {
      const first = editor.state.doc.firstChild
      if (first && first.type.name === "heading") {
        const headingText = first.textContent ?? ""
        if (headingText !== title) {
          setTitle(headingText)
        }
      }
    }

    ensureTitleNode()
    // initial sync
    handleUpdate()

    editor.on("update", handleUpdate)
    return () => {
      editor.off("update", handleUpdate)
    }
  }, [editor, title])

  const submitStore = useCallback(
    async (nextStatus: "draft" | "published" | "scheduled"): Promise<string | null> => {
      if (!workspaceId) {
        showSnackbar("No se pudo resolver el workspace activo.", 2200)
        return null
      }

      if (!editor) {
        showSnackbar("El editor todavía no está listo.", 2200)
        return null
      }

      if (title.trim().length === 0) {
        showSnackbar("Agrega un título antes de continuar.", 2200)
        return null
      }

      setIsSubmitting(true)

      const response = await sendJson(`/publishing/workspaces/${workspaceId}/posts`, "POST", {
        post_id: postId ?? undefined,
        title: title.trim(),
        type: "newsletter",
        content: contentBlocks(),
        excerpt: editor.getText({ blockSeparator: " " }).replace(/\s+/g, " ").trim().slice(0, 240),
        status: nextStatus,
        published_at: nextStatus === "scheduled" ? scheduleAt : undefined,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        publish_now: nextStatus === "published" && !postId,
      })

      setIsSubmitting(false)

      if (!response.ok) {
        showSnackbar("No se pudo guardar la newsletter.", 2200)
        return null
      }

      const payload = (await response.json()) as {
        data?: {
          id?: string
          status?: string
          published_at?: string | null
        }
      }

      if (payload.data?.id) {
        setPostId(payload.data.id)
      }

      if (payload.data?.status) {
        setStatus(payload.data.status)
      } else {
        setStatus(nextStatus)
      }

      if (payload.data?.published_at) {
        setScheduleAt(formatDateTimeForInput(payload.data.published_at))
      }

      setLastSavedSnapshot(serializeSnapshot())
      showSnackbar(nextStatus === "draft" ? "Borrador guardado" : nextStatus === "published" ? "Newsletter publicada" : "Newsletter programada")
      return payload.data?.id ?? postId
    },
    [contentBlocks, editor, postId, scheduleAt, serializeSnapshot, showSnackbar, title, workspaceId]
  )

  const openPublishingView = useCallback(async () => {
    if (!editor) {
      showSnackbar("El editor todavía no está listo.", 2200)
      return
    }

    const shouldSaveDraft = postId === null || hasUnsavedChanges
    const targetPostId = shouldSaveDraft ? await submitStore("draft") : postId

    if (!targetPostId) {
      return
    }

    router.visit(`/newsletters/publishing?post=${targetPostId}`)
  }, [editor, hasUnsavedChanges, postId, showSnackbar, submitStore])

  const submitSchedule = useCallback(async () => {
    if (!workspaceId) {
      showSnackbar("No se pudo resolver el workspace activo.", 2200)
      return
    }

    if (!editor || title.trim().length === 0) {
      showSnackbar("Agrega un título antes de programar.", 2200)
      return
    }

    if (postId) {
      setIsSubmitting(true)

      const response = await sendJson(`/publishing/posts/${postId}/schedule`, "POST", {
        title: title.trim(),
        content: contentBlocks(),
        excerpt: editor.getText({ blockSeparator: " " }).replace(/\s+/g, " ").trim().slice(0, 240),
        published_at: scheduleAt,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      })

      setIsSubmitting(false)

      if (!response.ok) {
        showSnackbar("No se pudo programar la newsletter.", 2200)
        return
      }

      setStatus("scheduled")
      setLastSavedSnapshot(serializeSnapshot())
      showSnackbar("Newsletter programada")
      setDialog(null)
      return
    }

    const saved = await submitStore("scheduled")

    if (saved) {
      setDialog(null)
    }
  }, [contentBlocks, editor, postId, scheduleAt, serializeSnapshot, showSnackbar, submitStore, title, workspaceId])

  const submitDelete = useCallback(async () => {
    if (!postId) {
      setDialog(null)
      router.visit("/newsletters/resume")
      return
    }

    setIsSubmitting(true)

    const response = await sendJson(`/publishing/posts/${postId}`, "DELETE", {})

    setIsSubmitting(false)

    if (!response.ok) {
      showSnackbar("No se pudo eliminar la newsletter.", 2200)
      return
    }

    setDialog(null)
    router.visit("/newsletters/resume?snackbar=newsletter-cancelada")
  }, [postId, showSnackbar])

  const handleBackClick = useCallback(() => {
    if (!editor) {
      router.visit("/newsletters/resume")
      return
    }

    const noMeaningfulContent = title.trim().length === 0 && editor.getText().trim().length === 0 && postId === null
    const noPendingChanges = !hasUnsavedChanges

    if (noMeaningfulContent || noPendingChanges) {
      router.visit("/newsletters/resume")
      return
    }

    setDialog("back")
  }, [editor, hasUnsavedChanges, postId, title])

  const actionMenuDisabled = isSubmitting || editor === null
  const deleteDisabled = actionMenuDisabled || !postId
  const isAlreadyPersisted = postId !== null || existingPost !== null

  const backDialog: ActionState = isAlreadyPersisted
    ? {
        title: "Guardar cambios antes de salir",
        message: "Hay cambios sin guardar. Si sales ahora, se perderán.",
        confirmLabel: "Guardar",
        cancelLabel: "Descartar",
      }
    : {
        title: "Antes de volver",
        message: "Puedes guardar un borrador antes de salir o descartar los cambios actuales.",
        confirmLabel: "Guardar borrador",
        cancelLabel: "Descartar",
      }
  const scheduleDialog = actionStateForDialog("schedule")
  const deleteDialog = actionStateForDialog(dialog === "delete-step-2" ? "delete-step-2" : "delete-step-1")

  return (
    <div className="min-h-screen bg-[#F7F4ED] text-zinc-900">
      <Head title={postId ? `${title || "Newsletter"} · Builder` : "Newsletter Builder"} />

      <header className="sticky top-0 z-40 border-b border-zinc-200/70 bg-[#F7F4ED]/95 backdrop-blur">
        <div className="mx-auto flex w-full max-w-5xl items-center justify-between px-3 py-2 sm:px-6 sm:py-3">
          <Button
            type="button"
            variant="ghost"
            onClick={handleBackClick}
            className="shrink-0"
          >
            <ArrowLeft className="h-4 w-4" />
          </Button>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button type="button" variant="ghost" disabled={actionMenuDisabled} aria-label="Abrir acciones">
                <EllipsisVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="min-w-52">
              <DropdownMenuItem onSelect={() => {
                void openPublishingView()
              }}>
                <span className="inline-flex items-center gap-2">
                  <Send className="h-4 w-4" />
                  Publicar
                </span>
              </DropdownMenuItem>
              <DropdownMenuItem onSelect={() => {
                void submitStore("draft")
              }}>
                <span className="inline-flex items-center gap-2">
                  Guardar
                </span>
              </DropdownMenuItem>
              <DropdownMenuItem onSelect={() => {
                setDialog("schedule")
              }}>
                <span className="inline-flex items-center gap-2">
                  <CalendarClock className="h-4 w-4" />
                  Programar
                </span>
              </DropdownMenuItem>
              <DropdownMenuItem
                disabled={deleteDisabled}
                onSelect={() => {
                  if (!deleteDisabled) {
                    setDialog("delete-step-1")
                  }
                }}
              >
                <span className="inline-flex items-center gap-2 text-rose-600">
                  <Trash2 className="h-4 w-4" />
                  Eliminar
                </span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <EditorContext.Provider value={{ editor }}>

      <main className="px-4 pb-30 pt-6 sm:px-6 lg:px-8">
        <div className="mx-auto flex w-full max-w-4xl flex-col gap-5">
          {!hasFeatureImage ? (
            <ImageUploadButton
              editor={editor ?? undefined}
              text="Add feature image"
              className="w-fit rounded-full border border-zinc-200 bg-white/70 px-3 py-1 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900"
            />
          ) : null}

          <div className="rounded-3xl bg-transparent">
            <div className="mx-auto w-full">
              <div className="relative">
                {/* Title placeholder overlay when heading is empty */}
                {editor && (title.trim().length === 0) ? (
                  <div
                    onClick={() => {
                      try {
                        editor.chain().focus().setTextSelection(0).run()
                      } catch (e) {
                        editor.commands.focus()
                      }
                    }}
                    className="cursor-text pointer-events-auto absolute left-0 top-0 select-none text-[2.4rem] font-semibold leading-none tracking-tight text-zinc-400"
                  >
                    Escribe el título...
                  </div>
                ) : null}
                <EditorContent editor={editor} />
              </div>
            </div>
          </div>
        </div>
      </main>

      {!isToolbarOpen ? (
        <button
          type="button"
          onClick={() => setIsToolbarOpen(true)}
          className="fixed bottom-5 right-5 z-50 inline-flex h-12 w-12 items-center justify-center rounded-full bg-zinc-900 text-white shadow-[0_10px_22px_rgba(0,0,0,0.25)]"
          aria-label="Mostrar barra de formato"
        >
          <ChevronUp className="h-5 w-5" />
        </button>
      ) : (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t border-zinc-200/80 bg-white/98 pb-[max(env(safe-area-inset-bottom),0px)] backdrop-blur-sm">
          <div className="flex items-center gap-2">
            <div className="no-scrollbar min-w-0 flex-1 overflow-x-auto px-2 py-2">
              <Toolbar variant="floating" data-plain="true" className="min-w-max gap-1">
                <ToolbarGroup>
                  <UndoRedoButton action="undo" />
                  <UndoRedoButton action="redo" />
                </ToolbarGroup>

                <ToolbarGroup>
                  <HeadingDropdownMenu modal={false} levels={[1, 2, 3]} />
                  <ListDropdownMenu modal={false} types={["bulletList", "orderedList", "taskList"]} />
                  <BlockquoteButton />
                </ToolbarGroup>

                <ToolbarGroup>
                  <MarkButton type="bold" />
                  <MarkButton type="italic" />
                  <MarkButton type="underline" />
                  <LinkPopover />
                </ToolbarGroup>

                <ToolbarGroup>
                  <TextAlignButton align="left" />
                  <TextAlignButton align="center" />
                  <TextAlignButton align="right" />
                </ToolbarGroup>
                <ToolbarGroup>
                  <ImageUploadButton text="Add" />
                </ToolbarGroup>
              </Toolbar>
            </div>

            <div className="h-7 w-px bg-zinc-200" />

            <button
              type="button"
              onClick={() => setIsToolbarOpen(false)}
              className="mr-2 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
              aria-label="Ocultar barra de formato"
            >
              <ChevronDown className="h-5 w-5" />
            </button>
          </div>
        </div>
      )}

      {snackbar.show ? (
        <button
          type="button"
          onClick={() => {
            if (snackbar.canGoTop) {
              window.scrollTo({ top: 0, behavior: "smooth" })
            }

            setSnackbar((current) => ({ ...current, show: false }))
          }}
          className="fixed bottom-20 left-1/2 z-90 inline-flex -translate-x-1/2 items-center gap-2 rounded-full bg-zinc-900 px-4 py-2 text-sm text-white shadow-[0_10px_22px_rgba(0,0,0,0.25)]"
        >
          {snackbar.canGoTop ? <ChevronUp className="h-4 w-4" /> : null}
          {snackbar.message}
        </button>
      ) : null}

      <NewsletterDialog
        open={dialog === "back"}
        title={backDialog.title}
        message={backDialog.message}
        confirmLabel={backDialog.confirmLabel}
        cancelLabel={backDialog.cancelLabel}
        onCancel={() => {
          setDialog(null)
          router.visit("/newsletters/resume")
        }}
        onConfirm={async () => {
          const saved = await submitStore("draft")

          if (saved) {
            setDialog(null)
            router.visit("/newsletters/resume")
          }
        }}
      />

      <NewsletterDialog
        open={dialog === "schedule"}
        title={scheduleDialog.title}
        message={scheduleDialog.message}
        confirmLabel={scheduleDialog.confirmLabel}
        cancelLabel={scheduleDialog.cancelLabel}
        onCancel={() => setDialog(null)}
        onConfirm={async () => {
          await submitSchedule()
        }}
      >
        <label className="flex flex-col gap-2 text-sm font-medium text-stone-700">
          Fecha y hora
          <Input
            type="datetime-local"
            value={scheduleAt}
            onChange={(event) => setScheduleAt(event.target.value)}
            className="border-stone-200 bg-stone-50"
          />
        </label>
      </NewsletterDialog>

      <NewsletterDialog
        open={dialog === "delete-step-1"}
        title={deleteDialog.title}
        message={deleteDialog.message}
        confirmLabel={deleteDialog.confirmLabel}
        cancelLabel={deleteDialog.cancelLabel}
        onCancel={() => setDialog(null)}
        onConfirm={() => setDialog("delete-step-2")}
      />

      <NewsletterDialog
        open={dialog === "delete-step-2"}
        title={actionStateForDialog("delete-step-2").title}
        message={actionStateForDialog("delete-step-2").message}
        confirmLabel={actionStateForDialog("delete-step-2").confirmLabel}
        cancelLabel={actionStateForDialog("delete-step-2").cancelLabel}
        onCancel={() => setDialog(null)}
        onConfirm={async () => {
          await submitDelete()
        }}
      />
      </EditorContext.Provider>
    </div>
  )
}
