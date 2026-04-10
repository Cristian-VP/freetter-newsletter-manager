<?php

namespace Domains\Delivery\Http\Controllers;

use Domains\Delivery\Http\Requests\SendCampaignRequest;
use Domains\Delivery\Jobs\SendCampaignJob;
use Domains\Delivery\Models\Campaign;
use Domains\Identity\Models\Membership;
use Domains\Identity\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CampaignController extends Controller
{
    public function index(Request $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->findOrFail($workspace);
        $this->ensureWorkspaceAccess($request, $workspaceModel->id);

        $campaigns = Campaign::query()
            ->where('workspace_id', $workspaceModel->id)
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => $campaigns->map(static fn (Campaign $campaign): array => [
                'id' => $campaign->id,
                'workspace_id' => $campaign->workspace_id,
                'post_id' => $campaign->post_id,
                'status' => $campaign->status,
                'stats' => $campaign->stats,
                'started_at' => $campaign->started_at,
                'completed_at' => $campaign->completed_at,
                'created_at' => $campaign->created_at,
            ])->values(),
        ]);
    }

    public function send(SendCampaignRequest $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->findOrFail($workspace);
        $this->ensureWorkspaceAccess($request, $workspaceModel->id);

        $campaign = Campaign::query()
            ->where('workspace_id', $workspaceModel->id)
            ->findOrFail($request->string('campaign_id')->value());

        if ($campaign->status !== 'queued') {
            return response()->json([
                'message' => 'Campaign is not in queued status.',
            ], 409);
        }

        SendCampaignJob::dispatch($campaign->id);

        return response()->json([
            'data' => [
                'campaign_id' => $campaign->id,
                'status' => 'queued',
            ],
        ], 202);
    }

    private function ensureWorkspaceAccess(Request $request, string $workspaceId): void
    {
        $user = $request->user();
        abort_unless($user, 401);

        $isMember = Membership::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403);
    }
}
