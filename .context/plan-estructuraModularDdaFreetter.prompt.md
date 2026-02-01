# Plan: Estructura Modular DDA - Freetter

Preparar la arquitectura modular completa de Freetter usando `internachi/modular` con 6 dominios (Identity, Publishing, Community, Audience, Delivery, Activity), implementando migraciones, models, service providers y configuraciones base para que cada módulo esté listo para desarrollar la lógica de negocio.

---

## EPIC 1: Configuración Base y Resolución de Conflictos

### [SETUP-001] Configurar entorno base PostgreSQL y eliminar conflictos del core

**Prioridad:** 🔴 CRÍTICA  
**Estimación:** 2h  
**Módulo:** Core Application

#### Descripción
Actualmente el proyecto tiene configuración SQLite y un modelo User en `App\Models\` que conflicta con la arquitectura modular. Según la documentación de Laravel sobre [base de datos PostgreSQL](https://laravel.com/docs/12.x/database#configuration), debemos configurar el driver correcto y actualizar las referencias de autenticación para que apunten al módulo Identity.

#### Tareas Técnicas

1. **Actualizar `.env` para PostgreSQL**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=freetter_dev
   DB_USERNAME=postgres
   DB_PASSWORD=secret
   ```

2. **Modificar `config/database.php`**
   - Cambiar `'default' => env('DB_CONNECTION', 'sqlite')` a `'pgsql'`
   - Verificar que la configuración `pgsql` tenga `'charset' => 'utf8'` y `'prefix' => ''`

