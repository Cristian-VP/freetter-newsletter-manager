<?php

namespace Domains\Publishing\Models;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'publishing_media';

    protected $fillable = [
        'workspace_id',
        'path',
        'disk',
        'mime_type',
        'size_kb',
    ];

    protected $casts = [
        'disk' => 'string',
    ];

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, $this->resolvePostMediaPivotTable(), 'media_id', 'post_id');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function url(): string
    {
        return Storage::disk((string) $this->disk)->url((string) $this->path);
    }

    private function resolvePostMediaPivotTable(): string
    {
        if (Schema::hasTable('publishing_post_media')) {
            return 'publishing_post_media';
        }

        if (Schema::hasTable('publishing__post_media')) {
            return 'publishing__post_media';
        }

        return 'publishing_post_media';
    }
}
