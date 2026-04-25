<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\UserBlocked;
use Domains\Community\Http\Requests\StoreBlockRequest;
use Domains\Community\Models\BlockedUser;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BlockController extends Controller
{
    public function store(StoreBlockRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $targetUserId = $request->string('target_user_id')->value();
        abort_if($targetUserId === (string) $user->id, 422, 'No puedes bloquearte a ti mismo.');

        try {
            $block = BlockedUser::query()->create([
                'user_id' => $user->id,
                'blocked_user_id' => $targetUserId,
            ]);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (in_array($sqlState, ['23505', '23000'], true)) {
                return response()->json([
                    'message' => 'User already blocked.',
                ], 409);
            }

            throw $exception;
        }

        event(new UserBlocked(
            block: $block,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $block->id,
                'user_id' => $block->user_id,
                'blocked_user_id' => $block->blocked_user_id,
            ],
        ], 201);
    }
}
