<?php

namespace Domains\Community\Models;

use Domains\Community\Database\Factories\CommentFactory;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'community_comments';

    protected $fillable = [
        'user_id',
        'post_id',
        'workspace_id',
        'parent_id',
        'content',
        'is_hidden',
        'moderated_by_user_id',
        'moderated_at',
        'moderation_reason',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'moderated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false)->whereNull('deleted_at');
    }

    public function scopeModerated(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->where('is_hidden', true)
                ->orWhereNotNull('moderated_at')
                ->orWhereNotNull('deleted_at');
        });
    }

    public function scopeForPost(Builder $query, string $postId): Builder
    {
        return $query->where('post_id', $postId);
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
