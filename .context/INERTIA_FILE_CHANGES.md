# FREETTER INERTIA.JS V3: ARCHIVO CHANGES + STRUCTURE

## 🎯 Propósito

Este documento muestra **EXACTAMENTE qué archivos cambiarán y cuáles se crearán** cuando integres Inertia.js en tu arquitectura modular.

Después del setup, tu proyecto lucirá así:

---

## FASE 1: Archivos Nuevos (QUICKSTART)

### Dependencias NPM
```
package.json
+ "dependencies": {
+   "@inertiajs/react": "^3.0.0",
+   "@inertiajs/vite": "^3.0.0",
+   "react": "^18.3.1",
+   "react-dom": "^18.3.1"
+ }
```

### Archivos Creados

```
✅ resources/views/app.blade.php           ← ROOT TEMPLATE (nuevo)
✅ resources/js/app.jsx                    ← ENTRY POINT (cambiar de .js a .jsx)
✅ app/Http/Middleware/HandleInertiaRequests.php  ← MIDDLEWARE (nuevo)
✅ resources/js/Pages/Welcome.jsx          ← PRIMER COMPONENTE (nuevo)
✅ resources/js/Layouts/AppLayout.jsx      ← LAYOUT BASE (nuevo)
✅ resources/js/Layouts/GuestLayout.jsx    ← LAYOUT AUTH (nuevo)
```

### Archivos Modificados

```
📝 vite.config.js                          ← Agregar inertia plugin + react
📝 bootstrap/app.php                       ← Registrar middleware
📝 routes/web.php                          ← Cambiar GET / a Inertia::render()
```

---

## FASE 2: Estructura Final de Directorios (Después de Setup)

### Antes (Blade)
```
resources/
├─ views/
│  ├─ welcome.blade.php
│  └─ ... (otras blade templates)
├─ js/
│  ├─ app.js
│  └─ bootstrap.js
└─ css/
   └─ app.css
```

### Después (Inertia + React)
```
resources/
├─ views/
│  ├─ app.blade.php                  ✅ NUEVO (root template)
│  └─ welcome.blade.php              ❌ REMOVIDO (reemplazado por React)
│
├─ js/
│  ├─ app.jsx                        📝 CAMBIÓ (input de Vite ahora .jsx)
│  ├─ bootstrap.js                   ✅ (sin cambios)
│  │
│  ├─ Layouts/                       ✅ NUEVO
│  │  ├─ AppLayout.jsx
│  │  ├─ GuestLayout.jsx
│  │  └─ WorkspaceLayout.jsx         (opcional, post-MVP)
│  │
│  ├─ Pages/                         ✅ NUEVO
│  │  ├─ Welcome.jsx
│  │  │
│  │  ├─ Identity/
│  │  │  ├─ Auth/
│  │  │  │  ├─ Login.jsx
│  │  │  │  ├─ Register.jsx
│  │  │  │  └─ MagicLink.jsx
│  │  │  └─ Workspace/
│  │  │     ├─ Index.jsx
│  │  │     ├─ Show.jsx
│  │  │     ├─ Create.jsx
│  │  │     └─ Members.jsx
│  │  │
│  │  ├─ Publishing/
│  │  │  ├─ Posts/
│  │  │  │  ├─ Index.jsx
│  │  │  │  ├─ Create.jsx
│  │  │  │  ├─ Edit.jsx
│  │  │  │  └─ Show.jsx
│  │  │  └─ Tags/
│  │  │     └─ Index.jsx
│  │  │
│  │  ├─ Audience/
│  │  │  ├─ Subscribers/
│  │  │  │  ├─ Index.jsx
│  │  │  │  └─ Import.jsx
│  │  │  └─ Segments.jsx
│  │  │
│  │  ├─ Community/
│  │  │  └─ Comments/
│  │  │     └─ Moderate.jsx
│  │  │
│  │  ├─ Delivery/
│  │  │  ├─ Campaigns/
│  │  │  │  ├─ Index.jsx
│  │  │  │  ├─ Create.jsx
│  │  │  │  └─ Show.jsx
│  │  │  └─ Analytics.jsx
│  │  │
│  │  ├─ Activity/
│  │  │  └─ Logs.jsx
│  │  │
│  │  └─ Errors/
│  │     ├─ 404.jsx
│  │     ├─ 500.jsx
│  │     └─ 403.jsx
│  │
│  ├─ Components/                    ✅ NUEVO (compartidos)
│  │  ├─ Button.jsx
│  │  ├─ Modal.jsx
│  │  ├─ Form/
│  │  │  ├─ Input.jsx
│  │  │  ├─ Select.jsx
│  │  │  ├─ Textarea.jsx
│  │  │  └─ FileInput.jsx
│  │  ├─ Alerts/
│  │  │  ├─ Success.jsx
│  │  │  ├─ Error.jsx
│  │  │  └─ Info.jsx
│  │  └─ Icons/
│  │     ├─ LogoIcon.jsx
│  │     └─ ... (otros)
│  │
│  └─ __tests__/                    (opcional, React tests)
│     └─ ...
│
└─ css/
   └─ app.css                       ✅ (sin cambios)
```

