<?php

namespace Domains\Delivery\Tests\Feature;

use Domains\Delivery\Jobs\SendCampaignJob;
use Domains\Delivery\Models\Campaign;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeliveryCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_queued_campaign_when_newsletter_is_published(): void
    {
        Queue::fake();

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->newsletter()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        event(new PostPublished($post));

        $this->assertDatabaseHas('delivery_campaigns', [
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'queued',
        ]);

        Queue::assertPushed(SendCampaignJob::class);
    }

    public function test_lists_workspace_campaigns(): void
    {
        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $campaign = Campaign::query()->create([
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'queued',
            'stats' => [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'opened' => 0,
            ],
        ]);

        $response = $this->getJson('/delivery/campaigns/'.$workspace->id);

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $campaign->id);
    }
}
