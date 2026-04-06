<?php

namespace Domains\Community\Tests\Feature\Events;

use Domains\Community\Events\CommentCreated;
use Domains\Community\Events\PostLiked;
use Domains\Community\Models\Comment;
use Domains\Community\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityActivityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_created_event_is_logged_in_activity(): void
    {
        $comment = Comment::factory()->create();

        event(new CommentCreated($comment, ['source' => 'integration_test']));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'community.comment.created',
            'entity_type' => 'comment',
            'entity_id' => $comment->id,
        ]);
    }

    public function test_post_liked_event_is_logged_in_activity(): void
    {
        $like = Like::factory()->create();

        event(new PostLiked($like, ['source' => 'integration_test']));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'community.post.liked',
            'entity_type' => 'post_like',
            'entity_id' => $like->id,
        ]);
    }
}