---

## FASE 3: Cambios en Controladores de Módulos

### Identity Module - Ejemplo

#### ANTES (Blade)
```php
// app-modules/identity/src/Http/Controllers/AuthController.php

class AuthController {
    public function showLogin() {
        return view('auth.login');  // ❌ Retorna Blade
    }
}
```

#### DESPUÉS (Inertia)
```php
// app-modules/identity/src/Http/Controllers/AuthController.php

use Inertia\Inertia;

class AuthController {
    public function showLogin() {
        return Inertia::render('Identity/Auth/Login');  // ✅ Retorna React
    }
}
```

### Publishing Module - Ejemplo

#### ANTES (Blade)
```php
// app-modules/publishing/src/Http/Controllers/PostController.php

class PostController {
    public function index(Workspace $workspace) {
        $posts = $workspace->posts()->paginate();
        return view('publishing::posts.index', ['posts' => $posts]);
    }
}
```

#### DESPUÉS (Inertia)
```php
// app-modules/publishing/src/Http/Controllers/PostController.php

use Inertia\Inertia;

class PostController {
    public function index(Workspace $workspace) {
        $posts = $workspace->posts()
            ->with('author', 'tags')
            ->paginate();

        return Inertia::render('Publishing/Posts/Index', [
            'posts' => $posts,
            'can' => [
                'create' => auth()->user()->can('create', Post::class),
            ],
        ]);
    }
}
```

---

## FASE 4: Cambios en Rutas de Módulos

### Identity Routes - ANTES vs DESPUÉS

#### ANTES
```php
// routes/identity-routes.php
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
// Retornan view() en controlador
```

#### DESPUÉS
```php
// routes/identity-routes.php
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
// Retornan Inertia::render() en controlador
// ❌ NO SE CAMBIAN las rutas, solo lo que retornan los controladores
```

---

## FASE 5: Cambios en Config + Middleware

### vite.config.js

#### ANTES
```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import { tailwindPlugin } from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        tailwindPlugin(),
    ],
})
```

#### DESPUÉS
```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import inertia from '@inertiajs/vite'
import { tailwindPlugin } from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.jsx'],  // ✅ .jsx
            refresh: true,
        }),
        react(),                             // ✅ NUEVO
        inertia(),                          // ✅ NUEVO
        tailwindPlugin(),
    ],
})
```

### bootstrap/app.php

#### ANTES
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sin middleware personalizado
    })
    ->withExceptionHandling()
    ->create();
```

#### DESPUÉS
```php
use App\Http\Middleware\HandleInertiaRequests;  // ✅ NUEVO import

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,  // ✅ NUEVO
        ]);
    })
    ->withExceptionHandling()
    ->create();
