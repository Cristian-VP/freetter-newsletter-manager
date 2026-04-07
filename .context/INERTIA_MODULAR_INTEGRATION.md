# INERTIA.JS MODULAR INTEGRATION GUIDE FOR FREETTER

## 🏗️ Propósito

Este documento especifica **EXACTAMENTE cómo integrar Inertia.js v3 con la arquitectura modular de Freetter**.

Los documentos anteriores eran genéricos. Este es **específico a tu estructura**.

---

## 1. Arquitectura Modular de Freetter + Inertia.js

### Estado Actual (Sin Inertia)

```
Blade views (tradicional)
    ↓
Controllers en app-modules/
    ↓
Rutas en app-modules/routes/
    ↓
Models + Events
```

### Estado Destino (Con Inertia)

```
React Components (Pages + Layouts + Components)
    ↓
Controllers en app-modules/ retornan Inertia::render()
    ↓
Rutas en app-modules/routes/ sin cambios
    ↓
Models + Events sin cambios
```

**El punto clave**: Los controladores **cambian su respuesta de Blade a Inertia**, pero TODO lo demás (lógica, modelos, eventos) **permanece igual**.

---

## 2. Validación: Estructura Modular Actual de Freetter

### Módulos Implementados (según CURRENT_STATE.md)

```
app-modules/
├─ identity/
│  ├─ src/
│  │  ├─ Http/
│  │  │  └─ Controllers/        ← AQUÍ van controladores Inertia
│  │  ├─ Models/                ← No cambios
│  │  ├─ Events/                ← No cambios
│  │  └─ Observers/             ← No cambios
│  ├─ routes/
│  │  └─ identity-routes.php    ← En vía de actualización
│  └─ ... (migrations, tests)
│
├─ publishing/
│  ├─ src/Http/Controllers/     ← AQUÍ van controladores Inertia
│  └─ routes/publishing-routes.php
│
├─ audience/
│  ├─ src/Http/Controllers/     ← AQUÍ van controladores Inertia
│  └─ routes/audience-routes.php
│
├─ community/
│  ├─ src/Http/Controllers/     ← AQUÍ van controladores Inertia
│  └─ routes/community-routes.php
│
├─ delivery/
│  ├─ src/Http/Controllers/     ← AQUÍ van controladores Inertia
│  └─ routes/delivery-routes.php
│
└─ activity/
   ├─ src/Http/Controllers/     ← AQUÍ van controladores Inertia
   └─ routes/activity-routes.php
```

**Conclusión**: Tu estructura modular es **perfecta para Inertia**. Cada módulo ya tiene el lugar para sus controladores.

---

## 3. Patrón: Cómo Estructura Funciona

### Flujo de Request en Freetter Modular

```
1️⃣ USER REQUEST
   GET /workspace/123/posts

2️⃣ ROUTER (routes/web.php)
   → require __DIR__.'/publishing-routes.php'

3️⃣ MODULE ROUTER (publishing-routes.php)
   Route::get('/workspace/{workspace}/posts',
       [PostController::class, 'index'])

4️⃣ MODULE CONTROLLER (publishing/src/Http/Controllers/PostController.php)
   ┌─────────────────────────────────────────┐
   │ public function index(Workspace $w)     │
   │ {                                       │
   │   return Inertia::render(              │
   │     'Publishing/Posts/Index',  ← AQUÍ  │
   │     ['workspace' => $w, ...]           │
   │   );                                    │
   │ }                                       │
   └─────────────────────────────────────────┘

5️⃣ INERTIA RESPONDE
   HTTP 200
   {
     "component": "Publishing/Posts/Index",  ← React component
     "props": { "workspace": {...}, ... },
     ...
   }

6️⃣ REACT MONTA
   resources/js/Pages/Publishing/Posts/Index.jsx
```

---

## 4. Convención de Nombres: Componentes React por Módulo

### Estructura Esperada de `resources/js/Pages/`

