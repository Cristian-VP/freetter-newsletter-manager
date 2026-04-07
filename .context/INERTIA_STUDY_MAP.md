# MAPA DE ESTUDIO: DOCUMENTACIÓN OFICIAL INERTIA.JS V3

## 🎯 Propósito

Este documento te indica **exactamente qué leer** de la documentación oficial de Inertia.js v3 para avanzar en cada fase de implementación. Evita leer todo; lee lo que necesitas cuando lo necesitas.

---

## 📚 FASE 1: Conceptos Fundamentales (Pre-Setup)

**⏱️ Tiempo**: 20 minutos | **Importancia**: CRÍTICA

### Lectura 1: "Who is Inertia for?"
- **URL**: https://inertiajs.com/docs/v3/introduction
- **Por qué**: Valida que Inertia.js es el approach correcto para Freetter
- **Qué buscar**:
  - Diferencia entre Inertia SPA vs tradicionales SPAs
  - Por qué no necesitas API REST separada
  - Comparación con Next.js, Nuxt, etc.
- **Tiempo**: 5 minutos
- **Checklist**: ✅ Entiendo que Inertia elimina la complejidad SPA

### Lectura 2: "How It Works"
- **URL**: https://inertiajs.com/docs/v3/how-it-works
- **Por qué**: Comprende el flujo de datos servidor → cliente → servidor
- **Qué buscar**:
  - How pages are rendered
  - How navigation works client-side
  - How form submissions work
  - The role of the server vs client
- **Tiempo**: 10 minutos
- **Sections clave**:
  ```
  Server renders initial HTML
  Client hydrates and takes over
  User clicks link → XHR request → JSON response
  Component re-renders with new props
  ```
- **Checklist**: ✅ Entiendo que no hay refresh de página completa

### Lectura 3: "The Protocol"
- **URL**: https://inertiajs.com/docs/v3/the-protocol
- **Por qué**: Comprende el formato de request/response entre cliente y servidor
- **Qué buscar**:
  - Estructura del JSON que server envía
  - Headers que cliente envía (X-Inertia, etc.)
  - Cómo el cliente detecta si es navegación normal o Inertia
- **Tiempo**: 5 minutos
- **Checklist**: ✅ Entiendo estructura de página HTTP vs Inertia

---

## 🔧 FASE 2: Setup e Instalación (Hands-On)

**⏱️ Tiempo**: 1.5 horas | **Importancia**: BLOQUEANTE

### Lectura 4: "Server-Side Setup" (Laravel)
- **URL**: https://inertiajs.com/docs/v3/installation/server-side-setup
- **Por qué**: Pasos exactos para instalar Inertia en Laravel
- **Qué buscar**:
  - `composer require inertiajs/inertia-laravel`
  - Root template Blade structure
  - Middleware registration en `bootstrap/app.php`
  - `Inertia::setRootView()` para customizar
- **Tiempo**: 20 minutos (incluye implementación)
- **Secciones a implementar**:
  ```markdown
  ## Installation
  - Install dependencies
  - Setup root template
  - Register middleware  ← CRÍTICO
  - Create your first response
  ```
- **Código que copiar**:
  ```blade
  <!-- resources/views/app.blade.php -->
  <html>
      <head>
          @vite('resources/js/app.js')
          <x-inertia::head />
      </head>
      <body>
          <x-inertia::app />
      </body>
  </html>
  ```
- **Checklist**:
  - ✅ Middleware generado y registrado
  - ✅ Root template creado
  - ✅ Server corre sin errores

### Lectura 5: "Client-Side Setup" (React)
- **URL**: https://inertiajs.com/docs/v3/installation/client-side-setup
- **Por qué**: Setup de React + Vite + Inertia
- **Qué buscar**:
  - NPM dependencies para React
  - Vite plugin configuration
  - `createInertiaApp()` setup minimal
  - Resolución automática de páginas
  - `@vitejs/plugin-react` configuration
- **Tiempo**: 25 minutos
- **Secciones críticas**:
  ```markdown
  ## Prerequisites (Framework + Vite)
  - npm install react react-dom @vitejs/plugin-react
  - Configure vite.config.js with react()

  ## Installation (Inertia specifics)
  - npm install @inertiajs/react @inertiajs/vite
  - Add inertia plugin to vite.config.js
  - Initialize createInertiaApp in app.jsx
  ```
