# 🔍 ANÁLISIS Y ESTRATEGIA - MÓDULO ACTIVITY

**Fecha:** 2 de febrero de 2026  
**Módulo:** `app-modules/activity`  
**Estado:** ⚠️ Migraciones vacías - Requiere implementación completa

---

## 📊 COMPARATIVA: Plan vs Entidades Corregidas vs Realidad

### **Tabla: `activity_logs`**

#### Plan dice:
```php
'user_id' → uuid()->nullable()
'workspace_id' → uuid()->nullable()
'event' → string() // 'post.published', 'user.invited'
'entity_type' → string()->nullable()
'entity_id' → uuid()->nullable()
'metadata' → json()->nullable()
'ip_address' → string(45)->nullable()
'user_agent' → text()->nullable()
'created_at' → timestamp()->useCurrent()
```

#### Entidades-corregidas.md dice:
```
• user_id (uuid, FK -> identity_users, Nullable)
• action (varchar(100)) ← 'workspace.deleted', 'post.published', 'permission.changed'
• entity_type (varchar(50)) ← 'workspace', 'post', 'campaign'
• entity_id (uuid)
• metadata (jsonb)
• ip_address (varchar(45))
• user_agent (text, Nullable)
• created_at (timestamp)

Indexes:
- (user_id, created_at) DESC
- (entity_type, entity_id)
```

#### ⚠️ **DIFERENCIAS CRÍTICAS:**

| Campo | Plan | Entidades Corregidas | Decisión |
|-------|------|---------------------|----------|
| `workspace_id` | ✅ Presente | ❌ NO existe | ❌ ELIMINAR (redundante con entity_type/id) |
| Campo de acción | `event` | `action` | ✅ Usar `action` (más claro) |
| JSON type | `json()` | `jsonb` | ✅ Usar `jsonb` (PostgreSQL) |

**RESOLUCIÓN:**
- ❌ Eliminar `workspace_id` (se infiere de entity)
- ✅ Usar `action` en lugar de `event`
- ✅ Usar `jsonb` en lugar de `json`

---

### **Tabla: `activity_streams`** (V1.1 - NO MVP)

#### Plan dice:
```php
'workspace_id' → uuid()
'user_id' → uuid()->nullable()
'activity_type' → string()
'actor_id' → uuid()
'actor_type' → string()
'subject_id' → uuid()
'subject_type' → string()
'data' → json()->nullable()
'is_public' → boolean()->default(true)
'created_at' → timestamp()
```

#### Entidades-corregidas.md dice:
```
• workspace_id (uuid, FK)
• log_id (uuid, FK -> activity_logs)
• event_type (varchar) ← 'post_published', 'subscriber_added'
• visibility (varchar) ← 'public', 'admin'
• created_at (timestamp)
```

#### ⚠️ **DIFERENCIAS CRÍTICAS:**

| Campo | Plan | Entidades Corregidas | Decisión |
|-------|------|---------------------|----------|
| Vínculo | NO menciona `log_id` | `log_id` (FK) | ✅ Agregar `log_id` |
| Visibilidad | `is_public` (boolean) | `visibility` (enum) | ✅ Usar `visibility` enum |
| Polimorfismo | `actor_type`, `subject_type` | NO polimórfico | ✅ Simplificar (no usar polimorfismo) |

**RESOLUCIÓN:**
- ✅ Agregar `log_id` FK a `activity_logs`
- ✅ Usar `visibility` enum: ['public', 'admin']
- ❌ Eliminar `actor_type`, `actor_id`, `subject_type`, `subject_id`
- ✅ Simplificar: solo `event_type` y vínculo a `log_id`

**NOTA:** Esta tabla es **V1.1**, no MVP. Puede omitirse inicialmente.

---

### **Tabla: `activity_alerts`** (V1.1 - NO MVP)

#### Plan dice:
```php
'user_id' → uuid()
'workspace_id' → uuid()->nullable()
'type' → enum(['info', 'warning', 'error', 'success'])
'title' → string()
'message' → text()
'action_url' → string()->nullable()
'is_read' → boolean()->default(false)
'read_at' → timestamp()->nullable()
'created_at' → timestamp()
```

#### Entidades-corregidas.md dice:
```
• workspace_id (uuid, FK)
• log_id (uuid, FK -> activity_logs)
• alert_type (varchar) ← 'hard_delete', 'permission_escalation', 'rate_limit_exceeded'
• severity (varchar) ← 'info', 'warning', 'critical'
• resolved_at (timestamp, Nullable)
• created_at (timestamp)
```