```
resources/js/Pages/
│
├─ Identity/                    ← Componentes del módulo identity
│  ├─ Auth/
│  │  ├─ Login.jsx             ← Route: GET /login
│  │  ├─ Register.jsx          ← Route: GET /register
│  │  └─ MagicLink.jsx         ← Route: GET /magic-link
│  │
│  └─ Workspace/
│     ├─ Index.jsx             ← Route: GET /workspace
│     ├─ Show.jsx              ← Route: GET /workspace/:id
│     ├─ Create.jsx            ← Route: GET /workspace/create
│     ├─ Members.jsx           ← Route: GET /workspace/:id/members
│     └─ Invitations.jsx       ← Route: GET /workspace/:id/invitations
│
├─ Publishing/                  ← Componentes del módulo publishing
│  ├─ Posts/
│  │  ├─ Index.jsx             ← Route: GET /workspace/:id/posts
│  │  ├─ Create.jsx            ← Route: GET /workspace/:id/posts/create
│  │  ├─ Edit.jsx              ← Route: GET /workspace/:id/posts/:id/edit
│  │  └─ Show.jsx              ← Route: GET /workspace/:id/posts/:id
│  │
│  └─ Tags/
│     └─ Index.jsx             ← Route: GET /workspace/:id/tags
│
├─ Audience/                    ← Componentes del módulo audience
│  └─ Subscribers/
│     ├─ Index.jsx             ← Route: GET /workspace/:id/subscribers
│     └─ Import.jsx            ← Route: GET /workspace/:id/subscribers/import
│
├─ Community/                   ← Componentes del módulo community
│  └─ Comments/
│     └─ Moderate.jsx          ← Route: GET /workspace/:id/comments/moderate
│
├─ Delivery/                    ← Componentes del módulo delivery
│  └─ Campaigns/
│     ├─ Index.jsx             ← Route: GET /workspace/:id/campaigns
│     ├─ Create.jsx            ← Route: GET /workspace/:id/campaigns/create
│     ├─ Show.jsx              ← Route: GET /workspace/:id/campaigns/:id
│     └─ Analytics.jsx         ← Route: GET /workspace/:id/analytics
│
├─ Activity/                    ← Componentes del módulo activity
│  └─ Logs.jsx                 ← Route: GET /workspace/:id/activity
│
├─ Layouts/
│  ├─ AppLayout.jsx            ← Sidebar + Navbar + Workspace context
│  ├─ GuestLayout.jsx          ← Solo para auth públicas
│  └─ WorkspaceLayout.jsx      ← Específico para dentro de workspace
│
├─ Components/
│  ├─ Button.jsx
│  ├─ Form/
│  │  ├─ Input.jsx
│  │  └─ Textarea.jsx
│  ├─ Icons/
│  └─ ... (compartidos entre módulos)
│
└─ Errors/
   ├─ 404.jsx
   ├─ 500.jsx
   └─ 403.jsx
```

**Regla**: `resources/js/Pages/[Module]/[Feature]/[Component].jsx`

---

## 5. Server-Side Setup Contextualizado a Freetter

### 5.1 Shared Data Global (HandleInertiaRequests)

Freetter tiene datos que **deben estar en todos los componentes**:

```php
// app/Http/Middleware/HandleInertiaRequests.php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // SHARED DATA GLOBAL
            'auth' => [
                'user' => $request->user(),
            ],

            // CONTEXTO DE WORKSPACE (si existe en ruta)
            'workspace' => $this->currentWorkspace($request),

            // FLASH MESSAGES
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
            ],
        ]);
    }

    protected function currentWorkspace(Request $request)
    {
        // Obtener workspace de la ruta actual
        // Ej: /workspace/{workspace}/posts
        $workspace = $request->route('workspace');

        if (!$workspace) {
            return null;
        }

        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'role' => auth()->user()?->roles($workspace)->first()?->name,
            'members_count' => $workspace->members()->count(),
        ];
    }
}
```

**En componentes, accede así**:

```jsx
import { usePage } from '@inertiajs/react'

export default function PostIndex() {
    const { auth, workspace, flash } = usePage().props

    useEffect(() => {
        if (flash.success) {
            // Mostrar toast
        }
    }, [flash])

    return <div>Workspace: {workspace.name}</div>
}
```

