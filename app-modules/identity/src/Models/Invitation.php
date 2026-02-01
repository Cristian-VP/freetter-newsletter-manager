<?php

namespace Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasUuids;

    protected $table = 'identity_invitations';

    public $timestamps = false; // ✅ Deshabilitar timestamps automáticos

    protected $fillable = [
        'workspace_id',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_by_user_id',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // Relaciones
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function acceptedByUser()
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereNull('accepted_by_user_id')
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_by_user_id')
                     ->where('expires_at', '<=', now());
    }

    // Helpers
    public static function generateToken()
    {
        return Str::random(64);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted()
    {
        return !is_null($this->accepted_by_user_id);
    }

    public function accept(User $user)
    {
        $this->update([
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ]);
    }
}
