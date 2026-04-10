# INERTIA.JS V3 EN FREETTER

## Punto de entrada

Freetter integra **Inertia.js v3** como capa de presentación en un **monolito modular Laravel 12**. Inertia convierte controladores en productores de componentes React en lugar de Blade views, manteniendo la arquitectura modular sin introducir API separada.

**¿Qué es Inertia?** Una alternativa moderna a Blade que permite escribir UI con React mientras el servidor sigue controlando las rutas y la navegación. No es una API REST—es una evolución de la arquitectura servidor-dirigida.

## Navega por tu necesidad

### 👉 Empezar rápido
**Lee [INERTIA_QUICKSTART.md](INERTIA_QUICKSTART.md)** si necesitas validar que tu ambiente está listo:
- Verifica que Inertia Laravel/React esté instalado
- Revisa que middleware HandleInertiaRequests esté registrado
- Comprueba que rutas carguen desde Service Providers
- Confirma que vite.config.js está correcto

### 👉 Crear una nueva página en tu módulo
**Lee [INERTIA_MODULAR_INTEGRATION.md](INERTIA_MODULAR_INTEGRATION.md)** para entender:
- Dónde viven las páginas React por módulo
- Cómo nombrarlas con convención `module::page`
- Cómo conectar rutas → controladores → componentes
- Dónde compartir estado global

### 👉 Aprender Inertia en profundidad
**Consulta las docs oficiales:**
- [Inertia.js docs](https://inertiajs.com) — Full reference
- [Inertia React adapter](https://inertiajs.com/client-side-setup) — React patterns
- [Inertia Laravel adapter](https://inertiajs.com/server-side-setup) — Laravel integration

### 👉 Ver cómo se hizo la integración
**Lee [INERTIA_FILE_CHANGES.md](INERTIA_FILE_CHANGES.md)** para referencia de:
- Archivos que se crearon
- Archivos que se modificaron
- Dependencias que se agregaron

## Principios de Freetter + Inertia

| Aspecto | Regla |
|---------|-------|
| **Lógica de dominio** | Permanece en su módulo (Models, Services, Events) |
| **Rutas** | Se cargan desde `app-modules/*/routes/web.php` via Service Provider |
| **Páginas React** | Viven en `app-modules/<module>/resources/js/pages/` |
| **Estado global** | Se define en `app/Http/Middleware/HandleInertiaRequests.php` |
| **Controladores** | Devuelven `Inertia::render('module::page', [props])` en lugar de views |
| **No cambia** | Modelos, migraciones, listeners, ownership de contextos |

## Stack de versiones

- **PHP** 8.4
- **Laravel** 12
- **Inertia Laravel** v3
- **React** 19
- **Vite** 7
- **Tailwind** v4

## Archivos clave del proyecto

- Template root: `/workspace/resources/views/app.blade.php`
- Entrada React: `/workspace/resources/js/app.tsx`
- Middleware: `/workspace/app/Http/Middleware/HandleInertiaRequests.php`
- Bootstrap: `/workspace/bootstrap/app.php`
- Configuración Vite: `/workspace/vite.config.js`
- Módulo ejemplo: `/workspace/app-modules/identity/`
```

---

## 📞 Recursos Principales

### Mi Documentación
- `/workspace/.context/INERTIA_IMPLEMENTATION_GUIDE.md` ← Comprensión
- `/workspace/.context/INERTIA_QUICKSTART.md` ← Implementación
- `/workspace/.context/INERTIA_STUDY_MAP.md` ← Aprendizaje
- `/workspace/.context/INERTIA_MODULAR_INTEGRATION.md` ← Módulos
- `/workspace/.context/INERTIA_FILE_CHANGES.md` ← Referencia

### Documentación Oficial Inertia
- [Inertia.js v3 Docs](https://inertiajs.com/docs/v3/)
- [Laravel Adapter](https://inertiajs.com/docs/v3/installation/server-side-setup)
- [React Adapter](https://inertiajs.com/docs/v3/installation/client-side-setup)

### Documentación Freetter Existente
- `/workspace/.context/PROJECT_ARCHITECTURE.md` ← Tu arq
- `/workspace/.context/CURRENT_STATE.md` ← Estado backend
- `/workspace/AGENTS.md` ← Normas de desarrollo

---

## 🎯 Tu Próximo Paso

**Ahora mismo**:
1. Abre `/workspace/.context/INERTIA_QUICKSTART.md`
2. Sigue los 13 pasos
3. En 90 minutos tendrás Inertia funcionando

**Después**:
1. Lees STUDY_MAP para aprender Inertia correctamente
2. Creas componentes por módulo
3. Integras uno a uno según el roadmap

---

## 💡 Mentalidad de Implementación

```
Tu Backend Modular
         ↓
    (sin cambios)
         ↓
Controladores retornan JSON (Inertia)
         ↓
         ↓
   React Components reciben props
         ↓
    User sees SPA without API
```

**Ventaja**: Mejor DX que Blade, mejor que API REST puro. Lo mejor de ambos.

---

**Última actualización**: 7 de abril de 2026
**Suite de documentación**: ✅ Completa y lista
**Tu proyecto**: ✅ Listo para Inertia

¿Preguntas antes de comenzar con QUICKSTART?
