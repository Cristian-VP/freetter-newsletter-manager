# INERTIA.JS V3 QUICKSTART: IMPLEMENTACIÓN EN FREETTER

## ⚡ Inicio Rápido (90 minutos)

Este documento es un **step-by-step ejecutable** para tener Inertia.js funcionando en Freetter con tu primer componente React.

---

## Paso 1: Instalar Dependencias NPM (5 minutos)

```bash
cd /workspace

# Instalar paquetes Inertia + React
npm install @inertiajs/react @inertiajs/vite react react-dom
```

**Verificar**:
```bash
npm list @inertiajs/react @inertiajs/vite react
```

**Salida esperada**:
```
├── @inertiajs/react@3.0.0
├── @inertiajs/vite@3.0.0
├── react@18.3.1
└── react-dom@18.3.1
```

---

## Paso 2: Crear Root Template Blade (5 minutos)

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

**Verificar**: `ls -la resources/views/app.blade.php` debe existir

---

## Paso 3: Generar Middleware Inertia (5 minutos)

```bash
php artisan inertia:middleware
```

**Verificar**: `ls -la app/Http/Middleware/HandleInertiaRequests.php` debe existir

**Editar** `bootstrap/app.php`:

Busca esta sección:
```php
->withMiddleware(function (Middleware $middleware) {
    //
})
```

Y reemplázala con:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

**Verificar**: `grep -A 3 "HandleInertiaRequests" bootstrap/app.php` debe retornar el middleware

---

## Paso 4: Actualizar Vite Config (5 minutos)

**Archivo**: `vite.config.js`

Abre el archivo actual y reemplaza TODO el contenido con:

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

**Verificar**: `grep "inertia()" vite.config.js` debe retornar la línea

---

## Paso 5: Crear Entry Point React (5 minutos)

**Archivo**: `resources/js/app.jsx`

```jsx
import { createInertiaApp } from '@inertiajs/react'

createInertiaApp()
```

**Verificar**: `cat resources/js/app.jsx` debe mostrar exactamente esto

---

## Paso 6: Crear Primer Componente (5 minutos)

**Archivo**: `resources/js/Pages/Welcome.jsx`

```jsx
import { Head } from '@inertiajs/react'

export default function Welcome({ message }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="flex items-center justify-center min-h-screen bg-gray-100">
                <div className="text-center">
                    <h1 className="text-4xl font-bold mb-4 text-gray-900">
                        Welcome to Freetter
                    </h1>
                    <p className="text-xl text-gray-600 mb-8">
                        {message}
                    </p>
                    <p className="text-sm text-gray-500">
                        Built with Inertia.js v3 + React + Tailwind CSS
                    </p>
                </div>
            </div>
        </>
    )
}
```

**Verificar**: `ls -la resources/js/Pages/Welcome.jsx` debe existir

---

## Paso 7: Actualizar Ruta Principal (5 minutos)

**Archivo**: `routes/web.php`

Reemplaza el contenido actual con:

```php
<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'message' => 'This is your first Inertia component!',
    ]);
});

// Cargar rutas de módulos
require __DIR__.'/identity-routes.php';
require __DIR__.'/publishing-routes.php';
require __DIR__.'/audience-routes.php';
require __DIR__.'/community-routes.php';
require __DIR__.'/delivery-routes.php';
require __DIR__.'/activity-routes.php';
```

**Verificar**: `grep "Inertia::render" routes/web.php` debe retornar la línea

---

## Paso 8: Verificar Build (10 minutos)

```bash
npm run build
```

**Salida esperada**:
```
✓ built in 3.45s
```

**Si falla**: Ejecuta `npm run dev` en otra terminal para debug

---

## Paso 9: Iniciar Dev Server (10 minutos)

```bash
composer run dev
```

Esto inicia:
- ✅ Laravel dev server (puerto 8000)
- ✅ Vite dev server (con HMR)
- ✅ Redis para colas
- ✅ Laravel logs

**Espera a ver**:
```
APP_URL: http://127.0.0.1:8000
Vite is ready: http://127.0.0.1:5173
```

---

## Paso 10: Verificar en Navegador (5 minutos)

Abre: **http://localhost:8000**

**Esperado**:
- ✅ Ves "Welcome to Freetter"
- ✅ Ves "This is your first Inertia component!"
- ✅ Estilos de Tailwind aplicados (fondo gris)
- ✅ Sin errores en consola del navegador

**Si no funciona**:
1. Verifica que no hay errores en terminal de `composer run dev`
2. Abre DevTools (F12) → Console → busca errores rojo
3. Verifica que middleware está en `bootstrap/app.php`

---

## Paso 11: Hot Module Reload (HMR) (5 minutos)

Para verificar que el desarrollo es ágil:

1. Abre `resources/js/Pages/Welcome.jsx`
2. Cambia el texto:
   ```jsx
   <h1 className="text-4xl font-bold mb-4 text-blue-600">
       ¡Bienvenido a Freetter!
   </h1>
   ```
