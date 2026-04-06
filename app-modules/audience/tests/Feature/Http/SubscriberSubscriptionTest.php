<?php

namespace Domains\Audience\Tests\Feature\Http;

use Domains\Audience\Events\SubscriberCreated;
use Domains\Audience\Models\Subscriber;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SubscriberSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_subscribe_from_public_form(): void
    {
        Event::fake([SubscriberCreated::class]);

        $workspace = Workspace::factory()->create();

        $response = $this->postJson('/audience/workspaces/'.$workspace->id.'/subscribe', [
            'email' => 'subscriber@example.com',
            'name' => 'Jane Subscriber',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('audience_subscribers', [
            'workspace_id' => $workspace->id,
            'email' => 'subscriber@example.com',
            'status' => 'active',
        ]);

        Event::assertDispatched(SubscriberCreated::class);
    }

    public function test_rejects_duplicate_subscription_in_same_workspace(): void
    {
        $workspace = Workspace::factory()->create();

        Subscriber::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'duplicated@example.com',
        ]);

        $response = $this->postJson('/audience/workspaces/'.$workspace->id.'/subscribe', [
            'email' => 'duplicated@example.com',
            'name' => 'Duplicated User',
        ]);

        $response->assertStatus(409);
    }
}
