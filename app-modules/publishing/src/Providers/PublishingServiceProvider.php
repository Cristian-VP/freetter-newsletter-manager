<?php

namespace Domains\Publishing\Providers;

use Illuminate\Support\ServiceProvider;

class PublishingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Domains\Publishing\Console\Commands\PublishScheduledPostsCommand::class,
            ]);
        }
    }
}
