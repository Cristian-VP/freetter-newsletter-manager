<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\PostReposted;
use Domains\Community\Http\Requests\StoreRepostRequest;
use Domains\Community\Models\Repost;
use Domains\Publishing\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RepostController extends Controller
{
    public function store(StoreRepostRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $post = Post::query()->findOrFail($request->string('post_id')->value());

        try {
            $repost = Repost::query()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (in_array($sqlState, ['23505', '23000'], true)) {
                return response()->json([
                    'message' => 'Repost already exists for this post.',
                ], 409);
            }

            throw $exception;
        }

        event(new PostReposted(
            repost: $repost,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $repost->id,
                'user_id' => $repost->user_id,
                'post_id' => $repost->post_id,
            ],
        ], 201);
    }
}
