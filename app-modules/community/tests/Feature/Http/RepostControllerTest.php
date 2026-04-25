<?php

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Events\PostReposted;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RepostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_repost_is_unique_for_user_and_post(): void
    {
        Event::fake([PostReposted::class]);

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $firstRepost = $this->actingAs($user)->postJson('/community/reposts', [
            'post_id' => $post->id,
        ]);

        $firstRepost->assertCreated();

        $duplicate = $this->actingAs($user)->postJson('/community/reposts', [
            'post_id' => $post->id,
        ]);

        $duplicate->assertStatus(409);

        $this->assertDatabaseHas('community_reposts', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        Event::assertDispatched(PostReposted::class);
    }
}
