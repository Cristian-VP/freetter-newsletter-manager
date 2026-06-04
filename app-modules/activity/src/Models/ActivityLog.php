<?php

namespace Domains\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Domains\Activity\Database\Factories\ActivityLogFactory;

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
    use HasUuids, HasFactory;

    protected $table = 'activity_logs';

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

    public function activityStream(): HasMany
    {
        return $this->hasMany(ActivityStream::class, 'log_id');
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
     *     userId: $currentUserId,
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
        ?string $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId, // Nullable para acciones del sistema
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
            userId: null,
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
            'unique_users' => $logs->pluck('user_id')->unique()->count(),
            'unique_entities' => $logs->pluck('entity_id')->unique()->count(),
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

    protected static function newFactory()
    {
        return ActivityLogFactory::new();
    }
}
