# 🎯 ANÁLISIS DE IMPLEMENTACIÓN: EVENT-DRIVEN ARCHITECTURE
## Fase 1: Identity → Activity (Demostración MVP)

**Fecha de Creación:** 8 de febrero de 2026  
**Estado:** 📋 Análisis para implementación  
**Objetivo:** Implementar eventos básicos para demostrar comunicación modular entre dominios  
**Contexto:** Primera implementación de Event-Driven Architecture siguiendo DDD_EVENTS_ARCHITECTURE_ANALYSIS.md

---

## 📋 ÍNDICE

1. [Visión General y Objetivos](#visión-general-y-objetivos)
2. [Análisis del Estado Actual](#análisis-del-estado-actual)
3. [Arquitectura Propuesta](#arquitectura-propuesta)
4. [Eventos de Identity a Implementar](#eventos-de-identity-a-implementar)
5. [Listeners de Activity a Implementar](#listeners-de-activity-a-implementar)
6. [Configuración del EventServiceProvider](#configuración-del-eventserviceprovider)
7. [Plan de Implementación Secuencial](#plan-de-implementación-secuencial)
8. [Demostración y Validación](#demostración-y-validación)
9. [Checklist de Implementación](#checklist-de-implementación)

---

## 1. Visión General y Objetivos {#visión-general-y-objetivos}

### 1.1. Objetivo Principal

**Demostrar que:**
1. ✅ Al crear un usuario (Identity), se registra automáticamente en activity_logs (Activity)
2. ✅ Los dominios NO se conocen entre sí (sin imports directos)
3. ✅ Los eventos son el ÚNICO mecanismo de comunicación
4. ✅ Se respeta la modularidad y los namespaces de cada dominio

### 1.2. Alcance de la Fase 1 (MVP)

**Eventos básicos a implementar:**

| Evento | Dominio Productor | Acción Disparadora | Listener en Activity |
|--------|-------------------|-------------------|----------------------|
| `UserRegistered` | Identity | Usuario creado | `LogUserRegistered` |
| `UserEmailVerified` | Identity | Email verificado | `LogUserEmailVerified` |
| `WorkspaceCreated` | Identity | Workspace creado | `LogWorkspaceCreated` |
| `MembershipCreated` | Identity | Miembro añadido | `LogMembershipCreated` |

**NO implementar en Fase 1 (dejar para después):**
- ❌ Invitations (más complejo)
- ❌ Membership role changes (requiere observer adicional)
- ❌ User/Workspace updates (demasiado granular)
- ❌ Soft deletes o hard deletes (requiere políticas adicionales)

### 1.3. Criterios de Éxito

✅ **Criterio 1: Desacoplamiento Total**
```php
// Identity NO debe importar NADA de Activity
// ❌ use Domains\Activity\Models\ActivityLog;
// ✅ use Domains\Identity\Events\UserRegistered;
```

✅ **Criterio 2: Registro Automático**
```bash
# Al ejecutar:
php artisan tinker
>>> User::factory()->create()

# Debe resultar en:
# 1. Usuario creado en identity_users
# 2. Log automático en activity_logs
```

✅ **Criterio 3: Auditoría Completa**
```php
// Poder consultar:
ActivityLog::where('action', 'user.registered')->get();
ActivityLog::forEntity('user', $userId)->get();
```

---

## 2. Análisis del Estado Actual {#análisis-del-estado-actual}

### 2.1. Estado del Dominio Identity

**✅ Existente:**
- [x] Migración `identity_users` (implementada)
- [x] Migración `identity_workspaces` (implementada)
- [x] Migración `identity_memberships` (implementada)
- [x] Migración `identity_invitations` (implementada)
- [x] Modelo `User` (implementado)
- [x] Modelo `Workspace` (implementado)
- [x] Modelo `Membership` (implementado)
- [x] Modelo `Invitation` (implementado)
- [x] Factories completas (implementadas)
- [x] `IdentityServiceProvider` (básico, sin eventos)

**❌ Faltante:**
- [ ] Directorio `src/Events/`
- [ ] Eventos de ciclo de vida
- [ ] Observers para disparar eventos
- [ ] Configuración de observers en ServiceProvider

### 2.2. Estado del Dominio Activity

**✅ Existente:**
- [x] Migración `activity_logs` (implementada)
- [x] Modelo `ActivityLog` con método `record()` (implementado)
- [x] Factory `ActivityLogFactory` (implementada)
- [x] `ActivityServiceProvider` (básico)

**❌ Faltante:**
- [ ] Directorio `src/Listeners/`
- [ ] Listeners para eventos de Identity
- [ ] Tests para listeners

### 2.3. Estado de la Configuración Global

**✅ Existente:**
- [x] `App\Providers\AppServiceProvider` (básico)

**❌ Faltante:**
- [ ] `App\Providers\EventServiceProvider` (NO EXISTE)
  - **CRÍTICO:** Debe crearse este archivo

---

## 3. Arquitectura Propuesta {#arquitectura-propuesta}

### 3.1. Diagrama de Flujo

```
┌──────────────────────────────────────────────────────────────┐
│                    FASE 1: IDENTITY → ACTIVITY                │
└──────────────────────────────────────────────────────────────┘

FLUJO: Crear Usuario

1. Developer/Tinker:
   User::factory()->create()
   
2. Eloquent:
   INSERT INTO identity_users (...)
   
3. Observer (NUEVO):
   UserObserver::created($user)
   
4. Event (NUEVO):
   event(new UserRegistered($user))
   
5. EventBus (Laravel):
   Lee EventServiceProvider::$listen
   
6. Listener (NUEVO):
   LogUserRegistered::handle($event)
   
7. Activity:
   ActivityLog::record(...)
   INSERT INTO activity_logs (...)

RESULTADO:
✅ Usuario creado en identity_users
✅ Log creado en activity_logs
✅ Identity NO conoce Activity
✅ Modularidad respetada
```

### 3.2. Estructura de Archivos a Crear

```
app-modules/identity/
├── src/
│   ├── Events/                              ← CREAR DIRECTORIO
│   │   ├── UserRegistered.php               ← CREAR
│   │   ├── UserEmailVerified.php            ← CREAR
│   │   ├── WorkspaceCreated.php             ← CREAR
│   │   └── MembershipCreated.php            ← CREAR
│   │
│   ├── Observers/                           ← CREAR DIRECTORIO
│   │   ├── UserObserver.php                 ← CREAR
│   │   ├── WorkspaceObserver.php            ← CREAR
│   │   └── MembershipObserver.php           ← CREAR
│   │
│   └── Providers/
│       └── IdentityServiceProvider.php      ← MODIFICAR (registrar observers)

app-modules/activity/
└── src/
    ├── Listeners/                           ← CREAR DIRECTORIO
    │   ├── LogUserRegistered.php            ← CREAR
    │   ├── LogUserEmailVerified.php         ← CREAR
    │   ├── LogWorkspaceCreated.php          ← CREAR
    │   └── LogMembershipCreated.php         ← CREAR
    │
    └── Providers/
        └── ActivityServiceProvider.php      ← (sin cambios)

app/
└── Providers/
    └── EventServiceProvider.php             ← CREAR (conecta eventos y listeners)
```

---

## 4. Eventos de Identity a Implementar {#eventos-de-identity-a-implementar}

### 4.1. Event: UserRegistered

**Archivo:** `app-modules/identity/src/Events/UserRegistered.php`

```php
<?php

namespace Domains\Identity\Events;

use Domains\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Un usuario se registró en el sistema
 * 
 * PROPÓSITO:
 * - Notificar que un nuevo usuario fue creado
 * - Permite auditoría, bienvenida, analytics, etc.
 * 
 * DATOS:
 * - User: El usuario creado
 * - context: Datos adicionales (IP, user agent, referrer)
 * 
 * CASOS DE USO:
 * - Activity: Registrar en ActivityLog
 * - Email: Enviar email de bienvenida
 * - Analytics: Trackear conversión de registro
 * - Slack: Notificar a equipo de nuevos registros
 */
class UserRegistered
{
    use Dispatchable, SerializesModels;

    /**
     * Constructor del evento
     * 
     * @param User $user El usuario que se registró
     * @param array $context Contexto adicional (ip, user_agent, etc.)
     */
    public function __construct(
        public User $user,
        public array $context = []
    ) {}
}
```

**¿Cuándo se dispara?**
- Al ejecutar `User::create()`
- Al ejecutar `User::factory()->create()`
- Automáticamente vía `UserObserver::created()`

---

### 4.2. Event: UserEmailVerified

**Archivo:** `app-modules/identity/src/Events/UserEmailVerified.php`

```php
<?php

namespace Domains\Identity\Events;

use Domains\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Un usuario verificó su email
 * 
 * PROPÓSITO:
 * - Notificar que un usuario completó la verificación de email
 * - Importante para GDPR y compliance
 * 
 * DATOS:
 * - User: El usuario que verificó su email
 * - verifiedAt: Timestamp de verificación
 */
class UserEmailVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public \DateTimeInterface $verifiedAt
    ) {}
}
```

**¿Cuándo se dispara?**
- Al actualizar `email_verified_at` de `null` a timestamp
- Vía `UserObserver::updated()` cuando detecta cambio

---

### 4.3. Event: WorkspaceCreated

**Archivo:** `app-modules/identity/src/Events/WorkspaceCreated.php`

```php
<?php

namespace Domains\Identity\Events;

use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Se creó un nuevo workspace
 * 
 * PROPÓSITO:
 * - Notificar creación de workspace (newsletter/blog)
 * - Permite auditoría, inicialización, analytics
 * 
 * DATOS:
 * - Workspace: El workspace creado
 * - ownerId: ID del usuario propietario
 */
class WorkspaceCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Workspace $workspace,
        public ?string $ownerId = null
    ) {}
}
```

**¿Cuándo se dispara?**
- Al ejecutar `Workspace::create()`
- Automáticamente vía `WorkspaceObserver::created()`

---

### 4.4. Event: MembershipCreated

**Archivo:** `app-modules/identity/src/Events/MembershipCreated.php`

```php
<?php

namespace Domains\Identity\Events;

use Domains\Identity\Models\Membership;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Se añadió un miembro a un workspace
 * 
 * PROPÓSITO:
 * - Notificar que un usuario se unió a un workspace
 * - Importante para auditoría de permisos
 * 
 * DATOS:
 * - Membership: La membresía creada (contiene user_id, workspace_id, role)
 */
class MembershipCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Membership $membership
    ) {}
}
```

**¿Cuándo se dispara?**
- Al ejecutar `Membership::create()`
- Al aceptar una invitación
- Automáticamente vía `MembershipObserver::created()`

---

## 5. Listeners de Activity a Implementar {#listeners-de-activity-a-implementar}

### 5.1. Listener: LogUserRegistered

**Archivo:** `app-modules/activity/src/Listeners/LogUserRegistered.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Identity\Events\UserRegistered;
use Domains\Activity\Models\ActivityLog;

/**
 * Listener: Registrar en Activity cuando un usuario se registra
 * 
 * UBICACIÓN: Activity Domain (NO Identity)
 * ¿POR QUÉ?: Activity es quien REGISTRA, no quien PRODUCE el evento
 */
class LogUserRegistered
{
    /**
     * Handle el evento
     * 
     * @param UserRegistered $event El evento de Identity
     */
    public function handle(UserRegistered $event): void
    {
        ActivityLog::create([
            'user_id' => null, // El usuario recién creado, no hay sesión activa
            'action' => 'user.registered',
            'entity_type' => 'user',
            'entity_id' => $event->user->id,
            'metadata' => [
                'name' => $event->user->name,
                'email' => $event->user->email,
                'context' => $event->context,
            ],
            'ip_address' => $event->context['ip'] ?? request()->ip(),
            'user_agent' => $event->context['user_agent'] ?? request()->userAgent(),
        ]);
    }
}
```

---

### 5.2. Listener: LogUserEmailVerified

**Archivo:** `app-modules/activity/src/Listeners/LogUserEmailVerified.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Identity\Events\UserEmailVerified;
use Domains\Activity\Models\ActivityLog;

class LogUserEmailVerified
{
    public function handle(UserEmailVerified $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'action' => 'user.email_verified',
            'entity_type' => 'user',
            'entity_id' => $event->user->id,
            'metadata' => [
                'email' => $event->user->email,
                'verified_at' => $event->verifiedAt->toIso8601String(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

### 5.3. Listener: LogWorkspaceCreated

**Archivo:** `app-modules/activity/src/Listeners/LogWorkspaceCreated.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Identity\Events\WorkspaceCreated;
use Domains\Activity\Models\ActivityLog;

class LogWorkspaceCreated
{
    public function handle(WorkspaceCreated $event): void
    {
        ActivityLog::create([
            'user_id' => $event->ownerId,
            'action' => 'workspace.created',
            'entity_type' => 'workspace',
            'entity_id' => $event->workspace->id,
            'metadata' => [
                'name' => $event->workspace->name,
                'slug' => $event->workspace->slug,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

### 5.4. Listener: LogMembershipCreated

**Archivo:** `app-modules/activity/src/Listeners/LogMembershipCreated.php`

```php
<?php

namespace Domains\Activity\Listeners;

use Domains\Identity\Events\MembershipCreated;
use Domains\Activity\Models\ActivityLog;

class LogMembershipCreated
{
    public function handle(MembershipCreated $event): void
    {
        ActivityLog::create([
            'user_id' => $event->membership->user_id,
            'action' => 'membership.created',
            'entity_type' => 'membership',
            'entity_id' => $event->membership->id,
            'metadata' => [
                'workspace_id' => $event->membership->workspace_id,
                'workspace_name' => $event->membership->workspace->name ?? null,
                'role' => $event->membership->role,
                'joined_at' => $event->membership->joined_at->toIso8601String(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

## 6. Configuración del EventServiceProvider {#configuración-del-eventserviceprovider}

### 6.1. Crear EventServiceProvider

**Archivo:** `app/Providers/EventServiceProvider.php` (NO EXISTE, CREAR)

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// ─────────────────────────────────────────────────────────────────
// EVENTS de Identity
// ─────────────────────────────────────────────────────────────────
use Domains\Identity\Events\UserRegistered;
use Domains\Identity\Events\UserEmailVerified;
use Domains\Identity\Events\WorkspaceCreated;
use Domains\Identity\Events\MembershipCreated;

// ─────────────────────────────────────────────────────────────────
// LISTENERS de Activity
// ─────────────────────────────────────────────────────────────────
use Domains\Activity\Listeners\LogUserRegistered;
use Domains\Activity\Listeners\LogUserEmailVerified;
use Domains\Activity\Listeners\LogWorkspaceCreated;
use Domains\Activity\Listeners\LogMembershipCreated;

/**
 * EventServiceProvider: Configuración central de eventos
 * 
 * PROPÓSITO:
 * - Conectar eventos de Identity con listeners de Activity
 * - Único punto de acoplamiento entre dominios
 * - Centralizar la configuración de eventos del sistema
 * 
 * ARQUITECTURA EVENT-DRIVEN:
 * - Identity dispara eventos (no conoce listeners)
 * - Activity define listeners (conoce eventos de Identity)
 * - Este provider conecta ambos (único punto de acoplamiento)
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapa de eventos → listeners
     * 
     * FASE 1: Identity → Activity
     * 
     * ✅ EXTENSIBLE: Agregar listeners sin modificar dominios
     * ✅ DESACOPLADO: Identity y Activity nunca se tocan directamente
     * ✅ FLEXIBLE: Múltiples listeners por evento
     */
    protected $listen = [
        // ─────────────────────────────────────────────────────────
        // Identity Events → Activity Listeners
        // ─────────────────────────────────────────────────────────
        
        UserRegistered::class => [
            LogUserRegistered::class,      // Activity: registrar auditoría
            // Futuro: SendWelcomeEmail::class,
            // Futuro: TrackUserSignup::class,
        ],

        UserEmailVerified::class => [
            LogUserEmailVerified::class,   // Activity: registrar verificación
            // Futuro: UnlockPremiumFeatures::class,
        ],

        WorkspaceCreated::class => [
            LogWorkspaceCreated::class,    // Activity: registrar creación
            // Futuro: InitializeWorkspaceDefaults::class,
            // Futuro: SendWorkspaceWelcome::class,
        ],

        MembershipCreated::class => [
            LogMembershipCreated::class,   // Activity: registrar membresía
            // Futuro: NotifyWorkspaceOwner::class,
            // Futuro: SendMemberWelcome::class,
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

### 6.2. Registrar EventServiceProvider en bootstrap/providers.php

**Archivo:** `bootstrap/providers.php`

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class, // ← AGREGAR ESTA LÍNEA
];
```

---

## 7. Plan de Implementación Secuencial {#plan-de-implementación-secuencial}

### 7.1. Fase 1A: Infraestructura de Eventos (Identity)

**Orden de implementación:**

```
PASO 1: Crear estructura de directorios
├─ app-modules/identity/src/Events/
└─ app-modules/identity/src/Observers/

PASO 2: Crear eventos
├─ UserRegistered.php
├─ UserEmailVerified.php
├─ WorkspaceCreated.php
└─ MembershipCreated.php

PASO 3: Crear observers
├─ UserObserver.php
├─ WorkspaceObserver.php
└─ MembershipObserver.php

PASO 4: Registrar observers en IdentityServiceProvider
└─ boot() method
```

**Detalle del PASO 4: Modificar IdentityServiceProvider**

```php
<?php

namespace Domains\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\Membership;
use Domains\Identity\Observers\UserObserver;
use Domains\Identity\Observers\WorkspaceObserver;
use Domains\Identity\Observers\MembershipObserver;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
    
    public function boot(): void
    {
        // Registrar observers para disparar eventos automáticamente
        User::observe(UserObserver::class);
        Workspace::observe(WorkspaceObserver::class);
        Membership::observe(MembershipObserver::class);
    }
}
```

---

### 7.2. Fase 1B: Infraestructura de Listeners (Activity)

**Orden de implementación:**

```
PASO 5: Crear estructura de directorios
└─ app-modules/activity/src/Listeners/

PASO 6: Crear listeners
├─ LogUserRegistered.php
├─ LogUserEmailVerified.php
├─ LogWorkspaceCreated.php
└─ LogMembershipCreated.php
```

---

### 7.3. Fase 1C: Configuración Global

**Orden de implementación:**

```
PASO 7: Crear EventServiceProvider
└─ app/Providers/EventServiceProvider.php

PASO 8: Registrar EventServiceProvider
└─ bootstrap/providers.php
```

---

### 7.4. Fase 1D: Validación y Testing

**Orden de implementación:**

```
PASO 9: Tests manuales con Tinker
├─ User::factory()->create()
├─ Workspace::factory()->create()
└─ Verificar activity_logs

PASO 10: Tests automatizados
├─ UserObserver test
├─ LogUserRegistered test
└─ Integration test
```

---

## 8. Demostración y Validación {#demostración-y-validación}

### 8.1. Demostración Manual (Tinker)

**Script de demostración:**

```bash
# 1. Iniciar Tinker
php artisan tinker

# 2. Crear usuario (dispara UserRegistered)
>>> $user = User::factory()->create(['name' => 'Juan Pérez', 'email' => 'juan@example.com']);

# 3. Verificar que existe en identity_users
>>> User::count();
# => 1

# 4. Verificar que se registró en activity_logs
>>> ActivityLog::where('action', 'user.registered')->count();
# => 1

# 5. Inspeccionar el log
>>> ActivityLog::latest()->first()->toArray();
# => [
#   "action" => "user.registered",
#   "entity_type" => "user",
#   "entity_id" => "uuid-del-usuario",
#   "metadata" => [
#     "name" => "Juan Pérez",
#     "email" => "juan@example.com"
#   ]
# ]

# 6. Crear workspace (dispara WorkspaceCreated)
>>> $workspace = Workspace::factory()->create(['name' => 'Mi Newsletter', 'slug' => 'mi-newsletter']);

# 7. Verificar logs de workspace
>>> ActivityLog::where('action', 'workspace.created')->count();
# => 1

# 8. Crear membership (dispara MembershipCreated)
>>> $membership = Membership::create([
...   'user_id' => $user->id,
...   'workspace_id' => $workspace->id,
...   'role' => 'owner',
...   'joined_at' => now()
... ]);

# 9. Verificar logs de membership
>>> ActivityLog::where('action', 'membership.created')->count();
# => 1

# 10. Verificar total de logs
>>> ActivityLog::count();
# => 3 (user.registered + workspace.created + membership.created)
```

---

### 8.2. Validación de Desacoplamiento

**Verificar que NO existe acoplamiento:**

```bash
# Buscar imports prohibidos en Identity
grep -r "use Domains\\Activity" app-modules/identity/src/

# Resultado esperado: (ninguna coincidencia)

# Verificar que Activity SÍ conoce eventos de Identity (permitido)
grep -r "use Domains\\Identity\\Events" app-modules/activity/src/

# Resultado esperado:
# app-modules/activity/src/Listeners/LogUserRegistered.php:use Domains\Identity\Events\UserRegistered;
# app-modules/activity/src/Listeners/LogWorkspaceCreated.php:use Domains\Identity\Events\WorkspaceCreated;
# ...
```

---

### 8.3. Test Automatizado de Integración

**Archivo:** `tests/Integration/IdentityActivityEventsTest.php`

```php
<?php

namespace Tests\Integration;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\Membership;
use Domains\Activity\Models\ActivityLog;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IdentityActivityEventsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear usuario registra en activity_logs
     */
    public function test_user_creation_logs_to_activity(): void
    {
        // Act
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Assert
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.registered',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);

        $log = ActivityLog::where('action', 'user.registered')
            ->where('entity_id', $user->id)
            ->first();

        $this->assertEquals('Test User', $log->metadata['name']);
        $this->assertEquals('test@example.com', $log->metadata['email']);
    }

    /**
     * Test: Crear workspace registra en activity_logs
     */
    public function test_workspace_creation_logs_to_activity(): void
    {
        $workspace = Workspace::factory()->create([
            'name' => 'Test Newsletter',
            'slug' => 'test-newsletter',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'workspace.created',
            'entity_type' => 'workspace',
            'entity_id' => $workspace->id,
        ]);
    }

    /**
     * Test: Crear membership registra en activity_logs
     */
    public function test_membership_creation_logs_to_activity(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $membership = Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'membership.created',
            'entity_type' => 'membership',
            'entity_id' => $membership->id,
        ]);

        $log = ActivityLog::where('action', 'membership.created')
            ->where('entity_id', $membership->id)
            ->first();

        $this->assertEquals('owner', $log->metadata['role']);
        $this->assertEquals($workspace->id, $log->metadata['workspace_id']);
    }

    /**
     * Test: Flujo completo (usuario → workspace → membership)
     */
    public function test_complete_workflow_generates_all_logs(): void
    {
        // 1. Crear usuario
        $user = User::factory()->create();

        // 2. Crear workspace
        $workspace = Workspace::factory()->create();

        // 3. Crear membership
        $membership = Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Assert: 3 logs creados
        $this->assertEquals(3, ActivityLog::count());

        $this->assertDatabaseHas('activity_logs', ['action' => 'user.registered']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'workspace.created']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'membership.created']);
    }
}
```

---

## 9. Checklist de Implementación {#checklist-de-implementación}

### 9.1. Fase 1A: Identity Events

- [ ] **PASO 1:** Crear directorio `app-modules/identity/src/Events/`
- [ ] **PASO 2:** Crear directorio `app-modules/identity/src/Observers/`
- [ ] **PASO 3:** Crear `UserRegistered.php`
- [ ] **PASO 4:** Crear `UserEmailVerified.php`
- [ ] **PASO 5:** Crear `WorkspaceCreated.php`
- [ ] **PASO 6:** Crear `MembershipCreated.php`
- [ ] **PASO 7:** Crear `UserObserver.php`
- [ ] **PASO 8:** Crear `WorkspaceObserver.php`
- [ ] **PASO 9:** Crear `MembershipObserver.php`
- [ ] **PASO 10:** Modificar `IdentityServiceProvider.php` para registrar observers

### 9.2. Fase 1B: Activity Listeners

- [ ] **PASO 11:** Crear directorio `app-modules/activity/src/Listeners/`
- [ ] **PASO 12:** Crear `LogUserRegistered.php`
- [ ] **PASO 13:** Crear `LogUserEmailVerified.php`
- [ ] **PASO 14:** Crear `LogWorkspaceCreated.php`
- [ ] **PASO 15:** Crear `LogMembershipCreated.php`

### 9.3. Fase 1C: Configuración Global

- [ ] **PASO 16:** Crear `app/Providers/EventServiceProvider.php`
- [ ] **PASO 17:** Modificar `bootstrap/providers.php` para registrar EventServiceProvider

### 9.4. Fase 1D: Validación

- [ ] **PASO 18:** Ejecutar migraciones si no están aplicadas
- [ ] **PASO 19:** Limpiar cache de Laravel (`php artisan optimize:clear`)
- [ ] **PASO 20:** Probar en Tinker creación de User
- [ ] **PASO 21:** Verificar log en activity_logs
- [ ] **PASO 22:** Probar en Tinker creación de Workspace
- [ ] **PASO 23:** Probar en Tinker creación de Membership
- [ ] **PASO 24:** Crear test `IdentityActivityEventsTest.php`
- [ ] **PASO 25:** Ejecutar tests (`php artisan test`)
- [ ] **PASO 26:** Validar desacoplamiento (búsqueda de imports prohibidos)

---

## 10. Próximos Pasos (Fase 2)

Una vez completada la Fase 1, implementar:

### Fase 2: Publishing Events
- `PostCreated`
- `PostPublished`
- `PostVersionCreated`
- `PostDeleted`

### Fase 3: Community Events
- `CommentCreated`
- `LikeAdded`
- `WorkspaceFollowed`

### Fase 4: Audience Events
- `SubscriberAdded`
- `SubscriberUnsubscribed`
- `ImportJobCompleted`

### Fase 5: Delivery Events
- `CampaignSent`
- `EmailBounced`

---

## 📊 RESUMEN EJECUTIVO

**Objetivo:** Implementar Event-Driven Architecture entre Identity y Activity

**Archivos a crear:** 17
- 4 eventos (Identity)
- 3 observers (Identity)
- 4 listeners (Activity)
- 1 EventServiceProvider (App)
- Modificar: 2 archivos existentes

**Resultado esperado:**
✅ Usuario creado → Log automático en activity_logs  
✅ Workspace creado → Log automático en activity_logs  
✅ Membership creado → Log automático en activity_logs  
✅ Desacoplamiento total: Identity NO conoce Activity  
✅ Modularidad respetada: namespaces independientes  
✅ Base sólida para eventos de Publishing, Community, Audience, Delivery

**Tiempo estimado:** 2-3 horas de implementación + 1 hora de testing

---

**FIN DEL ANÁLISIS**
