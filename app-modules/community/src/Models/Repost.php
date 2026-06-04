<?php

namespace Domains\Community\Models;

use Domains\Community\Database\Factories\RepostFactory;
use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repost extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'community_reposts';

    protected $fillable = [
        'user_id',
        'post_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    protected static function newFactory(): RepostFactory
    {
        return RepostFactory::new();
    }
}
