<?php

namespace Domains\Identity\Tests\Feature\Providers;

use Domains\Identity\Providers\IdentityServiceProvider;
use Tests\TestCase;

class IdentityServiceProviderTest extends TestCase
{
    public function test_identity_provider_is_loaded(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(IdentityServiceProvider::class));
    }

    public function test_identity_module_migration_path_is_registered(): void
    {
        $expectedPath = realpath(__DIR__.'/../../../database/migrations');
        $paths = array_map(
            static fn (string $path): string => realpath($path) ?: $path,
            app('migrator')->paths()
        );

        $this->assertContains($expectedPath, $paths);
    }
}
