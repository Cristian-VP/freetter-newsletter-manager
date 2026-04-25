<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Community\Events\CommentCreated;
use Domains\Community\Events\CommentModerated;
use Domains\Community\Events\PostBookmarked;
use Domains\Community\Events\PostLiked;
use Domains\Community\Events\PostReported;
use Domains\Community\Events\PostReposted;
use Domains\Community\Events\PostUnbookmarked;
use Domains\Community\Events\PostUnliked;
use Domains\Community\Events\UserBlocked;
use Domains\Community\Events\UserMuted;
use Domains\Community\Events\WorkspaceFollowed;
use Domains\Community\Events\WorkspaceUnfollowed;

class LogCommunityActivity
{
    public function handle(object $event): void
    {
        if ($event instanceof CommentCreated) {
            ActivityLog::query()->create([
                'user_id' => $event->comment->user_id,
                'action' => 'community.comment.created',
                'entity_type' => 'comment',
                'entity_id' => $event->comment->id,
                'metadata' => [
                    'post_id' => $event->comment->post_id,
                    'workspace_id' => $event->comment->workspace_id,
                    'parent_id' => $event->comment->parent_id,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof CommentModerated) {
            ActivityLog::query()->create([
                'user_id' => $event->comment->moderated_by_user_id,
                'action' => 'community.comment.moderated',
                'entity_type' => 'comment',
                'entity_id' => $event->comment->id,
                'metadata' => [
                    'post_id' => $event->comment->post_id,
                    'workspace_id' => $event->comment->workspace_id,
                    'action' => $event->action,
                    'moderation_reason' => $event->comment->moderation_reason,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof PostLiked) {
            ActivityLog::query()->create([
                'user_id' => $event->like->user_id,
                'action' => 'community.post.liked',
                'entity_type' => 'post_like',
                'entity_id' => $event->like->id,
                'metadata' => [
                    'post_id' => $event->like->post_id,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof PostUnliked) {
            ActivityLog::query()->create([
                'user_id' => $event->userId,
                'action' => 'community.post.unliked',
                'entity_type' => 'post',
                'entity_id' => $event->postId,
                'metadata' => [
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof PostBookmarked) {
            ActivityLog::query()->create([
                'user_id' => $event->bookmark->user_id,
                'action' => 'community.post.bookmarked',
                'entity_type' => 'post_bookmark',
                'entity_id' => $event->bookmark->id,
                'metadata' => [
                    'post_id' => $event->bookmark->post_id,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof PostUnbookmarked) {
            ActivityLog::query()->create([
                'user_id' => $event->userId,
                'action' => 'community.post.unbookmarked',
                'entity_type' => 'post',
                'entity_id' => $event->postId,
                'metadata' => [
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof PostReposted) {
            ActivityLog::query()->create([
                'user_id' => $event->repost->user_id,
                'action' => 'community.post.reposted',
                'entity_type' => 'post_repost',
                'entity_id' => $event->repost->id,
                'metadata' => [
                    'post_id' => $event->repost->post_id,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof UserMuted) {
            ActivityLog::query()->create([
                'user_id' => $event->mute->user_id,
                'action' => 'community.user.muted',
                'entity_type' => 'user_mute',
                'entity_id' => $event->mute->id,
                'metadata' => [
                    'muted_user_id' => $event->mute->muted_user_id,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof UserBlocked) {
            ActivityLog::query()->create([
                'user_id' => $event->block->user_id,
                'action' => 'community.user.blocked',
                'entity_type' => 'user_block',
                'entity_id' => $event->block->id,
                'metadata' => [
                    'blocked_user_id' => $event->block->blocked_user_id,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof PostReported) {
            ActivityLog::query()->create([
                'user_id' => $event->report->user_id,
                'action' => 'community.post.reported',
                'entity_type' => 'post_report',
                'entity_id' => $event->report->id,
                'metadata' => [
                    'post_id' => $event->report->post_id,
                    'category' => $event->report->category,
                    'status' => $event->report->status,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof WorkspaceFollowed) {
            ActivityLog::query()->create([
                'user_id' => $event->follower->follower_id,
                'action' => 'community.workspace.followed',
                'entity_type' => 'workspace_follow',
                'entity_id' => $event->follower->id,
                'metadata' => [
                    'followed_workspace_id' => $event->follower->followed_workspace_id,
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof WorkspaceUnfollowed) {
            ActivityLog::query()->create([
                'user_id' => $event->followerId,
                'action' => 'community.workspace.unfollowed',
                'entity_type' => 'workspace',
                'entity_id' => $event->workspaceId,
                'metadata' => [
                    'context' => $event->context,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
