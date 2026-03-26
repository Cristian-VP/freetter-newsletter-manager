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
}
