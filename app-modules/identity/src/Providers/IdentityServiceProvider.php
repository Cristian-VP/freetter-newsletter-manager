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
    /**
     * Register services del módulo Identity.
     */
    public function register(): void
    {
        // Aquí se registran bindings, singletons, etc
        // Por ahora, Identity no necesita nada aquí
    }
            

    public function boot(): void
    {
        // Registrar observers para disparar eventos automáticamente
        User::observe(UserObserver::class);
        Workspace::observe(WorkspaceObserver::class);
        Membership::observe(MembershipObserver::class);

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
