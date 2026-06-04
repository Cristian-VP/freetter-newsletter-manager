<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Publishing\Events\PostPublished;

class LogPostPublished
{
    public function handle(PostPublished $event): void
    {
        ActivityLog::query()->create([
            'user_id' => $event->publishedByUserId,
            'action' => 'post.published',
            'entity_type' => 'post',
            'entity_id' => $event->post->id,
            'metadata' => [
                'workspace_id' => $event->post->workspace_id,
                'type' => $event->post->type,
                'status' => $event->post->status,
                'context' => $event->context,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