### 5.2 Rutas de Módulo con Inertia

**Ejemplo**: `app-modules/publishing/routes/publishing-routes.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use Domains\Publishing\Http\Controllers\PostController;

Route::prefix('workspace/{workspace}')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        // Posts CRUD
        Route::get('/posts', [PostController::class, 'index'])
            ->name('posts.index');

        Route::get('/posts/create', [PostController::class, 'create'])
            ->name('posts.create');

        Route::post('/posts', [PostController::class, 'store'])
            ->name('posts.store');

        Route::get('/posts/{post}', [PostController::class, 'show'])
            ->name('posts.show');

        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])
            ->name('posts.edit');

        Route::put('/posts/{post}', [PostController::class, 'update'])
            ->name('posts.update');

        Route::delete('/posts/{post}', [PostController::class, 'destroy'])
            ->name('posts.destroy');
    });
```

### 5.3 Controlador que Retorna Inertia

**Ejemplo**: `app-modules/publishing/src/Http/Controllers/PostController.php`

```php
<?php

namespace Domains\Publishing\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Domains\Publishing\Models\Post;
use Domains\Identity\Models\Workspace;

class PostController
{
    /**
     * Mostrar lista de posts del workspace
     */
    public function index(Request $request, Workspace $workspace)
    {
        // Autorizar: solo miembros del workspace
        abort_unless(
            $request->user()->belongsToWorkspace($workspace),
            403
        );

        $posts = $workspace->posts()
            ->with('author', 'tags')
            ->latest()
            ->paginate(15)
            ->through(fn($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'published_at' => $post->published_at?->format('Y-m-d'),
                'author' => $post->author->only('id', 'name'),
                'tags' => $post->tags->pluck('name'),
            ]);

        return Inertia::render('Publishing/Posts/Index', [
            'posts' => $posts,
            'can' => [
                'create' => $request->user()->can('create', [Post::class, $workspace]),
                'publish' => $request->user()->can('publish', [Post::class, $workspace]),
            ],
        ]);
    }

    /**
     * Mostrar formulario para crear post
     */
    public function create(Request $request, Workspace $workspace)
    {
        abort_unless(
            $request->user()->can('create', [Post::class, $workspace]),
            403
        );

        return Inertia::render('Publishing/Posts/Create', [
            'workspace' => $workspace->only('id', 'name'),
        ]);
    }

    /**
     * Guardar post (procesa form)
     */
    public function store(Request $request, Workspace $workspace)
    {
        abort_unless(
            $request->user()->can('create', [Post::class, $workspace]),
            403
        );

        // IMPORTANTE:
        // La validación automáticamente redirige con errores
        // Inertia.js pone los errores en un prop 'errors'
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
        ]);

        $post = $workspace->posts()->create([
            ...$validated,
            'author_id' => $request->user()->id,
        ]);

        // Flash message + redirigir
        return redirect()->route('posts.show', [
            'workspace' => $workspace,
            'post' => $post,
        ])->with('success', 'Post created successfully');
    }

    /**
     * Mostrar un post específico
     */
    public function show(Request $request, Workspace $workspace, Post $post)
    {
        abort_unless($post->workspace_id === $workspace->id, 404);

        return Inertia::render('Publishing/Posts/Show', [
            'post' => $post->load('author', 'tags', 'versions')->toArray(),
            'can' => [
                'edit' => $request->user()->can('update', $post),
                'delete' => $request->user()->can('delete', $post),
                'publish' => $request->user()->can('publish', $post),
            ],
        ]);
    }
}
```

---

## 6. Client-Side Setup Contextualizado a Freetter

### 6.1 Vite Config (Igual que documentación genérica)

```javascript
// vite.config.js
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

### 6.2 App Entry Point (React)

```jsx
// resources/js/app.jsx
import { createInertiaApp } from '@inertiajs/react'

createInertiaApp()
```

### 6.3 Root Layout por Contexto

**Patrón CRÍTICO para Freetter**: Diferentes layouts según contexto

```jsx
// resources/js/Layouts/AppLayout.jsx
// Sidebar + Navbar + Workspace context