3. **Actualizar `config/auth.php`**
   - Cambiar `'model' => env('AUTH_MODEL', App\Models\User::class)`
   - Por: `'model' => env('AUTH_MODEL', Domains\Identity\Models\User::class)`
   - **Referencia:** [Laravel Authentication](https://laravel.com/docs/12.x/authentication#introduction)

4. **Eliminar modelo y migración del core**
   - Eliminar `app/Models/User.php` (se moverá a Identity en siguiente ticket)
   - Eliminar `database/migrations/0001_01_01_000000_create_users_table.php`
   - **Justificación:** Según el patrón de módulos independientes de `internachi/modular`, cada módulo debe ser dueño de sus propias migraciones

5. **Eliminar Factory del core**
   - Eliminar `database/factories/UserFactory.php` (se recreará en Identity)

#### Criterios de Aceptación
- [ ] Conexión exitosa a PostgreSQL con `php artisan db:show`
- [ ] `config/auth.php` apunta a `Domains\Identity\Models\User`
- [ ] No existen modelos/migraciones de User en el core
- [ ] `composer dump-autoload` ejecuta sin errores

#### Documentación de Referencia
- [Laravel 12.x Database Configuration](https://laravel.com/docs/12.x/database#configuration)
- [Laravel 12.x Authentication Configuration](https://laravel.com/docs/12.x/authentication#introduction)
- [internachi/modular Conventions](https://github.com/InterNACHI/modular)

---

## EPIC 2: Módulo Identity (Base del Sistema)

### [IDENTITY-001] Crear migraciones del módulo Identity

**Prioridad:** 🔴 CRÍTICA  
**Estimación:** 3h  
**Módulo:** `app-modules/identity`  
**Dependencias:** [SETUP-001]

#### Descripción
Identity es el módulo base que maneja usuarios, workspaces, membresías e invitaciones. Todos los demás módulos tienen foreign keys hacia estas tablas, por lo que debe implementarse primero según el orden de dependencias del archivo `.context/entidades-corregidas.md`.

#### Tareas Técnicas

**1. Crear migración `identity_users`**

```bash
php artisan make:migration create_identity_users_table --module=identity
```

**Campos requeridos (según entidades-corregidas.md):**
- `id` → `uuid()` como primary key
- `name` → `string()`
- `email` → `string()->unique()`
- `email_verified_at` → `timestamp()->nullable()`
- `avatar_path` → `string()->nullable()`
- `remember_token` → `string()->nullable()`
- `created_at` → `timestamp()`

**Índices:**
```php
$table->index('email');
$table->index('created_at');
```

**Referencia:** [Laravel Migrations - Available Column Types](https://laravel.com/docs/12.x/migrations#available-column-types)

---

**2. Crear migración `identity_workspaces`**

```bash
php artisan make:migration create_identity_workspaces_table --module=identity
```

**Campos requeridos:**
- `id` → `uuid()`
- `name` → `string()`
- `slug` → `string()->unique()`
- `branding_config` → `jsonb()`
- `donation_config` → `jsonb()`
- `created_at` → `timestamp()`

**Índices:**
```php
$table->index('slug');
```

---

**3. Crear migración `identity_memberships`**

```bash
php artisan make:migration create_identity_memberships_table --module=identity
```

**Campos requeridos:**
- `id` → `uuid()`
- `user_id` → `uuid()`
- `workspace_id` → `uuid()`
- `role` → `enum(['owner', 'admin', 'editor', 'writer'])`
- `joined_at` → `timestamp()`

**Índices compuestos:**
```php
$table->unique(['user_id', 'workspace_id']); // Un user no puede tener roles duplicados
```

**⚠️ NO usar `->foreign()`:** Seguimos el patrón Shared Database sin constraints FK a nivel DB

---

**4. Crear migración `identity_invitations`**

```bash
php artisan make:migration create_identity_invitations_table --module=identity
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `email` → `string()`
- `role` → `string()`
- `token` → `string()->unique()`
- `expires_at` → `timestamp()`
- `accepted_by_user_id` → `uuid()->nullable()`

**Índices:**
```php
$table->index('token');
$table->index('email');
```

#### Criterios de Aceptación
- [ ] 4 archivos de migración creados en `app-modules/identity/database/migrations/`
- [ ] Todas usan `uuid()` como primary key
- [ ] NO hay constraints `->foreign()` en las migraciones
- [ ] Nombres de tabla con prefijo `identity_`
- [ ] `php artisan migrate:status` muestra las 4 migraciones pendientes

#### Documentación de Referencia
- [Laravel Migrations - Column Modifiers](https://laravel.com/docs/12.x/migrations#column-modifiers)
- [Laravel Migrations - Indexes](https://laravel.com/docs/12.x/migrations#indexes)
- Archivo `.context/entidades-corregidas.md` (líneas 1-100)

---

### [IDENTITY-002] Crear Models del módulo Identity

**Prioridad:** 🔴 CRÍTICA  
**Estimación:** 2h  
**Módulo:** `app-modules/identity`  
**Dependencias:** [IDENTITY-001]

#### Descripción
Crear los 4 modelos Eloquent del módulo Identity con sus relaciones, casts y configuraciones según las convenciones de Laravel. Estos modelos serán la base para la autenticación y autorización de toda la aplicación.

#### Tareas Técnicas

**1. Crear `User.php`**

```bash
php artisan make:model User --module=identity
```

**Ubicación:** `app-modules/identity/src/Models/User.php`

**Configuración requerida:**
```php
namespace Domains\Identity\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasUuids, Notifiable;
    
    protected $table = 'identity_users';
    
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'avatar_url',
        'timezone',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    // Relaciones
    public function memberships() {
        return $this->hasMany(Membership::class);
    }
    
    public function workspaces() {
        return $this->belongsToMany(Workspace::class, 'identity_memberships')
                    ->withPivot('role', 'joined_at');
    }
}
```

**⚠️ IMPORTANTE:** 
- Heredar de `Authenticatable` (no `Model`)
- Usar trait `HasUuids` para UUIDs automáticos ([Laravel UUIDs](https://laravel.com/docs/12.x/eloquent#uuid-and-ulid-keys))
- NO incluir `password` en `$fillable` ni `$hidden`

---

**2. Crear `Workspace.php`**

```bash
php artisan make:model Workspace --module=identity
```

**Configuración requerida:**
```php
namespace Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Workspace extends Model
{
    use HasUuids;
    
    protected $table = 'identity_workspaces';
    
    protected $fillable = [
        'name',
        'slug',
        'avatar_url',
        'bio',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // Relaciones
    public function members() {
        return $this->belongsToMany(User::class, 'identity_memberships')
                    ->withPivot('role', 'joined_at');
    }
    
    public function memberships() {
        return $this->hasMany(Membership::class);
    }
}
```

---

**3. Crear `Membership.php`**

```bash
php artisan make:model Membership --module=identity
```

**Configuración requerida:**
```php
namespace Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Membership extends Model
{
    use HasUuids;
    
    protected $table = 'identity_memberships';
    
    protected $fillable = [
        'user_id',
        'workspace_id',
        'role',
        'joined_at',
    ];
    
    protected $casts = [
        'joined_at' => 'datetime',
    ];
    
    // Relaciones
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }
}
```

---

**4. Crear `Invitation.php`**

```bash
php artisan make:model Invitation --module=identity
```

**Configuración requerida:**
```php
namespace Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invitation extends Model
{
    use HasUuids;
    
    protected $table = 'identity_invitations';
    
    protected $fillable = [
        'workspace_id',
        'email',
        'role',
        'token',
        'invited_by',
        'accepted_at',
        'expires_at',
    ];
    
    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    // Relaciones
    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }
    
    public function inviter() {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
```

#### Criterios de Aceptación
- [ ] 4 modelos creados en `app-modules/identity/src/Models/`
- [ ] Todos usan trait `HasUuids`
- [ ] Todas las relaciones Eloquent definidas correctamente
- [ ] `protected $table` define el nombre correcto con prefijo
- [ ] No hay referencias a campo `password` en User
- [ ] `composer dump-autoload` sin errores

#### Documentación de Referencia
- [Laravel Eloquent - UUID Keys](https://laravel.com/docs/12.x/eloquent#uuid-and-ulid-keys)
- [Laravel Eloquent - Relationships](https://laravel.com/docs/12.x/eloquent-relationships)
- [Laravel Eloquent - Attribute Casting](https://laravel.com/docs/12.x/eloquent-mutators#attribute-casting)

---

### [IDENTITY-003] Crear Factory para testing y actualizar ServiceProvider

**Prioridad:** 🟡 ALTA  
**Estimación:** 1.5h  
**Módulo:** `app-modules/identity`  
**Dependencias:** [IDENTITY-002]

#### Descripción
Crear factories para generar datos de prueba en tests y actualizar el ServiceProvider para registrar correctamente las migraciones, rutas y vistas del módulo según las convenciones de `internachi/modular`.

#### Tareas Técnicas

**1. Crear `UserFactory.php`**

```bash
php artisan make:factory UserFactory --model=User --module=identity
```

**Ubicación:** `app-modules/identity/database/factories/UserFactory.php`

**Configuración:**
```php
namespace Domains\Identity\Database\Factories;

use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'avatar_url' => fake()->imageUrl(200, 200, 'people'),
            'timezone' => fake()->timezone(),
        ];
    }
    
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

**Referencia:** [Laravel Database Testing - Factories](https://laravel.com/docs/12.x/eloquent-factories)

---

**2. Crear `WorkspaceFactory.php`**

```bash
php artisan make:factory WorkspaceFactory --model=Workspace --module=identity
```

**Configuración:**
```php
namespace Domains\Identity\Database\Factories;

use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;
    
    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'avatar_url' => fake()->imageUrl(200, 200, 'business'),
            'bio' => fake()->sentence(20),
            'is_active' => true,
        ];
    }
    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

---

**3. Actualizar `IdentityServiceProvider.php`**

**Archivo:** `app-modules/identity/src/Providers/IdentityServiceProvider.php`

**Contenido completo:**
```php
namespace Domains\Identity\Providers;

use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Cargar rutas
        $this->loadRoutesFrom(__DIR__ . '/../../routes/identity-routes.php');
        
        // Cargar vistas con namespace 'identity'
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'identity');
        
        // Publicar configuraciones (opcional)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'identity-migrations');
        }
    }
}
```

**Justificación:**
- `loadMigrationsFrom()`: Permite que `php artisan migrate` detecte las migraciones ([docs](https://laravel.com/docs/12.x/packages#migrations))
- `loadRoutesFrom()`: Auto-carga las rutas sin necesidad de registrarlas en `routes/web.php`
- `loadViewsFrom()`: Permite usar vistas con `view('identity::index')`

---

**4. Actualizar `routes/identity-routes.php`**

Descomentar y crear rutas básicas:

```php
use Illuminate\Support\Facades\Route;

