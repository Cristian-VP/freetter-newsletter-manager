<?php

namespace Domains\Identity\Tests\Feature\Models;

use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear una membresía con datos válidos
     */
    public function test_can_create_membership_with_valid_data(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $membership = Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('identity_memberships', [
            'id' => $membership->id,
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'admin',
        ]);
    }

    /**
     * Test: Un usuario no puede tener dos membresías en el mismo workspace
     */
    public function test_user_cannot_have_duplicate_membership_in_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Test: Una membresía debe tener un rol válido
     */
    public function test_membership_must_have_valid_role(): void
    {
        $validRoles = ['owner', 'admin', 'editor', 'viewer', 'writer'];

        foreach ($validRoles as $role) {
            // Crear un nuevo user y workspace para cada rol
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create();

            $membership = Membership::factory()->create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
                'role' => $role,
            ]);

            $this->assertEquals($role, $membership->role);
        }
    }

    /**
     * Test: Membresía puede tener rol owner
     */
    public function test_membership_can_have_owner_role(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $membership = Membership::factory()
            ->owner()
            ->create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
            ]);

        $this->assertTrue($membership->isOwner());
    }

    /**
     * Test: Membresía puede tener rol admin
     */
    public function test_membership_can_have_admin_role(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $membership = Membership::factory()
            ->admin()
            ->create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
            ]);

        $this->assertEquals('admin', $membership->getRole());
    }

    /**
     * Test: Obtener rol de membresía
     */
    public function test_can_get_membership_role(): void
    {
        $membership = Membership::factory()->create(['role' => 'editor']);

        $this->assertEquals('editor', $membership->getRole());
    }

    /**
     * Test: Detectar si membresía es owner
     */
    public function test_can_detect_owner_role(): void
    {
        $ownerMembership = Membership::factory()->owner()->create();
        $adminMembership = Membership::factory()->admin()->create();

        $this->assertTrue($ownerMembership->isOwner());
        $this->assertFalse($adminMembership->isOwner());
    }

    /**
     * Test: Eliminar usuario elimina sus membresías (cascade)
     */
    public function test_deleting_user_deletes_memberships(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $this->assertDatabaseHas('identity_memberships', [
            'user_id' => $user->id,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('identity_memberships', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test: Eliminar workspace elimina membresías (cascade)
     */
    public function test_deleting_workspace_deletes_memberships(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $this->assertDatabaseHas('identity_memberships', [
            'workspace_id' => $workspace->id,
        ]);

        $workspace->delete();

        $this->assertDatabaseMissing('identity_memberships', [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Test: Membresía tiene relación con User
     */
    public function test_membership_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $membership = Membership::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $membership->user->id);
    }

    /**
     * Test: Membresía tiene relación con Workspace
     */
    public function test_membership_belongs_to_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $membership = Membership::factory()->create(['workspace_id' => $workspace->id]);

        $this->assertEquals($workspace->id, $membership->workspace->id);
    }
}
