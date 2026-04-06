<?php

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Events\CommentModerated;
use Domains\Community\Models\Comment;
use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ModerationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_moderate_comment(): void
    {
        Event::fake([CommentModerated::class]);

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        /** @var User $moderator */
        $moderator = User::factory()->create();
        $commentAuthor = User::factory()->create();

        Membership::factory()
            ->forUser($moderator)
            ->forWorkspace($workspace)
            ->editor()
            ->create();

        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'user_id' => $commentAuthor->id,
        ]);

        $response = $this->actingAs($moderator)->patchJson('/community/comments/'.$comment->id.'/moderate', [
            'action' => 'hide',
            'reason' => 'Inappropriate language',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('community_comments', [
            'id' => $comment->id,
            'is_hidden' => true,
            'moderated_by_user_id' => $moderator->id,
            'moderation_reason' => 'Inappropriate language',
        ]);

        Event::assertDispatched(CommentModerated::class);
    }

    public function test_writer_cannot_moderate_comment(): void
    {
        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        /** @var User $writer */
        $writer = User::factory()->create();
        $commentAuthor = User::factory()->create();

        Membership::factory()
            ->forUser($writer)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'workspace_id' => $workspace->id,
            'post_id' => $post->id,
            'user_id' => $commentAuthor->id,
        ]);

        $response = $this->actingAs($writer)->patchJson('/community/comments/'.$comment->id.'/moderate', [
            'action' => 'hide',
            'reason' => 'No permissions',
        ]);

        $response->assertForbidden();
    }
}