3. Guarda (Ctrl+S)
4. **El navegador se actualiza automáticamente sin refresco**

✅ Esto confirma que HMR funciona correctamente

---

## Paso 12: Setup de Shared Data (10 minutos)

Para que datos globales (como auth) estén disponibles en todos los componentes.

**Archivo**: `app/Http/Middleware/HandleInertiaRequests.php`

Reemplaza el método `share()`:

```php
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
```

**Verificar**: Navega a `/` y abre DevTools → Network → copia la primera request → Preview → verifica que ves `auth` en props

---

## Paso 13: Test Shared Data en Componente (10 minutos)

**Archivo**: `resources/js/Pages/Welcome.jsx`

Actualiza para acceder a shared data:

```jsx
import { Head, usePage } from '@inertiajs/react'

export default function Welcome({ message }) {
    const { auth } = usePage().props

    return (
        <>
            <Head title="Welcome" />
            <div className="flex items-center justify-center min-h-screen bg-gray-100">
                <div className="text-center">
                    <h1 className="text-4xl font-bold mb-4 text-gray-900">
                        Welcome to Freetter
                    </h1>

                    {auth.user && (
                        <p className="text-lg text-gray-600 mb-4">
                            Logged in as: <strong>{auth.user.name}</strong>
                        </p>
                    )}

                    {!auth.user && (
                        <p className="text-lg text-gray-600 mb-4">
                            You are not logged in
                        </p>
                    )}

                    <p className="text-sm text-gray-500">
                        Built with Inertia.js v3 + React + Tailwind CSS
                    </p>
                </div>
            </div>
        </>
    )
}
```

Guarda y verifica que aparece "You are not logged in"

---

## ✅ CHECKLIST: Ya Tienes una App Inertia Funcional

- [x] Dependencias npm instaladas
- [x] Root template `app.blade.php` creado
- [x] Middleware Inertia generado y registrado
- [x] `vite.config.js` configurado
- [x] Entry point `app.jsx` creado
- [x] Primer componente `Welcome.jsx` creado
- [x] Ruta `/` retorna `Inertia::render()`
- [x] Dev server se inicia sin errores
- [x] Componente es visible en navegador
- [x] HMR (hot reload) funciona
- [x] Shared data funciona

**Tiempo total que debería haber tardado**: 60-90 minutos

---

## 🚀 Próximos Pasos (Después de Este Quickstart)

### Paso 14: Crear Layout Base

```bash
# Crea resources/js/Layouts/AppLayout.jsx
# Con navbar, sidebar, etc.
```

### Paso 15: Crear Componentes de Identity

```bash
# resources/js/Pages/Identity/Auth/Login.jsx
# resources/js/Pages/Identity/Workspace/Index.jsx
# Etc.
```

### Paso 16: Integrar Módulos

```bash
# Actualizar controladores en app-modules/*/src/Http/Controllers
# Para usar Inertia::render() en lugar de Blade
```

### Paso 17: Agregar Forms

```bash
# Usar useForm() en componentes para submit
```

### Paso 18: Testing

```bash
# Tests de integración con Laravel
# Tests de componentes React
```

---

## 🆘 Troubleshooting Rápido

| Problema | Solución |
|----------|----------|
| "Unable to locate file in Vite manifest" | Ejecuta `npm run build` o `npm run dev` |
| Componente no renderiza | Verifica que está en `resources/js/Pages/` |
| HMR no funciona | Reinicia `npm run dev` |
| Middleware no funciona | Verifica `bootstrap/app.php` tiene `HandleInertiaRequests` |
| Props no llegan al componente | Verifica que controller pasa props en segundo argumento de `Inertia::render()` |
| Tailwind no applica | Verifica que `@vite(['..., resources/css/app.css'])` está en `app.blade.php` |
| Error "Cannot find module '@inertiajs/react'" | Ejecuta `npm install` nuevamente |

---

## 📚 Referencias Rápidas

- Documentación oficial: https://inertiajs.com/docs/v3
- Guía completa: `/workspace/.context/INERTIA_IMPLEMENTATION_GUIDE.md`
- Mapa de estudio: `/workspace/.context/INERTIA_STUDY_MAP.md`
- Proyecto Freetter: `/workspace/.context/PROJECT_ARCHITECTURE.md`

---

**Status**: ✅ LISTO PARA IMPLEMENTACIÓN

Si no tienes ningun error después del paso 13, **tu setup de Inertia.js es completamente funcional**.

Ahora puedes:
1. Leer la documentación oficial según el mapa en `INERTIA_STUDY_MAP.md`
2. Crear más componentes siguiendo los patrones en `INERTIA_IMPLEMENTATION_GUIDE.md`
3. Integrar con tus módulos Laravel modular

¡Felicidades! 🎉
