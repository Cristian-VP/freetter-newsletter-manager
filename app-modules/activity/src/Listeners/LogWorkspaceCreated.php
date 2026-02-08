<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Events\WorkspaceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogWorkspaceCreated
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
    public function handle(WorkspaceCreated $event): void
    {
        ActivityLog::create([
            'user_id' => $event->ownerId,
            'action' => 'workspace.created',
            'entity_type' => 'workspace',
            'entity_id' => $event->workspace->id,
            'metadata' => [
                'name' => $event->workspace->name,
                'slug' => $event->workspace->slug,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
