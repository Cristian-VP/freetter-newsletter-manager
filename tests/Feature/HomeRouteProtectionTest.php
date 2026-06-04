<?php

namespace Tests\Feature;

use Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_home(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_home(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
    }
}
