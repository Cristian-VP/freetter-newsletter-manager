<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Events\MembershipCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogMembershipCreated
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
    public function handle(MembershipCreated $event): void
    {
        ActivityLog::create([
            'user_id' => $event->membership->user_id,
            'action' => 'membership.created',
            'entity_type' => 'membership',
            'entity_id' => $event->membership->id,
            'metadata' => [
                'workspace_id' => $event->membership->workspace_id,
                'workspace_name' => $event->membership->workspace->name ?? null,
                'role' => $event->membership->role,
                'joined_at' => $event->membership->joined_at->format('c'),
            ],
            'ip_address' => $event->context['ip'] ?? request()->ip(),
            'user_agent' => $event->context['user_agent'] ?? request()->userAgent(),
        ]);
    }
}
