<?php

namespace Domains\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Domains\Activity\Database\Factories\ActivityAlertFactory;

class ActivityAlert extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'activity_alerts';

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


    /**
     * Obtener el ID del workspace (valor primitivo)
     *
     * Útil para filtrar alertas por workspace sin cargar la relación
     */
    public function getWorkspaceId(): string
    {
        return $this->workspace_id;
    }

    /**
     * Obtener el ID del log (valor primitivo)
     *
     * Útil para referencias sin eager loading
     */
    public function getLogId(): string
    {
        return $this->log_id;
    }

    /**
     * Verificar si la alerta está pendiente (no resuelta)
     *
     * Uso: if ($alert->isPending()) { ... }
     */
    public function isPending(): bool
    {
        return $this->resolved_at === null;
    }

    /**
     * Verificar si la alerta está resuelta
     *
     * Uso: if ($alert->isResolved()) { ... }
     * Nota: Alias para isResolved() existente
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Verificar si la alerta es crítica
     *
     * Uso: if ($alert->isCritical()) { ... }
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Verificar si la alerta es advertencia
     *
     * Uso: if ($alert->isWarning()) { ... }
     */
    public function isWarning(): bool
    {
        return $this->severity === 'warning';
    }

    /**
     * Verificar si la alerta es solo informativa
     *
     * Uso: if ($alert->isInfo()) { ... }
     */
    public function isInfo(): bool
    {
        return $this->severity === 'info';
    }

    /**
     * Obtener etiqueta legible de severidad
     *
     * Uso: $alert->getSeverityLabel() → "Crítico" | "Advertencia" | "Info"
     */
    public function getSeverityLabel(): string
    {
        return match($this->severity) {
            'critical' => 'Crítico',
            'warning' => 'Advertencia',
            'info' => 'Información',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener color para UI según severidad
     *
     * Uso: class="{{ $alert->getSeverityColor() }}"
     */
    public function getSeverityColor(): string
    {
        return match($this->severity) {
            'critical' => 'red',      // Tailwind: bg-red-500
            'warning' => 'yellow',    // Tailwind: bg-yellow-500
            'info' => 'blue',         // Tailwind: bg-blue-500
            default => 'gray',
        };
    }

    /**
     * Obtener descripción del tipo de alerta
     *
     * Uso: $alert->getAlertTypeDescription()
     */
    public function getAlertTypeDescription(): string
    {
        return match($this->alert_type) {
            'hard_delete' => 'Eliminación permanente detectada',
            'permission_escalation' => 'Escalada de permisos detectada',
            'rate_limit_exceeded' => 'Límite de velocidad excedido',
            'suspicious_activity' => 'Actividad sospechosa detectada',
            'bulk_operation' => 'Operación en lote detectada',
            default => ucfirst(str_replace('_', ' ', $this->alert_type)),
        };
    }

    /**
     * Obtener ícono para UI según tipo de alerta
     *
     * Uso: <i class="{{ $alert->getAlertTypeIcon() }}"></i>
     */
    public function getAlertTypeIcon(): string
    {
        return match($this->alert_type) {
            'hard_delete' => 'trash-2',
            'permission_escalation' => 'alert-triangle',
            'rate_limit_exceeded' => 'zap',
            'suspicious_activity' => 'shield-alert',
            'bulk_operation' => 'layers',
            default => 'alert-circle',
        };
    }

    /**
     * Obtener tiempo transcurrido desde creación
     *
     * Uso: $alert->getTimeSinceCreation() → "Hace 2 horas"
     */
    public function getTimeSinceCreation(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Obtener tiempo transcurrido desde resolución
     *
     * Uso: $alert->getTimeSinceResolution() → "Hace 30 minutos"
     * Devuelve null si no está resuelta
     */
    public function getTimeSinceResolution(): ?string
    {
        return $this->resolved_at?->diffForHumans();
    }

    /**
     * Obtener tiempo pendiente (si está resuelta, tiempo que estuvo pendiente)
     *
     * Uso: $alert->getPendingDuration() → "2 horas 30 minutos"
     * Útil para SLA tracking
     */
    public function getPendingDuration(): ?string
    {
        $endTime = $this->resolved_at ?? now();
        $minutes = $this->created_at->diffInMinutes($endTime);

        if ($minutes < 60) {
            return "{$minutes} minutos";
        }

        $hours = (int)($minutes / 60);
        $mins = $minutes % 60;

        return "{$hours} horas " . ($mins > 0 ? "{$mins} minutos" : "");
    }

    /**
     * Resolver alerta solo si es crítica
     *
     * Útil para acciones selectivas
     */
    public function resolveCritical(): void
    {
        if ($this->isCritical()) {
            $this->resolve();
        }
    }
    /**
     * Reabrir una alerta resuelta
     *
     * Útil si el problema vuelve a ocurrir
     */
    public function reopen(): void
    {
        $this->update(['resolved_at' => null]);
    }

    protected static function newFactory()
    {
        return ActivityAlertFactory::new();
    }
}
