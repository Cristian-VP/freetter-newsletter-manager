<?php

namespace Domains\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\Membership;
use Domains\Identity\Observers\UserObserver;
use Domains\Identity\Observers\WorkspaceObserver;
use Domains\Identity\Observers\MembershipObserver;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registrar observers para disparar eventos automáticamente
        User::observe(UserObserver::class);
        Workspace::observe(WorkspaceObserver::class);
        Membership::observe(MembershipObserver::class);
    }
}
