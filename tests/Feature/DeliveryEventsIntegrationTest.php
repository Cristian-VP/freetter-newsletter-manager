<?php

namespace Tests\Feature;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeliveryEventsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_newsletter_creates_delivery_campaign_and_activity_log(): void
    {
        Queue::fake();

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();

        $post = Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $response = $this->actingAs($author)->postJson('/publishing/posts/'.$post->id.'/publish', [
            'published_by_user_id' => $author->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('delivery_campaigns', [
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delivery.campaign.created',
            'entity_type' => 'delivery_campaign',
        ]);
    }
}
