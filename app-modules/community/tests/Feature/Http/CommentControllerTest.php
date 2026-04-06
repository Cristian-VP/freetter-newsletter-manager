<?php

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Events\CommentCreated;
use Domains\Community\Models\Comment;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_comment_and_reply_on_post(): void
    {
        Event::fake([CommentCreated::class]);

        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        /** @var User $commenter */
        $commenter = User::factory()->create();

        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $commentResponse = $this->actingAs($commenter)->postJson('/community/comments', [
            'post_id' => $post->id,
            'content' => 'First comment',
        ]);

        $commentResponse->assertCreated();

        $commentId = (string) $commentResponse->json('data.id');

        $this->assertDatabaseHas('community_comments', [
            'id' => $commentId,
            'user_id' => $commenter->id,
            'post_id' => $post->id,
            'workspace_id' => $workspace->id,
            'parent_id' => null,
            'is_hidden' => false,
        ]);

        $replyResponse = $this->actingAs($commenter)->postJson('/community/comments', [
            'post_id' => $post->id,
            'content' => 'Reply comment',
            'parent_id' => $commentId,
        ]);

        $replyResponse->assertCreated();

        $this->assertDatabaseHas('community_comments', [
            'id' => $replyResponse->json('data.id'),
            'post_id' => $post->id,
            'parent_id' => $commentId,
        ]);

        Event::assertDispatched(CommentCreated::class, 2);
    }

    public function test_rejects_reply_when_parent_comment_belongs_to_other_post(): void
    {
        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();
        /** @var User $commenter */
        $commenter = User::factory()->create();

        $firstPost = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $secondPost = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $parentComment = Comment::factory()->create([
            'user_id' => $commenter->id,
            'workspace_id' => $workspace->id,
            'post_id' => $firstPost->id,
        ]);

        $response = $this->actingAs($commenter)->postJson('/community/comments', [
            'post_id' => $secondPost->id,
            'content' => 'Invalid reply',
            'parent_id' => $parentComment->id,
        ]);

        $response->assertStatus(422);
    }
}