import { Link, usePage } from '@inertiajs/react'
import { useState } from 'react'

export default function AppLayout({ children }) {
    const { auth, workspace } = usePage().props
    const [sidebarOpen, setSidebarOpen] = useState(true)

    return (
        <div className="flex h-screen bg-gray-100">
            {/* SIDEBAR */}
            <aside
                className={`${
                    sidebarOpen ? 'w-64' : 'w-20'
                } bg-gray-900 text-white transition-all duration-300 overflow-y-auto`}
            >
                <div className="p-6">
                    <h2 className="text-2xl font-bold">
                        {workspace?.name?.slice(0, 2).toUpperCase()}
                    </h2>
                </div>

                <nav className="mt-6 space-y-1">
                    {/* Links por módulo */}
                    <NavLink
                        href={`/workspace/${workspace?.id}/posts`}
                        label="Posts"
                        icon="📝"
                    />
                    <NavLink
                        href={`/workspace/${workspace?.id}/subscribers`}
                        label="Audience"
                        icon="👥"
                    />
                    <NavLink
                        href={`/workspace/${workspace?.id}/campaigns`}
                        label="Campaigns"
                        icon="📧"
                    />
                    <NavLink
                        href={`/workspace/${workspace?.id}/comments/moderate`}
                        label="Moderation"
                        icon="🛡️"
                    />
                </nav>
            </aside>

            {/* MAIN CONTENT */}
            <main className="flex-1 overflow-y-auto">
                {/* NAVBAR */}
                <nav className="bg-white shadow">
                    <div className="px-6 py-4 flex items-center justify-between">
                        <button
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="text-gray-500 hover:text-gray-700"
                        >
                            ☰
                        </button>

                        <div className="flex items-center space-x-4">
                            <span className="text-gray-700">
                                {auth.user?.name}
                            </span>
                            <Link
                                method="post"
                                href="/logout"
                                className="text-red-600 hover:text-red-700"
                            >
                                Logout
                            </Link>
                        </div>
                    </div>
                </nav>

                {/* PAGE CONTENT */}
                <div className="p-6">
                    {children}
                </div>
            </main>
        </div>
    )
}

function NavLink({ href, label, icon }) {
    return (
        <Link
            href={href}
            className="block px-4 py-2 rounded hover:bg-gray-800 transition"
        >
            <span className="mr-2">{icon}</span>
            {label}
        </Link>
    )
}
```

```jsx
// resources/js/Layouts/GuestLayout.jsx
// Para páginas públicas (login, register)

export default function GuestLayout({ children }) {
    return (
        <div className="min-h-screen bg-gradient-to-tr from-blue-600 to-purple-600 flex items-center justify-center">
            <div className="bg-white rounded-lg shadow-lg p-8 w-96">
                {children}
            </div>
        </div>
    )
}
```

### 6.4 Componente Ejemplo: Publishing/Posts/Index.jsx

```jsx
// resources/js/Pages/Publishing/Posts/Index.jsx

import { Head, Link, usePage } from '@inertiajs/react'
import AppLayout from '@/Layouts/AppLayout'
import { useState } from 'react'

