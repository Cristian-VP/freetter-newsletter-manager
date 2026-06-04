<?php

namespace Domains\Identity\Tests\Feature\Http;

use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceAndInvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_workspace_and_owner_membership_via_http(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->postJson('/identity/workspaces', [
            'name' => 'Editorial Team',
            'slug' => 'editorial-team',
            'branding_config' => ['theme' => 'classic'],
            'donation_config' => ['currency' => 'EUR'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.slug', 'editorial-team');

        $workspaceId = $response->json('data.id');

        $this->assertDatabaseHas('identity_memberships', [
            'workspace_id' => $workspaceId,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function test_can_create_workspace_invitation_via_http(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $response = $this->actingAs($owner)->postJson('/identity/workspaces/'.$workspace->id.'/invitations', [
            'email' => 'writer@example.com',
            'role' => 'writer',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.role', 'writer');

        $this->assertDatabaseHas('identity_invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'writer@example.com',
            'role' => 'writer',
        ]);
    }

    public function test_guest_cannot_create_workspace_via_http(): void
    {
        $response = $this->postJson('/identity/workspaces', [
            'name' => 'Guest Workspace',
            'slug' => 'guest-workspace',
        ]);

        $response->assertUnauthorized();
    }

    public function test_workspace_slug_must_be_unique_via_http(): void
    {
        $owner = User::factory()->create();

        Workspace::factory()->create([
            'slug' => 'editorial-team',
        ]);

        $response = $this->actingAs($owner)->postJson('/identity/workspaces', [
            'name' => 'Editorial Team 2',
            'slug' => 'editorial-team',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['slug']);
    }

    public function test_workspace_requires_name_and_slug_via_http(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->postJson('/identity/workspaces', [
            'name' => '',
            'slug' => '',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_workspace_config_fields_must_be_arrays_via_http(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->postJson('/identity/workspaces', [
            'name' => 'Config Workspace',
            'slug' => 'config-workspace',
            'branding_config' => 'classic',
            'donation_config' => 'EUR',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['branding_config', 'donation_config']);
    }

    public function test_workspace_creation_rolls_back_if_membership_creation_fails(): void
    {
        $owner = User::factory()->create();

        $dispatcher = Membership::getEventDispatcher();
        Membership::flushEventListeners();

        Membership::creating(static function (): void {
            throw new \RuntimeException('Membership creation failed');
        });

        try {
            $response = $this->actingAs($owner)->postJson('/identity/workspaces', [
                'name' => 'Rollback Workspace',
                'slug' => 'rollback-workspace',
            ]);

            $response->assertStatus(500);

            $this->assertDatabaseMissing('identity_workspaces', [
                'slug' => 'rollback-workspace',
            ]);
        } finally {
            Membership::flushEventListeners();
            Membership::setEventDispatcher($dispatcher);
        }
    }
}
