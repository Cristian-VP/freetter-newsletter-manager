<?php

namespace Domains\Identity\Models;

use Domains\Identity\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

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

    protected $appends = ['avatar'];

    /**
     * Get the full URL for the user's avatar.
     */
    public function getAvatarAttribute(): ?string
    {
        $path = $this->avatar_path;

        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

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
