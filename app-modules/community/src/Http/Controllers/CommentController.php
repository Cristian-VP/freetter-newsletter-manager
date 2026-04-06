<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\CommentCreated;
use Domains\Community\Http\Requests\StoreCommentRequest;
use Domains\Community\Models\Comment;
use Domains\Publishing\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $post = Post::query()->findOrFail($request->string('post_id')->value());

        $parentId = $request->filled('parent_id') ? $request->string('parent_id')->value() : null;

        if ($parentId) {
            $parent = Comment::query()->findOrFail($parentId);

            if ($parent->post_id !== $post->id) {
                return response()->json([
                    'message' => 'Parent comment does not belong to this post.',
                ], 422);
            }
        }

        $comment = Comment::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'workspace_id' => $post->workspace_id,
            'parent_id' => $parentId,
            'content' => $request->string('content')->value(),
            'is_hidden' => false,
        ]);

        event(new CommentCreated(
            comment: $comment,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'workspace_id' => $comment->workspace_id,
                'parent_id' => $comment->parent_id,
                'content' => $comment->content,
            ],
        ], 201);
    }
}
