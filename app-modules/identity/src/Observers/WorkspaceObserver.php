<?php

namespace Domains\Identity\Observers;

use Domains\Identity\Models\Workspace;
use Domains\Identity\Events\WorkspaceCreated;

class WorkspaceObserver
{
    /**
     * Handle the Workspace "created" event.
     */
    public function created(Workspace $workspace): void
    {
        // Disparar evento WorkspaceCreated
        event(new WorkspaceCreated(
            workspace: $workspace,
            ownerId: auth()->id()
        ));
    }

    /**
     * Handle the Workspace "updated" event.
     */
    public function updated(Workspace $workspace): void
    {
        //
    }

    /**
     * Handle the Workspace "deleted" event.
     */
    public function deleted(Workspace $workspace): void
    {
        //
    }

    /**
     * Handle the Workspace "restored" event.
     */
    public function restored(Workspace $workspace): void
    {
        //
    }

    /**
     * Handle the Workspace "force deleted" event.
     */
    public function forceDeleted(Workspace $workspace): void
    {
        //
    }
}
