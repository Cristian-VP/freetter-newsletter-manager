<?php

namespace Domains\Publishing\Tests\Feature\Providers;

use Domains\Publishing\Providers\PublishingServiceProvider;
use Tests\TestCase;

class PublishingServiceProviderTest extends TestCase
{
    public function test_publishing_provider_is_loaded(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(PublishingServiceProvider::class));
    }

    public function test_publishing_module_migration_path_is_registered(): void
    {
        $expectedPath = realpath(__DIR__.'/../../../database/migrations');
        $paths = array_map(
            static fn (string $path): string => realpath($path) ?: $path,
            app('migrator')->paths()
        );

        $this->assertContains($expectedPath, $paths);
    }
}
