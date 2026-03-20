<?php

namespace Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Domains\Identity\Database\Factories\WorkspaceFactory;

class Workspace extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'identity_workspaces';

    protected $fillable = [
        'name',
        'slug',
        'branding_config',
        'donation_config',
    ];

    protected $casts = [
        'branding_config' => 'array',
        'donation_config' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constructor llamado por factory o Tinker
    protected static function newFactory()
    {
        return WorkspaceFactory::new();
    }

    // Relaciones
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'identity_memberships')
                    ->withPivot('role', 'joined_at');
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    // Helper: Obtener owner del workspace
    public function owner()
    {
        $ownerMembership = $this->memberships()
        ->where('role', 'owner')
        ->first();

        if (!$ownerMembership) {
            throw new \DomainException(
                "Workspace {$this->id} must have an owner"
            );
        }

        return $ownerMembership->user;
    }
}