export default function PostIndex({ posts, can }) {
    const { workspace, flash } = usePage().props
    const [searchTerm, setSearchTerm] = useState('')

    const filteredPosts = posts.data.filter(post =>
        post.title.toLowerCase().includes(searchTerm.toLowerCase())
    )

    return (
        <AppLayout>
            <Head title={`Posts - ${workspace.name}`} />

            {/* Success message */}
            {flash?.success && (
                <div className="mb-4 p-4 bg-green-100 text-green-700 rounded">
                    {flash.success}
                </div>
            )}

            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <h1 className="text-3xl font-bold text-gray-900">
                    Posts
                </h1>
                {can?.create && (
                    <Link
                        href={route('posts.create', { workspace: workspace.id })}
                        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                    >
                        + New Post
                    </Link>
                )}
            </div>

            {/* Search */}
            <div className="mb-6">
                <input
                    type="text"
                    placeholder="Search posts..."
                    value={searchTerm}
                    onChange={e => setSearchTerm(e.target.value)}
                    className="w-full px-4 py-2 border rounded-lg"
                />
            </div>

            {/* Posts list */}
            <div className="grid gap-4">
                {filteredPosts.length > 0 ? (
                    filteredPosts.map(post => (
                        <div
                            key={post.id}
                            className="bg-white p-4 rounded-lg shadow hover:shadow-lg transition"
                        >
                            <Link
                                href={route('posts.show', {
                                    workspace: workspace.id,
                                    post: post.id
                                })}
                                className="text-xl font-semibold text-blue-600 hover:text-blue-700"
                            >
                                {post.title}
                            </Link>

                            <p className="text-gray-600 text-sm mt-1">
                                By {post.author.name} • {post.published_at}
                            </p>

                            <div className="mt-3 flex items-center space-x-2">
                                <span className={`px-2 py-1 rounded text-xs font-semibold
                                    ${post.status === 'published'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800'
                                    }`}
                                >
                                    {post.status}
                                </span>

                                {can?.edit && (
                                    <Link
                                        href={route('posts.edit', {
                                            workspace: workspace.id,
                                            post: post.id
                                        })}
                                        className="text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </Link>
                                )}
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="text-center py-8 text-gray-500">
                        No posts found
                    </div>
                )}
            </div>

            {/* Pagination */}
            {posts.last_page > 1 && (
                <div className="mt-8 flex justify-center space-x-2">
                    {[...Array(posts.last_page)].map((_, i) => (
                        <Link
                            key={i + 1}
                            href={`?page=${i + 1}`}
                            className={`px-3 py-1 rounded ${
                                i + 1 === posts.current_page
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-200 hover:bg-gray-300'
                            }`}
                        >
                            {i + 1}
                        </Link>
                    ))}
                </div>
            )}
        </AppLayout>
    )
}
```

---

## 7. Patrón Module Loader + Routes

### 7.1 Cargar Rutas de Módulos

**Archivo**: `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// PUBLIC ROUTES (sin workspace)
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// AUTH ROUTES (identity module)
require __DIR__.'/identity-routes.php';

// WORKSPACE ROUTES (requieren autenticación)
Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas de cada módulo
    require __DIR__.'/publishing-routes.php';
    require __DIR__.'/audience-routes.php';
    require __DIR__.'/community-routes.php';
    require __DIR__.'/delivery-routes.php';
    require __DIR__.'/activity-routes.php';
});
```

**Nota**: Las rutas en `routes/web.php` **carga rutas de módulos**, pero esos archivos están en la misma carpeta `routes/`.

Alternativa: que cada módulo maneje sus propias rutas via ServiceProvider (patrón modular puro).

---

## 8. Autorización + Shared Data (Can Props)

Inertia permite pasar un `can` array con permiso booleanos:

```php
// En controlador
return Inertia::render('Posts/Index', [
    'posts' => $posts,
    'can' => [
        'create' => $request->user()->can('create', [Post::class, $workspace]),
        'edit' => $request->user()->can('update', [Post::class, $workspace]),
        'delete' => $request->user()->can('delete', [Post::class, $workspace]),
        'publish' => $request->user()->can('publish', [Post::class, $workspace]),
    ],
]);
```

En componente:

```jsx
export default function PostIndex({ posts, can }) {
    return (
        <>
            {can.create && (
                <button className="...">Create Post</button>
            )}
        </>
    )
}
```

---

## 9. Validation + Errors Workflow

### Laravel Side

```php
public function store(Request $request, Workspace $workspace)
{
    // Validación automáticamente redirige con errores
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    // Si validación pasa...
    $post = $workspace->posts()->create($validated);

    return redirect()->route('posts.show', $post)
        ->with('success', 'Post created!');
}
```

### React Side

```jsx
import { useForm } from '@inertiajs/react'