Route::prefix('identity')->name('identity.')->group(function () {
    // Placeholder - implementar en sprint de autenticación
    Route::get('/test', function () {
        return response()->json([
            'module' => 'identity',
            'status' => 'active',
        ]);
    })->name('test');
});
```

#### Criterios de Aceptación
- [ ] 2 factories creados en `app-modules/identity/database/factories/`
- [ ] ServiceProvider carga migraciones, rutas y vistas correctamente
- [ ] `php artisan route:list` muestra la ruta `identity.test`
- [ ] `php artisan migrate` ejecuta las migraciones de Identity
- [ ] Factory funciona: `Domains\Identity\Models\User::factory()->create()`

#### Documentación de Referencia
- [Laravel Package Development - Service Providers](https://laravel.com/docs/12.x/packages#service-providers)
- [Laravel Package Development - Migrations](https://laravel.com/docs/12.x/packages#migrations)
- [Laravel Eloquent Factories](https://laravel.com/docs/12.x/eloquent-factories)

---

## EPIC 3: Módulo Activity (Logging y Auditoría)

### [ACTIVITY-001] Crear módulo Activity completo

**Prioridad:** 🟡 ALTA  
**Estimación:** 2.5h  
**Módulo:** `app-modules/activity` (NO EXISTE)  
**Dependencias:** [IDENTITY-002]

#### Descripción
Activity es un módulo nuevo que no existe actualmente. Maneja el logging inmutable de todas las acciones en el sistema para auditoría GDPR y debugging. Según `.context/entidades-corregidas.md`, incluye 3 tablas: `activity_logs`, `activity_streams` y `activity_alerts`.

#### Tareas Técnicas

**1. Crear el módulo**

```bash
php artisan make:module activity
```

**Esto generará:**
- `app-modules/activity/` (directorio)
- Actualización de `composer.json` con `"domains/activity": "*"`
- Scaffold básico (src/, database/, routes/, etc.)

**Después ejecutar:**
```bash
composer update domains/activity
```

---

**2. Crear migración `activity_logs`**

```bash
php artisan make:migration create_activity_logs_table --module=activity
```

**Campos requeridos:**
- `id` → `uuid()`
- `user_id` → `uuid()->nullable()` (puede ser acción del sistema)
- `workspace_id` → `uuid()->nullable()`
- `event` → `string()` (ej: 'post.published', 'user.invited')
- `entity_type` → `string()->nullable()` (ej: 'Post', 'User')
- `entity_id` → `uuid()->nullable()`
- `metadata` → `json()->nullable()` (datos adicionales)
- `ip_address` → `string(45)->nullable()` (IPv6 compatible)
- `user_agent` → `text()->nullable()`
- `created_at` → `timestamp()->useCurrent()`

**⚠️ IMPORTANTE:**
- **NO** incluir `updated_at` (tabla inmutable)
- Usar `->useCurrent()` para timestamp automático
- Índices para búsquedas rápidas:

```php
$table->index('user_id');
$table->index('workspace_id');
$table->index('event');
$table->index(['entity_type', 'entity_id']);
$table->index('created_at');
```

**Justificación:** Tabla de append-only para cumplir con requisitos de auditoría ([Laravel Auditing](https://laravel.com/docs/12.x/database#pruning-models))

---

**3. Crear migración `activity_streams`**

```bash
php artisan make:migration create_activity_streams_table --module=activity
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `user_id` → `uuid()->nullable()`
- `activity_type` → `string()` (ej: 'post_published', 'comment_added')
- `actor_id` → `uuid()` (quien realizó la acción)
- `actor_type` → `string()` (User, System, etc.)
- `subject_id` → `uuid()` (sobre qué entidad)
- `subject_type` → `string()` (Post, Comment, etc.)
- `data` → `json()->nullable()`
- `is_public` → `boolean()->default(true)`
- `created_at` → `timestamp()->useCurrent()`

**Índices:**
```php
$table->index('workspace_id');
$table->index(['actor_type', 'actor_id']);
$table->index(['subject_type', 'subject_id']);
$table->index('activity_type');
$table->index('is_public');
$table->index('created_at');
```

---

**4. Crear migración `activity_alerts`**

```bash
php artisan make:migration create_activity_alerts_table --module=activity
```

**Campos requeridos:**
- `id` → `uuid()`
- `user_id` → `uuid()`
- `workspace_id` → `uuid()->nullable()`
- `type` → `enum(['info', 'warning', 'error', 'success'])`
- `title` → `string()`
- `message` → `text()`
- `action_url` → `string()->nullable()`
- `is_read` → `boolean()->default(false)`
- `read_at` → `timestamp()->nullable()`
- `created_at` → `timestamp()->useCurrent()`

**Índices:**
```php
$table->index('user_id');
$table->index('workspace_id');
$table->index('is_read');
$table->index('created_at');
```

---

**5. Crear Models**

```bash
php artisan make:model ActivityLog --module=activity
php artisan make:model ActivityStream --module=activity
php artisan make:model ActivityAlert --module=activity
```

**Configuración de `ActivityLog.php`:**
```php
namespace Domains\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityLog extends Model
{
    use HasUuids;
    
    protected $table = 'activity_logs';
    
    public const UPDATED_AT = null; // Deshabilitar updated_at
    
    protected $fillable = [
        'user_id',
        'workspace_id',
        'event',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}
```

