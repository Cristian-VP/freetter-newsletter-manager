<?php

declare(strict_types=1);

namespace Domains\Publishing\Tests\Feature\Http;

use Domains\Community\Models\Like;
use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomeFeedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_route_uses_web_and_auth_middlewares(): void
    {
        $route = Route::getRoutes()->getByName('home');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    public function test_home_feed_requires_authentication(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect(route('login'));
    }

    public function test_home_feed_includes_own_posts(): void
    {
        $workspace = Workspace::factory()->create();
        /** @var User $viewer */
        $viewer = User::withoutEvents(static function (): User {
            return User::factory()->create();
        });

        Membership::factory()
            ->forUser($viewer)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $ownPost = Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $viewer->id,
            'title' => 'My own post',
        ]);

        $response = $this->actingAs($viewer)->get('/home');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('publishing::Home', false)
            ->where('workspace_id', $workspace->id)
            ->has('posts', 1)
            ->where('posts.0.id', $ownPost->id)
        );
    }

    public function test_home_feed_limits_posts_to_thirty_items(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $workspace = Workspace::factory()->create();
        /** @var User $viewer */
        $viewer = User::withoutEvents(static function (): User {
            return User::factory()->create();
        });
        $author = User::factory()->create();

        Membership::factory()
            ->forUser($viewer)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $newestPost = null;

        for ($index = 0; $index < 35; $index++) {
            $post = Post::factory()->published()->create([
                'workspace_id' => $workspace->id,
                'author_id' => $author->id,
                'title' => sprintf('External post %d', $index + 1),
                'published_at' => now()->subMinutes($index),
            ]);

            if ($index === 0) {
                $newestPost = $post;
            }
        }

        $response = $this->actingAs($viewer)->get('/home');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('publishing::Home', false)
            ->where('workspace_id', $workspace->id)
            ->has('posts', 30)
            ->where('feed.has_more', true)
            ->where('posts.0.id', $newestPost?->id)
            ->missing('posts.30')
        );

        Carbon::setTestNow();
    }

    public function test_home_feed_shows_global_recent_posts_from_other_users(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $workspace = Workspace::factory()->create();
        /** @var User $viewer */
        $viewer = User::withoutEvents(static function (): User {
            return User::factory()->create();
        });
        $authorA = User::factory()->create(['name' => 'Author A']);
        $authorB = User::factory()->create(['name' => 'Author B']);

        Membership::factory()
            ->forUser($viewer)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $ownPost = Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $viewer->id,
            'title' => 'Own post',
            'published_at' => now()->subMinutes(5),
        ]);

        $oldPost = Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $authorA->id,
            'title' => 'Old external post',
            'published_at' => now()->subDays(2),
        ]);

        $recentPost = Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $authorB->id,
            'title' => 'Recent external post',
            'published_at' => now()->subHour(),
        ]);

        $media = Media::factory()->create([
            'workspace_id' => $workspace->id,
            'path' => 'https://images.example.com/recent-post.jpg',
            'disk' => 'local',
        ]);

        $recentPost->media()->attach($media->id);

        Like::factory()->create([
            'user_id' => $viewer->id,
            'post_id' => $recentPost->id,
        ]);

        Like::factory()->create([
            'user_id' => User::factory()->create()->id,
            'post_id' => $recentPost->id,
        ]);

        Like::factory()->create([
            'user_id' => User::factory()->create()->id,
            'post_id' => $oldPost->id,
        ]);

        $response = $this->actingAs($viewer)->get('/home');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('publishing::Home', false)
            ->where('workspace_id', $workspace->id)
            ->has('posts', 3)
            ->where('posts.0.id', $ownPost->id)
            ->where('posts.1.id', $recentPost->id)
            ->where('posts.1.author.name', 'Author B')
            ->where('posts.1.metrics.likes_count', 2)
            ->where('posts.1.metrics.liked_by_me', true)
            ->where('posts.1.media.0.url', 'https://images.example.com/recent-post.jpg')
            ->where('posts.2.id', $oldPost->id)
            ->where('posts.2.metrics.likes_count', 1)
            ->where('posts.2.metrics.liked_by_me', false)
            ->missing('posts.3')
        );

        $this->assertDatabaseHas('publishing_posts', ['id' => $ownPost->id]);

        Carbon::setTestNow();
    }

    public function test_feed_endpoint_supports_cursor_pagination(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $workspace = Workspace::factory()->create();
        /** @var User $viewer */
        $viewer = User::withoutEvents(static function (): User {
            return User::factory()->create();
        });
        $author = User::factory()->create();

        Membership::factory()
            ->forUser($viewer)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        for ($index = 0; $index < 35; $index++) {
            Post::factory()->published()->create([
                'workspace_id' => $workspace->id,
                'author_id' => $author->id,
                'title' => sprintf('Paged post %d', $index + 1),
                'published_at' => now()->subMinutes($index),
            ]);
        }

        $firstPage = $this->actingAs($viewer)->getJson('/publishing/feed');

        $firstPage
            ->assertOk()
            ->assertJsonCount(30, 'data.posts')
            ->assertJsonPath('data.has_more', true);

        $cursor = $firstPage->json('data.next_cursor');
        $this->assertIsString($cursor);

        $secondPage = $this->actingAs($viewer)->getJson('/publishing/feed?cursor='.$cursor);

        $secondPage
            ->assertOk()
            ->assertJsonCount(5, 'data.posts')
            ->assertJsonPath('data.has_more', false)
            ->assertJsonPath('data.next_cursor', null);

        Carbon::setTestNow();
    }

    public function test_home_feed_uses_stream_route_for_local_media_files(): void
    {
        Storage::fake('local');

        $workspace = Workspace::factory()->create();
        /** @var User $viewer */
        $viewer = User::withoutEvents(static function (): User {
            return User::factory()->create();
        });

        Membership::factory()
            ->forUser($viewer)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $viewer->id,
        ]);

        $path = 'publishing/posts/'.$workspace->id.'/example.jpg';
        Storage::disk('local')->put($path, 'binary-image-content');

        $media = Media::factory()->create([
            'workspace_id' => $workspace->id,
            'path' => $path,
            'disk' => 'local',
            'mime_type' => 'image/jpeg',
        ]);

        $post->media()->attach($media->id);

        $response = $this->actingAs($viewer)->get('/home');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('posts.0.media.0.url', route('publishing.media.show', ['media' => $media->id]))
        );

        $this->actingAs($viewer)
            ->get(route('publishing.media.show', ['media' => $media->id]))
            ->assertOk();
    }
}
