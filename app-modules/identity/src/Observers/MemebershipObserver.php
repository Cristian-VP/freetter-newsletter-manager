<?php

namespace Domains\Identity\Observers;

use Domains\Identity\Models\Membership;

class MembershipObserver
{
    /**
     * Handle the Membership "created" event.
     */
    public function created(Membership $membership): void
    {
        //
    }

    /**
     * Handle the Membership "updated" event.
     */
    public function updated(Membership $membership): void
    {
        //
    }

    /**
     * Handle the Membership "deleted" event.
     */
    public function deleted(Membership $membership): void
    {
        //
    }

    /**
     * Handle the Membership "restored" event.
     */
    public function restored(Membership $membership): void
    {
        //
    }

    /**
     * Handle the Membership "force deleted" event.
     */
    public function forceDeleted(Membership $membership): void
    {
        //
    }
}
