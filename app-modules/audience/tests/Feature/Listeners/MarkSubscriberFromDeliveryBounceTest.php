<?php

namespace Domains\Audience\Tests\Feature\Listeners;

use Domains\Audience\Events\SubscriberBounced;
use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Events\DeliveryBounceReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MarkSubscriberFromDeliveryBounceTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_subscriber_bounced_for_hard_soft_and_complaint_types(): void
    {
        foreach (['hard', 'soft', 'complaint'] as $bounceType) {
            Event::fake([SubscriberBounced::class]);

            $subscriber = Subscriber::factory()->active()->create([
                'email' => $bounceType.'@example.com',
            ]);

            event(new DeliveryBounceReceived(
                workspaceId: $subscriber->workspace_id,
                email: $subscriber->email,
                bounceType: $bounceType,
                messageId: 'msg-'.$bounceType,
            ));

            $this->assertDatabaseHas('audience_subscribers', [
                'id' => $subscriber->id,
                'status' => 'bounced',
            ]);

            Event::assertDispatched(SubscriberBounced::class, function (SubscriberBounced $event) use ($bounceType): bool {
                return $event->bounceType === $bounceType;
            });
        }
    }

    public function test_bounce_listener_is_idempotent_when_already_bounced(): void
    {
        Event::fake([SubscriberBounced::class]);

        $subscriber = Subscriber::factory()->bounced()->create();

        event(new DeliveryBounceReceived(
            workspaceId: $subscriber->workspace_id,
            email: $subscriber->email,
            bounceType: 'hard',
        ));

        Event::assertNotDispatched(SubscriberBounced::class);
    }
}