- **Código que copiar**:
  ```javascript
  // vite.config.js
  import inertia from '@inertiajs/vite'
  import react from '@vitejs/plugin-react'

  export default defineConfig({
      plugins: [
          laravel({ input: ['resources/js/app.jsx'] }),
          react(),
          inertia(),
      ],
  })
  ```

  ```jsx
  // resources/js/app.jsx
  import { createInertiaApp } from '@inertiajs/react'
  createInertiaApp()
  ```
- **Checklist**:
  - ✅ NPM dependencies instaladas
  - ✅ Vite config updated
  - ✅ `npm run build` funciona
  - ✅ No hay errores en desarrollo

---

## 📄 FASE 3: Conceptos Core (Building Pages)

**⏱️ Tiempo**: 1 hora | **Importancia**: CRÍTICA

### Lectura 6: "Pages" (The Basics)
- **URL**: https://inertiajs.com/docs/v3/the-basics/pages
- **Por qué**: Comprende qué son las páginas en Inertia
- **Qué buscar**:
  - Qué es una página (React component)
  - Dónde se guardan (`resources/js/Pages/`)
  - Cómo reciben props
  - Page naming conventions
  - How the Vite plugin resolves pages
- **Tiempo**: 15 minutos
- **Código de ejemplo**:
  ```jsx
  // resources/js/Pages/Dashboard.jsx
  export default function Dashboard({ data }) {
      return <div>{data}</div>
  }
  ```
- **Checklist**: ✅ Sé dónde poner componentes y cómo nombrarlos

### Lectura 7: "Responses" (The Basics)
- **URL**: https://inertiajs.com/docs/v3/the-basics/responses
- **Por qué**: Cómo los controladores returnan datos a React
- **Qué buscar**:
  - `Inertia::render('Component', [data])`
  - Props que pasar
  - Lazy props vs eager props
  - Partial reloads
- **Tiempo**: 15 minutos
- **Código de ejemplo**:
  ```php
  use Inertia\Inertia;

  class PostController {
      public function show(Post $post) {
          return Inertia::render('Posts/Show', [
              'post' => $post->only('id', 'title', 'content'),
          ]);
      }
  }
  ```
- **Checklist**: ✅ Entiendo cómo pasar datos del backend al React

### Lectura 8: "Shared Data" (Data & Props)
- **URL**: https://inertiajs.com/docs/v3/data-props/shared-data
- **Por qué**: Props globales disponibles en todos los componentes
- **Qué buscar**:
  - `share()` method en middleware
  - Datos globales (auth, flash messages)
  - Cómo acceder con `usePage()`
  - Diferencia entre shared data y page-specific props
- **Tiempo**: 15 minutos
- **Código de ejemplo**:
  ```php
  // app/Http/Middleware/HandleInertiaRequests.php
  public function share(Request $request): array {
      return [
          'auth' => ['user' => $request->user()],
          'flash' => ['success' => $request->session()->get('success')],
      ];
  }
  ```

  ```jsx
  // En cualquier componente
  import { usePage } from '@inertiajs/react'

  export default function Post() {
      const { auth, flash } = usePage().props
      return <div>{auth.user.name}</div>
  }
  ```
- **Checklist**: ✅ Sé cómo definir y acceder a datos globales

---

## 🎨 FASE 4: Características Esenciales (MVP)

**⏱️ Tiempo**: 2-3 horas | **Importancia**: ALTA

### Lectura 9: "Forms" (The Basics)
- **URL**: https://inertiajs.com/docs/v3/the-basics/forms
- **Por qué**: Cómo manejar envíos de formularios en React
- **Qué buscar**:
  - `<Form>` component vs `useForm()` hook
  - Form submission
  - Validation errors handling
  - Form state management
  - `preserveState` para mantener inputs después de error
- **Tiempo**: 30 minutos
- **Dos patrones**:
  ```jsx
  // Patrón 1: useForm hook (más control)
  import { useForm } from '@inertiajs/react'

  const { data, setData, post, errors } = useForm({
      title: '',
  })

  // Patrón 2: Form component (más declarativo)
  import { Form, Input, SubmitButton } from '@inertiajs/react'

  <Form action="/posts" method="post">
      <Input name="title" />
  </Form>
  ```
