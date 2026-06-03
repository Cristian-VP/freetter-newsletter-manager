<?php

namespace Domains\Identity\Tests\Feature\Http;

use Domains\Community\Models\Bookmark;
use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /profile ─────────────────────────────────────────────────────────

    public function test_profile_page_requires_authentication(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_profile_page_renders_for_authenticated_user(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create(['name' => 'Ana García']);
        });
        $workspace = Workspace::factory()->create();
        Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('identity::Profile', false)
            ->where('profile.name', 'Ana García')
            ->where('profile.workspace_id', $workspace->id)
            ->has('profile.stats.published_count')
            ->has('profile.stats.followers_count')
            ->has('profile.stats.following_count')
            ->has('profile.stats.bookmarks_count')
        );
    }

    public function test_profile_page_shows_zero_stats_without_workspace(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('identity::Profile', false)
            ->where('profile.workspace_id', null)
            ->where('profile.stats.published_count', 0)
            ->where('profile.stats.followers_count', 0)
        );
    }

    public function test_profile_page_counts_published_posts_correctly(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $workspace = Workspace::factory()->create();
        Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
        ]);

        Post::factory()->newsletter()->published()->create(['workspace_id' => $workspace->id, 'author_id' => $user->id]);
        Post::factory()->newsletter()->published()->create(['workspace_id' => $workspace->id, 'author_id' => $user->id]);
        Post::factory()->newsletter()->draft()->create(['workspace_id' => $workspace->id, 'author_id' => $user->id]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('profile.stats.published_count', 2)
        );
    }

    // ─── GET /profile/edit ────────────────────────────────────────────────────

    public function test_profile_edit_page_requires_authentication(): void
    {
        $this->get('/profile/edit')->assertRedirect('/login');
    }

    public function test_profile_edit_page_renders_for_authenticated_user(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });

        $response = $this->actingAs($user)->get('/profile/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('identity::ProfileEdit', false));
    }

    // ─── GET /identity/profile/newsletters ───────────────────────────────────

    public function test_profile_newsletters_api_requires_authentication(): void
    {
        $this->getJson('/identity/profile/newsletters')->assertUnauthorized();
    }

    public function test_profile_newsletters_api_returns_published_newsletters(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $workspace = Workspace::factory()->create(['slug' => 'my-workspace']);
        Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
        ]);

        Post::factory()->newsletter()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Mi primera newsletter',
        ]);
        Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson('/identity/profile/newsletters');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.newsletters');
        $response->assertJsonPath('data.newsletters.0.title', 'Mi primera newsletter');
    }

    public function test_profile_newsletters_api_returns_empty_without_workspace(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });

        $response = $this->actingAs($user)->getJson('/identity/profile/newsletters');

        $response->assertOk();
        $response->assertJsonCount(0, 'data.newsletters');
    }

    // ─── GET /identity/profile/bookmarks ─────────────────────────────────────

    public function test_profile_bookmarks_api_requires_authentication(): void
    {
        $this->getJson('/identity/profile/bookmarks')->assertUnauthorized();
    }

    public function test_profile_bookmarks_api_returns_user_bookmarks(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $author = User::withoutEvents(function () {
            return User::factory()->create(['name' => 'Carlos López']);
        });
        $workspace = Workspace::factory()->create(['slug' => 'otro-workspace']);
        $post = Post::factory()->newsletter()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'Newsletter guardada',
            'excerpt' => 'Un resumen interesante.',
        ]);

        Bookmark::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user)->getJson('/identity/profile/bookmarks');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.bookmarks');
        $response->assertJsonPath('data.bookmarks.0.title', 'Newsletter guardada');
        $response->assertJsonPath('data.bookmarks.0.author.name', 'Carlos López');
    }

    public function test_profile_bookmarks_api_does_not_return_other_users_bookmarks(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $otherUser = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $post = Post::factory()->create();

        Bookmark::factory()->create(['user_id' => $otherUser->id, 'post_id' => $post->id]);

        $response = $this->actingAs($user)->getJson('/identity/profile/bookmarks');

        $response->assertOk();
        $response->assertJsonCount(0, 'data.bookmarks');
    }
}
