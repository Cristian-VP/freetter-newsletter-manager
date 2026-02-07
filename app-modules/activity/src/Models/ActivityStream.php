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