- **Checklist**:
  - ✅ Entiendo `useForm()` hook
  - ✅ Sé manejar `errors` prop
  - ✅ Conozco `preserveState: true`

### Lectura 10: "Validation" (The Basics)
- **URL**: https://inertiajs.com/docs/v3/the-basics/validation
- **Por qué**: Cómo Laravel maneja errores de validación con Inertia
- **Qué buscar**:
  - Cómo Laravel redirige con errores
  - Estructura de `errors` prop
  - Errores por campo y globales
  - Back button con state preservation
- **Tiempo**: 15 minutos
- **Código important**:
  ```php
  // Laravel automáticamente redirige con errores
  public function store(Request $request) {
      $validated = $request->validate([
          'email' => 'required|email',
      ]);
      // si falla, Inertia redirige back con errors
  }
  ```

  ```jsx
  // React recibe errors automáticamente
  const { data, setData, post, errors } = useForm({ email: '' })

  return (
      <>
          <input value={data.email} onChange={...} />
          {errors.email && <span>{errors.email}</span>}
      </>
  )
  ```
- **Checklist**: ✅ Validación Laravel → Inertia → React funciona

### Lectura 11: "Links" (Navigation)
- **URL**: https://inertiajs.com/docs/v3/the-basics/navigation
- **Por qué**: Cómo navegar sin reloads de página
- **Qué buscar**:
  - `<Link>` component vs `<a>` tag
  - Diferencia entre navegación SPA vs HTTP GET
  - Cómo preservar state
  - Lazy evaluation de props
  - Preventing default behavior
- **Tiempo**: 15 minutos
- **Código de ejemplo**:
  ```jsx
  import { Link } from '@inertiajs/react'

  // Navegación normal (sin reload)
  <Link href="/posts">Posts</Link>

  // Preservar state de form
  <Link href="/posts" preserveState>Posts</Link>

  // Con método HTTP (POST, DELETE, etc.)
  <Link method="delete" href="/posts/1">Delete</Link>
  ```
- **Checklist**: ✅ `<Link>` funciona sin reloads

### Lectura 12: "Manual Visits" (Navigation)
- **URL**: https://inertiajs.com/docs/v3/the-basics/navigation
- **Por qué**: Navegar programáticamente en JavaScript
- **Qué buscar**:
  - `router.get()`, `.post()`, `.delete()`
  - Usar en event handlers
  - Guardar form antes de navegar
- **Tiempo**: 10 minutos
- **Código**:
  ```jsx
  import { router } from '@inertiajs/react'

  function deletePost(id) {
      router.delete(`/posts/${id}`, {
          onSuccess: () => console.log('deleted')
      })
  }
  ```
- **Checklist**: ✅ Sé navegar programáticamente

---

## 🏗️ FASE 5: Layouts & Architecture Patterns

**⏱️ Tiempo**: 1 hora | **Importancia**: MEDIA (post-MVP)

### Lectura 13: "Layouts" (Architecture)
- **URL**: https://inertiajs.com/docs/v3/the-basics/layout-persistence
- **Por qué**: Cómo mantener UI persistente (navbar, sidebar) sin reload
- **Qué buscar**:
  - Layout wrapper components
  - Nested layouts
  - Persistent layout state
- **Tiempo**: 20 minutos
- **Patrón clave**:
  ```jsx
  // resources/js/Layouts/AppLayout.jsx
  export default function AppLayout({ children }) {
      return (
          <div className="flex">
              <Sidebar />
              <main>{children}</main>
          </div>
      )
  }

  // resources/js/Pages/Dashboard.jsx
  import AppLayout from '@/Layouts/AppLayout'

  export default function Dashboard() {
      return (
          <AppLayout>
              <div>Dashboard content</div>
          </AppLayout>
      )
  }
  ```
- **Checklist**: ✅ Entiendo layout pattern

---

## ⚡ FASE 6: Características Avanzadas (Post-MVP)

**⏱️ Tiempo**: 1 hora | **Importancia**: MEDIA (no necesarios para MVP)

### Lectura 14: "Server-Side Rendering" (Advanced)
- **URL**: https://inertiajs.com/docs/v3/advanced/server-side-rendering
- **Por qué**: Renderizar React en servidor para mejor SEO/performance
- **Nota importante**: Automático en dev, requiere setup en producción
- **Cuándo estudiar**: Después de MVP completado
- **Tiempo**: 20 minutos (solo lectura, no implementar aún)

