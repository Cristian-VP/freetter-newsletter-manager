<?php

namespace Domains\Activity\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Events\UserEmailVerified;

class LogUserEmailVerified
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
     public function handle(UserEmailVerified $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'action' => 'user.email_verified',
            'entity_type' => 'user',
            'entity_id' => $event->user->id,
            'metadata' => [
                'email' => $event->user->email,
                'verified_at' => $event->verifiedAt->format('c'),
            ],
            'ip_address' => $event->context['ip'] ?? request()->ip(),
            'user_agent' => $event->context['user_agent'] ?? request()->userAgent(),
        ]);
    }
}
