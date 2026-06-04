<?php

namespace Domains\Publishing\Models;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'publishing_tags';

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
    ];

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'publishing_post_tag', 'tag_id', 'post_id');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }
}
