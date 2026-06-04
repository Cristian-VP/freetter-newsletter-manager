<?php

namespace Domains\Delivery\Models;

use Domains\Delivery\Database\Factories\BounceFactory;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bounce extends Model
{
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'delivery_bounces';

    protected $fillable = [
        'workspace_id',
        'campaign_id',
        'email',
        'bounce_type',
        'code',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    protected function bounceType(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value ? strtolower($value) : null,
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value ? strtolower($value) : null,
        );
    }

    protected static function newFactory(): BounceFactory
    {
        return BounceFactory::new();
    }
}
