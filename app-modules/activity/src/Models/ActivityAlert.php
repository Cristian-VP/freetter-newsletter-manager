<?php

namespace Domains\Activity\Models;

use Domains\Identity\Models\Workspace;
//use Domains\Activity\Database\Factories\ActivityAlertFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAlert extends Model
{
    use HasUuids, HasFactory;

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

    /**
    * Not yet implemented
    * protected static function newFactory()
    *   {
    *  return ActivityAlertFactory::new();
    *     }
    */

}