### Lectura 15: "Code Splitting" (Advanced)
- **URL**: https://inertiajs.com/docs/v3/advanced/code-splitting
- **Por qué**: Reducir tamaño bundle inicial
- **Contexto**: Pages lazy-load por defecto, así que ya funciona
- **Cuándo estudiar**: When bundle size becomes concern
- **Tiempo**: 15 minutos (solo lectura)

### Lectura 16: "Deferred Props" (Advanced)
- **URL**: https://inertiajs.com/docs/v3/data-props/deferred-props
- **Por qué**: Cargar datos no-críticos después del render
- **Ejemplo**: Lista de comentarios que cargas al scroll
- **Cuándo estudiar**: When optimizing performance
- **Tiempo**: 15 minutos (solo lectura)

---

## 📋 Orden de Lectura Recomendado

### Día 1: Conceptos (2 horas)
```
1. Who is Inertia for? (5 min)
2. How It Works (10 min)
3. The Protocol (5 min)
   ↓
[BREAK de 10 minutos]
   ↓
4. Server-Side Setup (20 min) [IMPLEMENTAR]
5. Client-Side Setup (25 min) [IMPLEMENTAR]
```

### Día 2: Primer Componente (3 horas)
```
1. Pages (15 min)
2. Responses (15 min)
3. Shared Data (15 min)
   ↓
[IMPLEMENTAR Pages/Welcome.jsx]
   ↓
4. Forms (30 min)
5. Validation (15 min)
6. Links (15 min)
7. Manual Visits (10 min)
```

### Día 3: Módulos + Testing (2 horas)
```
[IMPLEMENTAR componentes para módulos]
   ↓
[TESTING]
   ↓
Layouts (20 min)
```

---

## 🔍 Búsquedas Específicas (Cuando Necesites)

Si necesitas resolver un problema específico sin leer todo, usa estas búsquedas:

| Pregunta | Dónde buscar |
|----------|-----|
| "¿Cómo paso datos del backend al React?" | Lectura 7: Responses |
| "¿Cómo hago un form con validación?" | Lectura 9: Forms |
| "¿Cómo hago un link que no recarga?" | Lectura 11: Links |
| "¿Cómo accedo a auth user en componente?" | Lectura 8: Shared Data |
| "¿Cómo navego desde JavaScript?" | Lectura 12: Manual Visits |
| "¿Cómo creo un layout persistente?" | Lectura 13: Layouts |
| "¿Cómo redirijo después de form?" | Lectura 9: Forms |
| "¿Cómo hago paginación?" | Lectura 7: Responses (partial reloads) |

---

## 💡 Notas Importantes Mientras Lees

### Cambios en v3 vs v2

Si en algún punto encuentras ejemplos de v2, nota estos cambios:

- ❌ `Inertia::lazy()` fue removido → usar `Inertia::defer()` si necesitas
- ❌ `Inertia::location()` fue removido
- ✅ XHR client built-in (no necesitas Axios)
- ✅ `useHttp()` hook nuevo para requests custom
- ✅ Mejoras en SSR con Vite plugin

### React Specifics

Inertia soporta React, Vue, y Svelte. Ignora ejemplos de Vue/Svelte:

- Busca código con tags `<CodeGroup React>` o `icon="react"`
- Ignora ejemplos con `icon="vuejs"` o `icon="s"`

---

## ✅ Checklist de Lectura

Antes de empezar a implementar, marca estas:

- [ ] Lectura 1: Who is Inertia for?
- [ ] Lectura 2: How It Works
- [ ] Lectura 3: The Protocol
- [ ] Lectura 4: Server-Side Setup
- [ ] Lectura 5: Client-Side Setup
- [ ] Lectura 6: Pages
- [ ] Lectura 7: Responses
- [ ] Lectura 8: Shared Data
- [ ] Lectura 9: Forms
- [ ] Lectura 10: Validation
- [ ] Lectura 11: Links
- [ ] Lectura 12: Manual Visits
- [ ] Lectura 13: Layouts
- [ ] (Opcional) Lectura 14: SSR
- [ ] (Opcional) Lectura 15: Code Splitting
- [ ] (Opcional) Lectura 16: Deferred Props

---

**Última actualización**: 7 de abril de 2026
**Aprox. tiempo total**: 5-6 horas de lectura + implementación
