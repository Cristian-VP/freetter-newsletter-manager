<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Audience\Events\SubscriberCreated;

class LogSubscriberCreated
{
    public function handle(SubscriberCreated $event): void
    {
        ActivityLog::query()->create([
            'user_id' => null,
            'action' => 'subscriber.created',
            'entity_type' => 'subscriber',
            'entity_id' => $event->subscriber->id,
            'metadata' => [
                'workspace_id' => $event->subscriber->workspace_id,
                'email' => $event->subscriber->email,
                'status' => $event->subscriber->status,
                'context' => $event->context,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
