<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Audience\Events\SubscriberUnsubscribed;

class LogSubscriberUnsubscribed
{
    public function handle(SubscriberUnsubscribed $event): void
    {
        ActivityLog::query()->create([
            'user_id' => null,
            'action' => 'subscriber.unsubscribed',
            'entity_type' => 'subscriber',
            'entity_id' => $event->subscriber->id,
            'metadata' => [
                'workspace_id' => $event->subscriber->workspace_id,
                'email' => $event->subscriber->email,
                'context' => $event->context,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
