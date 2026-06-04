<?php

namespace Domains\Audience\Listeners;

use Domains\Audience\Events\SubscriberBounced;
use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Events\DeliveryBounceReceived;

class MarkSubscriberFromDeliveryBounce
{
    public function handle(DeliveryBounceReceived $event): void
    {
        $subscriber = Subscriber::query()
            ->where('workspace_id', $event->workspaceId)
            ->where('email', strtolower($event->email))
            ->first();

        if (! $subscriber) {
            return;
        }

        if (! $subscriber->markBounced()) {
            return;
        }

        event(new SubscriberBounced(
            subscriber: $subscriber->fresh(),
            bounceType: $event->bounceType,
            context: [
                'message_id' => $event->messageId,
                'provider_context' => $event->context,
            ],
        ));
    }
}
