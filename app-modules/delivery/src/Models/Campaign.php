<?php

namespace Domains\Delivery\Models;

use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Database\Factories\CampaignFactory;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'delivery_campaigns';

    protected $fillable = [
        'workspace_id',
        'post_id',
        'status',
        'stats',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'stats' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function bounces(): HasMany
    {
        return $this->hasMany(Bounce::class, 'campaign_id');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class, 'workspace_id', 'workspace_id');
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', 'queued');
    }

    public function scopeSending(Builder $query): Builder
    {
        return $query->where('status', 'sending');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function startSending(): void
    {
        $this->status = 'sending';
        $this->started_at = now();
        $this->save();
    }

    public function complete(array $stats): void
    {
        $failed = (int) ($stats['failed'] ?? 0);

        $this->status = $failed > 0 ? 'failed' : 'sent';
        $this->stats = $stats;
        $this->completed_at = now();
        $this->save();
    }

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign): void {
            if (! is_array($campaign->stats) || $campaign->stats === []) {
                $campaign->stats = [
                    'total' => 0,
                    'sent' => 0,
                    'failed' => 0,
                    'opened' => 0,
                ];
            }

            if (! $campaign->status) {
                $campaign->status = 'queued';
            }
        });

        static::created(function (Campaign $campaign): void {
            $campaign->stats = [
                'total' => (int) ($campaign->stats['total'] ?? 0),
                'sent' => (int) ($campaign->stats['sent'] ?? 0),
                'failed' => (int) ($campaign->stats['failed'] ?? 0),
                'opened' => (int) ($campaign->stats['opened'] ?? 0),
            ];
        });
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }
}
