<?php

namespace Domains\Activity\Tests\Feature\Providers;

use Domains\Activity\Providers\ActivityServiceProvider;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

class ActivityServiceProviderTest extends TestCase
{
    public function test_activity_provider_is_loaded(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(ActivityServiceProvider::class));
    }

    public function test_activity_module_migration_path_is_registered(): void
    {
        $expectedPath = realpath(__DIR__.'/../../../database/migrations');
        $paths = array_map(
            static fn (string $path): string => realpath($path) ?: $path,
            app('migrator')->paths()
        );

        $this->assertContains($expectedPath, $paths);
    }
}

