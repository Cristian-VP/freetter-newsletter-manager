<?php

declare(strict_types=1);

namespace Domains\Publishing\Tests\Feature\Http;

use Domains\Community\Models\Like;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
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

    public function test_home_feed_returns_empty_when_only_own_posts_exist(): void
    {
        $workspace = Workspace::factory()->create();
        /** @var User $viewer */
        $viewer = User::factory()->create();

        Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $viewer->id,
            'title' => 'My own post',
        ]);

        $response = $this->actingAs($viewer)->get('/home');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('publishing::Home', false)
            ->has('posts', 0)
        );
    }

    public function test_home_feed_limits_posts_to_thirty_items(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $workspace = Workspace::factory()->create();
        /** @var User $viewer */
        $viewer = User::factory()->create();
        $author = User::factory()->create();

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
            ->has('posts', 30)
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
        $viewer = User::factory()->create();
        $authorA = User::factory()->create(['name' => 'Author A']);
        $authorB = User::factory()->create(['name' => 'Author B']);

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
            ->has('posts', 2)
            ->where('posts.0.id', $recentPost->id)
            ->where('posts.0.author.name', 'Author B')
            ->where('posts.0.metrics.likes_count', 2)
            ->where('posts.0.metrics.liked_by_me', true)
            ->where('posts.0.media.0.url', 'https://images.example.com/recent-post.jpg')
            ->where('posts.1.id', $oldPost->id)
            ->where('posts.1.metrics.likes_count', 1)
            ->where('posts.1.metrics.liked_by_me', false)
            ->missing('posts.2')
        );

        $this->assertDatabaseHas('publishing_posts', ['id' => $ownPost->id]);

        Carbon::setTestNow();
    }
}
