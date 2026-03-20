<?php

namespace Domains\Identity\Observers;

use Domains\Identity\Models\User;
use Domains\Identity\Events\UserRegistered;
use Domains\Identity\Events\UserEmailVerified;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        event(new UserRegistered(
            user: $user,
            context: [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_via' => 'observer'
            ]
        ));
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $emailWasVerified = $user->wasChanged('email_verified_at')
                         && $user->email_verified_at !== null;

        if ($emailWasVerified) {
            event(new UserEmailVerified(
                user: $user,
                verifiedAt: $user->email_verified_at
            ));
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
