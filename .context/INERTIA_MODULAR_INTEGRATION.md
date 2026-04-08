# INERTIA.JS MODULAR INTEGRATION GUIDE FOR FREETTER

## Propósito

Esta guía explica cómo aplicar Inertia en el monolito modular de Freetter sin romper los límites de dominio.

## Regla principal

Inertia cambia la capa de presentación, no el dominio.

### Permanece igual

- modelos
- migraciones
- eventos y listeners
- reglas de negocio de cada módulo
- rutas y ownership de cada bounded context

### Cambia

- los controladores retornan `Inertia::render()`
- las páginas pasan a React
- el estado compartido se define en `HandleInertiaRequests`

## Estructura real del proyecto

Tu instalación actual ya usa esta forma:

- `app-modules/<modulo>/src/Http/Controllers/`
- `app-modules/<modulo>/routes/web.php`
- `app-modules/<modulo>/src/Providers/*ServiceProvider.php`
- `app-modules/<modulo>/resources/js/pages/`

## Convención de resolución

Freetter ya no depende de una única carpeta global `resources/js/Pages` para todo.

La convención práctica es:

- páginas globales: `resources/js/pages/*.tsx`
- páginas de módulo: `app-modules/<modulo>/resources/js/pages/*.tsx`
- rutas Inertia con nombre lógico de componente: `Modulo/Subruta/Pagina`

## Ejemplo de flujo

1. La ruta del módulo vive en `app-modules/<modulo>/routes/web.php`.
2. El Service Provider del módulo la carga con `loadRoutesFrom()`.
3. El controlador del módulo prepara los datos.
4. El controlador devuelve `Inertia::render()`.
5. `resources/views/app.blade.php` carga la entrada React correcta.
6. `resources/js/app.tsx` resuelve el componente apropiado.

## Shared data recomendada

El middleware Inertia debe compartir solo datos realmente globales:

- `auth.user`
- información de navegación global si aplica
- flashes de sesión

Los datos de workspace o módulo deben compartirse solo si son transversales y estables.

## Qué hacer por módulo

### Identity

- pantallas de autenticación y workspace
- controladores que devuelven páginas Inertia
- contexto de usuario y pertenencia

### Publishing

- índices, formularios y detalle de posts
- estado de publicación y edición

### Audience

- suscriptores, importación y estados de consentimiento

### Community

- comentarios, likes y moderación

### Delivery

- campañas, envío y estado de entrega

### Activity

- logs, stream y alertas de auditoría

## Reglas de naming

- usa nombres estables y legibles para páginas
- el módulo debe ser visible en la ruta del componente
- evita nombres genéricos como `Index` cuando no haya contexto

## Validación recomendada

- una ruta por módulo debe renderizar una página Inertia real
- los controladores no deben mezclar Blade e Inertia en el mismo flujo
- el root template debe ser único y consistente

## Referencia cruzada

- [INERTIA_IMPLEMENTATION_GUIDE.md](INERTIA_IMPLEMENTATION_GUIDE.md)
- [INERTIA_QUICKSTART.md](INERTIA_QUICKSTART.md)
- [INERTIA_FILE_CHANGES.md](INERTIA_FILE_CHANGES.md)

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
