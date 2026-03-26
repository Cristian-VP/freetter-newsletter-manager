<?php

namespace Domains\Identity\Tests\Feature\Http;

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

        $response = $this->postJson('/identity/workspaces', [
            'owner_user_id' => $owner->id,
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
        $workspace = Workspace::factory()->create();

        $response = $this->postJson('/identity/workspaces/'.$workspace->id.'/invitations', [
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
}
