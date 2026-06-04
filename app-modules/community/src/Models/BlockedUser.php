<?php

namespace Domains\Community\Models;

use Domains\Community\Database\Factories\BlockedUserFactory;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedUser extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'community_blocked_users';

    protected $fillable = [
        'user_id',
        'blocked_user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }

    protected static function newFactory(): BlockedUserFactory
    {
        return BlockedUserFactory::new();
    }
}
