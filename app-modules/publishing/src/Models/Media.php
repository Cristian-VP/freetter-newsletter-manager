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
        return $this->belongsToMany(Post::class, 'publishing_post_media', 'media_id', 'post_id');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }
}