```

### routes/web.php

#### ANTES
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/identity-routes.php';
require __DIR__.'/publishing-routes.php';
// ...
```

#### DESPUÉS
```php
<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Welcome');  // ✅ Inertia en lugar de view
});

require __DIR__.'/identity-routes.php';
require __DIR__.'/publishing-routes.php';
// ... (idéntico)
```

---

## FASE 6: Archivos Creados en Detail

### 1. resources/views/app.blade.php (NUEVO)
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

### 2. resources/js/app.jsx (NUEVO/MODIFICADO)
```jsx
import { createInertiaApp } from '@inertiajs/react'

createInertiaApp()
```

### 3. app/Http/Middleware/HandleInertiaRequests.php (NUEVO)
```php
<?php

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
            'auth' => [
                'user' => $request->user(),
            ],
            'workspace' => $this->currentWorkspace($request),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }

    protected function currentWorkspace(Request $request)
    {
        $workspace = $request->route('workspace');
        if (!$workspace) return null;

        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'role' => auth()->user()?->roles($workspace)->first()?->name,
        ];
    }
}
```

### 4. resources/js/Pages/Welcome.jsx (NUEVO)
```jsx
import { Head } from '@inertiajs/react'

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <div className="flex items-center justify-center min-h-screen bg-gray-100">
                <div className="text-center">
                    <h1 className="text-4xl font-bold mb-4">Welcome to Freetter</h1>
                    <p className="text-xl text-gray-600">Built with Inertia.js v3</p>
                </div>
            </div>
        </>
    )
}
```

### 5. resources/js/Layouts/AppLayout.jsx (NUEVO)
```jsx
import { Link, usePage } from '@inertiajs/react'

export default function AppLayout({ children }) {
    const { auth, workspace } = usePage().props

    return (
        <div className="flex h-screen">
            <sidebar className="w-64 bg-gray-900 text-white p-6">
                <h2 className="text-xl font-bold mb-6">{workspace?.name}</h2>
                <nav className="space-y-4">
                    <Link href="/dashboard" className="block hover:text-gray-300">
                        Dashboard
                    </Link>
                    <Link href={`/workspace/${workspace?.id}/posts`} className="block hover:text-gray-300">
                        Posts
                    </Link>
                </nav>
            </sidebar>
            <main className="flex-1 overflow-y-auto">
                {children}
            </main>
        </div>
    )
}
```

### 6. resources/js/Layouts/GuestLayout.jsx (NUEVO)
```jsx
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

---

## FASE 7: Resumen de Cambios por Scope

### NPM Package.json
```
+ @inertiajs/react
+ @inertiajs/vite
+ react
+ react-dom
```

### Blade/PHP (Backend)
```
✅ NUEVO: app/Http/Middleware/HandleInertiaRequests.php
📝 CAMBIO: bootstrap/app.php (registrar middleware)
📝 CAMBIO: vite.config.js (agregar plugins Inertia + React)
📝 CAMBIO: resources/js/app.js → resources/js/app.jsx
📝 CAMBIO: routes/web.php (una línea)
✅ NUEVO: resources/views/app.blade.php
❌ REMOVIDO: resources/views/welcome.blade.php (opcional, puedes mantener)
```

### React/JavaScript (Frontend)
```
✅ NUEVO: resources/js/Pages/ (toda la carpeta)
✅ NUEVO: resources/js/Layouts/ (toda la carpeta)
✅ NUEVO: resources/js/Components/ (toda la carpeta - opcional)
```

### Controladores de Módulos
```
📝 CAMBIO: return view() → return Inertia::render()
✅ No hay cambios en rutas
✅ No hay cambios en modelos
✅ No hay cambios en eventos
```

---

## FASE 8: Checklist de Archivos

### After QUICKSTART (90 min)

- [ ] `npm install` ejecutado
- [ ] `package.json` actualizado (4 dependencias nuevas)
- [ ] `resources/views/app.blade.php` creado
- [ ] `resources/js/app.jsx` creado
- [ ] `app/Http/Middleware/HandleInertiaRequests.php` creado
- [ ] `bootstrap/app.php` actualizado (middleware registrado)
- [ ] `vite.config.js` actualizado (inertia + react plugins)
- [ ] `routes/web.php` actualizado (GET / usa Inertia)
- [ ] `resources/js/Pages/Welcome.jsx` creado
- [ ] `npm run build` funciona sin errores
- [ ] `http://localhost:8000` muestra Welcome

