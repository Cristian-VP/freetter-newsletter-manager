<?php

namespace Domains\Delivery\Tests\Feature;

use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Events\CampaignCompleted;
use Domains\Delivery\Events\CampaignSendingStarted;
use Domains\Delivery\Jobs\SendCampaignJob;
use Domains\Delivery\Models\Campaign;
use Domains\Delivery\Notifications\NewsletterPublishedNotification;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendCampaignJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_to_active_subscribers_updates_stats_and_dispatches_events(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        Notification::fake();

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

        Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'ok@example.com',
        ]);

        Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'fail+user@example.com',
        ]);

        Subscriber::factory()->unsubscribed()->create([
            'workspace_id' => $workspace->id,
            'email' => 'ignored@example.com',
        ]);

        (new SendCampaignJob($campaign->id))->handle();

        $campaign->refresh();

        $this->assertSame('failed', $campaign->status);
        $this->assertSame(2, $campaign->stats['total']);
        $this->assertSame(1, $campaign->stats['sent']);
        $this->assertSame(1, $campaign->stats['failed']);

        Notification::assertSentOnDemandTimes(NewsletterPublishedNotification::class, 1);
        Notification::assertSentOnDemand(NewsletterPublishedNotification::class, function (NewsletterPublishedNotification $notification, array $channels, object $notifiable): bool {
            return $channels === ['mail'] && ($notifiable->routes['mail'] ?? null) === 'ok@example.com';
        });

        Event::assertDispatched(CampaignSendingStarted::class);
        Event::assertDispatched(CampaignCompleted::class);
    }
}
