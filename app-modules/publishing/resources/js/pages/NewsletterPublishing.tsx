import AuthenticatedHomeLayout from "@/layouts/authenticated-home-layout"
import { type PageProps } from "@/types"
import { Head, router, usePage } from "@inertiajs/react"
import { ArrowLeft, CalendarClock, Send } from "lucide-react"
import { useCallback, useMemo, useState } from "react"

type NewsletterBlock = {
  type?: string
  data?: {
    html?: string
    text?: string
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

type NewsletterPublishingProps = {
  workspace_id: string | null
  post: NewsletterPost | null
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
  method: "POST",
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

export default function NewsletterPublishing() {
  const page = usePage<PageProps & NewsletterPublishingProps>()
  const { auth } = page.props
  const { workspace_id: workspaceId, post } = page.props

  const [audience, setAudience] = useState<"all" | "subscribers">("subscribers")
  const [deliveryChannels, setDeliveryChannels] = useState<Array<"web" | "email">>(["web", "email"])
  const [scheduleEnabled, setScheduleEnabled] = useState<boolean>(() => !!post?.published_at)
  const [scheduleAt, setScheduleAt] = useState<string>(() => post?.published_at ? formatDateTimeForInput(post.published_at) : "")
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  const isChannelSelected = useCallback((channel: "web" | "email"): boolean => {
    return deliveryChannels.includes(channel)
  }, [deliveryChannels])

  const toggleChannel = useCallback((channel: "web" | "email") => {
    setDeliveryChannels((current) => {
      if (current.includes(channel)) {
        return current.filter((item) => item !== channel) as Array<"web" | "email">
      }

      return [...current, channel]
    })
  }, [])

  const canSubmit = useMemo(() => post !== null && deliveryChannels.length > 0, [deliveryChannels.length, post])

  const publishNow = useCallback(async () => {
    if (!post) {
      setErrorMessage("No pudimos resolver la newsletter a publicar.")
      return
    }

    if (deliveryChannels.length === 0) {
      setErrorMessage("Selecciona al menos un canal de publicación.")
      return
    }

    setIsSubmitting(true)
    setErrorMessage(null)

    const response = await sendJson(`/publishing/posts/${post.id}/publish`, "POST", {
      published_by_user_id: auth.user.id,
      audience,
      delivery_channels: deliveryChannels,
    })

    setIsSubmitting(false)

    if (!response.ok) {
      setErrorMessage("No pudimos publicar la newsletter ahora.")
      return
    }

    router.visit("/newsletters/resume?snackbar=newsletter-publicada")
  }, [audience, auth.user.id, deliveryChannels, post])

  const schedulePublication = useCallback(async () => {
    if (!post) {
      setErrorMessage("No pudimos resolver la newsletter a programar.")
      return
    }

    if (deliveryChannels.length === 0) {
      setErrorMessage("Selecciona al menos un canal de publicación.")
      return
    }

    if (!scheduleEnabled) {
      setErrorMessage("Activa la opción " + 'Programar' + " para elegir fecha y hora.")
      return
    }

    if (!scheduleAt) {
      setErrorMessage("Selecciona una fecha y hora.")
      return
    }

    setIsSubmitting(true)
    setErrorMessage(null)

    const response = await sendJson(`/publishing/posts/${post.id}/schedule`, "POST", {
      title: post.title,
      content: post.content,
      excerpt: post.excerpt,
      published_at: scheduleAt,
      audience,
      delivery_channels: deliveryChannels,
    })

    setIsSubmitting(false)

    if (!response.ok) {
      setErrorMessage("No pudimos programar la newsletter ahora.")
      return
    }

    router.visit("/newsletters/resume?snackbar=newsletter-programada")
  }, [audience, deliveryChannels, post, scheduleAt])

  return (
    <AuthenticatedHomeLayout>
      <Head title={post ? `Publicar · ${post.title}` : "Publicar newsletter"} />

      <section className="mx-auto flex w-full max-w-190 flex-col gap-5 md:max-w-205">
        <div className="rounded-4xl border border-zinc-200/70 bg-white/95 p-5 shadow-[0_16px_48px_rgba(15,23,42,0.08)] sm:p-7">
          <div className="flex items-center justify-between gap-3">
            <button
              type="button"
              onClick={() => router.visit(post ? `/newsletters/create?post=${post.id}` : "/newsletters/resume")}
              className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-950"
              aria-label="Volver al editor"
            >
              <ArrowLeft className="h-4 w-4" />
            </button>

            <div className="text-right">
              <p className="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">newsletters/publishing</p>
              <h1 className="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Publicar newsletter</h1>
            </div>
          </div>

          {post ? (
            <div className="mt-6 rounded-3xl bg-zinc-50 p-4 sm:p-5">
              <p className="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Contenido seleccionado</p>
              <h2 className="mt-2 text-xl font-semibold tracking-tight text-zinc-900">{post.title}</h2>
              <p className="mt-2 text-sm leading-6 text-zinc-600">{post.excerpt ?? "Esta newsletter se publicará desde la pantalla de publicación."}</p>
            </div>
          ) : (
            <div className="mt-6 rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-5 text-sm text-zinc-600">
              Selecciona una newsletter desde el resumen o el editor para continuar.
            </div>
          )}

          <div className="mt-6 grid gap-4 lg:grid-cols-2">
            <label className="flex flex-col gap-2 rounded-3xl border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-700">
              Audiencia
              <select
                value={audience}
                onChange={(event) => setAudience(event.target.value as "all" | "subscribers")}
                className="h-12 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 text-sm text-zinc-900 outline-none transition focus:border-zinc-900"
              >
                <option value="all">Todos</option>
                <option value="subscribers">Suscriptores</option>
              </select>
            </label>

            <div className="rounded-3xl border border-zinc-200 bg-white p-4">
              <p className="text-sm font-medium text-zinc-700">Publicación</p>
              <div className="mt-3 flex flex-col gap-3 text-sm text-zinc-700">
                <label className="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                  <input
                    type="checkbox"
                    checked={isChannelSelected("web")}
                    onChange={() => toggleChannel("web")}
                    className="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                  />
                  <span>Web app Freetter</span>
                </label>

                <label className="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                  <input
                    type="checkbox"
                    checked={isChannelSelected("email")}
                    onChange={() => toggleChannel("email")}
                    className="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                  />
                  <span>Correo electrónico</span>
                </label>
              </div>
              <p className="mt-3 text-xs leading-5 text-zinc-500">La publicación base queda disponible en la web. Si activas correo, además se enviará por Resend a los suscriptores activos.</p>
            </div>
          </div>

          <div className="mt-4 rounded-3xl border border-zinc-200 bg-white p-4">
            <label className="flex flex-col gap-2 text-sm font-medium text-zinc-700">
              Programar
              <div className="flex flex-col gap-3">
                <label className="inline-flex items-center gap-3">
                  <input
                    type="checkbox"
                    checked={scheduleEnabled}
                    onChange={() => setScheduleEnabled((s) => !s)}
                    className="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                  />
                  <span className="text-sm text-zinc-700">Activar programación</span>
                </label>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                  <input
                    type="datetime-local"
                    value={scheduleAt}
                    onChange={(event) => setScheduleAt(event.target.value)}
                    disabled={!scheduleEnabled}
                    className={`h-12 w-full rounded-2xl border px-4 text-sm text-zinc-900 outline-none transition focus:border-zinc-900 ${scheduleEnabled ? 'border-zinc-200 bg-zinc-50' : 'border-zinc-100 bg-zinc-100/60 text-zinc-400'}`}
                  />

                  <button
                    type="button"
                    onClick={() => void schedulePublication()}
                    disabled={!canSubmit || isSubmitting || !scheduleEnabled}
                    className="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-2xl border border-zinc-900 bg-zinc-900 px-5 text-sm font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    <CalendarClock className="h-4 w-4" />
                    Programar
                  </button>
                </div>
              </div>
            </label>
          </div>

          {errorMessage ? (
            <p className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{errorMessage}</p>
          ) : null}

          <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-zinc-500">Publicar ahora enviará la newsletter a la web y, si está seleccionado, también por correo.</p>
            <button
              type="button"
              onClick={() => void publishNow()}
              disabled={!canSubmit || isSubmitting}
              className="inline-flex h-12 items-center justify-center gap-2 rounded-2xl border border-transparent bg-black px-5 text-sm font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <Send className="h-4 w-4" />
              Publicar ahora
            </button>
          </div>
        </div>
      </section>
    </AuthenticatedHomeLayout>
  )
}
