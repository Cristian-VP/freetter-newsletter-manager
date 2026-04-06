<?php

namespace Domains\Community\Models;

use Domains\Community\Database\Factories\FollowerFactory;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follower extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'community_followers';

    protected $fillable = [
        'follower_id',
        'followed_workspace_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'followed_workspace_id');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('followed_workspace_id', $workspaceId);
    }

    public function scopeByFollower(Builder $query, string $followerId): Builder
    {
        return $query->where('follower_id', $followerId);
    }

    protected static function newFactory(): FollowerFactory
    {
        return FollowerFactory::new();
    }
}
