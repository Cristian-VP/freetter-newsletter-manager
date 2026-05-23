<?php

namespace Domains\Audience\Listeners;

use Domains\Audience\Models\Subscriber;
use Domains\Community\Events\UserSubscribedToWorkspace;
use Domains\Identity\Models\User;
use Illuminate\Support\Str;

class RegisterNewsletterSubscriber
{
    public function handle(UserSubscribedToWorkspace $event): void
    {
        $user = User::query()->find($event->userId);

        if (! $user || ! $user->email) {
            return;
        }

        Subscriber::updateOrCreate(
            [
                'workspace_id' => $event->workspaceId,
                'email' => strtolower($user->email),
            ],
            [
                'name' => $user->name,
                'status' => 'active',
                'consent_given_at' => now(),
                'consent_ip' => 'community_follow_action',
                'unsubscribe_token' => (string) Str::uuid(),
            ]
        );
    }
}
