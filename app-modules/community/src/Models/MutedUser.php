<?php

namespace Domains\Community\Models;

use Domains\Community\Database\Factories\MutedUserFactory;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutedUser extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'community_muted_users';

    protected $fillable = [
        'user_id',
        'muted_user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mutedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'muted_user_id');
    }

    protected static function newFactory(): MutedUserFactory
    {
        return MutedUserFactory::new();
    }
}