export default function CreatePost({ workspace }) {
    const { data, setData, post, errors, processing } = useForm({
        title: '',
        content: '',
    })

    const submit = (e) => {
        e.preventDefault()
        post(`/workspace/${workspace.id}/posts`)
    }

    return (
        <form onSubmit={submit} className="space-y-4">
            {/* Title */}
            <div>
                <label className="block text-sm font-medium mb-1">
                    Title
                </label>
                <input
                    type="text"
                    value={data.title}
                    onChange={e => setData('title', e.target.value)}
                    className={`w-full px-3 py-2 border rounded ${
                        errors.title ? 'border-red-500' : 'border-gray-300'
                    }`}
                />
                {errors.title && (
                    <p className="text-red-600 text-sm mt-1">
                        {errors.title}
                    </p>
                )}
            </div>

            {/* Content */}
            <div>
                <label className="block text-sm font-medium mb-1">
                    Content
                </label>
                <textarea
                    value={data.content}
                    onChange={e => setData('content', e.target.value)}
                    className={`w-full px-3 py-2 border rounded ${
                        errors.content ? 'border-red-500' : 'border-gray-300'
                    }`}
                />
                {errors.content && (
                    <p className="text-red-600 text-sm mt-1">
                        {errors.content}
                    </p>
                )}
            </div>

            {/* Submit */}
            <button
                type="submit"
                disabled={processing}
                className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
            >
                {processing ? 'Creating...' : 'Create Post'}
            </button>
        </form>
    )
}
```

**Automáticamente**:
- Validación falla → componente re-renderiza con `errors.title`, `errors.content`
- Sin refresh de página completa
- Form data se preserva

---

## 10. Checklist: Integración Modular Setup

### Pre-Implementación (Ahora)

- [ ] Entiendo que cada módulo tiene controladores que retornan `Inertia::render()`
- [ ] Entiendo la estructura `Pages/[Module]/[Feature]/[Component].jsx`
- [ ] Sé cómo shared data funciona en Freetter (auth + workspace)
- [ ] Entiendo que validación Laravel → React automáticamente

### Post-Quickstart (Después de 90 min)

- [ ] Setup genérico de Inertia completado
- [ ] Primer componente Welcome funciona
- [ ] HMR (hot reload) funciona
- [ ] Shared data accesible en componentes

### Inicio de Módulos (Semana 1)

- [ ] Identity controllers retornan Inertia (login, workspace)
- [ ] AppLayout creado
- [ ] GuestLayout creado
- [ ] Rutas de módulos actualizadas

### MVP Frontend (Semana 2-3)

- [ ] Publishing: Index, Create, Edit post
- [ ] Audience: Index subscribers
- [ ] Delivery: Index campaigns
- [ ] Community: Comment moderation
- [ ] Activity: Logs viewer

---

## 11. Ejemplo de Integración Modular Completa

### Paso a Paso: Cómo Freetter Pasa de Blade a Inertia

```
ANTES (Blade)
─────────────
routes/publishing-routes.php
    → Route::get('/posts', [PostController::class, 'index'])

PostController
    → return view('publishing::posts.index', $data)

resources/views/publishing/posts/index.blade.php
    → Blade template con HTML


DESPUÉS (Inertia)
──────────────────
routes/publishing-routes.php
    → (IGUAL, sin cambios)

PostController
    → return Inertia::render('Publishing/Posts/Index', $data)

resources/js/Pages/Publishing/Posts/Index.jsx
    → React component
```

**Cambio único**: El controlador retorna `Inertia::render()` en lugar de `view()`

Todo lo demás (rutas, lógica, modelos, eventos) **permanece idéntico**.

---

## 12. Próximos Pasos Después de Entender Esto

1. **Completa QUICKSTART.md** (90 min)
   - Setup genérico de Inertia

2. **Actualiza un controlador pequeño**
   - Ej: DashboardController (identity module)
   - Retorna `Inertia::render('Dashboard')`
   - Crea `resources/js/Pages/Dashboard.jsx`

3. **Crea AppLayout y GuestLayout**
   - Navegación persistente
   - Workspace context

4. **Migra módulos incrementalmente**
   - Identity (auth) → Publishing → Audience → etc.

---

**Última actualización**: 7 de abril de 2026
**Status**: LISTO PARA IMPLEMENTACIÓN MODULAR
