<?php

namespace Domains\Identity\Tests\Feature\Models;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\Membership;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear un usuario con datos válidos
     */
    public function test_can_create_user_with_valid_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
        ]);

        $this->assertDatabaseHas('identity_users', [
            'id' => $user->id,
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
        ]);
    }

    /**
     * Test: Email debe ser único
     */
    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);
    }

    /**
     * Test: Un usuario puede tener múltiples workspaces
     */
    public function test_user_can_have_multiple_workspaces(): void
    {
        $user = User::factory()->create();
        $workspaces = Workspace::factory()->count(3)->create();

        foreach ($workspaces as $workspace) {
            Membership::factory()->create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
                'role' => 'owner',
            ]);
        }

        $this->assertCount(3, $user->workspaces);
    }

    /**
     * Test: Un usuario puede tener múltiples membresías
     */
    public function test_user_can_have_multiple_memberships(): void
    {
        $user = User::factory()->create();

        Membership::factory()
            ->count(5)
            ->create([
                'user_id' => $user->id,
            ]);

        $this->assertCount(5, $user->memberships);
    }

    /**
     * Test: Eager loading previene N+1 queries
     */
    public function test_eager_loading_prevents_n_plus_one(): void
    {
        User::factory()->count(10)->create();

        $users = User::factory()->count(10)->create();
        foreach ($users as $user) {
            Workspace::factory()->count(3)->create()->each(function ($workspace) use ($user) {
                Membership::factory()->create([
                    'user_id' => $user->id,
                    'workspace_id' => $workspace->id,
                ]);
            });
        }

        Model::preventLazyLoading(true);

        try {
            $users = User::with('workspaces')->get();
            foreach ($users as $user) {
                foreach ($user->workspaces as $workspace) {
                    $workspace->name;
                }
            }
            $this->assertTrue(true); // Si llegamos aquí, no hubo N+1
        } finally {
                Model::preventLazyLoading(false);
        }
    }

    /**
     * Test: Usuario puede ser verificado por email
     */
    public function test_user_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);

        $user->update(['email_verified_at' => now()]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * Test: Usuario puede tener avatar
     */
    public function test_user_can_have_avatar(): void
    {
        $user = User::factory()
            ->withAvatar('avatars/custom.jpg')
            ->create();

        $this->assertEquals('avatars/custom.jpg', $user->avatar_path);
    }

    /**
     * Test: Usuario sin avatar tiene avatar_path nullable
     */
    public function test_user_without_avatar_has_null_path(): void
    {
        $user = User::factory()
            ->withoutAvatar()
            ->create();

        $this->assertNull($user->avatar_path);
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
}
