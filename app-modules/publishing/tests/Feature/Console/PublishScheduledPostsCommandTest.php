<?php

declare(strict_types=1);

namespace Domains\Publishing\Tests\Feature\Console;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PublishScheduledPostsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishes_scheduled_newsletter_and_dispatches_event_with_email_channel(): void
    {
        Event::fake([PostPublished::class]);
        Carbon::setTestNow('2026-06-01 10:00:00');

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();

        $newsletter = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);

        $this->artisan('publishing:publish-scheduled-posts')->assertSuccessful();

        $newsletter->refresh();
        $this->assertEquals('published', $newsletter->status);

        Event::assertDispatched(PostPublished::class, function (PostPublished $event) use ($newsletter): bool {
            return $event->post->id === $newsletter->id
                && ($event->context['delivery_channels'] ?? null) === ['email']
                && ($event->context['published_via'] ?? null) === 'scheduler';
        });

        Carbon::setTestNow();
    }

    public function test_publishes_scheduled_note_and_dispatches_event_with_web_channel(): void
    {
        Event::fake([PostPublished::class]);
        Carbon::setTestNow('2026-06-01 10:00:00');

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();

        $note = Post::factory()->note()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);

        $this->artisan('publishing:publish-scheduled-posts')->assertSuccessful();

        $note->refresh();
        $this->assertEquals('published', $note->status);

        Event::assertDispatched(PostPublished::class, function (PostPublished $event) use ($note): bool {
            return $event->post->id === $note->id
                && ($event->context['delivery_channels'] ?? null) === ['web']
                && ($event->context['published_via'] ?? null) === 'scheduler';
        });

        Carbon::setTestNow();
    }

    public function test_does_not_publish_posts_scheduled_in_the_future(): void
    {
        Event::fake([PostPublished::class]);
        Carbon::setTestNow('2026-06-01 10:00:00');

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();

        Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'status' => 'scheduled',
            'published_at' => now()->addHour(),
        ]);

        $this->artisan('publishing:publish-scheduled-posts')->assertSuccessful();

        Event::assertNotDispatched(PostPublished::class);

        Carbon::setTestNow();
    }

    public function test_skips_already_published_posts(): void
    {
        Event::fake([PostPublished::class]);
        Carbon::setTestNow('2026-06-01 10:00:00');

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();

        Post::factory()->newsletter()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $this->artisan('publishing:publish-scheduled-posts')->assertSuccessful();

        Event::assertNotDispatched(PostPublished::class);

        Carbon::setTestNow();
    }
}
