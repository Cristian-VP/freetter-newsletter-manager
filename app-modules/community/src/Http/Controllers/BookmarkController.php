<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\PostBookmarked;
use Domains\Community\Events\PostUnbookmarked;
use Domains\Community\Http\Requests\StoreBookmarkRequest;
use Domains\Community\Models\Bookmark;
use Domains\Publishing\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BookmarkController extends Controller
{
    public function store(StoreBookmarkRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $post = Post::query()->findOrFail($request->string('post_id')->value());

        try {
            $bookmark = Bookmark::query()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (in_array($sqlState, ['23505', '23000'], true)) {
                return response()->json([
                    'message' => 'Bookmark already exists for this post.',
                ], 409);
            }

            throw $exception;
        }

        event(new PostBookmarked(
            bookmark: $bookmark,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $bookmark->id,
                'user_id' => $bookmark->user_id,
                'post_id' => $bookmark->post_id,
            ],
        ], 201);
    }

    public function destroy(StoreBookmarkRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $postId = $request->string('post_id')->value();

        $bookmark = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('post_id', $postId)
            ->first();

        if (! $bookmark) {
            return response()->json([
                'data' => [
                    'post_id' => $postId,
                    'bookmarked' => false,
                    'removed' => false,
                ],
            ]);
        }

        $bookmark->delete();

        event(new PostUnbookmarked(
            userId: $user->id,
            postId: $postId,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'post_id' => $postId,
                'bookmarked' => false,
                'removed' => true,
            ],
        ]);
    }
}
