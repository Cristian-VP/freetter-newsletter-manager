<?php

namespace Domains\Audience\Tests\Feature\Providers;

use Domains\Audience\Providers\AudienceServiceProvider;
use Tests\TestCase;

class AudienceServiceProviderTest extends TestCase
{
    public function test_audience_provider_is_loaded(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(AudienceServiceProvider::class));
    }

    public function test_audience_module_migration_path_is_registered(): void
    {
        $expectedPath = realpath(__DIR__.'/../../../database/migrations');
        $paths = array_map(
            static fn (string $path): string => realpath($path) ?: $path,
            app('migrator')->paths()
        );

        $this->assertContains($expectedPath, $paths);
    }

    public function test_audience_mvp_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertTrue($routes->hasNamedRoute('audience.workspaces.subscribe'));
        $this->assertTrue($routes->hasNamedRoute('audience.unsubscribe'));
        $this->assertTrue($routes->hasNamedRoute('audience.workspaces.imports.store'));
    }
}
