<?php

namespace Domains\Community\Models;

use Domains\Community\Database\Factories\PostReportFactory;
use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReport extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'community_post_reports';

    protected $fillable = [
        'user_id',
        'post_id',
        'category',
        'reason',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    protected static function newFactory(): PostReportFactory
    {
        return PostReportFactory::new();
    }
}
