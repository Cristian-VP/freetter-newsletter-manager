<?php

namespace Tests\Feature;

use Domains\Activity\Providers\ActivityServiceProvider;
use Domains\Audience\Providers\AudienceServiceProvider;
use Domains\Community\Providers\CommunityServiceProvider;
use Domains\Delivery\Providers\DeliveryServiceProvider;
use Domains\Identity\Providers\IdentityServiceProvider;
use Domains\Publishing\Providers\PublishingServiceProvider;
use Tests\TestCase;

class ModuleReadinessSmokeTest extends TestCase
{
    public function test_all_module_service_providers_are_loaded(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(IdentityServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(PublishingServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(AudienceServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(CommunityServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(DeliveryServiceProvider::class));
        $this->assertTrue($this->app->providerIsLoaded(ActivityServiceProvider::class));
    }

    public function test_critical_mvp_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertTrue($routes->hasNamedRoute('identity.workspaces.store'));
        $this->assertTrue($routes->hasNamedRoute('identity.workspaces.invitations.store'));
        $this->assertTrue($routes->hasNamedRoute('publishing.workspaces.posts.store'));
        $this->assertTrue($routes->hasNamedRoute('publishing.posts.publish'));
        $this->assertTrue($routes->hasNamedRoute('audience.workspaces.subscribe'));
        $this->assertTrue($routes->hasNamedRoute('audience.workspaces.imports.store'));
        $this->assertTrue($routes->hasNamedRoute('audience.unsubscribe'));
        $this->assertTrue($routes->hasNamedRoute('community.comments.store'));
        $this->assertTrue($routes->hasNamedRoute('community.comments.moderate'));
        $this->assertTrue($routes->hasNamedRoute('delivery.campaigns.index'));
        $this->assertTrue($routes->hasNamedRoute('delivery.campaigns.send'));
        $this->assertTrue($routes->hasNamedRoute('delivery.webhooks.bounces.store'));
        $this->assertTrue($routes->hasNamedRoute('activity.logs.index'));
    }
}
