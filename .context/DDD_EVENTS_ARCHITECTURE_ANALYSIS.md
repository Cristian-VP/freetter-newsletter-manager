# 🏗️ ARQUITECTURA DDD: DESACOPLAMIENTO CON EVENTS & LISTENERS
## Análisis de Acoplamiento entre Dominios y Solución con Event-Driven Architecture

**Fecha de Creación:** 7 de febrero de 2026  
**Estado:** 🟡 En análisis - Base para reglas DDD  
**Propósito:** Documentar la arquitectura correcta para evitar acoplamiento entre dominios  
**Contexto:** Implementación del sistema de versionado de Posts con auditoría en Activity

---

## 📋 ÍNDICE

1. [El Problema Detectado](#el-problema-detectado)
2. [Principios DDD Violados](#principios-ddd-violados)
3. [Solución: Event-Driven Architecture](#solución-event-driven-architecture)
4. [Implementación Detallada](#implementación-detallada)
5. [Service Container y Dependency Injection](#service-container-y-dependency-injection)
6. [Flujo Completo de Ejecución](#flujo-completo-de-ejecución)
7. [Estructura de Archivos](#estructura-de-archivos)
8. [Testing con Desacoplamiento](#testing-con-desacoplamiento)
9. [Reglas DDD para Modelos y Factories](#reglas-ddd-para-modelos-y-factories)
10. [Checklist de Implementación](#checklist-de-implementación)
11. [Referencias y Próximos Pasos](#referencias-y-próximos-pasos)

---

## 1. El Problema Detectado {#el-problema-detectado}

### 1.1. Propuesta Inicial (INCORRECTA)

```php
// ❌ MAL: Publishing conoce directamente a Activity
namespace Domains\Publishing\Actions;

use Domains\Activity\Models\ActivityLog; // ← ACOPLAMIENTO DIRECTO

class CreatePostVersionAction
{
    public function handle(Post $post, ?User $user = null): PostVersion
    {
        // ... lógica de versionado
        
        // ❌ PROBLEMA: Publishing depende de Activity
        ActivityLog::record(
            action: 'post.versioned',
            entityType: 'post',
            entityId: $post->id,
            user: $user,
            metadata: [...]
        );
        
        return $version;
    }
}
```

### 1.2. ¿Por Qué Está Mal?

| Problema | Descripción | Impacto |
|---|---|---|
| **Acoplamiento Directo** | Publishing importa clases de Activity | Si Activity cambia, Publishing se rompe |
| **Violación DDD** | Un dominio no debe conocer otro dominio | Imposible reutilizar dominios independientemente |
| **Dependencia Obligatoria** | Publishing requiere Activity instalado | No puedes desactivar Activity sin romper Publishing |
| **Testing Complejo** | Tests de Publishing requieren mock de Activity | Suite de tests más lenta y frágil |
| **Falta de Flexibilidad** | Solo Activity puede reaccionar al evento | No puedes agregar notificaciones, analytics, etc. |

### 1.3. Observación Clave del Desarrollador

> "Un modelo de Publishing no debe saber nada de un modelo de Activity. 
> Entiendo que para que CreatePostVersionAction registre en Activity 
> no debe saber de ActivityLog, sino lanzar una solicitud a una clase X 
> que se encargaría de efectuar la conexión entre los dos dominios, 
> porque si paso Activity, aunque sea complementario, no debe violar el namespace."

**✅ CORRECTO:** Esta observación es fundamental para mantener la arquitectura DDD limpia.

---

## 2. Principios DDD Violados {#principios-ddd-violados}

### 2.1. Bounded Contexts

**Definición:** Cada dominio es un contexto delimitado con su propio modelo y lógica.

```
Bounded Context: Publishing
├─ Models: Post, PostVersion, Media, Tag
├─ Actions: CreatePostVersion, PublishPost
├─ Events: PostPublished, PostVersionCreated
└─ Responsabilidad: Gestión de contenido

Bounded Context: Activity
├─ Models: ActivityLog, ActivityStream, ActivityAlert
├─ Listeners: LogPostVersionCreated, LogPostPublished
└─ Responsabilidad: Auditoría y trazabilidad

❌ VIOLACIÓN: Publishing importa ActivityLog directamente
✅ CORRECTO: Publishing dispara eventos, Activity los escucha
```

### 2.2. Dependency Rule (Clean Architecture)

```
┌─────────────────────────────────────────┐
│         DIRECCIÓN DE DEPENDENCIAS        │
├─────────────────────────────────────────┤
│                                         │
│  ┌────────────┐     ┌────────────┐    │
│  │ Publishing │ ──X→ │  Activity  │    │  ❌ Acoplamiento directo
│  └────────────┘     └────────────┘    │
│                                         │
│  ┌────────────┐                        │
│  │ Publishing │ → Event                │  ✅ Publishing dispara evento
│  └────────────┘      ↓                 │
│                 EventBus                │
│                      ↓                  │
│                 ┌────────────┐         │
│                 │  Activity  │         │  ✅ Activity escucha evento
│                 └────────────┘         │
└─────────────────────────────────────────┘

Regla: Las dependencias deben apuntar hacia adentro (núcleo)
      Los dominios externos no pueden conocer dominios internos
```

### 2.3. Open/Closed Principle

**Definición:** Abierto para extensión, cerrado para modificación.

```php
// ❌ VIOLACIÓN: Si quieres agregar notificaciones, debes modificar CreatePostVersionAction
class CreatePostVersionAction {
    public function handle(...) {
        // Versionado
        ActivityLog::record(...);           // Auditoría
        Notification::send(...);            // Notificaciones ← Modificación
        Analytics::track(...);              // Analytics ← Modificación
    }
}

// ✅ CORRECTO: Agregar listeners sin modificar Publishing
class CreatePostVersionAction {
    public function handle(...) {
        // Versionado
        event(new PostVersionCreated(...)); // Un solo punto de extensión
    }
}

// Agregar en EventServiceProvider (sin tocar Publishing):
PostVersionCreated::class => [
    LogPostVersionCreated::class,      // Activity
    SendVersionNotification::class,    // Notificaciones ← Extensión
    TrackVersionAnalytics::class,      // Analytics ← Extensión
]
```

---

## 3. Solución: Event-Driven Architecture {#solución-event-driven-architecture}

### 3.1. Arquitectura Propuesta

```
┌──────────────────────────────────────────────────────────────┐
│                    EVENT-DRIVEN ARCHITECTURE                  │
└──────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│  Publishing Domain  │ (Productor de Eventos)
├─────────────────────┤
│ - Post              │
│ - PostVersion       │
│ - Actions           │
│   └─ CreateVersion  │ ──> event(PostVersionCreated)
│ - Events            │
│   └─ PostVersionCreated
└─────────────────────┘
         │
         │ Dispara evento (sin saber quién escucha)
         ▼
┌─────────────────────┐
│  Laravel Event Bus  │ (Mediador / Broker)
├─────────────────────┤
│ - EventServiceProvider
│ - Enruta eventos a listeners
│ - Desacoplamiento total
└─────────────────────┘
         │
         │ Distribuye a listeners registrados
         ├─────────────────────┬──────────────────────┐
         ▼                     ▼                      ▼
┌─────────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│  Activity Domain    │ │ Notifications    │ │ Analytics        │
├─────────────────────┤ ├──────────────────┤ ├──────────────────┤
│ - Listeners         │ │ - Listeners      │ │ - Listeners      │
│   └─ LogActivity    │ │   └─ SendEmail   │ │   └─ TrackEvent  │
└─────────────────────┘ └──────────────────┘ └──────────────────┘
```

### 3.2. Ventajas de Event-Driven

| Característica | Descripción | Beneficio |
|---|---|---|
| **Desacoplamiento** | Publishing no conoce Activity | Dominios independientes |
| **Escalabilidad** | Agregar listeners sin modificar código | Open/Closed Principle |
| **Testabilidad** | Mock del EventBus, no de dominios | Tests más rápidos |
| **Flexibilidad** | Listeners pueden ser síncronos o async | Performance optimizable |
| **Trazabilidad** | Eventos documentan qué pasó en el sistema | Debugging más fácil |
| **Reusabilidad** | Múltiples listeners para un mismo evento | DRY (Don't Repeat Yourself) |

### 3.3. Componentes del Sistema

```
1. EVENTS (Contratos)
   - Definen QUÉ pasó en el sistema
   - Son data transfer objects (DTOs)
   - Viven en el dominio que los produce
   - Ejemplo: PostVersionCreated

2. LISTENERS (Consumidores)
   - Reaccionan a eventos
   - Viven en el dominio que los consume
   - Pueden ser síncronos o en cola
   - Ejemplo: LogPostVersionCreated

3. EVENT SERVICE PROVIDER (Configuración)
   - Conecta eventos con listeners
   - Único punto de acoplamiento
   - Centraliza la configuración
```

---

## 4. Implementación Detallada {#implementación-detallada}

### 4.1. Event: PostVersionCreated

**Ubicación:** `app-modules/publishing/src/Events/PostVersionCreated.php`

```php
<?php

namespace Domains\Publishing\Events;

use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Se creó una versión de un post
 * 
 * PROPÓSITO:
 * - Notificar al sistema que se creó una nueva versión de un post
 * - Publishing NO conoce quién escucha (desacoplamiento total)
 * - Cualquier dominio puede reaccionar agregando un listener
 * 
 * DATOS QUE TRANSPORTA:
 * - Post: El post que fue versionado
 * - PostVersion: La versión creada (con version_number, content)
 * - userId: Quién causó la creación (para auditoría)
 * - reason: Por qué se creó (post_published, manual_save, etc.)
 * - context: Datos adicionales opcionales
 * 
 * CASOS DE USO:
 * - Activity: Registrar en ActivityLog
 * - Notifications: Notificar a colaboradores
 * - Analytics: Trackear frecuencia de versionado
 * - Search: Actualizar índice de búsqueda
 * 
 * ARQUITECTURA:
 * ┌─────────────┐
 * │ Publishing  │ → Dispara evento
 * └─────────────┘
 *       │
 *       ▼
 * ┌─────────────┐
 * │ EventBus    │ → Enruta a listeners
 * └─────────────┘
 *       │
 *       ├─→ Activity::LogPostVersionCreated
 *       ├─→ Notifications::SendVersionEmail
 *       └─→ Analytics::TrackVersionEvent
 */
class PostVersionCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Constructor del evento
     * 
     * @param Post $post El post que fue versionado
     * @param PostVersion $version La versión creada
     * @param string|null $userId ID del usuario que causó la versión
     * @param string $reason Razón de la versión (post_published, manual_save, etc.)
     * @param array $context Contexto adicional (workspace_id, content_blocks, etc.)
     */
    public function __construct(
        public Post $post,
        public PostVersion $version,
        public ?string $userId = null,
        public string $reason = 'manual',
        public array $context = []
    ) {}
}
```

**¿Por qué estos datos?**

```php
$event->post           // Acceso completo al post (title, slug, type, etc.)
$event->version        // Acceso a la versión (version_number, content, created_at)
$event->userId         // Para saber QUIÉN causó el evento (auditoría)
$event->reason         // Para saber POR QUÉ se creó (contexto de negocio)
$event->context        // Datos extras opcionales (flexibilidad futura)
```

**Traits utilizados:**

- `Dispatchable`: Permite usar `event(new PostVersionCreated(...))`
- `SerializesModels`: Serializa modelos para colas (si listener es async)

---

### 4.2. Action: CreatePostVersionAction (Modificada)

**Ubicación:** `app-modules/publishing/src/Actions/CreatePostVersionAction.php`

```php
<?php

namespace Domains\Publishing\Actions;

use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Domains\Publishing\Events\PostVersionCreated; // ← Solo conoce SU evento

/**
 * Action: Crear una versión inmutable de un post
 * 
 * RESPONSABILIDADES:
 * ✅ Calcular siguiente version_number
 * ✅ Crear snapshot en PostVersion
 * ✅ Disparar evento PostVersionCreated
 * ❌ NO conoce Activity (desacoplado)
 * ❌ NO conoce Notifications (desacoplado)
 * 
 * ARQUITECTURA DDD:
 * - Esta clase pertenece al dominio Publishing
 * - Solo conoce entidades de Publishing (Post, PostVersion)
 * - Comunica con otros dominios vía eventos
 * - Testeable sin dependencias externas
 * 
 * USO:
 * ```php
 * $version = (new CreatePostVersionAction())->handle(
 *     post: $post,
 *     userId: auth()->id(),
 *     reason: 'post_published'
 * );
 * ```
 */
class CreatePostVersionAction
{
    /**
     * Ejecutar la acción
     * 
     * @param Post $post El post del que se crea versión
     * @param string|null $userId ID del usuario que triggered (para audit)
     * @param string $reason Razón de la versión (post_published, manual_save, etc.)
     * @return PostVersion La versión creada
     */
    public function handle(
        Post $post,
        ?string $userId = null,
        string $reason = 'post_published'
    ): PostVersion {
        // 1️⃣ Calcular siguiente version_number
        $nextVersionNumber = $this->getNextVersionNumber($post);

        // 2️⃣ Crear snapshot (inmutable para siempre)
        $version = $post->versions()->create([
            'content' => $post->content,
            'version_number' => $nextVersionNumber,
        ]);

        // 3️⃣ DISPARAR EVENTO (sin saber quién escucha)
        // ✅ Publishing NO conoce Activity
        // ✅ EventBus enrutará a todos los listeners registrados
        // ✅ Si no hay listeners, no pasa nada (graceful degradation)
        event(new PostVersionCreated(
            post: $post,
            version: $version,
            userId: $userId,
            reason: $reason,
            context: [
                'content_blocks' => count($version->content['blocks'] ?? []),
                'workspace_id' => $post->workspace_id,
            ]
        ));

        return $version;
    }

    /**
     * Calcular el siguiente número de versión
     * 
     * Regla: tomar el max(version_number) + 1
     * Si no hay versiones, comenzar en 1
     * 
     * Ejemplo:
     * - Post sin versiones → version_number = 1
     * - Post con version_number = 3 → siguiente = 4
     */
    private function getNextVersionNumber(Post $post): int
    {
        $lastVersion = $post->versions()
            ->orderByDesc('version_number')
            ->first();

        return ($lastVersion?->version_number ?? 0) + 1;
    }
}
```

**Cambios respecto a la versión inicial:**

```diff
- use Domains\Activity\Models\ActivityLog; // ❌ Eliminado
+ use Domains\Publishing\Events\PostVersionCreated; // ✅ Solo evento

- ActivityLog::record(...); // ❌ Eliminado
+ event(new PostVersionCreated(...)); // ✅ Evento
```

---

### 4.3. Event: PostPublished

**Ubicación:** `app-modules/publishing/src/Events/PostPublished.php`

```php
<?php

namespace Domains\Publishing\Events;

use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Un post fue publicado
 * 
 * PROPÓSITO:
 * - Notificar que un post cambió de draft/scheduled a published
 * - Permite múltiples reacciones (auditoría, notificaciones, analytics)
 * 
 * DATOS:
 * - Post: El post publicado
 * - userId: Quién lo publicó
 * - versionNumber: Qué versión se creó
 */
class PostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post,
        public ?string $userId = null,
        public int $versionNumber = 1
    ) {}
}
```

---

### 4.4. Event: PostCreated

**Ubicación:** `app-modules/publishing/src/Events/PostCreated.php`

```php
<?php

namespace Domains\Publishing\Events;

use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Se creó un nuevo post (siempre en draft)
 */
class PostCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post,
        public ?string $userId = null
    ) {}
}
```

---

### 4.5. Event: PostDeleted

**Ubicación:** `app-modules/publishing/src/Events/PostDeleted.php`

```php
<?php

namespace Domains\Publishing\Events;

use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Un post fue eliminado
 * 
 * NOTA: Este evento se dispara antes del delete real
 *       para que listeners puedan acceder a los datos
 */
class PostDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post,
        public ?string $userId = null,
        public int $versionsCount = 0
    ) {}
}
```

---

### 4.6. Listener: LogPostVersionCreated (Activity Domain)

**Ubicación:** `app-modules/activity/src/Listeners/LogPostVersionCreated.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Publishing\Events\PostVersionCreated; // ← Activity conoce el evento
use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener: Registrar en Activity cuando se crea versión
 * 
 * PROPÓSITO:
 * - Escuchar PostVersionCreated (de Publishing)
 * - Registrar en ActivityLog (de Activity)
 * - Sin acoplar dominios
 * 
 * UBICACIÓN: Activity Domain (no Publishing)
 * ¿POR QUÉ AQUÍ?: Activity es quien REGISTRA, no quien PRODUCE el evento
 * 
 * FLUJO:
 * 1. Publishing dispara PostVersionCreated
 * 2. EventBus enruta a este listener
 * 3. Este listener guarda en activity_logs
 * 
 * OPCIONES:
 * - Implementar ShouldQueue para procesamiento async
 * - En este caso: síncrono (queremos auditoría inmediata)
 */
class LogPostVersionCreated
{
    /**
     * Handle el evento
     * 
     * @param PostVersionCreated $event El evento de Publishing
     */
    public function handle(PostVersionCreated $event): void
    {
        // ✅ Activity conoce sus propios modelos
        // ✅ Activity conoce el evento público de Publishing
        // ❌ Activity NO conoce las clases internas de Publishing
        
        ActivityLog::record(
            action: 'post.versioned',
            entityType: 'post',
            entityId: $event->post->id,
            user: $event->userId ? User::find($event->userId) : null,
            metadata: [
                'version_number' => $event->version->version_number,
                'reason' => $event->reason,
                'content_blocks' => $event->context['content_blocks'] ?? null,
                'workspace_id' => $event->context['workspace_id'] ?? null,
                'post_title' => $event->post->title,
                'post_type' => $event->post->type,
            ]
        );
    }
}
```

**¿Por qué aquí y no en Publishing?**

```
❌ Si está en Publishing:
   - Publishing conoce ActivityLog
   - Acoplamiento directo
   - Violación DDD

✅ Si está en Activity:
   - Activity escucha eventos públicos
   - Publishing no sabe que existe Activity
   - Desacoplamiento total
```

---

### 4.7. Listener: LogPostPublished

**Ubicación:** `app-modules/activity/src/Listeners/LogPostPublished.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Publishing\Events\PostPublished;
use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;

class LogPostPublished
{
    public function handle(PostPublished $event): void
    {
        ActivityLog::record(
            action: 'post.published',
            entityType: 'post',
            entityId: $event->post->id,
            user: $event->userId ? User::find($event->userId) : null,
            metadata: [
                'title' => $event->post->title,
                'type' => $event->post->type,
                'version_number' => $event->versionNumber,
                'published_at' => $event->post->published_at,
                'content_blocks' => count($event->post->content['blocks'] ?? []),
            ]
        );
    }
}
```

---

### 4.8. Listener: LogPostCreated

**Ubicación:** `app-modules/activity/src/Listeners/LogPostCreated.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Publishing\Events\PostCreated;
use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;

class LogPostCreated
{
    public function handle(PostCreated $event): void
    {
        ActivityLog::record(
            action: 'post.created',
            entityType: 'post',
            entityId: $event->post->id,
            user: $event->userId ? User::find($event->userId) : null,
            metadata: [
                'title' => $event->post->title,
                'type' => $event->post->type,
                'workspace_id' => $event->post->workspace_id,
            ]
        );
    }
}
```

---

### 4.9. Listener: LogPostDeleted

**Ubicación:** `app-modules/activity/src/Listeners/LogPostDeleted.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Publishing\Events\PostDeleted;
use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;

class LogPostDeleted
{
    public function handle(PostDeleted $event): void
    {
        ActivityLog::record(
            action: 'post.deleted',
            entityType: 'post',
            entityId: $event->post->id,
            user: $event->userId ? User::find($event->userId) : null,
            metadata: [
                'title' => $event->post->title,
                'status' => $event->post->status,
                'workspace_id' => $event->post->workspace_id,
                'versions_count' => $event->versionsCount,
            ]
        );
    }
}
```

---

### 4.10. Observer: PostObserver (Modificado)

**Ubicación:** `app-modules/publishing/src/Observers/PostObserver.php`

```php
<?php

namespace Domains\Publishing\Observers;

use Domains\Publishing\Models\Post;
use Domains\Publishing\Actions\CreatePostVersionAction;
use Domains\Publishing\Events\PostCreated;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Events\PostDeleted;

/**
 * Observer: Escucha eventos de ciclo de vida del Post
 * 
 * PROPÓSITO:
 * - Disparar Actions cuando el post cambia de estado
 * - Disparar Events para comunicar con otros dominios
 * - Mantener lógica de negocio en Actions (desacoplado)
 * 
 * CICLO DE VIDA MONITOREADO:
 * ✅ created: Se crea draft (disparar PostCreated)
 * ✅ updated: Después de actualizar (crear versión si pasó a published)
 * ✅ deleting: Antes de borrar (disparar PostDeleted)
 * 
 * VENTAJAS:
 * - Automático: no depende del Controller
 * - Desacoplado: Actions no conocen Observer
 * - Testeable: cada pieza por separado
 * - Reutilizable: funciona en API, CLI, UI
 */
class PostObserver
{
    /**
     * Handle the Post "created" event
     * 
     * Evento: Se crea un nuevo post (siempre en draft)
     * Acción: Disparar PostCreated para auditoría
     */
    public function created(Post $post): void
    {
        event(new PostCreated(
            post: $post,
            userId: auth()->id()
        ));
    }

    /**
     * Handle the Post "updated" event
     * 
     * Evento: El post fue actualizado
     * Casos:
     * - Draft → Draft: solo actualizar contenido (sin versión)
     * - Draft → Scheduled: cambiar fecha de publicación
     * - Scheduled → Published: publicar (CREAR VERSIÓN)
     * 
     * Regla: Solo versionar cuando entra en estado 'published'
     */
    public function updated(Post $post): void
    {
        // Detectar si cambió a published en esta actualización
        $wasPublished = $post->isDirty('status') && $post->status === 'published';

        if ($wasPublished) {
            // ✅ Crear versión snapshot
            $version = (new CreatePostVersionAction())->handle(
                post: $post,
                userId: auth()->id(),
                reason: 'post_published'
            );
            // Nota: CreatePostVersionAction ya dispara PostVersionCreated

            // También disparar PostPublished para auditoría específica
            event(new PostPublished(
                post: $post,
                userId: auth()->id(),
                versionNumber: $version->version_number
            ));
        }
    }

    /**
     * Handle the Post "deleting" event
     * 
     * Evento: El post está siendo eliminado
     * Acción: Disparar PostDeleted para auditoría de eliminación
     */
    public function deleting(Post $post): void
    {
        event(new PostDeleted(
            post: $post,
            userId: auth()->id(),
            versionsCount: $post->versions()->count()
        ));
    }
}
```

**Cambios respecto a versión inicial:**

```diff
- use Domains\Activity\Models\ActivityLog; // ❌ Eliminado
+ use Domains\Publishing\Events\PostCreated; // ✅ Eventos
+ use Domains\Publishing\Events\PostPublished;
+ use Domains\Publishing\Events\PostDeleted;

- ActivityLog::record(...); // ❌ Eliminado
+ event(new PostCreated(...)); // ✅ Eventos
+ event(new PostPublished(...));
+ event(new PostDeleted(...));
```

---

### 4.11. EventServiceProvider (Configuración Central)

**Ubicación:** `app/Providers/EventServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// ─────────────────────────────────────────────────────────────────
// EVENTS de Publishing
// ─────────────────────────────────────────────────────────────────
use Domains\Publishing\Events\PostVersionCreated;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Events\PostCreated;
use Domains\Publishing\Events\PostDeleted;

// ─────────────────────────────────────────────────────────────────
// LISTENERS de Activity
// ─────────────────────────────────────────────────────────────────
use Domains\Activity\Listeners\LogPostVersionCreated;
use Domains\Activity\Listeners\LogPostPublished;
use Domains\Activity\Listeners\LogPostCreated;
use Domains\Activity\Listeners\LogPostDeleted;

/**
 * EventServiceProvider: Configuración central de eventos
 * 
 * PROPÓSITO:
 * - Conectar eventos con sus listeners
 * - Único punto donde se define el acoplamiento
 * - Centralizar la configuración de eventos
 * 
 * ARQUITECTURA:
 * - Publishing dispara eventos (no conoce listeners)
 * - Activity define listeners (conoce eventos de Publishing)
 * - EventServiceProvider conecta ambos (único punto de acoplamiento)
 * 
 * BENEFICIOS:
 * ✅ Cambias esto SIN tocar Publishing ni Activity
 * ✅ Agregar listener = agregar línea aquí
 * ✅ Quitar listener = comentar línea aquí
 * ✅ Testing: mock del EventBus completo
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapa de eventos → listeners
     * 
     * Estructura:
     * EventClass::class => [
     *     ListenerClass1::class,
     *     ListenerClass2::class, // Múltiples listeners por evento
     * ]
     * 
     * ✅ EXTENSIBLE: Agregar listeners sin modificar código
     * ✅ DESACOPLADO: Publishing y Activity nunca se tocan
     * ✅ FLEXIBLE: Listeners síncronos o async (ShouldQueue)
     */
    protected $listen = [
        // ─────────────────────────────────────────────────────────
        // Publishing Events → Activity Listeners
        // ─────────────────────────────────────────────────────────
        
        PostVersionCreated::class => [
            LogPostVersionCreated::class,  // Activity: registrar auditoría
            // Aquí puedes agregar más listeners:
            // NotifyCollaboratorsOfVersion::class, // Notificaciones
            // UpdateSearchIndex::class,             // Búsqueda
            // TrackVersionAnalytics::class,         // Analytics
        ],

        PostPublished::class => [
            LogPostPublished::class,       // Activity: registrar publicación
            // SendPublishNotification::class, // Notificaciones
            // TriggerSocialMediaShare::class, // Social media
        ],

        PostCreated::class => [
            LogPostCreated::class,         // Activity: registrar creación
            // InitializePostMetadata::class, // Metadata
        ],

        PostDeleted::class => [
            LogPostDeleted::class,         // Activity: registrar eliminación
            // CleanupOrphanedMedia::class,   // Limpieza
            // NotifyCollaborators::class,    // Notificaciones
        ],
    ];

    /**
     * Register any events for your application
     */
    public function boot(): void
    {
        //
    }
}
```

**¿Por qué este archivo es clave?**

```
ANTES (acoplamiento directo):
┌─────────────┐
│ Publishing  │ ──────────────> │ Activity │
└─────────────┘                 └──────────┘
      ↑                               ↑
      └─────── ACOPLAMIENTO ──────────┘

DESPUÉS (event-driven):
┌─────────────┐
│ Publishing  │ ──> EventBus
└─────────────┘         │
                        │ EventServiceProvider
                        │ (único acoplamiento)
                        ↓
                  │ Activity │
                  └──────────┘

Ventaja: Publishing y Activity NUNCA se tocan directamente
```

---

## 5. Service Container y Dependency Injection {#service-container-y-dependency-injection}

### 5.1. ¿Qué es el Service Container?

**Referencia:** [Laravel Service Container Documentation](https://laravel.com/docs/12.x/container)

El Service Container de Laravel es un **gestor de dependencias** que:

1. **Resuelve dependencias automáticamente**
2. **Inyecta instancias en constructores**
3. **Gestiona el ciclo de vida de objetos**
4. **Permite binding de interfaces a implementaciones**

### 5.2. Uso en EventServiceProvider

**Opción 1: Array Simple (actual)**

```php
protected $listen = [
    PostVersionCreated::class => [
        LogPostVersionCreated::class, // Laravel lo instancia automáticamente
    ],
];
```

**Laravel hace:**
```php
// Internamente, Laravel hace esto:
$listener = app()->make(LogPostVersionCreated::class);
$listener->handle($event);
```

**Opción 2: Binding Manual (avanzado)**

```php
// En ActivityServiceProvider.php

public function register(): void
{
    // Bind interface a implementación
    $this->app->bind(
        ActivityLoggerInterface::class,
        ActivityLogService::class
    );
}

// En EventServiceProvider.php
protected $listen = [
    PostVersionCreated::class => [
        LogPostVersionCreated::class, // ← Inyecta ActivityLoggerInterface
    ],
];

// En LogPostVersionCreated.php
class LogPostVersionCreated
{
    public function __construct(
        private ActivityLoggerInterface $logger // ← Inyectado por container
    ) {}

    public function handle(PostVersionCreated $event): void
    {
        $this->logger->log('post.versioned', $event->post->id, [
            'version_number' => $event->version->version_number,
        ]);
    }
}
```

### 5.3. ¿Cuándo Usar Service Container para Listeners?

| Caso | Usar Container | Razón |
|---|:---:|---|
| **Listener simple** | ❌ No | Array en EventServiceProvider es suficiente |
| **Listener con dependencias** | ✅ Sí | Inyectar servicios en constructor |
| **Testing con mocks** | ✅ Sí | Facilita mock de dependencias |
| **Múltiples implementaciones** | ✅ Sí | Bind interface a implementación |
| **Configuración dinámica** | ✅ Sí | Resolver listeners en runtime |

### 5.4. Ejemplo: Listener con Dependency Injection

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Publishing\Events\PostVersionCreated;
use Domains\Activity\Contracts\ActivityLoggerInterface; // Interface
use Domains\Identity\Contracts\UserRepositoryInterface;

class LogPostVersionCreated
{
    /**
     * Constructor con dependency injection
     * 
     * ✅ Service Container inyecta automáticamente
     * ✅ Testing: mock estas dependencias
     */
    public function __construct(
        private ActivityLoggerInterface $logger,
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(PostVersionCreated $event): void
    {
        // Usar dependencia inyectada
        $user = $this->userRepository->find($event->userId);
        
        $this->logger->log(
            action: 'post.versioned',
            entityType: 'post',
            entityId: $event->post->id,
            user: $user,
            metadata: [
                'version_number' => $event->version->version_number,
                'reason' => $event->reason,
            ]
        );
    }
}
```

**Ventaja para testing:**

```php
// En el test
$this->mock(ActivityLoggerInterface::class, function ($mock) {
    $mock->shouldReceive('log')
        ->once()
        ->with('post.versioned', Mockery::any(), Mockery::any());
});

event(new PostVersionCreated($post, $version));
// El listener usa el mock automáticamente
```

---

## 6. Flujo Completo de Ejecución {#flujo-completo-de-ejecución}

### 6.1. Diagrama de Secuencia

```
Usuario                Controller              Action                 Observer               EventBus               Listener
  │                        │                     │                        │                      │                      │
  │ POST /publish          │                     │                        │                      │                      │
  ├───────────────────────>│                     │                        │                      │                      │
  │                        │                     │                        │                      │                      │
  │                        │ PublishPostAction   │                        │                      │                      │
  │                        │ ::handle($post)     │                        │                      │                      │
  │                        ├────────────────────>│                        │                      │                      │
  │                        │                     │                        │                      │                      │
  │                        │                     │ DB::transaction()      │                      │                      │
  │                        │                     │ {                      │                      │                      │
  │                        │                     │   $post->update()      │                      │                      │
  │                        │                     │   ────────────────────>│                      │                      │
  │                        │                     │                        │ PostObserver         │                      │
  │                        │                     │                        │ ::updated($post)     │                      │
  │                        │                     │                        │                      │                      │
  │                        │                     │   CreateVersionAction  │                      │                      │
  │                        │                     │   ::handle($post)      │                      │                      │
  │                        │                     │   <────────────────────┤                      │                      │
  │                        │                     │                        │                      │                      │
  │                        │                     │   $version = create()  │                      │                      │
  │                        │                     │                        │                      │                      │
  │                        │                     │   event(PostVersionCreated)                   │                      │
  │                        │                     │   ─────────────────────────────────────────>│                      │
  │                        │                     │                        │                      │ Enruta a listeners   │
  │                        │                     │                        │                      │                      │
  │                        │                     │                        │                      │ LogPostVersionCreated│
  │                        │                     │                        │                      ├─────────────────────>│
  │                        │                     │                        │                      │                      │
  │                        │                     │                        │                      │   ActivityLog::record()
  │                        │                     │                        │                      │   <────────────────┤
  │                        │                     │                        │                      │                      │
  │                        │                     │   event(PostPublished) │                      │                      │
  │                        │                     │   ─────────────────────────────────────────>│                      │
  │                        │                     │                        │                      │                      │
  │                        │                     │                        │                      │ LogPostPublished     │
  │                        │                     │                        │                      ├─────────────────────>│
  │                        │                     │                        │                      │                      │
  │                        │                     │                        │                      │   ActivityLog::record()
  │                        │                     │                        │                      │   <────────────────┤
  │                        │                     │ }                      │                      │                      │
  │                        │                     │ COMMIT                 │                      │                      │
  │                        │                     │                        │                      │                      │
  │                        │ return $post        │                        │                      │                      │
  │                        │<────────────────────┤                        │                      │                      │
  │                        │                     │                        │                      │                      │
  │ HTTP 200 OK            │                     │                        │                      │                      │
  │<───────────────────────┤                     │                        │                      │                      │
  │ {post, version}        │                     │                        │                      │                      │
```

### 6.2. Paso a Paso Detallado

```
┌─────────────────────────────────────────────────────────────┐
│ PASO 1: Usuario dispara publicación                         │
└─────────────────────────────────────────────────────────────┘

POST /api/workspaces/ws123/posts/post456/publish
Body: { publishedAt: "2026-02-07T10:00:00Z" }

        ↓ Laravel routea a:

┌─────────────────────────────────────────────────────────────┐
│ PASO 2: Controller recibe request                           │
└─────────────────────────────────────────────────────────────┘

class PostController {
    public function publish(Post $post) {
        // ✅ Delega TODO a PublishPostAction
        $post = (new PublishPostAction())->handle(
            post: $post,
            publishedAt: now(),
            user: auth()->user()
        );
        
        return response()->json(['post' => $post]);
    }
}

        ↓ Controller llama:

┌─────────────────────────────────────────────────────────────┐
│ PASO 3: PublishPostAction::handle()                         │
└─────────────────────────────────────────────────────────────┘

// VALIDACIÓN
if ($post->status === 'published') {
    throw new \InvalidArgumentException('Ya publicado');
}

// TRANSACCIÓN
DB::transaction(function () {
    // 3a. Actualizar post
    $post->update([
        'status' => 'published',
        'published_at' => now()
    ]);

        ↓ Esta actualización dispara:

    // ─────────────────────────────────────────────────
    // PASO 4: PostObserver::updated()
    // ─────────────────────────────────────────────────
    
    $wasPublished = $post->isDirty('status') && $post->status === 'published';
    
    if ($wasPublished) {
        // ✅ Disparar CreateVersionAction
        $version = (new CreatePostVersionAction())->handle(
            post: $post,
            userId: auth()->id(),
            reason: 'post_published'
        );

        ↓ CreateVersionAction hace:

        // ─────────────────────────────────────────────────
        // PASO 5: CreatePostVersionAction::handle()
        // ─────────────────────────────────────────────────

        // 5a. Crear PostVersion
        $version = $post->versions()->create([
            'content' => $post->content,
            'version_number' => 1,
        ]);

        // 5b. DISPARAR EVENTO (sin saber quién escucha)
        event(new PostVersionCreated(
            post: $post,
            version: $version,
            userId: auth()->id(),
            reason: 'post_published',
            context: [...]
        ));

        ↓ EventBus de Laravel:

        // ─────────────────────────────────────────────────
        // PASO 6: Laravel detecta listeners registrados
        // ─────────────────────────────────────────────────

        // Lee EventServiceProvider::$listen
        // PostVersionCreated → LogPostVersionCreated
        
        // Ejecuta listener:
        $listener = app()->make(LogPostVersionCreated::class);
        $listener->handle($event);

        ↓ Listener hace:

        // ─────────────────────────────────────────────────
        // PASO 7: LogPostVersionCreated::handle()
        // ─────────────────────────────────────────────────

        ActivityLog::record(
            action: 'post.versioned',
            entityType: 'post',
            entityId: $post->id,
            user: User::find($event->userId),
            metadata: [
                'version_number' => 1,
                'reason' => 'post_published',
                ...
            ]
        );
        // BD: ActivityLog creada

        ↓ De vuelta al Observer:

        // También disparar PostPublished
        event(new PostPublished(
            post: $post,
            userId: auth()->id(),
            versionNumber: 1
        ));

        ↓ EventBus ejecuta:

        // ─────────────────────────────────────────────────
        // PASO 8: LogPostPublished::handle()
        // ─────────────────────────────────────────────────

        ActivityLog::record(
            action: 'post.published',
            entityType: 'post',
            entityId: $post->id,
            user: User::find($event->userId),
            metadata: [
                'title' => 'Mi post',
                'version_number' => 1,
                ...
            ]
        );
        // BD: Otra ActivityLog creada
    }

    // Transacción terminada: COMMIT
    return $post->refresh();
});

        ↓ De vuelta al Controller:

┌─────────────────────────────────────────────────────────────┐
│ PASO 9: Responder al cliente                                │
└─────────────────────────────────────────────────────────────┘

HTTP 200 OK
{
    "post": {
        "id": "abc123",
        "status": "published",
        "published_at": "2026-02-07T10:00:00Z"
    }
}
```

### 6.3. Estado de la Base de Datos

```
TABLA: publishing_posts
┌───────────────────────────────────────────────┐
│ id     | status    | published_at           │
├────────┼───────────┼──────────────────────────┤
│ abc123 | published | 2026-02-07 10:00:00   │  ← CAMBIÓ
└───────────────────────────────────────────────┘

TABLA: publishing_post_versions (NUEVA)
┌──────────────────────────────────────────────────────┐
│ id   | post_id | content           | version_number │
├──────┼─────────┼───────────────────┼────────────────┤
│ v1   | abc123  | {...editor.js...} | 1              │  ← CREADA
└──────────────────────────────────────────────────────┘

TABLA: activity_logs (NUEVA)
┌──────────────────────────────────────────────────────────┐
│ action          | entity_type | entity_id | metadata  │
├─────────────────┼─────────────┼──────────┼──────────────┤
│ post.versioned  | post        | abc123   | {...}     │  ← LOG 1 (listener 1)
│ post.published  | post        | abc123   | {...}     │  ← LOG 2 (listener 2)
└──────────────────────────────────────────────────────────┘
```

---

## 7. Estructura de Archivos {#estructura-de-archivos}

### 7.1. Publishing Domain (Productor de Eventos)

```
app-modules/publishing/
├── src/
│   ├── Models/
│   │   ├── Post.php
│   │   └── PostVersion.php
│   │
│   ├── Actions/
│   │   ├── CreatePostVersionAction.php     ← Dispara eventos
│   │   └── PublishPostAction.php           ← Dispara eventos
│   │
│   ├── Events/                             ← NUEVO
│   │   ├── PostVersionCreated.php          ← Evento público
│   │   ├── PostPublished.php               ← Evento público
│   │   ├── PostCreated.php                 ← Evento público
│   │   └── PostDeleted.php                 ← Evento público
│   │
│   ├── Observers/
│   │   └── PostObserver.php                ← Dispara eventos
│   │
│   └── Providers/
│       └── PublishingServiceProvider.php   ← Registra observer
│
└── database/
    ├── migrations/
    │   ├── create_publishing_posts_table.php
    │   └── create_publishing_post_versions_table.php
    │
    └── factories/
        ├── PostFactory.php
        └── PostVersionFactory.php
```

### 7.2. Activity Domain (Consumidor de Eventos)

```
app-modules/activity/
├── src/
│   ├── Models/
│   │   └── ActivityLog.php
│   │
│   ├── Listeners/                          ← NUEVO
│   │   ├── LogPostVersionCreated.php       ← Escucha Publishing
│   │   ├── LogPostPublished.php            ← Escucha Publishing
│   │   ├── LogPostCreated.php              ← Escucha Publishing
│   │   └── LogPostDeleted.php              ← Escucha Publishing
│   │
│   └── Providers/
│       └── ActivityServiceProvider.php
│
└── database/
    ├── migrations/
    │   └── create_activity_logs_table.php
    │
    └── factories/
        └── ActivityLogFactory.php
```

### 7.3. Configuración Global (Laravel Core)

```
app/
└── Providers/
    └── EventServiceProvider.php            ← Conecta eventos con listeners
```

---

## 8. Testing con Desacoplamiento {#testing-con-desacoplamiento}

### 8.1. Test: CreatePostVersionAction (Aislado)

```php
<?php

namespace Domains\Publishing\Tests\Actions;

use Domains\Publishing\Models\Post;
use Domains\Publishing\Actions\CreatePostVersionAction;
use Domains\Publishing\Events\PostVersionCreated;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreatePostVersionActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Action crea versión correctamente
     */
    public function test_creates_version_with_correct_version_number(): void
    {
        // Arrange
        $post = Post::factory()->create();
        
        // Act
        $version = (new CreatePostVersionAction())->handle(
            post: $post,
            userId: 'user-123',
            reason: 'test'
        );

        // Assert
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals($post->content, $version->content);
    }

    /**
     * Test: Action dispara evento
     * 
     * ✅ IMPORTANTE: Testing de eventos, NO de listeners
     */
    public function test_dispatches_post_version_created_event(): void
    {
        // Arrange
        Event::fake(); // ← Mock del EventBus completo
        $post = Post::factory()->create();
        
        // Act
        $version = (new CreatePostVersionAction())->handle(
            post: $post,
            userId: 'user-123',
            reason: 'test'
        );

        // Assert
        Event::assertDispatched(PostVersionCreated::class, function ($event) use ($post, $version) {
            return $event->post->id === $post->id
                && $event->version->id === $version->id
                && $event->userId === 'user-123'
                && $event->reason === 'test';
        });
    }

    /**
     * Test: Versión incrementa correctamente
     */
    public function test_increments_version_number_correctly(): void
    {
        Event::fake();
        $post = Post::factory()->create();
        
        // Crear versión 1
        $version1 = (new CreatePostVersionAction())->handle($post);
        $this->assertEquals(1, $version1->version_number);
        
        // Crear versión 2
        $version2 = (new CreatePostVersionAction())->handle($post);
        $this->assertEquals(2, $version2->version_number);
        
        // Crear versión 3
        $version3 = (new CreatePostVersionAction())->handle($post);
        $this->assertEquals(3, $version3->version_number);
    }
}
```

**Ventaja:**
```
✅ Sin Event::fake():
   - Action dispara evento
   - Todos los listeners se ejecutan
   - Test lento (inserta en activity_logs, envía emails, etc.)

✅ Con Event::fake():
   - Action dispara evento (mock)
   - Listeners NO se ejecutan
   - Test rápido (solo testea lógica de Action)
```

---

### 8.2. Test: LogPostVersionCreated Listener (Aislado)

```php
<?php

namespace Domains\Activity\Tests\Listeners;

use Domains\Activity\Listeners\LogPostVersionCreated;
use Domains\Activity\Models\ActivityLog;
use Domains\Publishing\Events\PostVersionCreated;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogPostVersionCreatedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Listener registra en ActivityLog
     */
    public function test_logs_version_creation_in_activity_log(): void
    {
        // Arrange
        $post = Post::factory()->create();
        $version = PostVersion::factory()->create([
            'post_id' => $post->id,
            'version_number' => 1,
        ]);
        
        $event = new PostVersionCreated(
            post: $post,
            version: $version,
            userId: 'user-123',
            reason: 'post_published'
        );

        // Act
        (new LogPostVersionCreated())->handle($event);

        // Assert
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.versioned',
            'entity_type' => 'post',
            'entity_id' => $post->id,
            'user_id' => 'user-123',
        ]);
        
        $log = ActivityLog::latest()->first();
        $this->assertEquals('post_published', $log->metadata['reason']);
        $this->assertEquals(1, $log->metadata['version_number']);
    }

    /**
     * Test: Listener maneja user_id null (acciones del sistema)
     */
    public function test_handles_null_user_id(): void
    {
        $post = Post::factory()->create();
        $version = PostVersion::factory()->create(['post_id' => $post->id]);
        
        $event = new PostVersionCreated(
            post: $post,
            version: $version,
            userId: null, // ← Sin usuario (acción del sistema)
            reason: 'automated'
        );

        (new LogPostVersionCreated())->handle($event);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.versioned',
            'entity_id' => $post->id,
            'user_id' => null,
        ]);
    }
}
```

**Ventaja:**
```
✅ Testear listener aislado:
   - No necesitas crear el post real (factory)
   - No necesitas disparar evento completo
   - Solo testeas la lógica del listener
```

---

### 8.3. Test: Integración Completa (E2E)

```php
<?php

namespace Domains\Publishing\Tests\Integration;

use Domains\Publishing\Models\Post;
use Domains\Publishing\Actions\PublishPostAction;
use Domains\Activity\Models\ActivityLog;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostPublishingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Publicar post crea versión Y registra en Activity
     * 
     * ✅ Test de integración completa (sin mocks)
     */
    public function test_publishing_post_creates_version_and_logs_activity(): void
    {
        // Arrange
        $post = Post::factory()->draft()->create();
        
        // Act
        $publishedPost = (new PublishPostAction())->handle(
            post: $post,
            publishedAt: now(),
            user: null
        );

        // Assert Post
        $this->assertEquals('published', $publishedPost->status);
        $this->assertNotNull($publishedPost->published_at);

        // Assert Version
        $this->assertCount(1, $publishedPost->versions);
        $version = $publishedPost->versions->first();
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals($publishedPost->content, $version->content);

        // Assert Activity Logs
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.versioned',
            'entity_type' => 'post',
            'entity_id' => $publishedPost->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.published',
            'entity_type' => 'post',
            'entity_id' => $publishedPost->id,
        ]);
    }
}
```

---

## 9. Reglas DDD para Modelos y Factories {#reglas-ddd-para-modelos-y-factories}

### 9.1. Reglas para Modelos

#### ✅ PERMITIDO

```php
// ✅ 1. Modelo conoce modelos del MISMO dominio
namespace Domains\Publishing\Models;

use Domains\Publishing\Models\PostVersion; // ← Mismo dominio

class Post extends Model {
    public function versions() {
        return $this->hasMany(PostVersion::class);
    }
}
```

```php
// ✅ 2. Modelo conoce modelos de dominios COMPARTIDOS (Identity)
namespace Domains\Publishing\Models;

use Domains\Identity\Models\User;        // ← Dominio compartido
use Domains\Identity\Models\Workspace;   // ← Dominio compartido

class Post extends Model {
    public function author() {
        return $this->belongsTo(User::class);
    }
    
    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }
}
```

```php
// ✅ 3. Modelo dispara eventos PROPIOS
namespace Domains\Publishing\Models;

use Domains\Publishing\Events\PostCreated; // ← Evento propio

class Post extends Model {
    protected $dispatchesEvents = [
        'created' => PostCreated::class,
    ];
}
```

#### ❌ PROHIBIDO

```php
// ❌ 1. Modelo NO conoce modelos de otros dominios
namespace Domains\Publishing\Models;

use Domains\Activity\Models\ActivityLog; // ← ❌ PROHIBIDO

class Post extends Model {
    public function activityLogs() {
        return $this->hasMany(ActivityLog::class, 'entity_id'); // ❌ MAL
    }
}
```

```php
// ❌ 2. Modelo NO llama directamente a servicios de otros dominios
namespace Domains\Publishing\Models;

use Domains\Activity\Models\ActivityLog;

class Post extends Model {
    public function publish() {
        $this->status = 'published';
        $this->save();
        
        ActivityLog::record(...); // ❌ MAL (acoplamiento)
    }
}
```

```php
// ❌ 3. Modelo NO tiene lógica de negocio compleja
class Post extends Model {
    public function publish() {
        // ❌ MAL: Lógica compleja en modelo
        if ($this->status !== 'draft') {
            throw new \Exception('No se puede publicar');
        }
        
        DB::transaction(function () {
            $this->status = 'published';
            $this->save();
            
            $this->versions()->create([...]);
            
            event(new PostPublished($this));
        });
    }
}

// ✅ BIEN: Lógica en Action
class PublishPostAction {
    public function handle(Post $post) {
        // Lógica compleja aquí
    }
}
```

---

### 9.2. Reglas para Actions

#### ✅ PERMITIDO

```php
// ✅ 1. Action conoce modelos del MISMO dominio
namespace Domains\Publishing\Actions;

use Domains\Publishing\Models\Post;      // ← Mismo dominio
use Domains\Publishing\Models\PostVersion;

class CreatePostVersionAction {
    public function handle(Post $post): PostVersion {
        return $post->versions()->create([...]);
    }
}
```

```php
// ✅ 2. Action dispara eventos PROPIOS
namespace Domains\Publishing\Actions;

use Domains\Publishing\Events\PostVersionCreated; // ← Evento propio

class CreatePostVersionAction {
    public function handle(Post $post): PostVersion {
        $version = $post->versions()->create([...]);
        
        event(new PostVersionCreated($post, $version)); // ✅ Evento
        
        return $version;
    }
}
```

```php
// ✅ 3. Action orquesta lógica de negocio compleja
class PublishPostAction {
    public function handle(Post $post) {
        // Validación
        if ($post->status === 'published') {
            throw new \InvalidArgumentException('...');
        }
        
        // Transacción
        DB::transaction(function () use ($post) {
            $post->update([...]);
            (new CreatePostVersionAction())->handle($post);
            event(new PostPublished($post));
        });
        
        return $post;
    }
}
```

#### ❌ PROHIBIDO

```php
// ❌ 1. Action NO conoce modelos de otros dominios
namespace Domains\Publishing\Actions;

use Domains\Activity\Models\ActivityLog; // ← ❌ PROHIBIDO

class CreatePostVersionAction {
    public function handle(Post $post): PostVersion {
        $version = $post->versions()->create([...]);
        
        ActivityLog::record(...); // ❌ MAL (acoplamiento)
        
        return $version;
    }
}
```

---

### 9.3. Reglas para Events

#### ✅ PERMITIDO

```php
// ✅ 1. Event transporta modelos del MISMO dominio
namespace Domains\Publishing\Events;

use Domains\Publishing\Models\Post;      // ← Mismo dominio
use Domains\Publishing\Models\PostVersion;

class PostVersionCreated {
    public function __construct(
        public Post $post,              // ✅ Modelo propio
        public PostVersion $version,    // ✅ Modelo propio
        public ?string $userId = null,  // ✅ Primitivo
        public string $reason = 'manual'
    ) {}
}
```

```php
// ✅ 2. Event es READONLY (solo DTOs)
class PostVersionCreated {
    public function __construct(
        public readonly Post $post,           // ✅ Readonly
        public readonly PostVersion $version,
        public readonly ?string $userId = null
    ) {}
}
```

#### ❌ PROHIBIDO

```php
// ❌ 1. Event NO tiene lógica de negocio
class PostVersionCreated {
    public function __construct(
        public Post $post,
        public PostVersion $version
    ) {}
    
    public function shouldNotify(): bool {
        // ❌ MAL: Lógica en evento
        return $this->post->workspace->has_notifications;
    }
}
```

```php
// ❌ 2. Event NO conoce modelos de otros dominios
namespace Domains\Publishing\Events;

use Domains\Activity\Models\ActivityLog; // ← ❌ PROHIBIDO

class PostVersionCreated {
    public function __construct(
        public Post $post,
        public ActivityLog $log // ❌ MAL
    ) {}
}
```

---

### 9.4. Reglas para Listeners

#### ✅ PERMITIDO

```php
// ✅ 1. Listener conoce modelos de SU dominio
namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog; // ← Mismo dominio (Activity)

class LogPostVersionCreated {
    public function handle(PostVersionCreated $event): void {
        ActivityLog::record(...); // ✅ Modelo propio
    }
}
```

```php
// ✅ 2. Listener conoce eventos de OTROS dominios
namespace Domains\Activity\Listeners;

use Domains\Publishing\Events\PostVersionCreated; // ← Evento de Publishing

class LogPostVersionCreated {
    public function handle(PostVersionCreated $event): void {
        // ✅ Escucha evento público
    }
}
```

```php
// ✅ 3. Listener usa dependency injection
class LogPostVersionCreated {
    public function __construct(
        private ActivityLogService $logger // ✅ Inyección
    ) {}
    
    public function handle(PostVersionCreated $event): void {
        $this->logger->log(...);
    }
}
```

#### ❌ PROHIBIDO

```php
// ❌ 1. Listener NO modifica modelos de otros dominios
namespace Domains\Activity\Listeners;

use Domains\Publishing\Models\Post; // ← Evento es OK, modelo NO

class LogPostVersionCreated {
    public function handle(PostVersionCreated $event): void {
        ActivityLog::record(...);
        
        $event->post->update(['last_logged_at' => now()]); // ❌ MAL
    }
}
```

---

### 9.5. Reglas para Factories

#### ✅ PERMITIDO

```php
// ✅ 1. Factory crea modelos del MISMO dominio
namespace Domains\Publishing\Database\Factories;

use Domains\Publishing\Models\Post;
use Domains\Identity\Models\User;      // ← Dominio compartido OK
use Domains\Identity\Models\Workspace;

class PostFactory extends Factory {
    protected $model = Post::class;
    
    public function definition(): array {
        return [
            'workspace_id' => Workspace::factory(), // ✅ Compartido
            'author_id' => User::factory(),         // ✅ Compartido
            'title' => $this->faker->sentence(),
            'content' => ['blocks' => []],
        ];
    }
}
```

#### ❌ PROHIBIDO

```php
// ❌ 1. Factory NO crea modelos de otros dominios
namespace Domains\Publishing\Database\Factories;

use Domains\Activity\Models\ActivityLog; // ← ❌ PROHIBIDO

class PostFactory extends Factory {
    public function definition(): array {
        return [
            'title' => $this->faker->sentence(),
        ];
    }
    
    public function configure() {
        return $this->afterCreating(function (Post $post) {
            // ❌ MAL: Factory de Publishing no debe crear Activity
            ActivityLog::factory()->create([
                'entity_id' => $post->id,
            ]);
        });
    }
}
```

---

### 9.6. Tabla de Resumen: Qué Puede Conocer Cada Componente

| Componente | Mismo Dominio | Dominios Compartidos (Identity) | Otros Dominios (Activity, etc.) | Eventos Propios | Eventos Ajenos |
|---|:---:|:---:|:---:|:---:|:---:|
| **Model** | ✅ | ✅ | ❌ | ✅ | ❌ |
| **Action** | ✅ | ✅ | ❌ | ✅ | ❌ |
| **Event** | ✅ | ✅ | ❌ | N/A | N/A |
| **Listener** | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Observer** | ✅ | ✅ | ❌ | ✅ | ❌ |
| **Factory** | ✅ | ✅ | ❌ | ❌ | ❌ |

**Leyenda:**
- ✅ Permitido
- ❌ Prohibido
- N/A: No aplica

---

## 10. Checklist de Implementación {#checklist-de-implementación}

### 10.1. Publishing Domain (Productor)

**Events:**
- [ ] `app-modules/publishing/src/Events/PostVersionCreated.php`
- [ ] `app-modules/publishing/src/Events/PostPublished.php`
- [ ] `app-modules/publishing/src/Events/PostCreated.php`
- [ ] `app-modules/publishing/src/Events/PostDeleted.php`

**Actions (modificadas):**
- [ ] `CreatePostVersionAction.php` dispara `PostVersionCreated`
- [ ] `PublishPostAction.php` dispara `PostPublished`
- [ ] Eliminar imports de `Domains\Activity\Models\ActivityLog`

**Observer (modificado):**
- [ ] `PostObserver.php` dispara eventos en lugar de `ActivityLog::record()`

**Tests:**
- [ ] `CreatePostVersionActionTest.php` con `Event::fake()`
- [ ] Verificar que eventos se disparan correctamente

---

### 10.2. Activity Domain (Consumidor)

**Listeners:**
- [ ] `app-modules/activity/src/Listeners/LogPostVersionCreated.php`
- [ ] `app-modules/activity/src/Listeners/LogPostPublished.php`
- [ ] `app-modules/activity/src/Listeners/LogPostCreated.php`
- [ ] `app-modules/activity/src/Listeners/LogPostDeleted.php`

**Tests:**
- [ ] `LogPostVersionCreatedTest.php` testea listener aislado
- [ ] `LogPostPublishedTest.php` testea listener aislado
- [ ] Verificar que `ActivityLog` se crea correctamente

---

### 10.3. Configuración Global

**EventServiceProvider:**
- [ ] `app/Providers/EventServiceProvider.php`
- [ ] Mapear `PostVersionCreated` → `LogPostVersionCreated`
- [ ] Mapear `PostPublished` → `LogPostPublished`
- [ ] Mapear `PostCreated` → `LogPostCreated`
- [ ] Mapear `PostDeleted` → `LogPostDeleted`

**Service Container (opcional):**
- [ ] Si listeners tienen dependencias, usar DI en constructores
- [ ] Registrar bindings en `ActivityServiceProvider::register()`

---

### 10.4. Testing Completo

**Unit Tests:**
- [ ] Actions sin dependencias de Activity
- [ ] Listeners sin dependencias de Publishing
- [ ] Events con datos correctos

**Integration Tests:**
- [ ] Publicar post crea versión + activity log
- [ ] Eliminar post crea activity log
- [ ] Transacciones funcionan correctamente

**Performance Tests:**
- [ ] Verificar N+1 queries
- [ ] Verificar que eventos no ralentizan requests

---

### 10.5. Validación DDD

**Acoplamiento:**
- [ ] `Publishing` NO importa `ActivityLog`
- [ ] `Publishing` NO importa `Domains\Activity\*`
- [ ] `Activity` solo importa eventos públicos de `Publishing`

**Extensibilidad:**
- [ ] Agregar nuevo listener sin tocar `Publishing`
- [ ] Desactivar `Activity` sin romper `Publishing`

**Testing:**
- [ ] Tests de `Publishing` funcionan sin `Activity`
- [ ] Tests de `Activity` funcionan sin `Publishing`

---

## 11. Referencias y Próximos Pasos {#referencias-y-próximos-pasos}

### 11.1. Referencias de Laravel

| Concepto | Documentación Oficial |
|---|---|
| **Events & Listeners** | [https://laravel.com/docs/12.x/events](https://laravel.com/docs/12.x/events) |
| **Service Container** | [https://laravel.com/docs/12.x/container](https://laravel.com/docs/12.x/container) |
| **Observers** | [https://laravel.com/docs/12.x/eloquent#observers](https://laravel.com/docs/12.x/eloquent#observers) |
| **Testing Events** | [https://laravel.com/docs/12.x/mocking#event-fake](https://laravel.com/docs/12.x/mocking#event-fake) |
| **Queued Listeners** | [https://laravel.com/docs/12.x/events#queued-event-listeners](https://laravel.com/docs/12.x/events#queued-event-listeners) |

### 11.2. Conceptos DDD

| Concepto | Descripción | Aplicación en este Proyecto |
|---|---|---|
| **Bounded Context** | Cada dominio tiene su propio modelo | Publishing, Activity, Identity son contextos separados |
| **Domain Events** | Eventos que representan hechos del negocio | PostVersionCreated, PostPublished |
| **Anti-Corruption Layer** | Capa que protege de dependencias externas | EventBus actúa como ACL entre dominios |
| **Ubiquitous Language** | Lenguaje común del dominio | Events usan terminología del negocio (no técnica) |

### 11.3. Próximos Pasos

#### Corto Plazo (esta semana)

1. **Implementar Events & Listeners**
   - [ ] Crear eventos en Publishing
   - [ ] Crear listeners en Activity
   - [ ] Configurar EventServiceProvider
   - [ ] Eliminar acoplamiento directo

2. **Testing**
   - [ ] Tests unitarios con `Event::fake()`
   - [ ] Tests de integración E2E
   - [ ] Verificar performance

3. **Validación**
   - [ ] Revisar imports (no debe haber `Domains\Activity\*` en Publishing)
   - [ ] Verificar que desactivar Activity no rompe Publishing
   - [ ] Verificar que agregar listener no requiere tocar Publishing

#### Medio Plazo (próximas 2 semanas)

4. **Documento de Reglas DDD**
   - [ ] Crear `DDD_RULES.md` con reglas de este documento
   - [ ] Agregar ejemplos de uso correcto e incorrecto
   - [ ] Incluir checklist de validación
   - [ ] Definir proceso de code review

5. **Extensión a Otros Dominios**
   - [ ] Aplicar mismo patrón a Delivery
   - [ ] Aplicar mismo patrón a Community
   - [ ] Aplicar mismo patrón a Audience

6. **Optimización**
   - [ ] Evaluar listeners async (ShouldQueue)
   - [ ] Implementar retry logic para listeners críticos
   - [ ] Monitoreo de eventos (cuántos se disparan, cuánto tardan)

#### Largo Plazo (próximo mes)

7. **Event Sourcing (opcional)**
   - [ ] Evaluar si Activity podría ser event-sourced
   - [ ] Considerar almacenar todos los eventos en `activity_events`
   - [ ] Reconstruir estado desde eventos

8. **CQRS (opcional)**
   - [ ] Separar reads de writes en Activity
   - [ ] Crear read models optimizados
   - [ ] Usar eventos para sincronizar

---

## 📚 Glosario

| Término | Definición |
|---|---|
| **Bounded Context** | Límite conceptual donde un modelo de dominio es válido |
| **Domain Event** | Representación de un hecho que ocurrió en el dominio |
| **Event Bus** | Mediador que distribuye eventos a listeners |
| **Listener** | Componente que reacciona a un evento |
| **Observer** | Patrón que escucha cambios en un modelo |
| **Action** | Componente que encapsula lógica de negocio |
| **Service Container** | Gestor de dependencias de Laravel |
| **Dependency Injection** | Patrón donde dependencias se inyectan en constructor |
| **Anti-Corruption Layer** | Capa que protege un dominio de dependencias externas |
| **Ubiquitous Language** | Lenguaje común compartido por equipo y código |

---

## 📊 Comparación: Antes vs Después

| Aspecto | ❌ Antes (Acoplado) | ✅ Después (Desacoplado) |
|---|---|---|
| **Imports** | `use Domains\Activity\Models\ActivityLog` | `use Domains\Publishing\Events\PostVersionCreated` |
| **Llamadas** | `ActivityLog::record(...)` | `event(new PostVersionCreated(...))` |
| **Dependencias** | Publishing → Activity (directo) | Publishing → EventBus ← Activity |
| **Testear Publishing** | Requiere mock de Activity | `Event::fake()` (sin Activity) |
| **Agregar Notifications** | Modificar Publishing | Agregar listener en EventServiceProvider |
| **Desactivar Activity** | ❌ Rompe Publishing | ✅ Solo comentar listener |
| **Principios DDD** | ❌ Violados | ✅ Respetados |

---

**Documento Creado:** 7 de febrero de 2026  
**Autor:** Sistema de Análisis Técnico  
**Estado:** 🟡 En análisis - Base para implementación  
**Versión:** 1.0 (Event-Driven Architecture)  
**Próxima Revisión:** Después de implementar primer evento

---

## 🎯 Objetivo Final

**Lograr una arquitectura donde:**

```
✅ Cada dominio es independiente
✅ Comunicación via eventos (desacoplada)
✅ Agregar features sin modificar código existente
✅ Testing rápido y aislado
✅ Escalable a nuevos dominios
✅ Mantenible a largo plazo
```

**Este documento es tu guía para conseguirlo.** 🚀
