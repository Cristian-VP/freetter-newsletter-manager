<?php

namespace Domains\Audience\Models;

use Domains\Audience\Database\Factories\ImportJobFactory;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'audience_import_jobs';

    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'status',
        'file_path',
        'stats',
        'error_log',
        'expires_at',
        'completed_at',
    ];

    protected $casts = [
        'stats' => 'array',
        'error_log' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function incrementStat(string $key, int $by = 1): self
    {
        $stats = $this->stats ?? [];
        $stats[$key] = ($stats[$key] ?? 0) + $by;

        $this->stats = $stats;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $error
     */
    public function addError(array $error): self
    {
        $errors = $this->error_log ?? [];
        $errors[] = $error;

        $this->error_log = $errors;

        return $this;
    }

    public function markCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markFailed(): bool
    {
        return $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    protected static function newFactory(): ImportJobFactory
    {
        return ImportJobFactory::new();
    }
}
