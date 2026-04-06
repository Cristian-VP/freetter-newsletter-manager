<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\CommentModerated;
use Domains\Community\Http\Requests\ModerateCommentRequest;
use Domains\Community\Models\Comment;
use Domains\Identity\Models\Membership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ModerationController extends Controller
{
    public function update(ModerateCommentRequest $request, string $comment): JsonResponse
    {
        $commentModel = Comment::query()->findOrFail($comment);
        $this->ensureCanModerate($request, $commentModel->workspace_id);

        $user = $request->user();
        $action = $request->string('action')->value();

        $commentModel->is_hidden = true;
        $commentModel->moderated_by_user_id = $user?->id;
        $commentModel->moderated_at = now();
        $commentModel->moderation_reason = $request->filled('reason')
            ? $request->string('reason')->value()
            : null;
        $commentModel->save();

        if ($action === 'delete') {
            $commentModel->delete();
        }

        $updatedComment = Comment::query()->withTrashed()->findOrFail($commentModel->id);

        event(new CommentModerated(
            comment: $updatedComment,
            action: $action,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $updatedComment->id,
                'is_hidden' => $updatedComment->is_hidden,
                'moderated_at' => $updatedComment->moderated_at,
                'deleted_at' => $updatedComment->deleted_at,
                'action' => $action,
            ],
        ]);
    }

    private function ensureCanModerate(Request $request, string $workspaceId): void
    {
        $user = $request->user();
        abort_unless($user, 401);

        $isAllowed = Membership::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'editor'])
            ->exists();

        abort_unless($isAllowed, 403);
    }
}
