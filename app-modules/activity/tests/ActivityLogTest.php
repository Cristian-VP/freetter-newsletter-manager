<?php

namespace Domains\Activity\Tests\Feature;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear un log de auditoría
     */
    public function test_can_create_activity_log(): void
    {
        $user = User::factory()->create();

        $log = ActivityLog::record(
            action: 'post.published',
            entityType: 'post',
            entityId: 'some-uuid',
            user: $user,
            metadata: ['title' => 'Hello World']
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'action' => 'post.published',
            'entity_type' => 'post',
        ]);
    }

    /**
     * Test: Tabla es inmutable (no updated_at)
     */
    public function test_table_is_immutable(): void
    {
        $log = ActivityLog::factory()->create();

        // No puede actualizar
        $this->assertNull($log->updated_at);
    }

    /**
     * Test: Scope byUser funciona
     */
    public function test_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ActivityLog::factory()->create(['user_id' => $user1->id]);
        ActivityLog::factory()->create(['user_id' => $user2->id]);
        ActivityLog::factory()->systemAction()->create();

        $logs = ActivityLog::byUser($user1->id)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals($user1->id, $logs->first()->user_id);
    }

    /**
     * Test: Scope forEntity funciona
     */
    public function test_scope_for_entity(): void
    {
        $postId = 'post-123';

        ActivityLog::factory()
            ->forEntity('post', $postId)
            ->count(3)
            ->create();

        ActivityLog::factory()
            ->forEntity('workspace', 'workspace-456')
            ->count(2)
            ->create();

        $logs = ActivityLog::forEntity('post', $postId)->get();

        $this->assertCount(3, $logs);
        $this->assertTrue($logs->every(
            fn($log) => $log->entity_type === 'post' && $log->entity_id === $postId
        ));
    }

    /**
     * Test: Eager loading evita N+1
     */
    public function test_eager_loading_prevents_n_plus_one(): void
    {
        User::factory()->count(10)->create();
        ActivityLog::factory()->count(100)->create();

        Model::preventLazyLoading(true);

        try {
            $logs = ActivityLog::with('user')->get();
            foreach ($logs as $log) {
                $log->user?->name;
            }
            $this->assertTrue(true); // Si llegamos aquí, no hubo N+1
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    /**
     * Test: activitySummary funciona
     */
    public function test_activity_summary(): void
    {
        ActivityLog::factory()
            ->postPublished()
            ->count(10)
            ->create();

        ActivityLog::factory()
            ->count(5)
            ->create();

        $summary = ActivityLog::activitySummary(daysBack: 1);

        $this->assertEquals(15, $summary['total_logs']);
        $this->assertGreaterThan(0, $summary['unique_users']);
    }
}
