<?php

namespace Domains\Community\Tests\Feature\Events;

use Domains\Community\Events\CommentCreated;
use Domains\Community\Events\CommentModerated;
use Domains\Community\Events\PostLiked;
use Domains\Community\Events\PostUnliked;
use Domains\Community\Events\WorkspaceFollowed;
use Domains\Community\Events\WorkspaceUnfollowed;
use Domains\Community\Models\Comment;
use Domains\Community\Models\Follower;
use Domains\Community\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CommunityEventsDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_emits_expected_domain_events(): void
    {
        Event::fake([
            CommentCreated::class,
            CommentModerated::class,
            PostLiked::class,
            PostUnliked::class,
            WorkspaceFollowed::class,
            WorkspaceUnfollowed::class,
        ]);

        $comment = Comment::factory()->create();
        $like = Like::factory()->create();
        $follower = Follower::factory()->create();

        event(new CommentCreated($comment, ['source' => 'test']));
        event(new CommentModerated($comment, 'hide', ['source' => 'test']));
        event(new PostLiked($like, ['source' => 'test']));
        event(new PostUnliked($like->user_id, $like->post_id, ['source' => 'test']));
        event(new WorkspaceFollowed($follower, ['source' => 'test']));
        event(new WorkspaceUnfollowed($follower->follower_id, $follower->followed_workspace_id, ['source' => 'test']));

        Event::assertDispatched(CommentCreated::class);
        Event::assertDispatched(CommentModerated::class);
        Event::assertDispatched(PostLiked::class);
        Event::assertDispatched(PostUnliked::class);
        Event::assertDispatched(WorkspaceFollowed::class);
        Event::assertDispatched(WorkspaceUnfollowed::class);
    }
}