### After Layouts (2 horas)

- [ ] `resources/js/Layouts/AppLayout.jsx` creado
- [ ] `resources/js/Layouts/GuestLayout.jsx` creado
- [ ] Componentes usan layouts correctamente

### After Module 1 (Identity) (4-6 horas)

- [ ] `app-modules/identity/src/Http/Controllers/AuthController.php` actualizado
- [ ] `resources/js/Pages/Identity/Auth/Login.jsx` creado
- [ ] `resources/js/Pages/Identity/Auth/Register.jsx` creado
- [ ] `resources/js/Pages/Identity/Workspace/Index.jsx` creado
- [ ] Login/Register funciona sin Blade

---

## FASE 9: Orden de Actualización Recomendado

### Día 1: QUICKSTART (90 min)
```
1. npm install
2. Crear app.blade.php
3. Crear HandleInertiaRequests middleware
4. Actualizar bootstrap/app.php
5. Actualizar vite.config.js
6. Crear app.jsx
7. Crear Welcome.jsx
8. Actualizar routes/web.php GET /
9. Probar en navegador
```

### Día 2: Layouts + Shared Data (2 horas)
```
1. Crear AppLayout.jsx
2. Crear GuestLayout.jsx
3. Actualizar HandleInertiaRequests.share()
4. Crear componente Dashboard
5. Testar shared data (auth, workspace)
```

### Día 3+: Módulos (4-6 horas)
```
1. Módulo Identity:
   - AuthController → Inertia
   - Login, Register, MagicLink
   - Workspace Index/Show

2. Módulo Publishing:
   - PostController → Inertia
   - Posts Index, Create, Edit, Show

3. Módulo Audience:
   - SubscriberController → Inertia
   - Subscribers Index, Import

4. Etc...
```

---

## Ejemplo Visual: Un Controlador Transformado

### Identity Module - WorkspaceController

#### ANTES (Blade)
```
GET /workspace              ← RouteControlador
    ↓
WorkspaceController::index() ← view('workspace.index', $data)
    ↓
resources/views/workspace/index.blade.php ← Blade template
    ↓
Renderiza HTML en servidor ← Respuesta al navegador
```

#### DESPUÉS (Inertia)
```
GET /workspace              ← Ruta (igual)
    ↓
WorkspaceController::index() ← Inertia::render('Identity/Workspace/Index', $data)
    ↓
resources/js/Pages/Identity/Workspace/Index.jsx ← React component
    ↓
Renderiza JSON + React en cliente ← Respuesta al navegador
```

---

## ✅ Resumen Final

### Archivos Nuevos: ~12-15
```
app.blade.php
HandleInertiaRequests.php
app.jsx
Welcome.jsx
AppLayout.jsx
GuestLayout.jsx
+ Pages/* (según módulos, ~30+ componentes)
+ Components/* (compartidos)
```

### Archivos Modificados: ~5
```
vite.config.js
bootstrap/app.php
routes/web.php
package.json
app.js → app.jsx
```

### Archivos Sin Cambios: ~200+
```
app-modules/*/Models
app-modules/*/Events
app-modules/*/database
Tests
app-modules/*/routes (mismo archivo, mismo contenido)
```

**Cambio neto**: ~20 archivos nuevos/modificados, todo lo demás idéntico.

---

**Última actualización**: 7 de abril de 2026
**Status**: REFERENCIA COMPLETA DE CAMBIOS
