<?php

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Events\PostBookmarked;
use Domains\Community\Events\PostUnbookmarked;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookmarkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookmark_is_unique_and_can_be_removed(): void
    {
        Event::fake([PostBookmarked::class, PostUnbookmarked::class]);

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $firstBookmark = $this->actingAs($user)->postJson('/community/bookmarks', [
            'post_id' => $post->id,
        ]);

        $firstBookmark->assertCreated();

        $duplicateBookmark = $this->actingAs($user)->postJson('/community/bookmarks', [
            'post_id' => $post->id,
        ]);

        $duplicateBookmark->assertStatus(409);

        $remove = $this->actingAs($user)->deleteJson('/community/bookmarks', [
            'post_id' => $post->id,
        ]);

        $remove->assertOk();

        $this->assertDatabaseMissing('community_bookmarks', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        Event::assertDispatched(PostBookmarked::class);
        Event::assertDispatched(PostUnbookmarked::class);
    }
}
