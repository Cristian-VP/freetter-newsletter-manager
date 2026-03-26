<?php

namespace Domains\Identity\Http\Controllers;

use Domains\Identity\Http\Requests\StoreWorkspaceRequest;
use Domains\Identity\Models\Membership;
use Domains\Identity\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends Controller
{
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = DB::transaction(function () use ($request): Workspace {
            $workspace = Workspace::query()->create([
                'name' => $request->string('name')->value(),
                'slug' => $request->string('slug')->value(),
                'branding_config' => $request->input('branding_config', []),
                'donation_config' => $request->input('donation_config', []),
            ]);

            Membership::query()->create([
                'user_id' => $request->string('owner_user_id')->value(),
                'workspace_id' => $workspace->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $workspace;
        });

        return response()->json([
            'data' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
        ], 201);
    }
}
