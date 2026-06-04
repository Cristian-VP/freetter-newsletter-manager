<?php

namespace Domains\Community\Tests\Feature\Providers;

use Domains\Community\Providers\CommunityServiceProvider;
use Tests\TestCase;

class CommunityServiceProviderTest extends TestCase
{
    public function test_community_provider_is_loaded(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(CommunityServiceProvider::class));
    }
}
