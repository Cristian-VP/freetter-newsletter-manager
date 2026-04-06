<?php

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Events\WorkspaceFollowed;
use Domains\Community\Events\WorkspaceUnfollowed;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FollowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_follow_and_unfollow_workspace_with_unique_constraint(): void
    {
        Event::fake([WorkspaceFollowed::class, WorkspaceUnfollowed::class]);

        $workspace = Workspace::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $firstFollow = $this->actingAs($user)->postJson('/community/follows', [
            'followed_workspace_id' => $workspace->id,
        ]);

        $firstFollow->assertCreated();

        $this->assertDatabaseHas('community_followers', [
            'follower_id' => $user->id,
            'followed_workspace_id' => $workspace->id,
        ]);

        $duplicateFollow = $this->actingAs($user)->postJson('/community/follows', [
            'followed_workspace_id' => $workspace->id,
        ]);

        $duplicateFollow->assertStatus(409);

        $unfollow = $this->actingAs($user)->deleteJson('/community/follows', [
            'followed_workspace_id' => $workspace->id,
        ]);

        $unfollow->assertOk();

        $this->assertDatabaseMissing('community_followers', [
            'follower_id' => $user->id,
            'followed_workspace_id' => $workspace->id,
        ]);

        Event::assertDispatched(WorkspaceFollowed::class);
        Event::assertDispatched(WorkspaceUnfollowed::class);
    }
}
