<?php

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Events\PostLiked;
use Domains\Community\Events\PostUnliked;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LikeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_is_unique_and_can_be_removed(): void
    {
        Event::fake([PostLiked::class, PostUnliked::class]);

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $firstLike = $this->actingAs($user)->postJson('/community/likes', [
            'post_id' => $post->id,
        ]);

        $firstLike->assertCreated();

        $this->assertDatabaseHas('community_likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $duplicateLike = $this->actingAs($user)->postJson('/community/likes', [
            'post_id' => $post->id,
        ]);

        $duplicateLike->assertStatus(409);

        $unlike = $this->actingAs($user)->deleteJson('/community/likes', [
            'post_id' => $post->id,
        ]);

        $unlike->assertOk();

        $this->assertDatabaseMissing('community_likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        Event::assertDispatched(PostLiked::class);
        Event::assertDispatched(PostUnliked::class);
    }
}
