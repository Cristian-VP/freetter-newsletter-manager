# INERTIA.JS V3 IMPLEMENTATION GUIDE FOR FREETTER

## 📋 Tabla de Contenidos

1. [Visión General](#visión-general)
2. [Estado Actual vs Destino](#estado-actual-vs-destino)
3. [Documentación Oficial a Estudiar](#documentación-oficial-a-estudiar)
4. [Arquitectura de Integración](#arquitectura-de-integración)
5. [Roadmap de Implementación](#roadmap-de-implementación)
6. [Guía de Setup Paso a Paso](#guía-de-setup-paso-a-paso)
7. [Integración con Módulos Modular](#integración-con-módulos-modular)
8. [Patrones de Desarrollo en Freetter](#patrones-de-desarrollo-en-freetter)
9. [Testing](#testing)
10. [Referencias y Recursos](#referencias-y-recursos)

---

## Visión General

### Qué es Inertia.js v3

Inertia.js v3 (lanzado 26 de marzo de 2026) es un framework que permite construir **SPAs completamente renderizadas del lado del cliente sin la complejidad típica de una SPA moderna**:

- ❌ Sin enrutamiento del lado del cliente
- ❌ Sin necesidad de API REST separado
- ❌ Sin complejidad de comunicación servidor-cliente
- ✅ Patrones familiares de servidor
- ✅ Experiencia de página única (SPA)
- ✅ Controladores + componentes React

### Ventajas para Freetter

Para un monolito modular como Freetter, Inertia.js ofrece:

1. **Simplicidad arquitectónica**: Controladores returnan datos directamente a React
2. **Modularidad mantenida**: Cada módulo puede tener sus propias páginas
3. **Type-safety**: Posibilidad de tipar props en componentes
4. **Sin API bloat**: No necesita duplicar endpoints para frontend/backend
5. **Desarrollo rápido**: Forms, navegación y validación integradas

### Stack Final para Freetter

```
Backend          Frontend         Build Tool
├─ Laravel 12    ├─ React 18+     ├─ Vite 7
├─ PHP 8.2       ├─ TailwindCSS 4 ├─ @inertiajs/vite
├─ Inertia v3    └─ @inertiajs/   ├─ laravel-vite-plugin
└─ Modular           react        └─ @vitejs/plugin-react
```

---

## Estado Actual vs Destino

### Estado Actual (7 de abril de 2026)

| Componente | Instalado | Configurado | Activo |
|-----------|-----------|-------------|--------|
| `inertiajs/inertia-laravel` | ✅ v3.0 | ❌ No | ❌ No |
| `@inertiajs/react` | ❌ No | ❌ No | ❌ No |
| `@inertiajs/vite` | ❌ No | ❌ No | ❌ No |
| Root template `app.blade.php` | ❌ No | ❌ No | ❌ No |
| Middleware Inertia | ❌ No | ❌ No | ❌ No |
| React components | ❌ No | ❌ No | ❌ No |
| Vite config (Inertia) | ⚠️ Parcial | ❌ No | ❌ No |

**Vistas actuales**: Solo `welcome.blade.php` (Blade tradicional)

### Estado Destino

| Componente | Status |
|-----------|--------|
| ✅ Dependencias npm instaladas | Ready |
| ✅ Root template Blade creado | Ready |
| ✅ Middleware Inertia registrado | Ready |
| ✅ Vite plugin configurado | Ready |
| ✅ `resources/js/app.jsx` configurado | Ready |
| ✅ Estructura `Pages/` lista | Ready |
| ✅ Primer componente funcional | Ready |
| ✅ Tests de integración | Ready |
| ✅ Documentación en `.context/` | Ready |

---

## Documentación Oficial a Estudiar

### 🎯 Fase 1: Conceptos Fundamentales

**Estudiar en este orden**:

1. **"Who is Inertia for?"** (5 min)
   - Válida que el enfoque es correcto para Freetter
   - Entiende el contraste SPA vs Inertia

2. **"How Inertia Works"** (10 min)
   - Comprende el flujo de datos servidor → cliente
   - Entiende cómo Inertia intercepta navegación

3. **"The Protocol"** (5 min)
   - Una sola llamada HTTP por página
   - Estructura de request/response Inertia

### 🔧 Fase 2: Setup e Instalación

**Estudiar estos documentos oficial**:

1. **"Server-Side Setup" — Laravel**
   - `composer require inertiajs/inertia-laravel`
   - Root template con `<x-inertia::head />` y `<x-inertia::app />`
   - Middleware `HandleInertiaRequests`
   - Ubicación: [https://inertiajs.com/docs/v3/installation/server-side-setup](https://inertiajs.com/docs/v3/installation/server-side-setup)

2. **"Client-Side Setup" — React**
   - `npm install @inertiajs/react @inertiajs/vite`
   - Configuración Vite plugin
   - `createInertiaApp()` en `app.jsx`
   - Ubicación: [https://inertiajs.com/docs/v3/installation/client-side-setup](https://inertiajs.com/docs/v3/installation/client-side-setup)

### 📄 Fase 3: Conceptos Core (Para Desarrolladores)

**Estudiar antes de crear componentes**:

1. **"Pages"** — Qué son y cómo crearlas
   - React components en `resources/js/Pages/`
   - Reciben props como parámetros
   - Automáticamente resueltas por Vite plugin

2. **"Responses"** — Cómo renderizar desde controladores
   - `Inertia::render('Component', [data])`
   - Estructura mínima de props
   - Asset versioning

3. **"Shared Data"** — Props globales
   - Autenticación, usuario, workspace
   - Definidas en `HandleInertiaRequests`
   - Accesibles en todos los componentes

### 🏗️ Fase 4: Características Esenciales para MVP

**Estudiar de acuerdo a necessidad de feature**:

1. **"Forms & Validation"** (Crítico)
   - `<Form>` component
   - `useForm()` hook
   - Precognition para validación real-time
   - Manejo de errores

2. **"Links & Navigation"** (Crítico)
   - `<Link>` component para navegación
   - `router.get()`, `.post()`, etc. para programativa
   - `preserveState` para mantener estado de formularios

3. **"Layouts"** (Importante)
   - Layouts persistentes (navbar, sidebar)
   - Nested layouts
   - `usePage().props` para acceder a shared data

### 🚀 Fase 5: Características Avanzadas (Post-MVP)

**Para optimizaciones después del MVP**:

1. **"Server-Side Rendering (SSR)"**
   - Automático en dev con Vite plugin
   - `php artisan inertia:start-ssr` en producción
   - Mejora SEO y perceived performance

2. **"Code Splitting"**
   - Pages lazy-load por defecto
   - Control con `lazy: true/false`
   - Reducer tamaño bundle inicial

3. **"Deferred Props & Lazy Loading"**
   - `Inertia::defer()` para props no críticas
   - `WhenVisible` para cargar al scroll
   - Mejora tiempo inicial de render

4. **"Partial Reloads"**
   - `only()` para recargar solo algunos props
   - Útil para paginación y filtros

---

## Arquitectura de Integración

### Estructura de Directorios Post-Setup

```
/workspace/
├─ app/
│  └─ Http/
│     ├─ Controllers/
│     │  └─ DashboardController.php      (renderiza Pages/Dashboard.jsx)
│     └─ Middleware/
│        └─ HandleInertiaRequests.php    (share() aquí)
│
├─ app-modules/
│  ├─ identity/
│  │  ├─ src/
│  │  │  ├─ Http/
│  │  │  │  └─ Controllers/
│  │  │  │     ├─ WorkspaceController.php
│  │  │  │     ├─ AuthController.php
│  │  │  │     └─ InvitationController.php
│  │  │  └─ Events/
│  │  └─ routes/
│  │     └─ identity-routes.php
│  │
│  ├─ publishing/
│  │  ├─ src/Http/Controllers/
│  │  │  └─ PostController.php
│  │  └─ routes/publishing-routes.php
│  │
│  └─ ... (otros módulos)
│
├─ resources/
│  ├─ js/
│  │  ├─ app.jsx                        ← Punto entrada React
│  │  ├─ bootstrap.js                   ← Configuración global
│  │  ├─ Layouts/
│  │  │  ├─ AppLayout.jsx              ← Sidebar, navbar
│  │  │  └─ GuestLayout.jsx            ← Login, signup
│  │  └─ Pages/
│  │     ├─ Dashboard.jsx              ← Workspace principal
│  │     ├─ Identity/
│  │     │  ├─ Auth/
│  │     │  │  ├─ Login.jsx
│  │     │  │  ├─ Register.jsx
│  │     │  │  └─ MagicLink.jsx
│  │     │  └─ Workspace/
│  │     │     ├─ Show.jsx
│  │     │     ├─ Index.jsx
│  │     │     └─ Members.jsx
│  │     ├─ Publishing/
│  │     │  ├─ Posts/
│  │     │  │  ├─ Index.jsx
│  │     │  │  ├─ Create.jsx
│  │     │  │  ├─ Edit.jsx
│  │     │  │  └─ Show.jsx
│  │     │  └─ Tags/
│  │     ├─ Audience/
│  │     │  ├─ Subscribers/
│  │     │  │  ├─ Index.jsx
│  │     │  │  └─ Import.jsx
│  │     │  └─ SegmentList.jsx
│  │     ├─ Delivery/
│  │     │  ├─ Campaigns/
│  │     │  │  ├─ Index.jsx
│  │     │  │  ├─ Create.jsx
│  │     │  │  └─ Show.jsx
│  │     │  └─ Analytics.jsx
│  │     └─ Errors/
│  │        ├─ 404.jsx
│  │        └─ 500.jsx
│  │
│  ├─ views/
│  │  └─ app.blade.php                 ← Root template Inertia
│  │
│  └─ css/
│     └─ app.css                       ← TailwindCSS
│
├─ routes/
│  ├─ web.php                          ← Rutas principales
│  └─ (modular routes cargan aquí)
│
├─ vite.config.js                      ← Vite + Inertia plugin
├─ package.json
├─ composer.json
└─ .context/
   └─ INERTIA_IMPLEMENTATION_GUIDE.md  ← Este archivo
```

### Cómo Funciona el Flujo Inertia en Freetter

```
┌─────────────────────────────────────────────────────────────────┐
│ USER VISIT: GET /workspace/123/posts                            │
└─────────────────────────────────────────────────────────────────┘

1️⃣ BROWSER
   └─> GET /workspace/123/posts

2️⃣ LARAVEL ROUTER
   └─> Route::get('/workspace/{workspace}/posts', [PostController::class, 'index'])

3️⃣ POSTCONTROLLER::INDEX
   └─> Inertia::render('Publishing/Posts/Index', [
       'workspace' => $workspace->only('id', 'name'),
       'posts' => $posts->map(fn($p) => $p->only('id', 'title', 'status')),
   ])

4️⃣ HANDLEINERTIAREQUESTS MIDDLEWARE
   └─> share([
       'auth' => auth()->user(),
       'workspace' => workspace_context(),
   ])

5️⃣ HTTP RESPONSE (application/json)
   └─> {
       "component": "Publishing/Posts/Index",
       "props": {
           "auth": { "id": 1, "email": "..." },
           "workspace": { "id": "abc", "name": "..." },
           "posts": [ ... ],
       },
       "url": "/workspace/123/posts",
       "version": "asset-hash"
   }

6️⃣ INERTIA CLIENT (React)
   └─> Resuelve parámetro "Publishing/Posts/Index"
   └─> Carga: resources/js/Pages/Publishing/Posts/Index.jsx
   └─> Renderiza con props

7️⃣ REACT COMPONENT MOUNTS
   └─> export default function Index({ auth, workspace, posts }) {
           return <div>...</div>
       }
```

### Shared Data (Global Props)

Datos disponibles en **todos los componentes** sin pasar explícitamente:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        'auth' => [
            'user' => $request->user(),
        ],
        'workspace' => $request->user()?->currentWorkspace(),
        'flash' => [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
        ],
    ];
}
```

Acceso en componentes:

```jsx
// resources/js/Pages/Dashboard.jsx
import { usePage } from '@inertiajs/react'

export default function Dashboard() {
    const { auth, workspace, flash } = usePage().props

    return (
        <div>
            <p>Welcome, {auth.user.name}</p>
            <p>Workspace: {workspace.name}</p>
        </div>
    )
}
```

---

## Roadmap de Implementación

### Fase 1: Setup Infraestructura (1-2 horas)

- [ ] Instalar dependencias npm
- [ ] Crear root template `app.blade.php`
- [ ] Generar & registrar middleware Inertia
- [ ] Configurar Vite plugin
- [ ] Crear `resources/js/app.jsx`
- [ ] Validar que build funciona (`npm run build`)

### Fase 2: Primer Componente Funcional (1-2 horas)

- [ ] Crear página de bienvenida: `Pages/Welcome.jsx`
- [ ] Modificar ruta `/` para usar Inertia en lugar de Blade
- [ ] Setup Shared Data (auth, workspace)
- [ ] Testar navegación básica

### Fase 3: Layouts & Navigation (2-3 horas)

- [ ] Crear `Layouts/AppLayout.jsx` (sidebar, navbar)
- [ ] Crear `Layouts/GuestLayout.jsx` (login)
- [ ] Implementar `<Link>` para navegación interna
- [ ] Setup rutas autenticadas vs públicas

### Fase 4: Identity Module UI (3-4 horas)

- [ ] Página de login: `Pages/Identity/Auth/Login.jsx`
- [ ] Página de magic link: `Pages/Identity/Auth/MagicLink.jsx`
- [ ] Página de workspace selector/creator: `Pages/Identity/Workspace/Index.jsx`
- [ ] Página de miembros del workspace: `Pages/Identity/Workspace/Members.jsx`
- [ ] Forms con validación y error handling

### Fase 5: Publishing Module UI (4-5 horas)

- [ ] Lista de posts: `Pages/Publishing/Posts/Index.jsx`
- [ ] Crear post: `Pages/Publishing/Posts/Create.jsx`
- [ ] Editar post: `Pages/Publishing/Posts/Edit.jsx`
- [ ] Ver post: `Pages/Publishing/Posts/Show.jsx`
- [ ] Implementar editor de contenido

### Fase 6: Audience Module UI (2-3 horas)

- [ ] Lista de suscriptores: `Pages/Audience/Subscribers/Index.jsx`
- [ ] Importar suscriptores: `Pages/Audience/Subscribers/Import.jsx`
- [ ] Filtros y paginación

### Fase 7: Delivery Module UI (2-3 horas)

- [ ] Lista de campañas: `Pages/Delivery/Campaigns/Index.jsx`
- [ ] Crear campaña: `Pages/Delivery/Campaigns/Create.jsx`
- [ ] Analytics: `Pages/Delivery/Analytics.jsx`

### Fase 8: Validación & Testing (2-3 horas)

- [ ] Tests de integración Inertia
- [ ] Tests de componentes React
- [ ] Validación end-to-end

### Estimación Total: 15-23 horas (depende de complejidad UI)

---

## Guía de Setup Paso a Paso

### Paso 1: Instalación de Dependencias NPM

```bash
npm install \
  @inertiajs/react \
  @inertiajs/vite \
  react \
  react-dom
```

**Verificar**: `cat package.json | grep "@inertiajs"`

**Dependencias esperadas**:
```json
{
  "@inertiajs/react": "^3.0.0",
  "@inertiajs/vite": "^3.0.0",
  "react": "^18.2.0",
  "react-dom": "^18.2.0"
}
```

### Paso 2: Crear Root Template Blade

**Archivo**: `resources/views/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        @viteReactRefresh
        @vite(['resources/js/app.jsx', 'resources/css/app.css'])
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
```

**Notas**:
- `@viteReactRefresh` habilita Fast Refresh en desarrollo
- `@vite(['resources/js/app.jsx', ...])` especifica entry point
- `<x-inertia::head />` renderiza `<meta>`, `<title>`, etc.
- `<x-inertia::app />` monta React en `<div id="app">`

### Paso 3: Generar & Registrar Middleware Inertia

```bash
php artisan inertia:middleware
```

**Esto crea**: `app/Http/Middleware/HandleInertiaRequests.php`

**Editar** `bootstrap/app.php` para registrar el middleware:

```php
use App\Http\Middleware\HandleInertiaRequests;
use // ... otros imports

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptionHandling()
    ->create();
```

### Paso 4: Configurar Vite Plugin

**Archivo**: `vite.config.js`

```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import inertia from '@inertiajs/vite'
import { tailwindPlugin } from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
        inertia(),
        tailwindPlugin(),
    ],
})
```

**Cambio respecto a config anterior**:
- Agregar `import inertia from '@inertiajs/vite'`
- Agregar `inertia()` al array de plugins
- Cambiar entrada de `resources/js/app.js` a `resources/js/app.jsx`

### Paso 5: Crear Entry Point React

**Archivo**: `resources/js/app.jsx`

```jsx
import { createInertiaApp } from '@inertiajs/react'

createInertiaApp()
```

**Eso es todo lo que necesitas**. El Vite plugin automáticamente:
- Resuelve componentes en `resources/js/Pages/` (o `pages/`)
- Maneja SSR en desarrollo
- Code-splits pages

### Paso 6: Configurar Shared Data

**Archivo**: `app/Http/Middleware/HandleInertiaRequests.php`

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
```

### Paso 7: Crear Primer Componente

**Archivo**: `resources/js/Pages/Welcome.jsx`

```jsx
import { Head } from '@inertiajs/react'

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <div className="flex items-center justify-center min-h-screen bg-gray-100">
                <div className="text-center">
                    <h1 className="text-4xl font-bold mb-4">Welcome to Freetter</h1>
                    <p className="text-gray-600 mb-8">Built with Inertia.js + React</p>
                </div>
            </div>
        </>
    )
}
```

### Paso 8: Actualizar Ruta Principal

**Archivo**: `routes/web.php`

```php
<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

// ... include module routes
require __DIR__.'/identity-routes.php';
require __DIR__.'/publishing-routes.php';
// etc.
```

### Paso 9: Verificar Build

```bash
npm run dev  # Dev mode con HMR
npm run build  # Producción
composer run dev  # Dev completo (servidor + vite + queue)
```

**Esperado**:
- ✅ Servidor Laravel en `http://localhost:8000`
- ✅ Vite dev server en background
- ✅ Componente renderiza correctamente
- ✅ Puedes navegar sin reloads de página

---

## Integración con Módulos Modular

### Principio Fundamental

**Cada módulo puede tener sus propios controladores que retornan Inertia responses.**

Ejemplo estructura:

```
app-modules/
├─ publishing/
│  ├─ src/
│  │  ├─ Http/
│  │  │  └─ Controllers/
│  │  │     └─ PostController.php      ← Retorna Inertia::render()
│  │  └─ routes/
│  │     └─ publishing-routes.php      ← Define rutas de módulo
│  └─ ...
```

### Ejemplo 1: Controlador de Posts (Publishing)

**Archivo**: `app-modules/publishing/src/Http/Controllers/PostController.php`

```php
<?php

namespace Domains\Publishing\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Domains\Publishing\Models\Post;
use Domains\Identity\Models\Workspace;

class PostController
{
    public function index(Request $request, Workspace $workspace)
    {
        $posts = $workspace->posts()
            ->with('author', 'tags')
            ->latest()
            ->paginate(15)
            ->through(fn($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'publishedAt' => $post->published_at?->format('Y-m-d'),
                'author' => $post->author->only('name'),
            ]);

        return Inertia::render('Publishing/Posts/Index', [
            'workspace' => $workspace->only('id', 'name'),
            'posts' => $posts,
        ]);
    }

    public function show(Request $request, Workspace $workspace, Post $post)
    {
        // Validar ownership
        abort_unless($post->workspace_id === $workspace->id, 404);

        return Inertia::render('Publishing/Posts/Show', [
            'workspace' => $workspace->only('id', 'name'),
            'post' => $post->load('versions', 'tags', 'author')->toArray(),
        ]);
    }
}
```

### Ejemplo 2: Componente React correspondiente

**Archivo**: `resources/js/Pages/Publishing/Posts/Index.jsx`

```jsx
import { Head, Link } from '@inertiajs/react'
import AppLayout from '@/Layouts/AppLayout'

export default function PostIndex({ workspace, posts }) {
    return (
        <AppLayout>
            <Head title={`Posts - ${workspace.name}`} />

            <div className="max-w-6xl mx-auto px-4 py-8">
                <div className="flex items-center justify-between mb-8">
                    <h1 className="text-3xl font-bold">Posts</h1>
                    <Link
                        href={route('posts.create', { workspace: workspace.id })}
                        className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                    >
                        New Post
                    </Link>
                </div>

                <div className="list">
                    {posts.data.map(post => (
                        <div key={post.id} className="border p-4 mb-2 rounded">
                            <h3 className="font-bold">{post.title}</h3>
                            <p className="text-sm text-gray-600">{post.status}</p>
                            <Link href={route('posts.show', {
                                workspace: workspace.id,
                                post: post.id
                            })}>
                                View
                            </Link>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    )
}
```

### Naming Conventions para Componentes

Sigue esta estructura para organizar componentes por dominio:

```
resources/js/Pages/
├─ Identity/
│  ├─ Auth/
│  │  ├─ Login.jsx
│  │  ├─ Register.jsx
│  │  └─ MagicLink.jsx
│  └─ Workspace/
│     ├─ Index.jsx
│     ├─ Show.jsx
│     └─ Members.jsx
├─ Publishing/
│  └─ Posts/
│     ├─ Index.jsx
│     ├─ Create.jsx
│     ├─ Edit.jsx
│     └─ Show.jsx
├─ Audience/
│  └─ Subscribers/
│     ├─ Index.jsx
│     └─ Import.jsx
├─ Community/
│  └─ Comments/
│     └─ Index.jsx
├─ Delivery/
│  └─ Campaigns/
│     ├─ Index.jsx
│     ├─ Create.jsx
│     └─ Show.jsx
└─ Layouts/
   ├─ AppLayout.jsx
   └─ GuestLayout.jsx
```

### Compartir Componentes entre Módulos

**Para componentes genéricos** (botones, modales, etc):

```
resources/js/Components/
├─ Button.jsx
├─ Modal.jsx
├─ Form/
│  ├─ Input.jsx
│  ├─ Select.jsx
│  └─ Textarea.jsx
└─ Alerts/
   ├─ Success.jsx
   └─ Error.jsx
```

**Importarlos en Pages**:

```jsx
// resources/js/Pages/Publishing/Posts/Create.jsx
import Button from '@/Components/Button'
import { Input, Textarea } from '@/Components/Form'

export default function Create() {
    return (
        <form>
            <Input label="Title" name="title" />
            <Textarea label="Content" name="content" />
            <Button type="submit">Publish</Button>
        </form>
    )
}
```

---

## Patrones de Desarrollo en Freetter

### 1. Patrón: Shared Data + Module Routes

Las shared data (auth, workspace, flash) están disponibles globalmente:

```jsx
// En cualquier componente
import { usePage } from '@inertiajs/react'

export default function Component() {
    const { auth, workspace, flash } = usePage().props

    useEffect(() => {
        if (flash?.success) {
            // mostrar toast de éxito
        }
    }, [flash])
}
```

### 2. Patrón: Forms con Validación

Usa `useForm()` para manejar envios:

```jsx
import { useForm } from '@inertiajs/react'

export default function CreatePost({ workspace }) {
    const { data, setData, post, errors } = useForm({
        title: '',
        content: '',
        status: 'draft',
    })

    const submit = (e) => {
        e.preventDefault()
        post(`/workspace/${workspace.id}/posts`)
    }

    return (
        <form onSubmit={submit}>
            <input
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                className={errors.title ? 'border-red-500' : ''}
            />
            {errors.title && <span>{errors.title}</span>}

            <textarea
                value={data.content}
                onChange={e => setData('content', e.target.value)}
            />

            <button type="submit">Create</button>
        </form>
    )
}
```

**Desde controlador**:

```php
public function store(Request $request, Workspace $workspace)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'status' => 'required|in:draft,published',
    ]);

    $post = $workspace->posts()->create($validated);

    return redirect()->route('posts.show', [
        'workspace' => $workspace,
        'post' => $post,
    ])->with('success', 'Post created successfully');
}
```

### 3. Patrón: Layouts Persistentes

Define un layout para mantener UI consistente:

```jsx
// resources/js/Layouts/AppLayout.jsx
import { Link, usePage } from '@inertiajs/react'

export default function AppLayout({ children }) {
    const { auth, workspace } = usePage().props

    return (
        <div className="flex">
            <sidebar className="w-64 bg-gray-900 text-white p-6">
                <h2 className="text-xl font-bold mb-6">{workspace?.name}</h2>
                <nav className="space-y-4">
                    <Link href="/dashboard" className="block hover:text-gray-300">
                        Dashboard
                    </Link>
                    <Link href={`/workspace/${workspace?.id}/posts`} className="block hover:text-gray-300">
                        Posts
                    </Link>
                    {/* ... más links ... */}
                </nav>
            </sidebar>

            <main className="flex-1">
                {children}
            </main>
        </div>
    )
}
```

**Usa en componentes**:

```jsx
import AppLayout from '@/Layouts/AppLayout'

export default function PostIndex({ posts }) {
    return (
        <AppLayout>
            <div className="p-6">
                {/* contenido de posteo */}
            </div>
        </AppLayout>
    )
}
```

### 4. Patrón: Navegación entre Workspace

En Freetter, usuarios pueden tener múltiples workspaces:

```jsx
// resources/js/Pages/Dashboard.jsx
import { Link, usePage } from '@inertiajs/react'

export default function Dashboard() {
    const { auth, workspace } = usePage().props

    return (
        <div>
            <h1>Welcome, {auth.user.name}</h1>

            <div>
                <p>Current workspace: {workspace.name}</p>
                <Link href="/workspace">Switch workspace</Link>
            </div>
        </div>
    )
}
```

**Actualizar shared data en middleware**:

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
        ],
        'workspace' => $request->user()?->currentWorkspace()?->only(
            'id', 'name', 'role'
        ),
    ]);
}
```

### 5. Patrón: Errores de Validación

Laravel automáticamente redirige con errores usando Inertia:

```php
// Controlador: valida y captura errores
public function store(PostStoreRequest $request, Workspace $workspace)
{
    // Si falla validación, laravel automáticamente redirige
    // con prop 'errors' en la página anterior

    $post = $workspace->posts()->create($request->validated());

    return redirect('/posts');
}
```

**En componente**:

```jsx
import { useForm } from '@inertiajs/react'

export default function CreatePost() {
    const { data, setData, post, errors } = useForm({
        title: '',
    })

    return (
        <form onSubmit={e => {
            e.preventDefault()
            post('/posts')
        }}>
            <input value={data.title} onChange={...} />
            {errors.title && <p className="text-red-500">{errors.title}</p>}
            <button>Create</button>
        </form>
    )
}
```

---

## Testing

### Tests de Integración (Laravel)

Verifica que controladores retornan Inertia responses correctas:

```php
// tests/Feature/PublishingPostsTest.php
namespace Tests\Feature;

use Tests\TestCase;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;

class PublishingPostsTest extends TestCase
{
    public function test_posts_index_returns_inertia_response()
    {
        $workspace = Workspace::factory()->create();
        Post::factory()->count(3)->create(['workspace_id' => $workspace->id]);

        $response = $this->get("/workspace/{$workspace->id}/posts");

        $response->assertInertia(fn($page) => $page
            ->component('Publishing/Posts/Index')
            ->has('workspace')
            ->has('posts.data', 3)
        );
    }

    public function test_create_post_returns_inertia_component()
    {
        $workspace = $this->createWorkspace();

        $response = $this->actingAs($workspace->owner)->get(
            "/workspace/{$workspace->id}/posts/create"
        );

        $response->assertInertia(fn($page) => $page
            ->component('Publishing/Posts/Create')
        );
    }

    public function test_store_post_validates_input()
    {
        $workspace = $this->createWorkspace();

        $response = $this->actingAs($workspace->owner)->post(
            "/workspace/{$workspace->id}/posts",
            ['title' => ''] // validación falla
        );

        $response->assertInertia(fn($page) => $page
            ->has('errors.title')
        );
    }
}
```

### Tests de Componentes React

Con Testing Library:

```jsx
// resources/js/Pages/__tests__/PostIndex.test.jsx
import { render, screen } from '@testing-library/react'
import PostIndex from '../Publishing/Posts/Index'

describe('PostIndex', () => {
    it('renders posts list', () => {
        const workspace = { id: '1', name: 'Test Workspace' }
        const posts = {
            data: [
                { id: '1', title: 'First Post', status: 'published' },
                { id: '2', title: 'Second Post', status: 'draft' },
            ]
        }

        render(
            <PostIndex workspace={workspace} posts={posts} />
        )

        expect(screen.getByText('First Post')).toBeInTheDocument()
        expect(screen.getByText('Second Post')).toBeInTheDocument()
    })
})
```

---

## Referencias y Recursos

### 📚 Documentación Oficial de Inertia.js

**v3 Core**:
- Getting Started: https://inertiajs.com/docs/v3/getting-started
- Server-Side Setup: https://inertiajs.com/docs/v3/installation/server-side-setup
- Client-Side Setup: https://inertiajs.com/docs/v3/installation/client-side-setup
- The Basics - Pages: https://inertiajs.com/docs/v3/the-basics/pages
- The Basics - Responses: https://inertiajs.com/docs/v3/the-basics/responses
- Data Props - Shared Data: https://inertiajs.com/docs/v3/data-props/shared-data
- Forms & Validation: https://inertiajs.com/docs/v3/the-basics/forms

**Advanced**:
- Server-Side Rendering: https://inertiajs.com/docs/v3/advanced/server-side-rendering
- Code Splitting: https://inertiajs.com/docs/v3/advanced/code-splitting
- Deferred Props: https://inertiajs.com/docs/v3/data-props/deferred-props

### 📖 Laravel Boost Documentation

- Search Docs: Usa para búsquedas versionadas
- MCP Tools: Debugging queries, schemas
- Browser Logs: Inspecciona errores React

### 🔗 Referencia Proyecto Freetter

- `.context/PROJECT_ARCHITECTURE.md` - Estructura modular
- `.context/CURRENT_STATE.md` - Estado actual de módulos
- `.context/USE_CASES.md` - Casos de uso por módulo
- AGENTS.md - Reglas de desarrollo

### 🎬 Comandos Útiles

```bash
# Setup inicial
npm run dev              # Dev con HMR
npm run build            # Build producción
composer run dev         # Dev completo

# Debugging
php artisan route:list   # Ver rutas
php artisan tinker       # REPL si necesitas testear

# Testing
php artisan test --compact               # Todos los tests
php artisan test --compact --filter=PostTest
npm run test             # React tests (si configuras)
```

---

## Checklist de Validación

Antes de marcar una fase como completa:

### Fase 1: Setup
- [ ] `npm list @inertiajs/react` retorna v3.x
- [ ] `resources/views/app.blade.php` existe
- [ ] `app/Http/Middleware/HandleInertiaRequests.php` existe
- [ ] `bootstrap/app.php` registra middleware en web group
- [ ] `vite.config.js` incluye `inertia()`
- [ ] `resources/js/app.jsx` existe y es minimal
- [ ] `npm run build` completa sin errores
- [ ] `npm run dev` inicia sin errores

### Fase 2: Primer componente
- [ ] `resources/js/Pages/Welcome.jsx` existe
- [ ] Ruta `/` retorna `Inertia::render('Welcome')`
- [ ] Navegas a `http://localhost:8000/` y ves componente
- [ ] No hay errores en consola del navegador
- [ ] Hot reload funciona (modificas `.jsx` y se refleja)

### Fase 3: Shared data
- [ ] `HandleInertiaRequests.share()` retorna auth y flash
- [ ] Componentes acceden a `usePage().props.auth`
- [ ] Flash messages aparecen después de submit

### Fase 4: Module integration
- [ ] Controlador en módulo retorna `Inertia::render()`
- [ ] Componente en `Pages/[Module]/...` existe
- [ ] Rutas de módulo funcionan

---

## Próximos Pasos Después de Este Setup

1. **Implements auth flows** en Identity module
2. **Build publishing UI** con editor
3. **Setup audience management**
4. **Integrate delivery campaigns**
5. **Add error pages** (404, 500, 403)
6. **Config SSR** para producción (post-MVP)
7. **Performance optimization** (code splitting, lazy loading)

---

**Última actualización**: 7 de abril de 2026
**Versión**: 1.0
**Estado**: Listo para implementación
