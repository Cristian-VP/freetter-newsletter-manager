<?php

namespace Domains\Identity\Models;

use Domains\Identity\Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'identity_memberships';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function getRole(): string
    {
        return $this->role;
    }

    // Constructor called by factory or Tinker
    protected static function newFactory()
    {
        return MembershipFactory::new();
    }
}
