<?php

namespace Domains\Delivery\Tests\Feature;

use Domains\Delivery\Jobs\SendCampaignJob;
use Domains\Delivery\Models\Campaign;
use Domains\Delivery\Notifications\NewsletterPublishedNotification;
use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeliveryCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_queued_campaign_when_newsletter_is_published(): void
    {
        Event::fake([\Domains\Delivery\Events\CampaignSendingStarted::class, \Domains\Delivery\Events\CampaignCompleted::class]);
        \Illuminate\Support\Facades\Http::fake([
            'api.resend.com/emails/batch' => \Illuminate\Support\Facades\Http::response(['results' => [['status' => 'queued']]], 200),
        ]);
        config(['services.resend.key' => 'test-key']);

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->newsletter()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $subscriber = \Domains\Audience\Models\Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'reader@example.com',
        ]);

        event(new PostPublished($post));

        $this->assertDatabaseHas('delivery_campaigns', [
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'sent',
        ]);

        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($subscriber) {
            return $request->url() === 'https://api.resend.com/emails/batch' &&
                   $request->data()[0]['to'] === $subscriber->email;
        });

        Event::assertDispatched(\Domains\Delivery\Events\CampaignSendingStarted::class);
        Event::assertDispatched(\Domains\Delivery\Events\CampaignCompleted::class);
    }

    public function test_lists_workspace_campaigns(): void
    {
        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();

        Membership::factory()
            ->forUser($author)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

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

        $response = $this->actingAs($author)->getJson('/delivery/campaigns/'.$workspace->id);

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $campaign->id);
    }

    public function test_guest_cannot_list_workspace_campaigns(): void
    {
        $workspace = Workspace::factory()->create();

        $response = $this->getJson('/delivery/campaigns/'.$workspace->id);

        $response->assertUnauthorized();
    }

    public function test_non_member_cannot_list_workspace_campaigns(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/delivery/campaigns/'.$workspace->id);

        $response->assertForbidden();
    }
}
