<?php

namespace Domains\Publishing\Models;

use Domains\Publishing\Database\Factories\PostVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostVersion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'publishing_post_versions';

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'content',
        'version_number',
    ];

    protected $casts = [
        'content' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function newFactory(): PostVersionFactory
    {
        return PostVersionFactory::new();
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
