<?php

namespace Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Domains\Identity\Database\Factories\MembershipFactory;
class Membership extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'identity_memberships';

    public $timestamps = false; // Asumimos que no hay timestamps en esta tabla

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
