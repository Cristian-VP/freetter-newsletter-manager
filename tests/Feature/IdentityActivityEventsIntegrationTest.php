<?php

namespace Tests\Feature;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Identity\Models\Membership;
use Domains\Activity\Models\ActivityLog;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IdentityActivityEventsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear usuario registra en activity_logs
     */
    public function test_user_creation_logs_to_activity(): void
    {
        // Act
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Assert
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.registered',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);
    }

    /**
     * Test: Crear workspace registra en activity_logs
     */
    public function test_workspace_creation_logs_to_activity(): void
    {
        $workspace = Workspace::factory()->create([
            'name' => 'Test Newsletter',
            'slug' => 'test-newsletter',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'workspace.created',
            'entity_type' => 'workspace',
            'entity_id' => $workspace->id,
        ]);
    }

    /**
     * Test: Crear membership registra en activity_logs
     */
    public function test_membership_creation_logs_to_activity(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $membership = Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'membership.created',
            'entity_type' => 'membership',
            'entity_id' => $membership->id,
        ]);
    }

    /**
     * Test: Flujo completo genera todos los logs
     */
    public function test_complete_workflow_generates_all_logs(): void
    {
        // Act
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $membership = Membership::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Assert: 3 logs creados
        $this->assertCount(3, ActivityLog::all());
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.registered']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'workspace.created']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'membership.created']);
    }
}
