# INERTIA.JS V3 QUICKSTART: FREETTER MODULAR

## Objetivo

Validar el arranque Inertia con la estructura modular real del proyecto, sin asumir rutas o archivos genéricos que no existen aquí.

## Estado esperado en Freetter

- `resources/views/app.blade.php` como root template Inertia.
- `resources/js/app.tsx` como entrada React.
- `app/Http/Middleware/HandleInertiaRequests.php` registrado en `bootstrap/app.php`.
- Rutas de módulo en `app-modules/*/routes/web.php` cargadas desde sus Service Providers.
- Páginas React resueltas con la convención `module::page`.

## Verificación rápida

### 1. Dependencias instaladas

- `@inertiajs/react`
- `@inertiajs/vite`
- `react`
- `react-dom`

### 2. Root template correcto

Comprueba que [resources/views/app.blade.php](resources/views/app.blade.php) usa:

- `@viteReactRefresh`
- `@inertiaHead`
- `@inertia`
- carga condicional de páginas del módulo con `@vite([... "app-modules/.../resources/js/pages/...tsx"])`

### 3. Middleware compartido

Comprueba que [bootstrap/app.php](bootstrap/app.php) registra:

- `HandleInertiaRequests::class`
- `AddLinkHeadersForPreloadedAssets::class`

### 4. Entrada React

Comprueba que [resources/js/app.tsx](resources/js/app.tsx) hace:

- `createInertiaApp()`
- `resolvePageComponent()`
- soporte para componentes `module::page`

### 5. Ruta raíz

La ruta principal debe devolver una página Inertia real o una página del flujo actual del proyecto, no Blade genérico.

## Flujo recomendado

1. Verificar el arranque con `npm run build`.
2. Levantar el entorno de desarrollo.
3. Abrir la página inicial y confirmar que Inertia resuelve la vista correcta.
4. Probar una ruta de módulo ya existente.

## Páginas de ejemplo a revisar

- autenticación/identidad si ya existe pantalla
- páginas de workspace
- cualquier página de módulo que ya esté conectada a rutas reales

## Lo que no debes copiar de una plantilla genérica

- `resources/js/app.jsx` si el proyecto ya usa `app.tsx`
- `resources/js/Pages/` si el proyecto resuelve módulos desde `app-modules/*/resources/js/pages`
- `x-inertia::app` o `x-inertia::head` si el root template real usa `@inertia` y `@inertiaHead`

## Resultado buscado

Si el quickstart está bien, el proyecto arranca con Inertia sin perder su estructura modular y sin introducir rutas o carpetas que no pertenecen al diseño actual.

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
