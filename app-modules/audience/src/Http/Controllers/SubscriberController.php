<?php

namespace Domains\Audience\Http\Controllers;

use Domains\Audience\Events\SubscriberCreated;
use Domains\Audience\Events\SubscriberUnsubscribed;
use Domains\Audience\Http\Requests\SubscribeRequest;
use Domains\Audience\Models\Subscriber;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class SubscriberController extends Controller
{
    public function subscribe(SubscribeRequest $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->findOrFail($workspace);

        try {
            $subscriber = Subscriber::query()->create([
                'workspace_id' => $workspaceModel->id,
                'email' => strtolower($request->string('email')->value()),
                'name' => $request->filled('name') ? $request->string('name')->value() : null,
                'status' => 'active',
                'consent_given_at' => now(),
                'consent_ip' => (string) $request->ip(),
                'unsubscribe_token' => (string) Str::uuid(),
            ]);
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (in_array($sqlState, ['23505', '23000'], true)) {
                return response()->json([
                    'message' => 'Subscriber already exists for this workspace.',
                ], 409);
            }

            throw $exception;
        }

        event(new SubscriberCreated(
            subscriber: $subscriber,
            context: [
                'source' => 'public_form',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $subscriber->id,
                'workspace_id' => $subscriber->workspace_id,
                'status' => $subscriber->status,
            ],
        ], 201);
    }

    public function unsubscribe(string $token): JsonResponse
    {
        $subscriber = Subscriber::query()->where('unsubscribe_token', $token)->firstOrFail();

        if ($subscriber->markUnsubscribed()) {
            event(new SubscriberUnsubscribed(
                subscriber: $subscriber->fresh(),
                context: [
                    'source' => 'unsubscribe_link',
                ],
            ));
        }

        return response()->json([
            'data' => [
                'id' => $subscriber->id,
                'status' => $subscriber->fresh()->status,
            ],
        ]);
    }
}
