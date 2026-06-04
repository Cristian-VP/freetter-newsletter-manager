<?php

namespace Domains\Delivery\Tests\Feature\Providers;

use Domains\Delivery\Providers\DeliveryServiceProvider;
use Tests\TestCase;

class DeliveryServiceProviderTest extends TestCase
{
    public function test_delivery_provider_is_loaded(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(DeliveryServiceProvider::class));
    }
}
