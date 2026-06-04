<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\UserMuted;
use Domains\Community\Http\Requests\StoreMuteRequest;
use Domains\Community\Models\MutedUser;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class MuteController extends Controller
{
    public function store(StoreMuteRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $targetUserId = $request->string('target_user_id')->value();
        abort_if($targetUserId === (string) $user->id, 422, 'No puedes silenciarte a ti mismo.');

        try {
            $mute = MutedUser::query()->create([
                'user_id' => $user->id,
                'muted_user_id' => $targetUserId,
            ]);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (in_array($sqlState, ['23505', '23000'], true)) {
                return response()->json([
                    'message' => 'User already muted.',
                ], 409);
            }

            throw $exception;
        }

        event(new UserMuted(
            mute: $mute,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $mute->id,
                'user_id' => $mute->user_id,
                'muted_user_id' => $mute->muted_user_id,
            ],
        ], 201);
    }
}
