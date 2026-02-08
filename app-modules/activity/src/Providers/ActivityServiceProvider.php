<?php

namespace Domains\Activity\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class ActivityServiceProvider extends ServiceProvider
{
    /**
     * Register services del módulo Activity.
     */
    public function register(): void
    {
        // Aquí se registran bindings, singletons, etc
        // Por ahora, Activity no necesita nada aquí
    }

    /**
     * Bootstrap services del módulo Activity.
     */
    public function boot(): void
    {
        //  Cargar migraciones del módulo
        $this->loadMigrationsFrom(__DIR__ . '../../database/migrations');

        //  Lazy Loading Prevention (desarrollo)
        if ($this->app->environment('local')) {
            Model::preventLazyLoading(true);
        }

        //  Rate Limiting (opcional MVP, crítico V2)
        RateLimiter::for('activity-log', function ($request) {
            return Limit::perMinute(100)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