#### ⚠️ **DIFERENCIAS CRÍTICAS:**

| Campo | Plan | Entidades Corregidas | Decisión |
|-------|------|---------------------|----------|
| Usuario | `user_id` (FK) | NO existe | ❌ ELIMINAR (se saca de log_id) |
| Tipo | `type` (enum) | `alert_type` (varchar) + `severity` (varchar) | ✅ Dos campos separados |
| Estado | `is_read`, `read_at` | `resolved_at` | ✅ Usar `resolved_at` |
| Contenido | `title`, `message`, `action_url` | NO existen | ❌ ELIMINAR (redundante con log) |

**RESOLUCIÓN:**
- ❌ Eliminar `user_id` (se infiere de `log_id`)
- ✅ Usar `alert_type` + `severity` (dos campos)
- ✅ Usar `resolved_at` en lugar de `is_read`/`read_at`
- ❌ Eliminar `title`, `message`, `action_url`

**NOTA:** Esta tabla es **V1.1**, no MVP. Puede omitirse inicialmente.

---

## ✅ ESTRUCTURA FINAL RECOMENDADA (MVP)

### **`activity_logs`** (CRÍTICA - MVP)

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id')->nullable(); // FK -> identity_users
    $table->string('action', 100); // 'workspace.deleted', 'post.published'
    $table->string('entity_type', 50); // 'workspace', 'post', 'campaign'
    $table->uuid('entity_id');
    $table->jsonb('metadata')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamp('created_at')->useCurrent();

    // Indexes
    $table->index(['user_id', 'created_at']);
    $table->index(['entity_type', 'entity_id']);
    $table->index('action');
});
```

**Justificación:**
- ✅ Sigue `entidades-corregidas.md`
- ✅ Tabla inmutable (solo INSERT, no UPDATE/DELETE)
- ✅ Indexes para queries eficientes
- ✅ `jsonb` para PostgreSQL
- ✅ NO tiene `workspace_id` (redundante)

---

### **`activity_streams`** (V1.1 - OPCIONAL)

```php
Schema::create('activity_streams', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id'); // FK -> identity_workspaces
    $table->uuid('log_id'); // FK -> activity_logs
    $table->string('event_type'); // 'post_published', 'subscriber_added'
    $table->enum('visibility', ['public', 'admin'])->default('public');
    $table->timestamp('created_at')->useCurrent();

    // Foreign keys
    $table->foreign('workspace_id')->references('id')->on('identity_workspaces')->onDelete('cascade');
    $table->foreign('log_id')->references('id')->on('activity_logs')->onDelete('cascade');

    // Indexes
    $table->index('workspace_id');
    $table->index('log_id');
    $table->index(['event_type', 'visibility']);
    $table->index('created_at');
});
```

**Justificación:**
- ✅ Simplificado vs plan original
- ✅ Vínculo claro a `activity_logs`
- ✅ Visibility enum (más claro que boolean)
- ❌ NO incluye polimorfismo innecesario

---

### **`activity_alerts`** (V1.1 - OPCIONAL)

```php
Schema::create('activity_alerts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id'); // FK -> identity_workspaces
    $table->uuid('log_id'); // FK -> activity_logs
    $table->string('alert_type'); // 'hard_delete', 'permission_escalation', 'rate_limit_exceeded'
    $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
    $table->timestamp('resolved_at')->nullable();
    $table->timestamp('created_at')->useCurrent();

    // Foreign keys
    $table->foreign('workspace_id')->references('id')->on('identity_workspaces')->onDelete('cascade');
    $table->foreign('log_id')->references('id')->on('activity_logs')->onDelete('cascade');

    // Indexes
    $table->index('workspace_id');
    $table->index('log_id');
    $table->index(['alert_type', 'severity']);
    $table->index('resolved_at'); // Para filtrar alertas sin resolver
    $table->index('created_at');
});
```

**Justificación:**
- ✅ Simplificado vs plan original
- ✅ Dos campos para tipo (`alert_type` + `severity`)
- ✅ `resolved_at` más claro que `is_read`
- ❌ NO duplica info del log (se accede via `log_id`)

---

## 📋 MODELOS REQUERIDOS

### **`ActivityLog.php`** (MVP)

```php
<?php

namespace Domains\Activity\Models;

