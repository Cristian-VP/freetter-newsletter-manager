<?php

namespace Domains\Identity\Models;

use Domains\Identity\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'identity_users';

    protected $fillable = [
        'name',
        'handle',
        'bio',
        'email',
        'email_verified_at',
        'avatar_path',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Constructor called by factory or Tinker
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    // Relaciones
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'identity_memberships')
            ->withPivot('role', 'joined_at');
    }
}
