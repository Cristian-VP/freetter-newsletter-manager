<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Audience\Events\SubscriberBounced;

class LogSubscriberBounced
{
    public function handle(SubscriberBounced $event): void
    {
        ActivityLog::query()->create([
            'user_id' => null,
            'action' => 'subscriber.bounced',
            'entity_type' => 'subscriber',
            'entity_id' => $event->subscriber->id,
            'metadata' => [
                'workspace_id' => $event->subscriber->workspace_id,
                'email' => $event->subscriber->email,
                'bounce_type' => $event->bounceType,
                'context' => $event->context,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
