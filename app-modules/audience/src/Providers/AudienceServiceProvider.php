<?php

namespace Domains\Audience\Providers;

use Domains\Audience\Console\Commands\CleanupExpiredImportJobsCommand;
use Illuminate\Support\ServiceProvider;

class AudienceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupExpiredImportJobsCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
    }
}
