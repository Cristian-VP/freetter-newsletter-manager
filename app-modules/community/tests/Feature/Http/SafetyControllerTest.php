<?php

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Events\PostReported;
use Domains\Community\Events\UserBlocked;
use Domains\Community\Events\UserMuted;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SafetyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mute_and_block_another_user(): void
    {
        Event::fake([UserMuted::class, UserBlocked::class]);

        /** @var User $user */
        $user = User::factory()->create();
        $target = User::factory()->create();

        $mute = $this->actingAs($user)->postJson('/community/mutes', [
            'target_user_id' => $target->id,
        ]);

        $mute->assertCreated();

        $block = $this->actingAs($user)->postJson('/community/blocks', [
            'target_user_id' => $target->id,
        ]);

        $block->assertCreated();

        $selfMute = $this->actingAs($user)->postJson('/community/mutes', [
            'target_user_id' => $user->id,
        ]);

        $selfMute->assertStatus(422);

        $this->assertDatabaseHas('community_muted_users', [
            'user_id' => $user->id,
            'muted_user_id' => $target->id,
        ]);

        $this->assertDatabaseHas('community_blocked_users', [
            'user_id' => $user->id,
            'blocked_user_id' => $target->id,
        ]);

        Event::assertDispatched(UserMuted::class);
        Event::assertDispatched(UserBlocked::class);
    }

    public function test_user_can_report_post_with_reason_and_category(): void
    {
        Event::fake([PostReported::class]);

        $workspace = Workspace::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();
        $author = User::factory()->create();

        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $response = $this->actingAs($user)->postJson('/community/reports', [
            'post_id' => $post->id,
            'category' => 'spam',
            'reason' => 'Este contenido parece spam automatizado.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('community_post_reports', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        Event::assertDispatched(PostReported::class);
    }
}
