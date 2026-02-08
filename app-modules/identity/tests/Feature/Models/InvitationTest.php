<?php

namespace Domains\Identity\Tests\Feature\Models;

use Domains\Identity\Models\Invitation;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Membership;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear una invitación con datos válidos
     */
    public function test_can_create_invitation_with_valid_data(): void
    {
        $workspace = Workspace::factory()->create();
        $owner = User::factory()->create();

        Membership::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'new@example.com',
            'role' => 'editor',
        ]);

        $this->assertDatabaseHas('identity_invitations', [
            'id' => $invitation->id,
            'workspace_id' => $workspace->id,
            'email' => 'new@example.com',
            'role' => 'editor',
        ]);
    }

    /**
     * Test: Token de invitación debe ser único
     */
    public function test_invitation_token_must_be_unique(): void
    {
        $token = Invitation::generateToken();

        Invitation::factory()->create([
            'token' => $token,
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Invitation::factory()->create([
            'token' => $token,
        ]);
    }

    /**
     * Test: Generar token de invitación
     */
    public function test_can_generate_invitation_token(): void
    {
        $token1 = Invitation::generateToken();
        $token2 = Invitation::generateToken();

        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1));
    }

    /**
     * Test: Invitación pendiente puede ser aceptada
     */
    public function test_pending_invitation_can_be_accepted(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'accepted_by_user_id' => null,
            'accepted_at' => null,
        ]);

        $invitation->accept($user);

        $this->assertNotNull($invitation->accepted_by_user_id);
        $this->assertNotNull($invitation->accepted_at);
        $this->assertEquals($user->id, $invitation->accepted_by_user_id);
    }

    /**
     * Test: Identificar invitaciones pendientes
     */
    public function test_can_identify_pending_invitations(): void
    {
        $workspace = Workspace::factory()->create();

        $pending = Invitation::factory()
            ->count(3)
            ->create([
                'workspace_id' => $workspace->id,
                'accepted_by_user_id' => null,
                'expires_at' => now()->addDays(7),
            ]);

        $expired = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'accepted_by_user_id' => null,
            'expires_at' => now()->subDay(),
        ]);

        $result = Invitation::pending()->get();

        $this->assertCount(3, $result);
        $this->assertFalse($result->contains($expired));
    }

    /**
     * Test: Identificar invitaciones expiradas
     */
    public function test_can_identify_expired_invitations(): void
    {
        Invitation::factory()
            ->count(2)
            ->create([
                'accepted_by_user_id' => null,
                'expires_at' => now()->subDay(),
            ]);

        $result = Invitation::expired()->get();

        $this->assertCount(2, $result);
    }

    /**
     * Test: Detectar si invitación está expirada
     */
    public function test_can_check_if_invitation_is_expired(): void
    {
        $expiredInvitation = Invitation::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $validInvitation = Invitation::factory()->create([
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertTrue($expiredInvitation->isExpired());
        $this->assertFalse($validInvitation->isExpired());
    }

    /**
     * Test: Detectar si invitación fue aceptada
     */
    public function test_can_check_if_invitation_is_accepted(): void
    {
        $user = User::factory()->create();

        $acceptedInvitation = Invitation::factory()->create([
            'accepted_by_user_id' => $user->id,
        ]);

        $pendingInvitation = Invitation::factory()->create([
            'accepted_by_user_id' => null,
        ]);

        $this->assertTrue($acceptedInvitation->isAccepted());
        $this->assertFalse($pendingInvitation->isAccepted());
    }

    /**
     * Test: Invitación puede tener rol admin
     */
    public function test_invitation_can_have_admin_role(): void
    {
        $invitation = Invitation::factory()->admin()->create();

        $this->assertEquals('admin', $invitation->role);
    }

    /**
     * Test: Invitación puede tener rol editor
     */
    public function test_invitation_can_have_editor_role(): void
    {
        $invitation = Invitation::factory()->editor()->create();

        $this->assertEquals('editor', $invitation->role);
    }

    /**
     * Test: Invitación puede tener rol viewer
     */
    public function test_invitation_can_have_viewer_role(): void
    {
        $invitation = Invitation::factory()->viewer()->create();

        $this->assertEquals('viewer', $invitation->role);
    }

    /**
     * Test: Invitación para email específico
     */
    public function test_invitation_can_be_for_specific_email(): void
    {
        $invitation = Invitation::factory()
            ->forEmail('specific@example.com')
            ->create();

        $this->assertEquals('specific@example.com', $invitation->email);
    }

    /**
     * Test: Invitación para workspace específico
     */
    public function test_invitation_can_be_for_specific_workspace(): void
    {
        $workspace = Workspace::factory()->create();

        $invitation = Invitation::factory()
            ->forWorkspace($workspace)
            ->create();

        $this->assertEquals($workspace->id, $invitation->workspace_id);
    }

    /**
     * Test: Invitación que expira en X días
     */
    public function test_invitation_expires_in_specified_days(): void
    {
        $now = now();
        $invitation = Invitation::factory()
            ->expiresIn(14)
            ->create();

        $expectedDate = $now->addDays(14);

        $this->assertTrue(
            $invitation->expires_at->isBetween(
                $expectedDate->copy()->subMinute(),
                $expectedDate->copy()->addMinute()
            )
        );
    }

    /**
     * Test: Invitación con token específico
     */
    public function test_invitation_with_specific_token(): void
    {
        $token = 'custom-token-' . Invitation::generateToken();

        $invitation = Invitation::factory()
            ->withToken($token)
            ->create();

        $this->assertEquals($token, $invitation->token);
    }

    /**
     * Test: Invitación tiene relación con Workspace
     */
    public function test_invitation_belongs_to_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $invitation = Invitation::factory()->create(['workspace_id' => $workspace->id]);

        $this->assertEquals($workspace->id, $invitation->workspace->id);
    }

    /**
     * Test: Invitación tiene relación con Usuario (cuando es aceptada)
     */
    public function test_invitation_belongs_to_user_when_accepted(): void
    {
        $user = User::factory()->create();
        $invitation = Invitation::factory()->create(['accepted_by_user_id' => $user->id]);

        $this->assertEquals($user->id, $invitation->acceptedByUser->id);
    }

    /**
     * Test: Eliminar usuario elimina invitaciones aceptadas (set null)
     */
    public function test_deleting_user_sets_null_on_accepted_invitations(): void
    {
        $user = User::factory()->create();
        $invitation = Invitation::factory()->create(['accepted_by_user_id' => $user->id]);

        $user->delete();

        $this->assertNull($invitation->fresh()->accepted_by_user_id);
    }

    /**
     * Test: Eliminar workspace elimina invitaciones (cascade)
     */
    public function test_deleting_workspace_deletes_invitations(): void
    {
        $workspace = Workspace::factory()->create();

        Invitation::factory()
            ->count(3)
            ->create(['workspace_id' => $workspace->id]);

        $this->assertCount(3, $workspace->invitations);

        $workspace->delete();

        $this->assertDatabaseMissing('identity_invitations', [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Test: Eager loading previene N+1 queries
     */
    public function test_eager_loading_prevents_n_plus_one(): void
    {
        Invitation::factory()
            ->count(20)
            ->create();

        Model::preventLazyLoading(true);

        try {
             $invitations = Invitation::with('workspace', 'acceptedByUser')->get();
             foreach ($invitations as $invitation) {
                 $invitation->workspace->name;
                 $invitation->acceptedByUser?->name;
             }
             $this->assertTrue(true); // Si llegamos aquí, no hubo N+1
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
