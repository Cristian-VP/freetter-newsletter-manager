<?php

namespace Domains\Delivery\Tests\Feature;

use Domains\Delivery\Events\BounceCaptured;
use Domains\Delivery\Events\DeliveryBounceReceived;
use Domains\Delivery\Models\Campaign;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BounceWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_creates_bounce_and_dispatches_events(): void
    {
        Event::fake([BounceCaptured::class, DeliveryBounceReceived::class]);

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $campaign = Campaign::query()->create([
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'sent',
            'stats' => [
                'total' => 10,
                'sent' => 10,
                'failed' => 0,
                'opened' => 0,
            ],
        ]);

        $payload = [
            'workspace_id' => $workspace->id,
            'campaign_id' => $campaign->id,
            'email' => 'bounce@example.com',
            'bounce_type' => 'hard',
            'code' => '550',
            'reason' => 'mailbox not found',
        ];

        $response = $this->postJson('/delivery/webhooks/bounces', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('delivery_bounces', [
            'workspace_id' => $workspace->id,
            'campaign_id' => $campaign->id,
            'email' => 'bounce@example.com',
            'bounce_type' => 'hard',
            'code' => '550',
        ]);

        Event::assertDispatched(BounceCaptured::class);
        Event::assertDispatched(DeliveryBounceReceived::class);
    }

    public function test_webhook_is_idempotent_for_duplicate_payload(): void
    {
        $workspace = Workspace::factory()->create();

        $payload = [
            'workspace_id' => $workspace->id,
            'campaign_id' => null,
            'email' => 'duplicate@example.com',
            'bounce_type' => 'soft',
            'code' => '450',
            'reason' => 'temporary issue',
        ];

        $first = $this->postJson('/delivery/webhooks/bounces', $payload);
        $second = $this->postJson('/delivery/webhooks/bounces', $payload);

        $first->assertCreated();
        $second->assertOk();
        $this->assertDatabaseCount('delivery_bounces', 1);
    }
}
