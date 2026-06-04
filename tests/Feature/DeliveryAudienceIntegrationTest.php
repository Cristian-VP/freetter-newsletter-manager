<?php

namespace Tests\Feature;

use Domains\Audience\Models\Subscriber;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryAudienceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hard_bounce_marks_subscriber_as_bounced(): void
    {
        $workspace = Workspace::factory()->create();

        $subscriber = Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'listener@example.com',
        ]);

        $response = $this->postJson('/delivery/webhooks/bounces', [
            'workspace_id' => $workspace->id,
            'email' => $subscriber->email,
            'bounce_type' => 'hard',
            'code' => '550',
            'reason' => 'hard bounce',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('delivery_bounces', [
            'workspace_id' => $workspace->id,
            'email' => $subscriber->email,
            'bounce_type' => 'hard',
        ]);

        $this->assertDatabaseHas('audience_subscribers', [
            'id' => $subscriber->id,
            'status' => 'bounced',
        ]);
    }
}
