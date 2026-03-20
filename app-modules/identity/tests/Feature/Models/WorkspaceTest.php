<?php

namespace Domains\Identity\Tests\Feature\Models;

use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Membership;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear un workspace con datos válidos
     */
    public function test_can_create_workspace_with_valid_data(): void
    {
        $workspace = Workspace::factory()->create([
            'name' => 'Mi Workspace',
            'slug' => 'mi-workspace',
        ]);

        $this->assertDatabaseHas('identity_workspaces', [
            'id' => $workspace->id,
            'name' => 'Mi Workspace',
            'slug' => 'mi-workspace',
        ]);
    }

    /**
     * Test: Slug del workspace debe ser único
     */
    public function test_workspace_slug_must_be_unique(): void
    {
        Workspace::factory()->create([
            'slug' => 'unique-slug',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Workspace::factory()->create([
            'slug' => 'unique-slug',
        ]);
    }

    /**
     * Test: Un workspace puede tener múltiples miembros
     */
    public function test_workspace_can_have_multiple_members(): void
    {
        $workspace = Workspace::factory()->create();

        User::factory()
            ->count(5)
            ->create()
            ->each(function ($user) use ($workspace) {
                Membership::factory()->create([
                    'user_id' => $user->id,
                    'workspace_id' => $workspace->id,
                ]);
            });

        $this->assertCount(5, $workspace->users);
    }

    /**
     * Test: Un workspace debe tener un owner
     */
    public function test_workspace_must_have_owner(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->assertEquals($owner->id, $workspace->owner()->id);
    }

    /**
     * Test: Lanzar excepción si workspace no tiene owner
     */
    public function test_throw_exception_if_workspace_has_no_owner(): void
    {
        $workspace = Workspace::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Workspace {$workspace->id} must have an owner");

        $workspace->owner();
    }

    /**
     * Test: Workspace puede tener configuración de branding
     */
    public function test_workspace_can_have_branding_config(): void
    {
        $workspace = Workspace::factory()
            ->withCustomBranding(
                logoUrl: 'https://example.com/logo.png',
                primaryColor: '#FF0000',
                secondaryColor: '#00FF00'
            )
            ->create();

        $this->assertEquals('https://example.com/logo.png', $workspace->branding_config['logo_url']);
        $this->assertEquals('#FF0000', $workspace->branding_config['primary_color']);
    }

    /**
     * Test: Workspace puede tener configuración de donaciones
     */
    public function test_workspace_can_have_donation_config(): void
    {
        $workspace = Workspace::factory()
            ->withCustomDonationConfig(
                defaultAmount: 25.50,
                currency: 'EUR'
            )
            ->create();

        $this->assertEquals(25.50, $workspace->donation_config['default_amount']);
        $this->assertEquals('EUR', $workspace->donation_config['currency']);
    }

    /**
     * Test: Workspace puede ser sin donaciones (con config vacío)
     */
    public function test_workspace_can_be_without_donation_config(): void
    {
        $workspace = Workspace::factory()->create([
            'donation_config' => [],  // Config vacío, no null
        ]);

        $this->assertIsArray($workspace->donation_config);
        $this->assertEmpty($workspace->donation_config);
    }

    /**
     * Test: Eager loading previene N+1 queries
     */
    public function test_eager_loading_prevents_n_plus_one(): void
    {
        Workspace::factory()
            ->count(10)
            ->create()
            ->each(function ($workspace) {
                User::factory()
                    ->count(5)
                    ->create()
                    ->each(function ($user) use ($workspace) {
                        Membership::factory()->create([
                            'user_id' => $user->id,
                            'workspace_id' => $workspace->id,
                        ]);
                    });
            });

        Model::preventLazyLoading(true);

        try {
            $workspaces = Workspace::with('users')->get();
            foreach ($workspaces as $workspace) {
                foreach ($workspace->users as $user) {
                    $user->name;
                }
            }
            $this->assertTrue(true); // Si llegamos aquí, no hubo N+1
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    /**
     * Test: Workspace puede tener múltiples invitaciones
     */
    public function test_workspace_can_have_multiple_invitations(): void
    {
        $workspace = Workspace::factory()->create();

        \Domains\Identity\Models\Invitation::factory()
            ->count(5)
            ->create([
                'workspace_id' => $workspace->id,
            ]);

        $this->assertCount(5, $workspace->invitations);
    }

    /**
     * Test: Eliminar workspace elimina membresías (cascade)
     */
    public function test_deleting_workspace_deletes_memberships(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('identity_memberships', [
            'workspace_id' => $workspace->id,
        ]);

        $workspace->delete();

        $this->assertDatabaseMissing('identity_memberships', [
            'workspace_id' => $workspace->id,
        ]);
    }
}