use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityLog extends Model
{
    use HasUuids;

    protected $table = 'activity_logs';

    // Tabla inmutable - NO permite updates
    public $timestamps = false;

    // Solo created_at se gestiona automáticamente
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
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

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helpers
    public static function log(
        string $action,
        string $entityType,
        string $entityId,
        ?array $metadata = null,
        ?User $user = null
    ): self {
        return self::create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

### **`ActivityStream.php`** (V1.1)

```php
<?php

namespace Domains\Activity\Models;

use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityStream extends Model
{
    use HasUuids;

    protected $table = 'activity_streams';

    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'log_id',
        'event_type',
        'visibility',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relaciones
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function log()
    {
        return $this->belongsTo(ActivityLog::class, 'log_id');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeForWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }
}
```

---

### **`ActivityAlert.php`** (V1.1)

```php
<?php

namespace Domains\Activity\Models;

use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityAlert extends Model
{
    use HasUuids;

    protected $table = 'activity_alerts';

    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'log_id',
        'alert_type',
        'severity',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relaciones
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function log()
    {
        return $this->belongsTo(ActivityLog::class, 'log_id');
    }

    // Helpers
    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
```

---

## 🏭 FACTORIES REQUERIDAS

### **`ActivityLogFactory.php`** (MVP)

```php
<?php

namespace Domains\Activity\Database\Factories;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Domains\Activity\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'workspace.created',
                'workspace.deleted',
                'post.published',
                'post.deleted',
                'user.invited',
                'permission.changed',
            ]),
            'entity_type' => $this->faker->randomElement(['workspace', 'post', 'user', 'campaign']),
            'entity_id' => $this->faker->uuid(),
            'metadata' => [
                'previous_value' => $this->faker->word(),
                'new_value' => $this->faker->word(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Log sin usuario (acción del sistema)
     */
    public function systemAction(): static
    {
        return $this->state([
            'user_id' => null,
        ]);
    }

    /**
     * Log de publicación de post
     */
    public function postPublished(): static
    {
        return $this->state([
            'action' => 'post.published',
            'entity_type' => 'post',
        ]);
    }

    /**
     * Log de eliminación de workspace
     */
    public function workspaceDeleted(): static
    {
        return $this->state([
            'action' => 'workspace.deleted',
            'entity_type' => 'workspace',
        ]);
    }
}
```

**Nota:** Factories para `ActivityStream` y `ActivityAlert` no son necesarias en MVP.

---

## 🎯 ESTRATEGIA DE IMPLEMENTACIÓN

### **Fase 1: MVP (Solo `activity_logs`)** 🔴 CRÍTICA

1. ✅ Crear migración `activity_logs`
2. ✅ Crear modelo `ActivityLog`
3. ✅ Crear factory `ActivityLogFactory`
4. ✅ Actualizar `ActivityServiceProvider`
5. ✅ Tests básicos

**Estimación:** 2 horas

---

### **Fase 2: V1.1 (Streams y Alerts)** 🟡 OPCIONAL

1. ⏳ Crear migración `activity_streams`
2. ⏳ Crear migración `activity_alerts`
3. ⏳ Crear modelos `ActivityStream`, `ActivityAlert`
4. ⏳ Tests avanzados

**Estimación:** 2 horas

---

## ✅ DECISIONES FINALES

| Decisión | Fuente | Justificación |
|----------|--------|---------------|
| Usar `action` (no `event`) | entidades-corregidas.md | Más descriptivo |
| NO usar `workspace_id` en logs | entidades-corregidas.md | Redundante con entity |
| Usar `jsonb` (no `json`) | PostgreSQL best practices | Mejor rendimiento |
| Tabla inmutable (no `updated_at`) | Audit requirements | Inmutabilidad GDPR |
| Simplificar Streams/Alerts | Arquitectura real | Evitar over-engineering |

---

## 📝 PRÓXIMOS PASOS

1. ✅ Implementar migración `activity_logs` (MVP)
2. ✅ Implementar modelo `ActivityLog`
3. ✅ Implementar factory `ActivityLogFactory`
4. ⏳ Posponer Streams/Alerts a V1.1
5. ⏳ Continuar con módulo PUBLISHING

---

**Documento generado mediante análisis comparativo de:**
- `plan-estructuraModularDdaFreetter.prompt.md`
- `entidades-corregidas.md`
- Migraciones existentes
- Convenciones Laravel 12.x
