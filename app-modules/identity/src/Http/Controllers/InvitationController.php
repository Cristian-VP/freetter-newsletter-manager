<?php

namespace Domains\Identity\Http\Controllers;

use Domains\Identity\Http\Requests\StoreInvitationRequest;
use Domains\Identity\Models\Invitation;
use Domains\Identity\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class InvitationController extends Controller
{
    public function store(StoreInvitationRequest $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->findOrFail($workspace);

        $invitation = Invitation::query()->create([
            'workspace_id' => $workspaceModel->id,
            'email' => $request->string('email')->value(),
            'role' => $request->string('role')->value(),
            'token' => Invitation::generateToken(),
            'expires_at' => $request->date('expires_at') ?? now()->addDays(7),
            'accepted_by_user_id' => null,
            'accepted_at' => null,
        ]);

        return response()->json([
            'data' => [
                'id' => $invitation->id,
                'workspace_id' => $invitation->workspace_id,
                'email' => $invitation->email,
                'role' => $invitation->role,
            ],
        ], 201);
    }
}
