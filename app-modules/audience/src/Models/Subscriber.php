<?php

namespace Domains\Audience\Models;

use Domains\Audience\Database\Factories\SubscriberFactory;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscriber extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'audience_subscribers';

    protected $fillable = [
        'workspace_id',
        'email',
        'name',
        'status',
        'consent_given_at',
        'consent_ip',
        'unsubscribe_token',
    ];

    protected $casts = [
        'consent_given_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function markUnsubscribed(): bool
    {
        if ($this->status === 'unsubscribed') {
            return false;
        }

        $this->status = 'unsubscribed';

        return $this->save();
    }

    public function markBounced(): bool
    {
        if ($this->status === 'bounced') {
            return false;
        }

        $this->status = 'bounced';

        return $this->save();
    }

    protected static function newFactory(): SubscriberFactory
    {
        return SubscriberFactory::new();
    }
}
