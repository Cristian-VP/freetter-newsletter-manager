<?php

namespace Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Workspace extends Model
{
    use HasUuids;

    protected $table = 'identity_workspaces';

    protected $fillable = [
        'name',
        'slug',
        'branding_config',
        'donation_config',
    ];

    protected $casts = [
        'branding_config' => 'array',  // ✅ JSONB -> Array automático
        'donation_config' => 'array',   // ✅ JSONB -> Array automático
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
        return $this->users()->wherePivot('role', 'owner')->first();
    }
}
