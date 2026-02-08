<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Register the listener in EventServiceProvider,
 * when the event UserRegistered is fired.
 */

class LogUserRegistered
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
    public function handle(UserRegistered $event): void
    {
        ActivityLog::create([
            'user_id' => null,
            'action' => 'user.registered',
            'entity_type' => 'user',
            'entity_id' => $event->user->id,
            'metadata' => [
                'name' => $event->user->name,
                'email' => $event->user->email,
                'context' => $event->context,
            ],
            'ip_address' => $event->context['ip'] ?? request()->ip(),
            'user_agent' => $event->context['user_agent'] ?? request()->userAgent(),
        ]);
    }
}
