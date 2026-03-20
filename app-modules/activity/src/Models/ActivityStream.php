<?php

namespace Domains\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Domains\Activity\Database\Factories\ActivityStreamFactory;

class ActivityStream extends Model
{
    use HasUuids, HasFactory;

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

    // Helpers
    /**
     * Obtener el ID del workspace (valor primitivo)
     *
     * Útil para queries que necesitan el workspace sin cargar la relación
     */
    public function getWorkspaceId(): string
    {
        return $this->workspace_id;
    }

    /**
     * Obtener el ID del log de actividad (valor primitivo)
     *
     * Útil para obtener referencia al log sin eager loading
     */
    public function getLogId(): string
    {
        return $this->log_id;
    }

    /**
     * Verificar si el stream es público
     *
     * Uso: if ($stream->isPublic()) { ... }
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Verificar si el stream es solo para admins
     *
     * Uso: if ($stream->isAdminOnly()) { ... }
     */
    public function isAdminOnly(): bool
    {
        return $this->visibility === 'admin';
    }

    /**
     * Obtener tipo de visibilidad legible
     *
     * Uso: $stream->getVisibilityLabel() → "Público" | "Solo Admins"
     */
    public function getVisibilityLabel(): string
    {
        return match($this->visibility) {
            'public' => 'Público',
            'admin' => 'Solo Admins',
            default => 'Desconocido',
        };
    }

    /**
     * Verificar si el stream es reciente (últimas 24 horas)
     *
     * Útil para "actividad destacada"
     */
    public function isRecent(int $hoursThreshold = 24): bool
    {
        return $this->created_at->diffInHours(now()) < $hoursThreshold;
    }

    /**
     * Obtener descripción del evento para UI
     *
     * Uso: $stream->getEventDescription() → "Post publicado"
     */
    public function getEventDescription(): string
    {
        return match($this->event_type) {
            'post.published' => 'Post publicado',
            'post.created' => 'Post creado',
            'post.deleted' => 'Post eliminado',
            'workspace.created' => 'Workspace creado',
            'workspace.deleted' => 'Workspace eliminado',
            'user.invited' => 'Usuario invitado',
            'membership.created' => 'Miembro agregado',
            'membership.removed' => 'Miembro removido',
            default => ucfirst(str_replace('.', ' ', $this->event_type)),
        };
    }

    /**
     * Obtener ícono para UI según tipo de evento
     *
     * Uso: <i class="{{ $stream->getEventIcon() }}"></i>
     */
    public function getEventIcon(): string
    {
        return match($this->event_type) {
            'post.published' => 'check-circle',
            'post.created' => 'file-plus',
            'post.deleted' => 'trash',
            'workspace.created' => 'folder-plus',
            'workspace.deleted' => 'folder-minus',
            'user.invited' => 'user-plus',
            'membership.created' => 'users-plus',
            'membership.removed' => 'users-minus',
            default => 'activity',
        };
    }

    protected static function newFactory()
    {
        return ActivityStreamFactory::new();
    }
}
