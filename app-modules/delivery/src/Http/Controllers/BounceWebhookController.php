<?php

namespace Domains\Delivery\Http\Controllers;

use Domains\Delivery\Events\BounceCaptured;
use Domains\Delivery\Events\DeliveryBounceReceived;
use Domains\Delivery\Http\Requests\BounceWebhookRequest;
use Domains\Delivery\Models\Bounce;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BounceWebhookController extends Controller
{
    public function store(BounceWebhookRequest $request): JsonResponse
    {
        $workspaceId = $request->string('workspace_id')->value();
        $campaignId = $request->filled('campaign_id') ? $request->string('campaign_id')->value() : null;
        $email = strtolower($request->string('email')->value());
        $bounceType = strtolower($request->string('bounce_type')->value());
        $code = $request->filled('code') ? $request->string('code')->value() : null;
        $reason = $request->filled('reason') ? $request->string('reason')->value() : null;

        $bounce = Bounce::query()->firstOrCreate(
            [
                'workspace_id' => $workspaceId,
                'campaign_id' => $campaignId,
                'email' => $email,
                'bounce_type' => $bounceType,
                'code' => $code,
            ],
            [
                'reason' => $reason,
            ]
        );

        if ($bounce->wasRecentlyCreated) {
            event(new BounceCaptured($bounce));
        }

        event(new DeliveryBounceReceived(
            workspaceId: $workspaceId,
            email: $email,
            bounceType: $bounceType,
            messageId: $code,
            campaignId: $campaignId,
            reason: $reason,
            context: [
                'source' => 'delivery.webhook',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $bounce->id,
                'workspace_id' => $bounce->workspace_id,
                'campaign_id' => $bounce->campaign_id,
                'email' => $bounce->email,
                'bounce_type' => $bounce->bounce_type,
            ],
        ], $bounce->wasRecentlyCreated ? 201 : 200);
    }
}
