<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\PostLiked;
use Domains\Community\Events\PostUnliked;
use Domains\Community\Http\Requests\StoreLikeRequest;
use Domains\Community\Models\Like;
use Domains\Publishing\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class LikeController extends Controller
{
    public function store(StoreLikeRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $post = Post::query()->findOrFail($request->string('post_id')->value());

        try {
            $like = Like::query()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (in_array($sqlState, ['23505', '23000'], true)) {
                return response()->json([
                    'message' => 'Like already exists for this post.',
                ], 409);
            }

            throw $exception;
        }

        event(new PostLiked(
            like: $like,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $like->id,
                'user_id' => $like->user_id,
                'post_id' => $like->post_id,
            ],
        ], 201);
    }

    public function destroy(StoreLikeRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $postId = $request->string('post_id')->value();

        $like = Like::query()
            ->where('user_id', $user->id)
            ->where('post_id', $postId)
            ->first();

        if (! $like) {
            return response()->json([
                'data' => [
                    'post_id' => $postId,
                    'liked' => false,
                    'removed' => false,
                ],
            ]);
        }

        $like->delete();

        event(new PostUnliked(
            userId: $user->id,
            postId: $postId,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'post_id' => $postId,
                'liked' => false,
                'removed' => true,
            ],
        ]);
    }
}
