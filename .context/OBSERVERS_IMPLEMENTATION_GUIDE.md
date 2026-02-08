# 👁️ GUÍA DE IMPLEMENTACIÓN: OBSERVERS
## Identity Domain - Disparadores de Eventos

**Fecha:** 8 de febrero de 2026  
**Propósito:** Código completo de los observers para disparar eventos automáticamente  
**Referencia:** IDENTITY_TO_ACTIVITY_EVENTS_IMPLEMENTATION_ANALYSIS.md

---

## 📋 ÍNDICE

1. [Concepto de Observers](#concepto-de-observers)
2. [UserObserver](#userobserver)
3. [WorkspaceObserver](#workspaceobserver)
4. [MembershipObserver](#membershipobserver)
5. [Registro en ServiceProvider](#registro-en-serviceprovider)

---

## 1. Concepto de Observers {#concepto-de-observers}

### ¿Qué es un Observer?

Los **Observers** en Laravel son clases que escuchan eventos del ciclo de vida de Eloquent:

```
CICLO DE VIDA DE UN MODELO ELOQUENT:

creating → created → updating → updated → deleting → deleted
   ↓          ↓          ↓          ↓          ↓          ↓
 Antes    Después    Antes    Después    Antes    Después
```

### ¿Por qué usar Observers para eventos?

```php
// ❌ OPCIÓN 1: Disparar eventos manualmente
$user = User::create([...]);
event(new UserRegistered($user)); // ← Fácil olvidar esto

// ✅ OPCIÓN 2: Observers automáticos
$user = User::create([...]); 
// ← UserObserver::created() se ejecuta AUTOMÁTICAMENTE
// ← event(new UserRegistered($user)) se dispara automáticamente
```

**Ventajas:**
- ✅ Automático: NO depende del Controller/Service
- ✅ Consistente: SIEMPRE se dispara (API, CLI, Tinker, Tests)
- ✅ DRY: UN solo lugar define el comportamiento
- ✅ Testeable: Mock del Observer si necesitas

---

## 2. UserObserver {#userobserver}

**Archivo:** `app-modules/identity/src/Observers/UserObserver.php`

```php
<?php

namespace Domains\Identity\Observers;

use Domains\Identity\Models\User;
use Domains\Identity\Events\UserRegistered;
use Domains\Identity\Events\UserEmailVerified;

/**
 * Observer: Escucha eventos del ciclo de vida de User
 * 
 * PROPÓSITO:
 * - Disparar eventos de negocio cuando User cambia
 * - Desacoplar la lógica de eventos del modelo
 * - Garantizar que eventos se disparan automáticamente
 * 
 * CICLO DE VIDA MONITOREADO:
 * ✅ created: Usuario registrado (nuevo usuario)
 * ✅ updated: Usuario actualizado (detectar email_verified_at)
 * 
 * NO MONITOREADO (Fase 1):
 * ❌ deleting: Usuario eliminado (Fase 2)
 * ❌ restored: Usuario restaurado (si soft deletes)
 */
class UserObserver
{
    /**
     * Handle the User "created" event
     * 
     * Se ejecuta DESPUÉS de crear el registro en la BD
     * 
     * Escenarios:
     * - User::create([...])
     * - User::factory()->create()
     * - $user->save() (si es nuevo)
     * 
     * @param User $user El usuario recién creado
     */
    public function created(User $user): void
    {
        // Disparar evento UserRegistered
        event(new UserRegistered(
            user: $user,
            context: [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_via' => 'observer',
            ]
        ));
    }

    /**
     * Handle the User "updated" event
     * 
     * Se ejecuta DESPUÉS de actualizar el registro en la BD
     * 
     * Detecta:
     * - Cambio de email_verified_at de null → timestamp
     * 
     * @param User $user El usuario actualizado
     */
    public function updated(User $user): void
    {
        // Detectar si se verificó el email en esta actualización
        // wasChanged() detecta cambios DESPUÉS del save
        $emailWasVerified = $user->wasChanged('email_verified_at') 
                         && $user->email_verified_at !== null;

        if ($emailWasVerified) {
            event(new UserEmailVerified(
                user: $user,
                verifiedAt: $user->email_verified_at
            ));
        }
    }

    /**
     * Handle the User "deleting" event (OPCIONAL - Fase 2)
     * 
     * Se ejecuta ANTES de borrar el registro
     * Útil para capturar datos antes de que desaparezcan
     */
    // public function deleting(User $user): void
    // {
    //     event(new UserDeleted($user));
    // }
}
```

---

## 3. WorkspaceObserver {#workspaceobserver}

**Archivo:** `app-modules/identity/src/Observers/WorkspaceObserver.php`

```php
<?php

namespace Domains\Identity\Observers;

use Domains\Identity\Models\Workspace;
use Domains\Identity\Events\WorkspaceCreated;

/**
 * Observer: Escucha eventos del ciclo de vida de Workspace
 * 
 * PROPÓSITO:
 * - Disparar eventos cuando se crea un workspace
 * - Permite auditoría y acciones automáticas (inicialización, etc.)
 * 
 * CICLO DE VIDA MONITOREADO:
 * ✅ created: Workspace creado (newsletter/blog nuevo)
 * 
 * NO MONITOREADO (Fase 1):
 * ❌ updated: Workspace actualizado (Fase 2)
 * ❌ deleting: Workspace eliminado (Fase 2 - crítico para auditoría)
 */
class WorkspaceObserver
{
    /**
     * Handle the Workspace "created" event
     * 
     * Se ejecuta DESPUÉS de crear el registro en la BD
     * 
     * Escenarios:
     * - Workspace::create([...])
     * - Workspace::factory()->create()
     * - Usuario crea su primera newsletter
     * 
     * @param Workspace $workspace El workspace recién creado
     */
    public function created(Workspace $workspace): void
    {
        // Disparar evento WorkspaceCreated
        event(new WorkspaceCreated(
            workspace: $workspace,
            ownerId: auth()->id() // Puede ser null si se crea desde CLI/Tinker
        ));
    }

    /**
     * Handle the Workspace "deleting" event (OPCIONAL - Fase 2)
     * 
     * CRÍTICO: Workspace eliminado es acción de alto riesgo
     * Debe quedar registrado en activity_logs
     */
    // public function deleting(Workspace $workspace): void
    // {
    //     event(new WorkspaceDeleted(
    //         workspace: $workspace,
    //         userId: auth()->id(),
    //         postsCount: $workspace->posts()->count(),
    //         subscribersCount: $workspace->subscribers()->count()
    //     ));
    // }
}
```

---

## 4. MembershipObserver {#membershipobserver}

**Archivo:** `app-modules/identity/src/Observers/MembershipObserver.php`

```php
<?php

namespace Domains\Identity\Observers;

use Domains\Identity\Models\Membership;
use Domains\Identity\Events\MembershipCreated;

/**
 * Observer: Escucha eventos del ciclo de vida de Membership
 * 
 * PROPÓSITO:
 * - Registrar cuando un usuario se une a un workspace
 * - Importante para auditoría de permisos (RBAC)
 * - Detectar cambios de role (owner → admin, etc.)
 * 
 * CICLO DE VIDA MONITOREADO:
 * ✅ created: Usuario se unió a workspace
 * 
 * NO MONITOREADO (Fase 1):
 * ❌ updated: Role cambiado (Fase 2)
 * ❌ deleting: Usuario removido del workspace (Fase 2)
 */
class MembershipObserver
{
    /**
     * Handle the Membership "created" event
     * 
     * Se ejecuta DESPUÉS de crear el registro en la BD
     * 
     * Escenarios:
     * - Usuario acepta invitación
     * - Owner añade colaborador manualmente
     * - Membership::create([...]) desde tests/seeder
     * 
     * @param Membership $membership La membresía recién creada
     */
    public function created(Membership $membership): void
    {
        // Disparar evento MembershipCreated
        event(new MembershipCreated(
            membership: $membership
        ));
    }

    /**
     * Handle the Membership "updated" event (OPCIONAL - Fase 2)
     * 
     * Útil para detectar cambios de role:
     * - Writer → Editor
     * - Admin → Owner (transferencia de propiedad)
     */
    // public function updated(Membership $membership): void
    // {
    //     // Detectar cambio de role
    //     if ($membership->wasChanged('role')) {
    //         event(new MembershipRoleChanged(
    //             membership: $membership,
    //             oldRole: $membership->getOriginal('role'),
    //             newRole: $membership->role
    //         ));
    //     }
    // }

    /**
     * Handle the Membership "deleting" event (OPCIONAL - Fase 2)
     * 
     * Importante para auditoría: saber QUIÉN removió a QUIÉN
     */
    // public function deleting(Membership $membership): void
    // {
    //     event(new MembershipRemoved(
    //         membership: $membership,
    //         removedBy: auth()->id()
    //     ));
    // }
}
```

---

## 5. Registro en ServiceProvider {#registro-en-serviceprovider}

### 5.1. Modificar IdentityServiceProvider

**Archivo:** `app-modules/identity/src/Providers/IdentityServiceProvider.php`

```php
<?php

namespace Domains\Identity\Providers;

use Illuminate\Support\ServiceProvider;

// Modelos
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\Membership;

// Observers
use Domains\Identity\Observers\UserObserver;
use Domains\Identity\Observers\WorkspaceObserver;
use Domains\Identity\Observers\MembershipObserver;

/**
 * IdentityServiceProvider: Configuración del dominio Identity
 * 
 * RESPONSABILIDADES:
 * - Registrar observers de modelos
 * - Cargar rutas, migraciones, vistas (futuro)
 * - Configurar servicios del dominio (futuro)
 */
class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Aquí se registran bindings, singletons, etc.
        // Por ahora, Identity no necesita nada aquí
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // ─────────────────────────────────────────────────────────
        // REGISTRAR OBSERVERS
        // ─────────────────────────────────────────────────────────
        // Estos observers disparan eventos automáticamente
        // cuando los modelos cambian (create, update, delete)
        
        User::observe(UserObserver::class);
        Workspace::observe(WorkspaceObserver::class);
        Membership::observe(MembershipObserver::class);
        
        // Nota: NO necesitas registrar los eventos aquí
        // Los observers ya los disparan
        // La conexión eventos → listeners está en EventServiceProvider
    }
}
```

---

## 📊 CHECKLIST DE VERIFICACIÓN

### Archivos Creados

- [ ] `app-modules/identity/src/Observers/UserObserver.php`
- [ ] `app-modules/identity/src/Observers/WorkspaceObserver.php`
- [ ] `app-modules/identity/src/Observers/MembershipObserver.php`

### Archivo Modificado

- [ ] `app-modules/identity/src/Providers/IdentityServiceProvider.php`

### Verificación de Funcionamiento

```bash
# Test 1: Verificar que observers se registran correctamente
php artisan tinker
>>> User::getObservableEvents()
# => ["retrieved", "creating", "created", "updating", "updated", ...]

# Test 2: Crear usuario y verificar evento
>>> \Event::fake()
>>> User::factory()->create()
>>> \Event::assertDispatched(\Domains\Identity\Events\UserRegistered::class)

# Test 3: Sin Event::fake(), verificar que listener se ejecuta
>>> User::factory()->create()
>>> \Domains\Activity\Models\ActivityLog::where('action', 'user.registered')->count()
# => debería incrementar
```

---

## 🔍 DEBUGGING: Si los observers NO funcionan

### Problema 1: Observer no se dispara

```bash
# Verificar que el ServiceProvider está registrado
php artisan about

# Buscar: IdentityServiceProvider en la lista
# Si no aparece, revisar config/app-modules.php
```

### Problema 2: Evento se dispara pero listener no se ejecuta

```bash
# Verificar EventServiceProvider
php artisan event:list

# Buscar: UserRegistered → LogUserRegistered
# Si no aparece, revisar app/Providers/EventServiceProvider.php
```

### Problema 3: "Class not found"

```bash
# Limpiar cache de Laravel
php artisan optimize:clear

# Regenerar autoload de Composer
composer dump-autoload
```

---

## 🎯 PRÓXIMO PASO

Una vez implementados estos observers, continuar con:
1. Crear los Listeners en Activity (ver IDENTITY_TO_ACTIVITY_EVENTS_IMPLEMENTATION_ANALYSIS.md)
2. Crear el EventServiceProvider global
3. Ejecutar tests de validación

---

**FIN DE LA GUÍA DE OBSERVERS**