**⚠️ IMPORTANTE:** `public const UPDATED_AT = null;` deshabilita el timestamp `updated_at` ([docs](https://laravel.com/docs/12.x/eloquent#timestamps))

---

**6. Actualizar `ActivityServiceProvider.php`**

```php
namespace Domains\Activity\Providers;

use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/activity-routes.php');
    }
}
```

---

**7. Actualizar `composer.json` del proyecto**

Verificar que se añadió automáticamente:
```json
"require": {
    "domains/activity": "*"
}
```

Si no está, añadirlo manualmente y ejecutar `composer update`.

#### Criterios de Aceptación
- [ ] Módulo `activity` existe en `app-modules/activity/`
- [ ] 3 migraciones creadas con campos correctos
- [ ] 3 modelos creados con configuración inmutable en `ActivityLog`
- [ ] ServiceProvider registra migraciones y rutas
- [ ] `composer.json` incluye `"domains/activity": "*"`
- [ ] `php artisan migrate:status` muestra las 3 nuevas migraciones
- [ ] Test de creación: `ActivityLog::create([...])` funciona sin `updated_at`

#### Documentación de Referencia
- [Laravel Eloquent - Timestamps](https://laravel.com/docs/12.x/eloquent#timestamps)
- [Laravel JSON Columns](https://laravel.com/docs/12.x/queries#json-where-clauses)
- Archivo `.context/entidades-corregidas.md` (sección ACTIVITY)

---

## EPIC 4: Módulo Publishing (Contenido)

### [PUBLISHING-001] Crear migraciones del módulo Publishing

**Prioridad:** 🟡 ALTA  
**Estimación:** 3.5h  
**Módulo:** `app-modules/publishing`  
**Dependencias:** [IDENTITY-002]

#### Descripción
Publishing es el módulo central de contenido de Freetter. Maneja posts, versiones, media y tags. Según `.context/entidades-corregidas.md`, requiere 6 tablas con relaciones complejas incluyendo polimorfismo para el sistema de media.

#### Tareas Técnicas

**1. Crear migración `publishing_posts`**

```bash
php artisan make:migration create_publishing_posts_table --module=publishing
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `author_id` → `uuid()` (FK a identity_users)
- `title` → `string()`
- `slug` → `string()`
- `content` → `json()` (Editor.js format)
- `excerpt` → `text()->nullable()`
- `featured_image_url` → `string()->nullable()`
- `status` → `enum(['draft', 'scheduled', 'published'])`
- `published_at` → `timestamp()->nullable()`
- `scheduled_for` → `timestamp()->nullable()`
- `carbon_score` → `integer()->nullable()` (0-100)
- `word_count` → `integer()->default(0)`
- `reading_time` → `integer()->default(0)` (minutos)
- `meta_title` → `string()->nullable()`
- `meta_description` → `text()->nullable()`
- `created_at`, `updated_at` → `timestamps()`

**Índices:**
```php
$table->unique(['workspace_id', 'slug']);
$table->index('author_id');
$table->index('status');
$table->index('published_at');
$table->index('scheduled_for');
$table->index('carbon_score');
```

**Referencia:** [Laravel Enum Columns](https://laravel.com/docs/12.x/migrations#column-method-enum)

---

**2. Crear migración `publishing_post_versions`**

```bash
php artisan make:migration create_publishing_post_versions_table --module=publishing
```

**Campos requeridos:**
- `id` → `uuid()`
- `post_id` → `uuid()` (FK a publishing_posts)
- `version_number` → `integer()`
- `title` → `string()`
- `content` → `json()`
- `created_by` → `uuid()` (FK a identity_users)
- `created_at` → `timestamp()->useCurrent()`

**⚠️ IMPORTANTE:**
- NO incluir `updated_at` (versiones son inmutables)
- Índice único compuesto:

```php
$table->unique(['post_id', 'version_number']);
$table->index('created_by');
$table->index('created_at');
```

**Justificación:** Sistema de control de versiones como Google Docs ([Laravel Model Versioning](https://github.com/overtrue/laravel-versioning))

---

**3. Crear migración `publishing_media`**

```bash
php artisan make:migration create_publishing_media_table --module=publishing
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `filename` → `string()`
- `file_path` → `string()`
- `file_size` → `bigInteger()` (bytes)
- `mime_type` → `string()`
- `type` → `enum(['image', 'video', 'document'])`
- `uploaded_by` → `uuid()` (FK a identity_users)
- `alt_text` → `string()->nullable()`
- `caption` → `text()->nullable()`
- `created_at`, `updated_at` → `timestamps()`

**Índices:**
```php
$table->index('workspace_id');
$table->index('type');
$table->index('uploaded_by');
$table->index('created_at');
```

---

**4. Crear migración `publishing_post_media` (Tabla Pivote Polimórfica)**

```bash
php artisan make:migration create_publishing_post_media_table --module=publishing
```

**Campos requeridos:**
- `id` → `uuid()`
- `media_id` → `uuid()` (FK a publishing_media)
- `mediable_type` → `string()` (ej: 'Domains\Publishing\Models\Post')
- `mediable_id` → `uuid()` (ID del Post)
- `order` → `integer()->default(0)` (orden de aparición)
- `created_at` → `timestamp()->useCurrent()`

**Índices:**
```php
$table->index('media_id');
$table->index(['mediable_type', 'mediable_id']);
$table->index('order');
```

**Referencia:** [Laravel Polymorphic Relationships](https://laravel.com/docs/12.x/eloquent-relationships#polymorphic-relationships)

---

**5. Crear migración `publishing_tags`**

```bash
php artisan make:migration create_publishing_tags_table --module=publishing
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `name` → `string()`
- `slug` → `string()`
- `color` → `string()->nullable()` (hex color)
- `created_at`, `updated_at` → `timestamps()`

**Índices:**
```php
$table->unique(['workspace_id', 'slug']);
$table->index('name');
```

---

**6. Crear migración `publishing_post_tag` (Tabla Pivote)**

```bash
php artisan make:migration create_publishing_post_tag_table --module=publishing
```

**Campos requeridos:**
- `post_id` → `uuid()`
- `tag_id` → `uuid()`

**Índices:**
```php
$table->primary(['post_id', 'tag_id']);
$table->index('tag_id');
```

**⚠️ IMPORTANTE:** Usar primary key compuesto sin ID autoincrementable ([Laravel Pivot Tables](https://laravel.com/docs/12.x/eloquent-relationships#many-to-many))

#### Criterios de Aceptación
- [ ] 6 archivos de migración creados
- [ ] `publishing_posts` tiene enum de status correcto
- [ ] `publishing_post_versions` sin `updated_at`
- [ ] `publishing_post_media` configurada como pivote polimórfica
- [ ] `publishing_post_tag` tiene primary key compuesto
- [ ] Todos los índices definidos correctamente
- [ ] `php artisan migrate:status` muestra las 6 migraciones pendientes

#### Documentación de Referencia
- [Laravel Polymorphic Relationships](https://laravel.com/docs/12.x/eloquent-relationships#polymorphic-relationships)
- [Laravel Many-to-Many Relationships](https://laravel.com/docs/12.x/eloquent-relationships#many-to-many)
- Archivo `.context/entidades-corregidas.md` (sección PUBLISHING)

---

### [PUBLISHING-002] Crear Models del módulo Publishing

**Prioridad:** 🟡 ALTA  
**Estimación:** 2.5h  
**Módulo:** `app-modules/publishing`  
**Dependencias:** [PUBLISHING-001]

#### Descripción
Crear los modelos Eloquent con relaciones complejas: relación polimórfica para media, relación many-to-many con tags, y relación uno-a-muchos con versiones. Incluir scopes para filtrar por status y fechas de publicación.

#### Tareas Técnicas

**1. Crear `Post.php`**

```bash
php artisan make:model Post --module=publishing
```

**Configuración:**
```php
namespace Domains\Publishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $table = 'publishing_posts';
    
    protected $fillable = [
        'workspace_id',
        'author_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image_url',
        'status',
        'published_at',
        'scheduled_for',
        'carbon_score',
        'word_count',
        'reading_time',
        'meta_title',
        'meta_description',
    ];
    
    protected $casts = [
        'content' => 'array', // Editor.js JSON
        'published_at' => 'datetime',
        'scheduled_for' => 'datetime',
    ];
    
    // Relaciones
    public function workspace() {
        return $this->belongsTo(\Domains\Identity\Models\Workspace::class);
    }
    
    public function author() {
        return $this->belongsTo(\Domains\Identity\Models\User::class, 'author_id');
    }
    
    public function versions() {
        return $this->hasMany(PostVersion::class)->orderBy('version_number', 'desc');
    }
    
    public function tags() {
        return $this->belongsToMany(Tag::class, 'publishing_post_tag');
    }
    
    public function media() {
        return $this->morphToMany(Media::class, 'mediable', 'publishing_post_media')
                    ->withPivot('order')
                    ->orderBy('order');
    }
    
    // Scopes
    public function scopePublished($query) {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }
    
    public function scopeDraft($query) {
        return $query->where('status', 'draft');
    }
    
    public function scopeScheduled($query) {
        return $query->where('status', 'scheduled')
                    ->whereNotNull('scheduled_for')
                    ->where('scheduled_for', '>', now());
    }
}
```

**⚠️ IMPORTANTE:**
- Usar `SoftDeletes` para no perder contenido ([Laravel Soft Deletes](https://laravel.com/docs/12.x/eloquent#soft-deleting))
- Cast `content` como `array` para trabajar con JSON Editor.js
- Scopes para queries comunes (published, draft, scheduled)

---

**2. Crear `PostVersion.php`**

```bash
php artisan make:model PostVersion --module=publishing
```

**Configuración:**
```php
namespace Domains\Publishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PostVersion extends Model
{
    use HasUuids;
    
    protected $table = 'publishing_post_versions';
    
    public const UPDATED_AT = null;
    
    protected $fillable = [
        'post_id',
        'version_number',
        'title',
        'content',
        'created_by',
    ];
    
    protected $casts = [
        'content' => 'array',
        'created_at' => 'datetime',
    ];
    
    // Relaciones
    public function post() {
        return $this->belongsTo(Post::class);
    }
    
    public function creator() {
        return $this->belongsTo(\Domains\Identity\Models\User::class, 'created_by');
    }
}
```

---

**3. Crear `Media.php`**

```bash
php artisan make:model Media --module=publishing
```

**Configuración:**
```php
namespace Domains\Publishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Media extends Model
{
    use HasUuids;
    
    protected $table = 'publishing_media';
    
    protected $fillable = [
        'workspace_id',
        'filename',
        'file_path',
        'file_size',
        'mime_type',
        'type',
        'uploaded_by',
        'alt_text',
        'caption',
    ];
    
    protected $casts = [
        'file_size' => 'integer',
    ];
    
    // Relaciones
    public function workspace() {
        return $this->belongsTo(\Domains\Identity\Models\Workspace::class);
    }
    
    public function uploader() {
        return $this->belongsTo(\Domains\Identity\Models\User::class, 'uploaded_by');
    }
    
    // Helper methods
    public function getUrlAttribute() {
        return asset('storage/' . $this->file_path);
    }
    
    public function getHumanSizeAttribute() {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

---

**4. Crear `Tag.php`**

```bash
php artisan make:model Tag --module=publishing
```

**Configuración:**
```php
namespace Domains\Publishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tag extends Model
{
    use HasUuids;
    
    protected $table = 'publishing_tags';
    
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'color',
    ];
    
    // Relaciones
    public function workspace() {
        return $this->belongsTo(\Domains\Identity\Models\Workspace::class);
    }
    
    public function posts() {
        return $this->belongsToMany(Post::class, 'publishing_post_tag');
    }
}
```

---

**5. Actualizar `PublishingServiceProvider.php`**

```php
namespace Domains\Publishing\Providers;

use Illuminate\Support\ServiceProvider;

class PublishingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/publishing-routes.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'publishing');
    }
}
```

#### Criterios de Aceptación
- [ ] 4 modelos creados con relaciones correctas
- [ ] `Post` tiene scopes `published()`, `draft()`, `scheduled()`
- [ ] `Post` usa `SoftDeletes`
- [ ] `PostVersion` sin `updated_at`
- [ ] Relación polimórfica `media()` configurada
- [ ] Relación many-to-many `tags()` configurada
- [ ] ServiceProvider carga migraciones y vistas
- [ ] Test: `Post::factory()->create()` funciona con relaciones

#### Documentación de Referencia
- [Laravel Query Scopes](https://laravel.com/docs/12.x/eloquent#query-scopes)
- [Laravel Soft Deletes](https://laravel.com/docs/12.x/eloquent#soft-deleting)
- [Laravel Accessors & Mutators](https://laravel.com/docs/12.x/eloquent-mutators)

---

## EPIC 5: Módulos Community, Audience y Delivery

### [COMMUNITY-001] Implementar módulo Community

**Prioridad:** 🟡 ALTA  
**Estimación:** 2.5h  
**Módulo:** `app-modules/community`  
**Dependencias:** [PUBLISHING-002], [IDENTITY-002]

#### Descripción
Community maneja la interacción social: comentarios, likes y follows. Requiere eliminar una migración duplicada existente y crear 3 tablas con índices optimizados para queries de feeds.

#### Tareas Técnicas

**1. Eliminar migraciones duplicadas**

Actualmente existen 2 archivos:
- `2026_02_01_093546_set_up_community_module.php`
- `2026_02_01_093847_set_up_community_module.php`

**Acción:** Eliminar ambos archivos (están vacíos).

---

**2. Crear migración `community_comments`**

```bash
php artisan make:migration create_community_comments_table --module=community
```

**Campos requeridos:**
- `id` → `uuid()`
- `post_id` → `uuid()` (FK a publishing_posts)
- `user_id` → `uuid()` (FK a identity_users)
- `parent_id` → `uuid()->nullable()` (para comentarios anidados)
- `content` → `text()`
- `is_approved` → `boolean()->default(true)`
- `created_at`, `updated_at` → `timestamps()`
- `deleted_at` → `timestamp()->nullable()` (soft deletes)

**Índices:**
```php
$table->index('post_id');
$table->index('user_id');
$table->index('parent_id');
$table->index('is_approved');
$table->index('created_at');
```

**Justificación:** Índice en `parent_id` para queries de hilos de comentarios ([Laravel Nested Comments](https://github.com/spatie/laravel-comments))

---

**3. Crear migración `community_likes`**

```bash
php artisan make:migration create_community_likes_table --module=community
```

**Campos requeridos:**
- `id` → `uuid()`
- `user_id` → `uuid()`
- `likeable_type` → `string()` (polimórfico: Post, Comment)
- `likeable_id` → `uuid()`
- `created_at` → `timestamp()->useCurrent()`

**⚠️ IMPORTANTE:** NO incluir `updated_at` (los likes no se editan)

**Índices:**
```php
$table->unique(['user_id', 'likeable_type', 'likeable_id']); // Un user solo puede dar 1 like
$table->index(['likeable_type', 'likeable_id']);
$table->index('created_at');
```

---

**4. Crear migración `community_followers`**

```bash
php artisan make:migration create_community_followers_table --module=community
```

**Campos requeridos:**
- `id` → `uuid()`
- `follower_id` → `uuid()` (FK a identity_users)
- `followed_workspace_id` → `uuid()` (FK a identity_workspaces)
- `created_at` → `timestamp()->useCurrent()`

**Índices:**
```php
$table->unique(['follower_id', 'followed_workspace_id']);
$table->index('followed_workspace_id');
$table->index('created_at');
```

---

**5. Crear Models**

```bash
php artisan make:model Comment --module=community
php artisan make:model Like --module=community
php artisan make:model Follower --module=community
```

**`Comment.php`:**
```php
namespace Domains\Community\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $table = 'community_comments';
    
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
        'is_approved',
    ];
    
    protected $casts = [
        'is_approved' => 'boolean',
    ];
    
    // Relaciones
    public function post() {
        return $this->belongsTo(\Domains\Publishing\Models\Post::class);
    }
    
    public function user() {
        return $this->belongsTo(\Domains\Identity\Models\User::class);
    }
    
    public function parent() {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
    
    public function replies() {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }
    
    public function likes() {
        return $this->morphMany(\Domains\Community\Models\Like::class, 'likeable');
    }
    
    // Scopes
    public function scopeApproved($query) {
        return $query->where('is_approved', true);
    }
    
    public function scopeRootComments($query) {
        return $query->whereNull('parent_id');
    }
}
```

**`Like.php`:**
```php
namespace Domains\Community\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Like extends Model
{
    use HasUuids;
    
    protected $table = 'community_likes';
    
    public const UPDATED_AT = null;
    
    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
    ];
    
    // Relaciones
    public function user() {
        return $this->belongsTo(\Domains\Identity\Models\User::class);
    }
    
    public function likeable() {
        return $this->morphTo();
    }
}
```

**`Follower.php`:**
```php
namespace Domains\Community\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Follower extends Model
{
    use HasUuids;
    
    protected $table = 'community_followers';
    
    public const UPDATED_AT = null;
    
    protected $fillable = [
        'follower_id',
        'followed_workspace_id',
    ];
    
    // Relaciones
    public function follower() {
        return $this->belongsTo(\Domains\Identity\Models\User::class, 'follower_id');
    }
    
    public function workspace() {
        return $this->belongsTo(\Domains\Identity\Models\Workspace::class, 'followed_workspace_id');
    }
}
```

---

**6. Actualizar `CommunityServiceProvider.php`**

```php
namespace Domains\Community\Providers;

use Illuminate\Support\ServiceProvider;

class CommunityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/community-routes.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'community');
    }
}
```

#### Criterios de Aceptación
- [ ] Migraciones duplicadas eliminadas
- [ ] 3 nuevas migraciones creadas
- [ ] `community_likes` sin `updated_at`
- [ ] `Comment` tiene relación `replies()` para comentarios anidados
- [ ] `Like` configurado como relación polimórfica
- [ ] Índice único en likes para evitar duplicados
- [ ] ServiceProvider actualizado
- [ ] Test: Crear comentario con respuesta funciona

#### Documentación de Referencia
- [Laravel Polymorphic Relationships](https://laravel.com/docs/12.x/eloquent-relationships#polymorphic-relationships)
- [Laravel Self-Referencing Relationships](https://laravel.com/docs/12.x/eloquent-relationships#one-to-many)

---

### [AUDIENCE-001] Implementar módulo Audience

**Prioridad:** 🟢 MEDIA  
**Estimación:** 2h  
**Módulo:** `app-modules/audience`  
**Dependencias:** [IDENTITY-002]

#### Descripción
Audience maneja suscriptores externos (no usuarios registrados) con campos GDPR para consent tracking. Incluye sistema de importación masiva con Jobs asíncronos.

#### Tareas Técnicas

**1. Crear migración `audience_subscribers`**

```bash
php artisan make:migration create_audience_subscribers_table --module=audience
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `email` → `string()`
- `name` → `string()->nullable()`
- `status` → `enum(['active', 'unsubscribed', 'bounced'])`
- `unsubscribe_token` → `string()->unique()`
- `unsubscribed_at` → `timestamp()->nullable()`
- `consent_given_at` → `timestamp()->nullable()` (GDPR)
- `consent_ip` → `string(45)->nullable()` (GDPR)
- `source` → `string()->nullable()` (ej: 'import', 'form', 'api')
- `created_at`, `updated_at` → `timestamps()`

**Índices:**
```php
$table->unique(['workspace_id', 'email']);
$table->index('status');
$table->index('unsubscribe_token');
$table->index('created_at');
```

**Justificación:** `consent_given_at` y `consent_ip` necesarios para GDPR compliance ([GDPR Laravel](https://gdpr.eu/cookies/))

---

**2. Crear migración `audience_import_jobs`**

```bash
php artisan make:migration create_audience_import_jobs_table --module=audience
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `user_id` → `uuid()` (quien inició la importación)
- `filename` → `string()`
- `file_path` → `string()`
- `status` → `enum(['pending', 'processing', 'completed', 'failed'])`
- `total_rows` → `integer()->default(0)`
- `processed_rows` → `integer()->default(0)`
- `successful_imports` → `integer()->default(0)`
- `failed_imports` → `integer()->default(0)`
- `error_log` → `json()->nullable()`
- `started_at` → `timestamp()->nullable()`
- `completed_at` → `timestamp()->nullable()`
- `created_at`, `updated_at` → `timestamps()`

**Índices:**
```php
$table->index('workspace_id');
$table->index('user_id');
$table->index('status');
$table->index('created_at');
```

---

**3. Crear Models**

```bash
php artisan make:model Subscriber --module=audience
php artisan make:model ImportJob --module=audience
```

**`Subscriber.php`:**
```php
namespace Domains\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Subscriber extends Model
{
    use HasUuids;
    
    protected $table = 'audience_subscribers';
    
    protected $fillable = [
        'workspace_id',
        'email',
        'name',
        'status',
        'unsubscribe_token',
        'unsubscribed_at',
        'consent_given_at',
        'consent_ip',
        'source',
    ];
    
    protected $casts = [
        'unsubscribed_at' => 'datetime',
        'consent_given_at' => 'datetime',
    ];
    
    // Relaciones
    public function workspace() {
        return $this->belongsTo(\Domains\Identity\Models\Workspace::class);
    }
    
    // Scopes
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }
    
    public function scopeUnsubscribed($query) {
        return $query->where('status', 'unsubscribed');
    }
}
```

**`ImportJob.php`:**
```php
namespace Domains\Audience\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ImportJob extends Model
{
    use HasUuids;
    
    protected $table = 'audience_import_jobs';
    
    protected $fillable = [
        'workspace_id',
        'user_id',
        'filename',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_imports',
        'failed_imports',
        'error_log',
        'started_at',
        'completed_at',
    ];
    
    protected $casts = [
        'error_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    // Relaciones
    public function workspace() {
        return $this->belongsTo(\Domains\Identity\Models\Workspace::class);
    }
    
    public function user() {
        return $this->belongsTo(\Domains\Identity\Models\User::class);
    }
}
```

---

**4. Actualizar `AudienceServiceProvider.php`**

```php
namespace Domains\Audience\Providers;

use Illuminate\Support\ServiceProvider;

class AudienceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/audience-routes.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'audience');
    }
}
```

#### Criterios de Aceptación
- [ ] 2 migraciones creadas
- [ ] Campos GDPR (`consent_given_at`, `consent_ip`) presentes
- [ ] `unsubscribe_token` con índice único
- [ ] `ImportJob` con campos de progreso
- [ ] Índice único compuesto en `[workspace_id, email]`
- [ ] ServiceProvider actualizado
- [ ] Test: Crear subscriber con GDPR fields funciona

#### Documentación de Referencia
- [Laravel Queue Jobs](https://laravel.com/docs/12.x/queues)
- [GDPR Compliance Laravel](https://gdpr.eu/)

---

### [DELIVERY-001] Implementar módulo Delivery

**Prioridad:** 🟢 MEDIA  
**Estimación:** 2h  
**Módulo:** `app-modules/delivery`  
**Dependencias:** [PUBLISHING-002], [AUDIENCE-001]

#### Descripción
Delivery maneja el envío de emails vía Mailgun con tracking de bounces y sistema de colas para envíos masivos.

#### Tareas Técnicas

**1. Crear migración `delivery_campaigns`**

```bash
php artisan make:migration create_delivery_campaigns_table --module=delivery
```

**Campos requeridos:**
- `id` → `uuid()`
- `workspace_id` → `uuid()`
- `post_id` → `uuid()` (FK a publishing_posts)
- `name` → `string()`
- `subject` → `string()`
- `from_name` → `string()`
- `from_email` → `string()`
- `status` → `enum(['draft', 'scheduled', 'sending', 'sent', 'failed'])`
- `scheduled_for` → `timestamp()->nullable()`
- `sent_at` → `timestamp()->nullable()`
- `total_recipients` → `integer()->default(0)`
- `sent_count` → `integer()->default(0)`
- `failed_count` → `integer()->default(0)`
- `open_count` → `integer()->default(0)`
- `click_count` → `integer()->default(0)`
- `created_at`, `updated_at` → `timestamps()`

**Índices:**
```php
$table->index('workspace_id');
$table->index('post_id');
$table->index('status');
$table->index('scheduled_for');
$table->index('sent_at');
```

---

**2. Crear migración `delivery_bounces`**

```bash
php artisan make:migration create_delivery_bounces_table --module=delivery
```

**Campos requeridos:**
- `id` → `uuid()`
- `campaign_id` → `uuid()`
- `subscriber_id` → `uuid()` (FK a audience_subscribers)
- `email` → `string()`
- `bounce_type` → `enum(['hard', 'soft', 'complaint'])`
- `reason` → `text()->nullable()`
- `mailgun_event_id` → `string()->nullable()`
- `bounced_at` → `timestamp()->useCurrent()`

**Índices:**
```php
$table->index('campaign_id');
$table->index('subscriber_id');
$table->index('email');
$table->index('bounce_type');
$table->index('bounced_at');
```

**Justificación:** Hard bounces deben marcar automáticamente el subscriber como `bounced` ([Mailgun Webhooks](https://documentation.mailgun.com/en/latest/api-webhooks.html))

---

**3. Crear Models**

```bash
php artisan make:model Campaign --module=delivery
php artisan make:model Bounce --module=delivery
```

**`Campaign.php`:**
```php
namespace Domains\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Campaign extends Model
{
    use HasUuids;
    
    protected $table = 'delivery_campaigns';
    
    protected $fillable = [
        'workspace_id',
        'post_id',
        'name',
        'subject',
        'from_name',
        'from_email',
        'status',
        'scheduled_for',
        'sent_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'open_count',
        'click_count',
    ];
    
    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];
    
    // Relaciones
    public function workspace() {
        return $this->belongsTo(\Domains\Identity\Models\Workspace::class);
    }
    
    public function post() {
        return $this->belongsTo(\Domains\Publishing\Models\Post::class);
    }
    
    public function bounces() {
        return $this->hasMany(Bounce::class);
    }
    
    // Scopes
    public function scopeSent($query) {
        return $query->where('status', 'sent');
    }
    
    public function scopeScheduled($query) {
        return $query->where('status', 'scheduled')
                    ->whereNotNull('scheduled_for')
                    ->where('scheduled_for', '>', now());
    }
}
```

**`Bounce.php`:**
```php
namespace Domains\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Bounce extends Model
{
    use HasUuids;
    
    protected $table = 'delivery_bounces';
    
    public const UPDATED_AT = null;
    
    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'email',
        'bounce_type',
        'reason',
        'mailgun_event_id',
        'bounced_at',
    ];
    
    protected $casts = [
        'bounced_at' => 'datetime',
    ];
    
    // Relaciones
    public function campaign() {
        return $this->belongsTo(Campaign::class);
    }
    
    public function subscriber() {
        return $this->belongsTo(\Domains\Audience\Models\Subscriber::class);
    }
}
```

---

**4. Actualizar `DeliveryServiceProvider.php`**

```php
namespace Domains\Delivery\Providers;

use Illuminate\Support\ServiceProvider;

class DeliveryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/delivery-routes.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'delivery');
    }
}
```

#### Criterios de Aceptación
- [ ] 2 migraciones creadas
- [ ] `delivery_bounces` sin `updated_at`
- [ ] Enum `bounce_type` con valores correctos
- [ ] Campos de métricas en Campaign (open_count, click_count)
- [ ] ServiceProvider actualizado
- [ ] Test: Crear campaign con relación a post funciona

#### Documentación de Referencia
- [Laravel Mail Configuration](https://laravel.com/docs/12.x/mail#mailgun-driver)
- [Mailgun API Documentation](https://documentation.mailgun.com/)

---

## EPIC 6: Configuración Final y Tests

### [CONFIG-001] Actualizar configuraciones globales

**Prioridad:** 🟡 ALTA  
**Estimación:** 1h  
**Dependencias:** Todos los módulos implementados

#### Descripción
Actualizar configuraciones de Laravel para que reconozca correctamente todos los módulos, especialmente el modelo de autenticación y las rutas.

#### Tareas Técnicas

**1. Verificar `config/app-modules.php`**

```php
return [
    'modules_namespace' => 'Domains',
    'modules_vendor' => null,
    'modules_directory' => 'app-modules',
    'tests_base' => 'Tests\TestCase',
];
```

---

**2. Actualizar `config/auth.php`**

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', Domains\Identity\Models\User::class),
    ],
],
```

---

**3. Actualizar `.env`**

```env
APP_NAME=Freetter
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=freetter_dev
DB_USERNAME=postgres
DB_PASSWORD=secret

AUTH_MODEL=Domains\Identity\Models\User

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=noreply@freetter.com
MAIL_FROM_NAME="${APP_NAME}"

MAILGUN_DOMAIN=
MAILGUN_SECRET=
MAILGUN_ENDPOINT=api.mailgun.net
```

---

**4. Ejecutar `php artisan modules:sync`**

Este comando actualizará:
- `phpunit.xml` para incluir suite de tests de módulos
- Configuración de PhpStorm (si existe)

---

**5. Ejecutar migraciones**

```bash
php artisan migrate:fresh
```

**Orden esperado:**
1. Identity (users, workspaces, memberships, invitations)
2. Activity (logs, streams, alerts)
3. Publishing (posts, versions, media, tags, pivotes)
4. Community (comments, likes, followers)
5. Audience (subscribers, import_jobs)
6. Delivery (campaigns, bounces)

#### Criterios de Aceptación
- [ ] `php artisan config:cache` sin errores
- [ ] `php artisan route:list` muestra rutas de todos los módulos
- [ ] `php artisan migrate:fresh` ejecuta todas las migraciones en orden correcto
- [ ] `php artisan tinker` puede crear: `Domains\Identity\Models\User::factory()->create()`
- [ ] No hay conflictos de namespace

#### Documentación de Referencia
- [Laravel Configuration](https://laravel.com/docs/12.x/configuration)
- [Laravel Package Discovery](https://laravel.com/docs/12.x/packages#package-discovery)

---

### [TEST-001] Crear tests básicos de integración

**Prioridad:** 🟢 MEDIA  
**Estimación:** 2h  
**Dependencias:** [CONFIG-001]

#### Descripción
Crear tests feature que validen la integridad de las relaciones entre módulos y la correcta configuración de las migraciones.

#### Tareas Técnicas

**1. Crear test para Identity**

```bash
php artisan make:test Identity/UserWorkspaceTest --module=identity
```

**Ubicación:** `app-modules/identity/tests/Feature/UserWorkspaceTest.php`

```php
namespace Domains\Identity\Tests\Feature;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\Membership;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserWorkspaceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_belong_to_multiple_workspaces(): void
    {
        $user = User::factory()->create();
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();
        
        Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace1->id,
            'role' => 'owner',
        ]);
        
        Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace2->id,
            'role' => 'editor',
        ]);
        
        $this->assertCount(2, $user->workspaces);
    }
    
    public function test_workspace_cannot_have_duplicate_members(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        
        Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
        ]);
        
        $this->expectException(\Exception::class);
        
        Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'admin',
        ]);
    }
}
```

---

**2. Crear test para Publishing**

```bash
php artisan make:test Publishing/PostPublishingTest --module=publishing
```

```php
namespace Domains\Publishing\Tests\Feature;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostPublishingTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_post_can_be_created_with_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        
        $post = Post::create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => ['blocks' => []],
            'status' => 'draft',
        ]);
        
        $this->assertDatabaseHas('publishing_posts', [
            'id' => $post->id,
            'workspace_id' => $workspace->id,
        ]);
    }
    
    public function test_published_scope_filters_correctly(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();
        
        Post::create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => ['blocks' => []],
            'status' => 'draft',
        ]);
        
        Post::create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Published Post',
            'slug' => 'published-post',
            'content' => ['blocks' => []],
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);
        
        $publishedPosts = Post::published()->get();
        
        $this->assertCount(1, $publishedPosts);
        $this->assertEquals('Published Post', $publishedPosts->first()->title);
    }
}
```

---

**3. Crear test para Community**

```bash
php artisan make:test Community/CommentThreadTest --module=community
```

```php
namespace Domains\Community\Tests\Feature;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Domains\Community\Models\Comment;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommentThreadTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_comment_can_have_replies(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $post = Post::create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => ['blocks' => []],
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $parentComment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Parent comment',
        ]);
        
        $replyComment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
            'content' => 'Reply comment',
        ]);
        
        $this->assertCount(1, $parentComment->replies);
        $this->assertEquals($parentComment->id, $replyComment->parent->id);
    }
}
```

---

**4. Ejecutar suite de tests**

```bash
php artisan test
```

#### Criterios de Aceptación
- [ ] 3 archivos de test creados
- [ ] Todos los tests pasan con `php artisan test`
- [ ] Tests validan relaciones entre módulos
- [ ] Tests validan unique constraints
- [ ] Tests validan scopes de Eloquent

#### Documentación de Referencia
- [Laravel Testing - Database](https://laravel.com/docs/12.x/database-testing)
- [Laravel Testing - Factories](https://laravel.com/docs/12.x/eloquent-factories#creating-models-using-factories)

---

## 📊 Resumen de Implementación

### Orden de Ejecución Recomendado

| Sprint | EPICs | Duración Estimada | Prioridad |
|--------|-------|-------------------|-----------|
| **Sprint 1** | EPIC 1 (Setup) + EPIC 2 (Identity) | 7.5h | 🔴 CRÍTICA |
| **Sprint 2** | EPIC 3 (Activity) + EPIC 4 (Publishing) | 8h | 🟡 ALTA |
| **Sprint 3** | EPIC 5 (Community, Audience, Delivery) | 6.5h | 🟡 ALTA |
| **Sprint 4** | EPIC 6 (Config + Tests) | 3h | 🟢 MEDIA |

**Total:** ~25 horas de implementación

### Checklist Final

- [ ] 6 módulos creados y funcionales
- [ ] 24 migraciones implementadas (4 Identity + 3 Activity + 6 Publishing + 3 Community + 2 Audience + 2 Delivery + 4 Core eliminadas)
- [ ] 17 Models creados con relaciones Eloquent
- [ ] 6 ServiceProviders actualizados
- [ ] PostgreSQL configurado correctamente
- [ ] `App\Models\User` eliminado y movido a `Domains\Identity\Models\User`
- [ ] Todas las migraciones ejecutadas con `php artisan migrate:fresh`
- [ ] Tests básicos creados y pasando
- [ ] Configuración de auth apuntando al módulo Identity
- [ ] Composer autoload regenerado

### Notas Técnicas Importantes

1. **Orden de Migraciones:** Identity debe ejecutarse primero por las FK
2. **UUIDs en todos los Models:** Usar trait `HasUuids`
3. **Sin Foreign Key Constraints:** Validación en capa aplicación
4. **Tablas Inmutables:** `activity_logs`, `publishing_post_versions`, `community_likes`, `delivery_bounces` sin `updated_at`
5. **GDPR Compliance:** Campos `consent_given_at` y `consent_ip` en `audience_subscribers`
6. **Relaciones Polimórficas:** `publishing_post_media` (media) y `community_likes` (likeable)

### Próximos Pasos (Post-Estructura)

Una vez completada esta fase, estarás listo para:

1. Implementar Magic Link Authentication en Identity
2. Crear Controllers para cada módulo
3. Implementar Actions/Services (DDD pattern)
4. Crear Policies para autorización RBAC
5. Integrar Editor.js en Publishing
6. Implementar Jobs para importación CSV en Audience
7. Configurar webhooks de Mailgun en Delivery
8. Crear API REST con Laravel Sanctum
9. Implementar rate limiting en Activity
10. Crear dashboards con Livewire/Inertia

---

**Referencias Generales:**
- [Laravel 12.x Documentation](https://laravel.com/docs/12.x)
- [internachi/modular Package](https://github.com/InterNACHI/modular)
- [Domain-Driven Design with Laravel](https://docs.spatie.be/laravel-data/v3/introduction)
- Archivo `.context/entidades-corregidas.md` (fuente de verdad para estructura de datos)
