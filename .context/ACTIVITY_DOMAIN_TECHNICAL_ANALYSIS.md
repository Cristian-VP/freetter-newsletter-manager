# 📚 ANÁLISIS TÉCNICO DETALLADO: DOMINIO ACTIVITY
## Auditoría, Logging Inmutable y Trazabilidad

**Fecha de Creación:** 5 de febrero de 2026  
**Estado:** ✅ Listo para implementación (MVP)  
**Fuente de Verdad:** 
- entidades-corregidas.md
- rule_build_correct_migrations_models.md (Mejores prácticas Laravel 12.x)
- plan-estructuraModularDdaFreetter.prompt.md

---

## 📋 ÍNDICE

1. [Introducción y Propósito](#introducción-y-propósito)
2. [Análisis de Entidades](#análisis-de-entidades)
3. [Diseño de Migraciones](#diseño-de-migraciones)
4. [Diseño de Modelos Eloquent](#diseño-de-modelos-eloquent)
5. [Optimizaciones de Performance](#optimizaciones-de-performance)
6. [Factories y Testing](#factories-y-testing)
7. [Scopes y Helpers](#scopes-y-helpers)
8. [Patrones de Implementación](#patrones-de-implementación)
9. [Checklist de Implementación](#checklist-de-implementación)
10. [Resumen de Decisiones Técnicas](#resumen-de-decisiones-técnicas)

---

## 1. Introducción y Propósito {#introducción-y-propósito}

### Visión General

El dominio **Activity** es responsable de:
- ✅ Registrar inmutablemente cada acción crítica en el sistema (MVP)
- ✅ Proporcionar auditoría legal para GDPR
- ✅ Facilitar debugging y trazabilidad de cambios
- 🟡 Permitir feeds públicos de actividad (V1.1 - opcional)
- 🟡 Detectar anomalías mediante alertas inteligentes (V1.1 - opcional)

### Dependencias de Arquitectura

```
Activity depende de:
├─ Identity (user_id FK a identity_users)
└─ Sin otros módulos (es independiente)

Módulos que dependen de Activity:
├─ Publishing (para registrar cambios de posts)
├─ Delivery (para registrar envíos)
├─ Community (para registrar interacciones)
└─ Audience (para registrar cambios en suscriptores)
```

### Características Clave

| Característica | MVP | V1.1 | Descripción |
|---|:---:|:---:|---|
| **Tabla `activity_logs`** | ✅ | ✅ | Registro inmutable de acciones |
| **Tabla `activity_streams`** | ❌ | ✅ | Feed público de cambios |
| **Tabla `activity_alerts`** | ❌ | ✅ | Sistema de alertas de anomalías |
| **Modelo `ActivityLog`** | ✅ | ✅ | Entidad de logging |
| **Modelo `ActivityStream`** | ❌ | ✅ | Entidad de feed |
| **Modelo `ActivityAlert`** | ❌ | ✅ | Entidad de alertas |
| **Inmutabilidad** | ✅ | ✅ | Solo INSERT, jamás UPDATE/DELETE |
| **Índices compuestos** | ✅ | ✅ | Para queries eficientes |

---

## 2. Análisis de Entidades {#análisis-de-entidades}

### 2.1. Especificación: `activity_logs` (MVP - CRÍTICA)

**Fuente:** entidades-corregidas.md, línea 62

```
Tabla: activity_logs
Nivel: 🟢 CRÍTICA (MVP)

Propósito:
Registro inmutable de acciones críticas. Vital para auditoría legal 
y debugging. Rate limiting: máximo 10 años de retención.

Campos:
• id: uuid (PK)
• user_id: uuid (FK -> identity_users, Nullable)
• action: varchar(100) ('workspace.deleted', 'post.published', 'permission.changed')
• entity_type: varchar(50) ('workspace', 'post', 'campaign')
• entity_id: uuid
• metadata: jsonb (contexto adicional)
• ip_address: varchar(45) (IPv4 o IPv6)
• user_agent: text (Nullable)
• created_at: timestamp

Índices:
• (user_id, created_at) DESC - para queries de auditoria por usuario
• (entity_type, entity_id) - para queries de entidad
• (action) - para búsquedas por tipo de acción
```

**Patrones de Uso (Casos Reales):**

```php
// Auditoría: "¿Qué hizo el usuario X en los últimos 7 días?"
ActivityLog::where('user_id', $userId)
    ->where('created_at', '>=', now()->subDays(7))
    ->orderByDesc('created_at')
    ->get();

// Trazabilidad: "¿Qué acciones se han realizado sobre el post Y?"
ActivityLog::where('entity_type', 'post')
    ->where('entity_id', $postId)
    ->orderByDesc('created_at')
    ->get();

// Debugging: "¿Qué fue lo que rompió el workspace?"
ActivityLog::where('entity_type', 'workspace')
    ->where('action', 'workspace.deleted')
    ->latest()
    ->first();
```

### 2.2. Especificación: `activity_streams` (V1.1 - OPCIONAL)

**Fuente:** entidades-corregidas.md, línea 63

```
Tabla: activity_streams
Nivel: 🟡 OPCIONAL (V1.1)

Propósito:
Feed visible de cambios. Permite mostrar "Historial de cambios" 
a los followers públicamente. Subconjunto filtrado de activity_logs.

Campos:
• id: uuid (PK)
• workspace_id: uuid (FK -> identity_workspaces)
• log_id: uuid (FK -> activity_logs)
• event_type: varchar ('post_published', 'subscriber_added')
• visibility: varchar ('public', 'admin')
• created_at: timestamp

Índices:
• (workspace_id, visibility, created_at DESC)
• (log_id)
```

### 2.3. Especificación: `activity_alerts` (V1.1 - OPCIONAL)

**Fuente:** entidades-corregidas.md, línea 64

```
Tabla: activity_alerts
Nivel: 🟡 OPCIONAL (V1.1)

Propósito:
Sistema de alertas para detectar anomalías (borrado masivo, 
cambio sospechoso de permisos, rate limit exceeded).

Campos:
• id: uuid (PK)
• workspace_id: uuid (FK -> identity_workspaces)
• log_id: uuid (FK -> activity_logs)
• alert_type: varchar ('hard_delete', 'permission_escalation', 'rate_limit_exceeded')
• severity: varchar ('info', 'warning', 'critical')
• resolved_at: timestamp (Nullable)
• created_at: timestamp

Índices:
• (workspace_id, resolved_at, severity, created_at DESC)
• (workspace_id, created_at DESC)
```

---

## 3. Diseño de Migraciones {#diseño-de-migraciones}

### 3.1. Estrategia General (Laravel 12.x Best Practices)

**Referencias:**
- [Laravel 12 Migrations Documentation](https://laravel.com/docs/12.x/migrations)
- rule_build_correct_migrations_models.md - Sección 1

**Decisiones de Diseño:**

| Decisión | Implementación | Justificación |
|---|---|---|
| **Primary Key** | `uuid()` | Distribuido, ordenable, sin colisiones |
| **Timestamps** | Solo `created_at` (NO `updated_at`) | Tabla inmutable - append-only |
| **Índices** | Compuestos según patrones reales | Optimiza queries, evita sobre-indexación |
| **Foreign Keys** | `foreignId()->constrained()` | Integridad referencial automática |
| **JSONB** | Para `metadata` | PostgreSQL soporte nativo, flexible |
| **Enum** | Para status fields | Type-safety en BD |

### 3.2. Migración: `CreateActivityLogsTable` (MVP)

**Archivo:** `/workspace/app-modules/activity/database/migrations/[timestamp]_create_activity_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla inmutable de auditoría para todas las acciones críticas.
     * 
     * Propósito: Registrar de manera append-only cada cambio en el sistema
     * Datos: ~100-1000 inserts/día típicamente
     * Retención: 10 años (política de datos)
     * 
     * Patrones de Consulta:
     * 1. "¿Qué hizo el usuario X?" → WHERE user_id + ORDER BY created_at DESC
     * 2. "¿Qué pasó con la entidad Y?" → WHERE entity_type, entity_id
     * 3. "¿Qué acciones tipo Z hubo?" → WHERE action
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Foreign Keys
            $table->uuid('user_id')
                ->nullable()
                ->comment('FK a identity_users. Nullable = acción del sistema');

            // Core Fields
            $table->string('action', 100)
                ->comment('ej: workspace.deleted, post.published, permission.changed');
            
            $table->string('entity_type', 50)
                ->comment('ej: workspace, post, campaign, user');
            
            $table->uuid('entity_id')
                ->comment('ID de la entidad que fue modificada');

            // Contextual Data
            $table->jsonb('metadata')
                ->nullable()
                ->comment('Datos adicionales: { previous_value, new_value, reason }');

            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IPv4 o IPv6 del cliente');

            $table->text('user_agent')
                ->nullable()
                ->comment('User-Agent del navegador');

            // Timestamps (Tabla Inmutable)
            $table->timestamp('created_at')
                ->useCurrent()
                ->comment('No hay updated_at - tabla append-only');

            // Índices para Queries Frecuentes
            // 🔍 Índice 1: Auditoría por usuario + fecha
            // Query típica: ActivityLog::where('user_id', $id)->orderBy('created_at', 'desc')
            $table->index(
                ['user_id', 'created_at'],
                'idx_activity_logs_user_created'
            );

            // 🔍 Índice 2: Búsqueda por entidad
            // Query típica: ActivityLog::where('entity_type', 'post')->where('entity_id', $id)
            $table->index(
                ['entity_type', 'entity_id'],
                'idx_activity_logs_entity'
            );

            // 🔍 Índice 3: Búsqueda por acción (menos frecuente, pero útil)
            // Query típica: ActivityLog::where('action', 'workspace.deleted')
            $table->index('action', 'idx_activity_logs_action');

            // 🔍 Índice 4: Timestamp para limpieza de datos antiguos
            // Query típica: ActivityLog::where('created_at', '<', now()->subYears(10))->delete()
            $table->index('created_at', 'idx_activity_logs_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

**Análisis de Índices:**

```
Índice 1: (user_id, created_at) DESC
├─ Cardinalidad: Media (pocas acciones por usuario)
├─ Selectividad: Alta (user_id es muy selectivo)
├─ Casos de uso: Auditoría de un usuario, reportes históricos
└─ Costo: Bajo (optimiza lectura, no afecta escritura)

Índice 2: (entity_type, entity_id)
├─ Cardinalidad: Media (cada entidad tiene N logs)
├─ Selectividad: Alta (combinación es selectiva)
├─ Casos de uso: Trazabilidad de cambios en una entidad
└─ Costo: Bajo (optimiza lectura)

Índice 3: action
├─ Cardinalidad: Muy alta (solo ~20 tipos diferentes)
├─ Selectividad: Baja (muchos logs por tipo)
├─ Casos de uso: Búsquedas por tipo de evento
└─ Decisión: Útil para debugging, mantener pero evaluar en V2

Índice 4: created_at
├─ Cardinalidad: Baja (valor crece monotónicamente)
├─ Selectividad: Baja (muchos logs por fecha)
├─ Casos de uso: Limpieza de datos, queries de rango
└─ Costo: Muy bajo (imprescindible)
```

### 3.3. Migración: `CreateActivityStreamsTable` (V1.1)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feed público de actividad de un workspace.
     * 
     * Propósito: Mostrar a followers del workspace qué está pasando
     * Es un SUBCONJUNTO filtrado de activity_logs
     * 
     * Relación: 1 ActivityStream : 1 ActivityLog (puede haber múltiples streams de 1 log)
     */
    public function up(): void
    {
        Schema::create('activity_streams', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign Keys
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspaces', 'id')
                ->cascadeOnDelete()
                ->comment('Workspace propietario del evento');

            $table->foreignUuid('log_id')
                ->constrained('activity_logs', 'id')
                ->cascadeOnDelete()
                ->comment('Referencia al log original');

            // Event Classification
            $table->string('event_type')
                ->comment('ej: post_published, subscriber_added');

            $table->enum('visibility', ['public', 'admin'])
                ->default('admin')
                ->comment('¿Quién puede ver este evento?');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index(
                ['workspace_id', 'visibility', 'created_at'],
                'idx_activity_streams_feed'
            );
            $table->index('log_id', 'idx_activity_streams_log');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_streams');
    }
};
```

### 3.4. Migración: `CreateActivityAlertsTable` (V1.1)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistema de alertas para anomalías.
     * 
     * Propósito: Notificar admins de eventos sospechosos
     * Disparadores: Cambios de permisos, borrados en lote, rate limit exceeded
     */
    public function up(): void
    {
        Schema::create('activity_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign Keys
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspaces', 'id')
                ->cascadeOnDelete();

            $table->foreignUuid('log_id')
                ->constrained('activity_logs', 'id')
                ->cascadeOnDelete();

            // Alert Information
            $table->string('alert_type')
                ->comment('hard_delete, permission_escalation, rate_limit_exceeded');

            $table->enum('severity', ['info', 'warning', 'critical'])
                ->default('warning');

            // Resolution Tracking
            $table->timestamp('resolved_at')
                ->nullable()
                ->comment('Cuándo fue resuelta la alerta');

            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index(
                ['workspace_id', 'resolved_at', 'severity', 'created_at'],
                'idx_activity_alerts_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_alerts');
    }
};
```

---

## 4. Diseño de Modelos Eloquent {#diseño-de-modelos-eloquent}

### 4.1. Estrategia General (Laravel 12.x)

**Referencias:**
- [Laravel 12 Eloquent Documentation](https://laravel.com/docs/12.x/eloquent)
- rule_build_correct_migrations_models.md - Sección 2

**Principios Aplicados:**

| Principio | Implementación | Razón |
|---|---|---|
| **Tipado Fuerte** | Type hints en relaciones | Previene N+1 en IDEs |
| **Relaciones Correctas** | `belongsTo`, `hasMany`, etc. | Query builder eficiente |
| **Casts** | JSONB → `array` | Automatiza conversión |
| **Immutability** | `const UPDATED_AT = null` | Enforza append-only |
| **Scopes** | Methods con `Builder` | Queries reutilizables |
| **Helpers** | Static methods | Interfaz amigable |

### 4.2. Modelo: `ActivityLog` (MVP)

**Archivo:** `/workspace/app-modules/activity/src/Models/ActivityLog.php`

```php
<?php

namespace Domains\Activity\Models;

use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Auditoría Inmutable
 * 
 * Tabla: activity_logs
 * Principio: Append-only, jamás update/delete
 * 
 * @property string $id UUID
 * @property ?string $user_id UUID del usuario que realizó la acción
 * @property string $action Tipo de acción (ej: workspace.deleted)
 * @property string $entity_type Tipo de entidad afectada (ej: post)
 * @property string $entity_id ID de la entidad
 * @property array|null $metadata Contexto adicional (valores anteriores, etc)
 * @property ?string $ip_address IPv4 o IPv6
 * @property ?string $user_agent User-Agent del navegador
 * @property \Carbon\Carbon $created_at
 * 
 * @method static ActivityLog|null findByAction(string $action)
 * @method static Builder byUser(?string $userId)
 * @method static Builder forEntity(string $entityType, string $entityId)
 * @method static Builder ofAction(string $action)
 */
class ActivityLog extends Model
{
    use HasUuids;

    protected $table = 'activity_logs';

    // ✅ INMUTABILIDAD: Solo created_at, jamás updated_at
    public const UPDATED_AT = null;

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
        // JSONB → PHP Array (automático en inserts/updates)
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación: ¿Quién realizó esta acción?
     * 
     * Nullable porque puede ser acción del sistema (cron jobs, webhooks)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────────────────────────
    // SCOPES: Queries Reutilizables y Expresivas
    // ─────────────────────────────────────────────────────────────────

    /**
     * Scope: Logs de un usuario específico
     * 
     * Uso: ActivityLog::byUser($userId)->latest()->get()
     */
    public function scopeByUser(Builder $query, ?string $userId): Builder
    {
        return $userId 
            ? $query->where('user_id', $userId)
            : $query->whereNull('user_id');
    }

    /**
     * Scope: Logs de una entidad específica
     * 
     * Uso: ActivityLog::forEntity('post', $postId)->latest()->get()
     */
    public function scopeForEntity(Builder $query, string $entityType, string $entityId): Builder
    {
        return $query
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Scope: Logs de un tipo de acción
     * 
     * Uso: ActivityLog::ofAction('workspace.deleted')->get()
     */
    public function scopeOfAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Logs recientes (últimas N horas/días)
     * 
     * Uso: ActivityLog::recent(days: 7)->get()
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Logs sin usuario (acciones del sistema)
     */
    public function scopeSystemActions(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope: Logs con usuario (acciones de personas)
     */
    public function scopeUserActions(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope: Logs anteriores a una fecha (para cleanup)
     */
    public function scopeOlderThan(Builder $query, \Carbon\Carbon $date): Builder
    {
        return $query->where('created_at', '<', $date);
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS: Métodos Estáticos Útiles
    // ─────────────────────────────────────────────────────────────────

    /**
     * Crear un log de auditoría de manera elegante
     * 
     * Uso en controladores/actions:
     * ```
     * ActivityLog::record(
     *     action: 'workspace.deleted',
     *     entityType: 'workspace',
     *     entityId: $workspace->id,
     *     user: $currentUser,
     *     metadata: ['reason' => 'Owner request']
     * );
     * ```
     * 
     * Ventajas:
     * - Automáticamente captura IP y User-Agent
     * - Más legible que ActivityLog::create([...])
     * - Fail-safe si fallan inserts
     */
    public static function record(
        string $action,
        string $entityType,
        string $entityId,
        ?User $user = null,
        ?array $metadata = null
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

    /**
     * Variante: Log de acción del sistema (sin usuario)
     */
    public static function recordSystem(
        string $action,
        string $entityType,
        string $entityId,
        ?array $metadata = null
    ): self {
        return self::record(
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            user: null,
            metadata: $metadata
        );
    }

    /**
     * Buscar el último log de una acción en una entidad
     */
    public static function lastActionOnEntity(
        string $action,
        string $entityType,
        string $entityId
    ): ?self {
        return self::ofAction($action)
            ->forEntity($entityType, $entityId)
            ->latest()
            ->first();
    }

    /**
     * Obtener resumen de actividad (para dashboards)
     * 
     * Retorna: [
     *     'total_logs' => 5000,
     *     'unique_users' => 120,
     *     'actions_count' => {'workspace.created' => 50, ...}
     * ]
     */
    public static function activitySummary(int $daysBack = 30): array
    {
        $logs = self::recent($daysBack)->get();

        return [
            'total_logs' => $logs->count(),
            'unique_users' => $logs->distinct('user_id')->count(),
            'unique_entities' => $logs->distinct('entity_id')->count(),
            'actions_count' => $logs
                ->groupBy('action')
                ->map(fn($group) => count($group))
                ->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // ACCESSORS: Transformaciones de Datos
    // ─────────────────────────────────────────────────────────────────

    /**
     * Descripción legible de la acción
     * 
     * Uso: $log->action_label → "Workspace Eliminado"
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'workspace.created' => 'Workspace Creado',
            'workspace.deleted' => 'Workspace Eliminado',
            'post.published' => 'Post Publicado',
            'post.deleted' => 'Post Eliminado',
            'user.invited' => 'Usuario Invitado',
            'permission.changed' => 'Permiso Modificado',
            default => ucfirst(str_replace('.', ' ', $this->action)),
        };
    }

    /**
     * Ícono para UI según el tipo de acción
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'workspace.created' => 'folder-plus',
            'workspace.deleted' => 'folder-minus',
            'post.published' => 'check-circle',
            'post.deleted' => 'trash',
            'user.invited' => 'user-plus',
            'permission.changed' => 'lock',
            default => 'activity',
        };
    }
}
```

**Patrones de Uso:**

```php
// ✅ Crear un log
ActivityLog::record(
    action: 'post.published',
    entityType: 'post',
    entityId: $post->id,
    user: auth()->user(),
    metadata: ['title' => $post->title]
);

// ✅ Consultar: "¿Qué hizo el usuario en los últimos 7 días?"
$userActivity = ActivityLog::byUser($userId)
    ->recent(days: 7)
    ->latest()
    ->get();

// ✅ Consultar: "¿Qué pasó con este post?"
$postHistory = ActivityLog::forEntity('post', $postId)
    ->with('user')
    ->latest()
    ->get();

// ✅ Consultar: "¿Cuántos workspaces se eliminaron hoy?"
$deletions = ActivityLog::ofAction('workspace.deleted')
    ->whereDate('created_at', today())
    ->count();

// ✅ Scopes encadenados
$recentUserActions = ActivityLog::byUser($userId)
    ->recent(days: 30)
    ->ofAction('post.published')
    ->latest()
    ->paginate(50);

// ✅ Resumen de actividad
$summary = ActivityLog::activitySummary(daysBack: 7);
// Output: ['total_logs' => 1500, 'unique_users' => 45, ...]
```

### 4.3. Modelo: `ActivityStream` (V1.1)

```php
<?php

namespace Domains\Activity\Models;

use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityStream extends Model
{
    use HasUuids;

    protected $table = 'activity_streams';

    // Solo created_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'log_id',
        'event_type',
        'visibility',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class, 'log_id');
    }

    // Scopes
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('visibility', 'admin');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }
}
```

### 4.4. Modelo: `ActivityAlert` (V1.1)

```php
<?php

namespace Domains\Activity\Models;

use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAlert extends Model
{
    use HasUuids;

    protected $table = 'activity_alerts';

    public const UPDATED_AT = null;

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

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class, 'log_id');
    }

    // Scopes
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', 'critical');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    // Helpers
    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
```

---

## 5. Optimizaciones de Performance {#optimizaciones-de-performance}

### 5.1. Prevención de N+1 Queries

**Referencia:** rule_build_correct_migrations_models.md - 2.2

**Problem:** Sin eager loading, cada ActivityLog genera 1 query adicional para cargar su usuario.

```php
// ❌ MALO: N+1 Query (1 + 100 = 101 queries)
$logs = ActivityLog::recent(days: 7)->get();
foreach ($logs as $log) {
    echo $log->user->name; // ← Query extra por log
}
// Total: 1 (get logs) + 100 (get users) = 101 queries

// ✅ BUENO: Eager Loading (2 queries)
$logs = ActivityLog::with('user')
    ->recent(days: 7)
    ->get();
foreach ($logs as $log) {
    echo $log->user->name; // ← Sin queries adicionales
}
// Total: 1 (get logs + users en JOIN) + 1 (get users por relación) = 2 queries
```

### 5.2. Lazy Loading Prevention (Desarrollo)

**Referencia:** [Laravel 12 preventLazyLoading](https://laravel.com/docs/12.x/eloquent#configuring-eloquent-strictness)

```php
// En AppServiceProvider.php
public function boot(): void
{
    // Rompe la request si hay lazy loading en desarrollo
    Model::preventLazyLoading(!$this->app->isProduction());
}
```

**Beneficio:** Durante desarrollo, lanzará excepción si olvidamos usar `with()`.

### 5.3. Chunking para Grandes Volúmenes

**Referencia:** [Laravel 12 Chunking Results](https://laravel.com/docs/12.x/eloquent#chunking-results)

**Problem:** Cargar 1 millón de logs en memoria causa OOM.

```php
// ❌ MALO: OOM Error
$allLogs = ActivityLog::all(); // 1 millón de objetos en RAM

// ✅ BUENO: Chunking (procesa 1000 a la vez)
ActivityLog::chunk(1000, function ($logs) {
    foreach ($logs as $log) {
        // Procesar chunk
        $log->doSomething();
    }
    // RAM se limpia después de cada chunk
});

// ✅ MEJOR: Lazy Loading (memoria mínima)
ActivityLog::lazy(1000)->each(fn($log) => $log->doSomething());
```

### 5.4. Cursor para Procesamiento Masivo

**Referencia:** [Laravel 12 Cursors](https://laravel.com/docs/12.x/eloquent#cursors)

**Ventaja:** Solo 1 query, 1 modelo en RAM a la vez.

```php
// ✅ Para limpieza de datos antiguos (10 años)
ActivityLog::where('created_at', '<', now()->subYears(10))
    ->cursor() // ← Una sola query
    ->each(fn($log) => $log->delete());

// Nota: cursor() NO puede usar eager loading
// Si necesitas relaciones, usa lazy() en su lugar:
ActivityLog::with('user')
    ->where('created_at', '<', now()->subYears(10))
    ->lazy(100)
    ->each(fn($log) => $log->delete());
```

### 5.5. Índices: Justificación y Cardinalidad

| Índice | Selectividad | Cardinalidad | Costo de Mantenimiento | Recomendación |
|---|:---:|:---:|:---:|---|
| `(user_id, created_at)` | Alta | Media | Bajo | ✅ Mantener |
| `(entity_type, entity_id)` | Alta | Media | Bajo | ✅ Mantener |
| `(action)` | Baja | Alta | Medio | ⚠️ Evaluar en V2 |
| `(created_at)` | Baja | Baja | Muy Bajo | ✅ Imprescindible |

**Decisión:** Mantener 4 índices en MVP. En V2, si crece a >10M logs, revisar `action`.

### 5.6. Rate Limiting (Opcional en MVP, Crítico en V2)

```php
// En AppServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('activity-log', function (Request $request) {
    // Max 100 logs por minuto por usuario
    return Limit::perMinute(100)
        ->by($request->user()?->id ?: $request->ip());
});
```

---

## 6. Factories y Testing {#factories-y-testing}

### 6.1. Factory: `ActivityLogFactory`

**Archivo:** `/workspace/app-modules/activity/database/factories/ActivityLogFactory.php`

```php
<?php

namespace Domains\Activity\Database\Factories;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define el estado por defecto de un ActivityLog
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'workspace.created',
                'workspace.deleted',
                'workspace.updated',
                'post.published',
                'post.deleted',
                'post.updated',
                'user.invited',
                'user.removed',
                'permission.changed',
                'subscriber.added',
                'subscriber.removed',
            ]),
            'entity_type' => $this->faker->randomElement([
                'workspace',
                'post',
                'user',
                'campaign',
                'subscriber',
            ]),
            'entity_id' => $this->faker->uuid(),
            'metadata' => [
                'previous_value' => $this->faker->word(),
                'new_value' => $this->faker->word(),
                'reason' => $this->faker->sentence(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Variante: Log de acción del sistema (sin usuario)
     */
    public function systemAction(): self
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Variante: Log de publicación de post
     */
    public function postPublished(): self
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'post.published',
            'entity_type' => 'post',
            'metadata' => ['status' => 'published'],
        ]);
    }

    /**
     * Variante: Log de eliminación crítica
     */
    public function deletion(): self
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'workspace.deleted',
            'entity_type' => 'workspace',
            'metadata' => ['backup_created' => true],
        ]);
    }

    /**
     * Variante: Log antiguo (para tests de limpieza)
     */
    public function old(int $yearsAgo = 10): self
    {
        return $this->state(fn(array $attributes) => [
            'created_at' => now()->subYears($yearsAgo),
        ]);
    }

    /**
     * Variante: Log de hoy
     */
    public function today(): self
    {
        return $this->state(fn(array $attributes) => [
            'created_at' => now()->startOfDay()->addHours(
                $this->faker->numberBetween(0, 23)
            ),
        ]);
    }

    /**
     * Variante: Múltiples logs para una entidad
     */
    public function forEntity(string $entityType, string $entityId): self
    {
        return $this->state(fn(array $attributes) => [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }
}
```

**Uso en Tests:**

```php
// ✅ Crear 1 log
$log = ActivityLog::factory()->create();

// ✅ Crear 10 logs
$logs = ActivityLog::factory()->count(10)->create();

// ✅ Crear log del sistema
$log = ActivityLog::factory()->systemAction()->create();

// ✅ Crear logs de publicación
$logs = ActivityLog::factory()
    ->postPublished()
    ->count(5)
    ->create();

// ✅ Crear logs antiguos (para test de limpieza)
$oldLogs = ActivityLog::factory()
    ->old(yearsAgo: 12)
    ->count(100)
    ->create();

// ✅ Crear logs para una entidad específica
$postLogs = ActivityLog::factory()
    ->forEntity('post', $post->id)
    ->count(3)
    ->create();
```

### 6.2. Tests: `ActivityLogTest`

```php
<?php

namespace Domains\Activity\Tests\Feature;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear un log de auditoría
     */
    public function test_can_create_activity_log(): void
    {
        $user = User::factory()->create();

        $log = ActivityLog::record(
            action: 'post.published',
            entityType: 'post',
            entityId: 'some-uuid',
            user: $user,
            metadata: ['title' => 'Hello World']
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'action' => 'post.published',
            'entity_type' => 'post',
        ]);
    }

    /**
     * Test: Tabla es inmutable (no updated_at)
     */
    public function test_table_is_immutable(): void
    {
        $log = ActivityLog::factory()->create();

        // No puede actualizar
        $this->assertNull($log->updated_at);
    }

    /**
     * Test: Scope byUser funciona
     */
    public function test_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ActivityLog::factory()->create(['user_id' => $user1->id]);
        ActivityLog::factory()->create(['user_id' => $user2->id]);
        ActivityLog::factory()->systemAction()->create();

        $logs = ActivityLog::byUser($user1->id)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals($user1->id, $logs->first()->user_id);
    }

    /**
     * Test: Scope forEntity funciona
     */
    public function test_scope_for_entity(): void
    {
        $postId = 'post-123';

        ActivityLog::factory()
            ->forEntity('post', $postId)
            ->count(3)
            ->create();

        ActivityLog::factory()
            ->forEntity('workspace', 'workspace-456')
            ->count(2)
            ->create();

        $logs = ActivityLog::forEntity('post', $postId)->get();

        $this->assertCount(3, $logs);
        $this->assertTrue($logs->every(
            fn($log) => $log->entity_type === 'post' && $log->entity_id === $postId
        ));
    }

    /**
     * Test: Eager loading evita N+1
     */
    public function test_eager_loading_prevents_n_plus_one(): void
    {
        User::factory()->count(10)->create();
        ActivityLog::factory()->count(100)->create();

        $this->assertNumQueries(2, function () {
            $logs = ActivityLog::with('user')->get();
            foreach ($logs as $log) {
                $log->user?->name;
            }
        });
    }

    /**
     * Test: activitySummary funciona
     */
    public function test_activity_summary(): void
    {
        ActivityLog::factory()
            ->postPublished()
            ->count(10)
            ->create();

        ActivityLog::factory()
            ->count(5)
            ->create();

        $summary = ActivityLog::activitySummary(daysBack: 1);

        $this->assertEquals(15, $summary['total_logs']);
        $this->assertGreaterThan(0, $summary['unique_users']);
    }
}
```

---

## 7. Scopes y Helpers {#scopes-y-helpers}

### 7.1. Tabla de Scopes Disponibles

| Scope | Parámetros | Ejemplo | Caso de Uso |
|---|---|---|---|
| `byUser()` | `?string $userId` | `ActivityLog::byUser($id)` | Auditoría de un usuario |
| `forEntity()` | `string $type, string $id` | `ActivityLog::forEntity('post', $id)` | Historial de una entidad |
| `ofAction()` | `string $action` | `ActivityLog::ofAction('post.published')` | Búsqueda por tipo |
| `recent()` | `int $days` | `ActivityLog::recent(7)` | Últimos N días |
| `systemActions()` | Ninguno | `ActivityLog::systemActions()` | Acciones automatizadas |
| `userActions()` | Ninguno | `ActivityLog::userActions()` | Acciones de usuarios |
| `olderThan()` | `Carbon $date` | `ActivityLog::olderThan(date)` | Limpieza de datos |

### 7.2. Tabla de Helpers Estáticos

| Helper | Parámetros | Retorna | Uso |
|---|---|---|---|
| `record()` | action, entityType, entityId, user?, metadata? | ActivityLog | Crear log |
| `recordSystem()` | action, entityType, entityId, metadata? | ActivityLog | Log del sistema |
| `lastActionOnEntity()` | action, entityType, entityId | ?ActivityLog | Último cambio |
| `activitySummary()` | int $daysBack | array | Dashboard |

---

## 8. Patrones de Implementación {#patrones-de-implementación}

### 8.1. Patrón: Logging en Actions/Controllers

**Contexto:** Cuando se publica un post.

```php
// En App\Http\Controllers\PostController o App\Actions\PublishPost

use Domains\Activity\Models\ActivityLog;

class PublishPost
{
    public function handle(Post $post, User $user): Post
    {
        // ... lógica de publicación
        $post->publish();

        // ✅ Registrar en auditoría
        ActivityLog::record(
            action: 'post.published',
            entityType: 'post',
            entityId: $post->id,
            user: $user,
            metadata: [
                'title' => $post->title,
                'published_at' => $post->published_at,
            ]
        );

        return $post;
    }
}
```

### 8.2. Patrón: Logging en Observers

**Contexto:** Detectar cambios automáticamente.

```php
// En Domains\Publishing\Observers\PostObserver

use Domains\Activity\Models\ActivityLog;

class PostObserver
{
    public function updated(Post $post)
    {
        // ✅ Registrar cualquier cambio
        ActivityLog::record(
            action: 'post.updated',
            entityType: 'post',
            entityId: $post->id,
            user: auth()->user(),
            metadata: [
                'changes' => $post->getChanges(),
                'dirty' => $post->getDirty(),
            ]
        );
    }
}
```

### 8.3. Patrón: Querying para Auditoría

```php
// Dashboard admin: "¿Quién ha eliminado workspaces en las últimas 24h?"
$deletions = ActivityLog::ofAction('workspace.deleted')
    ->where('created_at', '>=', now()->subDay())
    ->with('user')
    ->latest()
    ->get();

foreach ($deletions as $deletion) {
    echo "{$deletion->user->name} eliminó workspace {$deletion->entity_id}";
    echo " hace {$deletion->created_at->diffForHumans()}";
}
```

### 8.4. Patrón: Limpieza de Datos Antiguos (Cron Job)

```php
// En app/Console/Commands/PruneActivityLogs.php

use Illuminate\Console\Command;
use Domains\Activity\Models\ActivityLog;

class PruneActivityLogs extends Command
{
    protected $signature = 'activity:prune {--days=3650}'; // 10 años default

    public function handle()
    {
        $cutoffDate = now()->subDays((int)$this->option('days'));

        $deleted = ActivityLog::where('created_at', '<', $cutoffDate)
            ->lazy(1000) // Procesar en chunks
            ->each(fn($log) => $log->delete())
            ->count();

        $this->info("Prunned $deleted old activity logs");
    }
}

// En config/app.php o schedule:
// $schedule->command('activity:prune')->yearly();
```

---

## 9. Checklist de Implementación {#checklist-de-implementación}

### 9.1. MVP Phase (Activity Logs Only)

**Migraciones:**
- [ ] Archivo: `/workspace/app-modules/activity/database/migrations/[timestamp]_create_activity_logs_table.php`
- [ ] Campos correctos (id, user_id, action, entity_type, entity_id, metadata, ip_address, user_agent)
- [ ] Timestamps: solo `created_at`
- [ ] Índices: 4 compuestos
- [ ] `php artisan migrate` ejecuta sin errores

**Modelos:**
- [ ] Archivo: `/workspace/app-modules/activity/src/Models/ActivityLog.php`
- [ ] Usa trait `HasUuids`
- [ ] `const UPDATED_AT = null` (inmutabilidad)
- [ ] Relación `user()` definida con type hints
- [ ] 6 Scopes implementados (byUser, forEntity, ofAction, recent, systemActions, userActions, olderThan)
- [ ] Helpers estáticos: `record()`, `recordSystem()`, `lastActionOnEntity()`, `activitySummary()`
- [ ] Casts correctos: `metadata` → array
- [ ] Accessors: `action_label`, `action_icon`

**Factories:**
- [ ] Archivo: `/workspace/app-modules/activity/database/factories/ActivityLogFactory.php`
- [ ] `definition()` completo con datos realistas
- [ ] Variantes: `systemAction()`, `postPublished()`, `deletion()`, `old()`, `today()`, `forEntity()`
- [ ] `ActivityLog::factory()->create()` funciona en tinker

**Tests:**
- [ ] Archivo: `/workspace/app-modules/activity/tests/Feature/ActivityLogTest.php`
- [ ] Test: creación de log
- [ ] Test: inmutabilidad (no updated_at)
- [ ] Test: scopes funcionan correctamente
- [ ] Test: N+1 prevention con eager loading
- [ ] Test: activitySummary retorna datos correctos
- [ ] Todos los tests pasan: `php artisan test --module=activity`

**Integración:**
- [ ] `ActivityServiceProvider.php` carga migraciones
- [ ] Modelos registrados en autoload
- [ ] `php artisan migrate:status` muestra `activity_logs`
- [ ] `php artisan migrate` ejecuta exitosamente
- [ ] `composer dump-autoload` sin errores

### 9.2. V1.1 Phase (Streams & Alerts)

- [ ] Migraciones: `activity_streams`, `activity_alerts`
- [ ] Modelos: `ActivityStream`, `ActivityAlert` con relaciones
- [ ] Factories: Variantes para streams y alerts
- [ ] Tests: Query tests, scopes, resolución de alertas
- [ ] Integridad de FKs validada

---

## 10. Resumen de Decisiones Técnicas {#resumen-de-decisiones-técnicas}

| Decisión | Justificación | Referencia |
|---|---|---|
| **UUID como PK** | Distribuido, ordenable, sin colisiones | Laravel 12 Best Practices |
| **Solo created_at** | Tabla inmutable (append-only) | Auditoría legal requerida |
| **4 Índices compuestos** | Optimiza queries reales sin sobre-indexar | rule_build_correct_migrations_models.md |
| **Scopes reutilizables** | Queries expresivas y eficientes | Laravel Query Scopes Docs |
| **Eager loading por defecto** | Previene N+1 en 90% de casos | Laravel Eloquent Performance |
| **Chunking para grandes volúmenes** | Evita OOM con millones de logs | Laravel Memory Management |
| **Metadata como JSONB** | Flexible, indexable, PostgreSQL nativo | JSONB en PostgreSQL 17 |
| **Type hints en relaciones** | Mejora IDE autocompletion | Laravel best practices |
| **Factory con variantes** | Facilita testing con diferentes escenarios | Laravel Factory Pattern |
| **Immutability enforcement** | `const UPDATED_AT = null` previene errores | Arquitectura de auditoría |

---

## 📌 Relaciones y Dependencias Visualizadas

```
┌─────────────────────────────────────────────────────────┐
│          MÓDULO ACTIVITY (Auditoría)                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────┐       ┌──────────────┐              │
│  │ActivityLog   │◄──────│identity_users│ (FK)         │
│  │(MVP)         │       │(User)        │              │
│  ├──────────────┤       └──────────────┘              │
│  │ id (UUID)    │                                     │
│  │ user_id (FK) │                                     │
│  │ action       │                                     │
│  │ entity_type  │                                     │
│  │ entity_id    │                                     │
│  │ metadata     │                                     │
│  │ ip_address   │                                     │
│  │ user_agent   │                                     │
│  │ created_at   │                                     │
│  └──────────────┘                                     │
│        │                                              │
│        │ (1:N)                                        │
│        ├──────────────┐                              │
│        │              │                              │
│  ┌─────────────────┐  ┌─────────────────┐           │
│  │ActivityStream   │  │ActivityAlert    │           │
│  │(V1.1)           │  │(V1.1)           │           │
│  │log_id (FK)      │  │log_id (FK)      │           │
│  │workspace_id(FK) │  │workspace_id(FK) │           │
│  │visibility       │  │severity         │           │
│  └─────────────────┘  └─────────────────┘           │
│                                                     │
└─────────────────────────────────────────────────────┘

Consumidores:
┌─────────────┐  ┌──────────────┐  ┌───────────────┐
│ Publishing  │  │ Community    │  │ Delivery      │
│ (Posts log) │  │ (Comments)   │  │ (Campaigns)   │
└─────────────┘  └──────────────┘  └───────────────┘
```

---

## 📚 Referencias Bibliográficas

1. **Laravel 12 Official Documentation**
   - [Migrations](https://laravel.com/docs/12.x/migrations)
   - [Eloquent](https://laravel.com/docs/12.x/eloquent)
   - [Eloquent Relationships](https://laravel.com/docs/12.x/eloquent-relationships)
   - [Query Builder](https://laravel.com/docs/12.x/queries)

2. **Performance & Optimization**
   - rule_build_correct_migrations_models.md (este proyecto)
   - [Laravel Query Optimization](https://laravel.com/docs/12.x/eloquent#chunking-results)
   - [Database Indexing Best Practices](https://use-the-index-luke.com/)

3. **PostgreSQL**
   - [JSONB Documentation](https://www.postgresql.org/docs/17/datatype-json.html)
   - [Index Types](https://www.postgresql.org/docs/17/indexes-types.html)

4. **Domain-Driven Design**
   - entidades-corregidas.md (especificación técnica)
   - plan-estructuraModularDdaFreetter.prompt.md (arquitectura global)
   - GLOBAL_STRATEGY.md (validación)

---

**Documento Generado:** 5 de febrero de 2026  
**Responsable:** Sistema de Análisis Técnico  
**Estado:** ✅ Validado y listo para implementación  
**Versión:** 1.0 (MVP Focus)
