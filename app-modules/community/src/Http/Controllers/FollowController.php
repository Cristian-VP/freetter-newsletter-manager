<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\UserSubscribedToWorkspace;
use Domains\Community\Events\WorkspaceFollowed;
use Domains\Community\Events\WorkspaceUnfollowed;
use Domains\Community\Http\Requests\StoreFollowRequest;
use Domains\Community\Models\Follower;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class FollowController extends Controller
{
    public function store(StoreFollowRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceId = $request->string('followed_workspace_id')->value();

        try {
            $follow = Follower::query()->create([
                'follower_id' => $user->id,
                'followed_workspace_id' => $workspaceId,
            ]);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (in_array($sqlState, ['23505', '23000'], true)) {
                return response()->json([
                    'message' => 'Follow already exists for this workspace.',
                ], 409);
            }

            throw $exception;
        }

        event(new WorkspaceFollowed(
            follower: $follow,
            context: [
                'source' => 'community.http',
            ],
        ));

        // Also notify Audience that the follower has effectively subscribed
        event(new UserSubscribedToWorkspace(
            workspaceId: $workspaceId,
            userId: $user->id,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $follow->id,
                'follower_id' => $follow->follower_id,
                'followed_workspace_id' => $follow->followed_workspace_id,
            ],
        ], 201);
    }

    public function destroy(StoreFollowRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceId = $request->string('followed_workspace_id')->value();

        $follow = Follower::query()
            ->where('follower_id', $user->id)
            ->where('followed_workspace_id', $workspaceId)
            ->first();

        if (! $follow) {
            return response()->json([
                'data' => [
                    'followed_workspace_id' => $workspaceId,
                    'following' => false,
                    'removed' => false,
                ],
            ]);
        }

        $follow->delete();

        event(new WorkspaceUnfollowed(
            followerId: $user->id,
            workspaceId: $workspaceId,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'followed_workspace_id' => $workspaceId,
                'following' => false,
                'removed' => true,
            ],
        ]);
    }
}
