<?php

namespace Domains\Delivery\Tests\Feature;

use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Events\CampaignCompleted;
use Domains\Delivery\Events\CampaignSendingStarted;
use Domains\Delivery\Jobs\SendCampaignJob;
use Domains\Delivery\Models\Campaign;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendCampaignJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_to_active_subscribers_updates_stats_and_dispatches_events(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        // Fake HTTP responses from Resend batch endpoint
        Http::fake(function ($request) {
            $payload = $request->data();

            $results = [];
            foreach ($payload as $emailMsg) {
                $to = $emailMsg['to'] ?? '';
                if (str_contains($to, 'fail+')) {
                    $results[] = ['to' => $to, 'status' => 'failed'];
                } else {
                    $results[] = ['to' => $to, 'status' => 'sent'];
                }
            }

            return Http::response(['results' => $results], 200);
        });

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

        Event::assertDispatched(CampaignSendingStarted::class);
        Event::assertDispatched(CampaignCompleted::class);
    }

    public function test_job_handles_multiple_chunks_for_large_subscriber_lists(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        Http::fake(function ($request) {
            $payload = $request->data();
            $results = [];
            foreach ($payload as $emailMsg) {
                $results[] = ['to' => $emailMsg['to'] ?? '', 'status' => 'sent'];
            }

            return Http::response(['results' => $results], 200);
        });

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
            ],
        ]);

        // Create 250 subscribers to force 3 chunks (100, 100, 50)
        for ($i = 1; $i <= 250; $i++) {
            Subscriber::factory()->active()->create([
                'workspace_id' => $workspace->id,
                'email' => "user{$i}@example.com",
            ]);
        }

        (new SendCampaignJob($campaign->id))->handle();

        $campaign->refresh();

        $this->assertSame('sent', $campaign->status);
        $this->assertSame(250, $campaign->stats['total']);
        $this->assertSame(250, $campaign->stats['sent']);
        $this->assertSame(0, $campaign->stats['failed']);

        // Verify 3 HTTP calls were made (one per chunk)
        Http::assertSentCount(3);
    }

    public function test_job_handles_zero_active_subscribers(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        Http::fake();

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
            ],
        ]);

        (new SendCampaignJob($campaign->id))->handle();

        $campaign->refresh();

        $this->assertSame('sent', $campaign->status);
        $this->assertSame(0, $campaign->stats['total']);
        $this->assertSame(0, $campaign->stats['sent']);
        $this->assertSame(0, $campaign->stats['failed']);

        Event::assertDispatched(CampaignCompleted::class);
    }

    public function test_job_throws_exception_when_api_key_not_configured(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        config(['services.resend.key' => null]);

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
            ],
        ]);

        Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'test@example.com',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resend API key not configured');

        (new SendCampaignJob($campaign->id))->handle();
    }

    public function test_job_sends_html_with_inlined_css(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        Http::fake(function ($request) {
            return Http::response(['results' => [['to' => 'test@example.com', 'status' => 'sent']]], 200);
        });

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'CSS Test',
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Hello world'],
                        ],
                    ],
                ],
            ],
        ]);

        $campaign = Campaign::query()->create([
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'queued',
            'stats' => [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
            ],
        ]);

        Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'test@example.com',
        ]);

        (new SendCampaignJob($campaign->id))->handle();

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $html = $payload[0]['html'] ?? '';

            return str_contains($html, 'style=') && ! str_contains($html, '<style');
        });
    }

    public function test_job_preserves_absolute_image_urls_in_html(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        Http::fake(function ($request) {
            return Http::response(['results' => [['to' => 'test@example.com', 'status' => 'sent']]], 200);
        });

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'Image URL Test',
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'image',
                        'attrs' => [
                            'src' => 'https://cdn.example.com/newsletter-banner.jpg',
                            'alt' => 'Banner',
                            'title' => 'Banner',
                        ],
                    ],
                ],
            ],
        ]);

        $campaign = Campaign::query()->create([
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'queued',
            'stats' => [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
            ],
        ]);

        Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'test@example.com',
        ]);

        (new SendCampaignJob($campaign->id))->handle();

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $html = $payload[0]['html'] ?? '';

            return str_contains($html, 'https://cdn.example.com/newsletter-banner.jpg');
        });
    }

    public function test_job_rejects_base64_images_in_html(): void
    {
        Event::fake([CampaignSendingStarted::class, CampaignCompleted::class]);
        Http::fake(function ($request) {
            return Http::response(['results' => [['to' => 'test@example.com', 'status' => 'sent']]], 200);
        });

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'Base64 Image Test',
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Hello world'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'attrs' => [
                            'src' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
                            'alt' => 'Base64 image',
                            'title' => 'Base64 image',
                        ],
                    ],
                ],
            ],
        ]);

        $campaign = Campaign::query()->create([
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'status' => 'queued',
            'stats' => [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
            ],
        ]);

        Subscriber::factory()->active()->create([
            'workspace_id' => $workspace->id,
            'email' => 'test@example.com',
        ]);

        (new SendCampaignJob($campaign->id))->handle();

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $html = $payload[0]['html'] ?? '';

            return ! str_contains($html, 'data:image');
        });
    }
}
