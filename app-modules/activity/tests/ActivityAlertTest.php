<?php

namespace Domains\Activity\Tests\Feature;

use Domains\Activity\Models\ActivityLog;
use Domains\Activity\Models\ActivityAlert;
use Domains\Identity\Models\Workspace;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityAlertTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear una alerta
     */
    public function test_can_create_activity_alert(): void
    {
        $workspace = Workspace::factory()->create();
        $log = ActivityLog::factory()->create();

        $alert = ActivityAlert::factory()->create([
            'workspace_id' => $workspace->id,
            'log_id' => $log->id,
            'alert_type' => 'hard_delete',
            'severity' => 'critical',
            'resolved_at' => null,
        ]);

        $this->assertDatabaseHas('activity_alerts', [
            'id' => $alert->id,
            'workspace_id' => $workspace->id,
            'log_id' => $log->id,
            'severity' => 'critical',
            'resolved_at' => null,
        ]);
    }

    /**
     * Test: Helper isPending() e isResolved()
     */
    public function test_pending_and_resolved_helpers(): void
    {
        $pending = ActivityAlert::factory()->create(['resolved_at' => null]);
        $resolved = ActivityAlert::factory()->create(['resolved_at' => now()]);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isResolved());

        $this->assertFalse($resolved->isPending());
        $this->assertTrue($resolved->isResolved());
    }

    /**
     * Test: Helper isCritical()
     */
    public function test_is_critical_helper(): void
    {
        $critical = ActivityAlert::factory()->create(['severity' => 'critical']);
        $warning = ActivityAlert::factory()->create(['severity' => 'warning']);
        $info = ActivityAlert::factory()->create(['severity' => 'info']);

        $this->assertTrue($critical->isCritical());
        $this->assertFalse($warning->isCritical());
        $this->assertFalse($info->isCritical());
    }

    /**
     * Test: Método resolve()
     */
    public function test_resolve_alert(): void
    {
        $alert = ActivityAlert::factory()->create(['resolved_at' => null]);

        $this->assertNull($alert->resolved_at);

        $alert->resolve();

        $this->assertNotNull($alert->refresh()->resolved_at);
        $this->assertTrue($alert->isResolved());
    }

    /**
     * Test: Scope unresolved()
     */
    public function test_scope_unresolved(): void
    {
        ActivityAlert::factory(4)->create(['resolved_at' => null]);
        ActivityAlert::factory(2)->create(['resolved_at' => now()]);

        $unresolved = ActivityAlert::unresolved()->get();

        $this->assertCount(4, $unresolved);
        $this->assertTrue($unresolved->every(fn($a) => $a->resolved_at === null));
    }

    /**
     * Test: Scope critical()
     */
    public function test_scope_critical(): void
    {
        ActivityAlert::factory(5)->create(['severity' => 'critical']);
        ActivityAlert::factory(3)->create(['severity' => 'warning']);
        ActivityAlert::factory(2)->create(['severity' => 'info']);

        $critical = ActivityAlert::critical()->get();

        $this->assertCount(5, $critical);
        $this->assertTrue($critical->every(fn($a) => $a->severity === 'critical'));
    }

    /**
     * Test: Scope forWorkspace()
     */
    public function test_scope_for_workspace(): void
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        ActivityAlert::factory(3)->create(['workspace_id' => $workspace1->id]);
        ActivityAlert::factory(2)->create(['workspace_id' => $workspace2->id]);

        $alerts = ActivityAlert::forWorkspace($workspace1->id)->get();

        $this->assertCount(3, $alerts);
        $this->assertTrue($alerts->every(fn($a) => $a->workspace_id === $workspace1->id));
    }

    /**
     * Test: Helper getSeverityLabel()
     */
    public function test_get_severity_label_helper(): void
    {
        $critical = ActivityAlert::factory()->create(['severity' => 'critical']);
        $warning = ActivityAlert::factory()->create(['severity' => 'warning']);
        $info = ActivityAlert::factory()->create(['severity' => 'info']);

        $this->assertEquals('Crítico', $critical->getSeverityLabel());
        $this->assertEquals('Advertencia', $warning->getSeverityLabel());
        $this->assertEquals('Información', $info->getSeverityLabel());
    }

    /**
     * Test: Helper getWorkspaceId()
     */
    public function test_get_workspace_id_helper(): void
    {
        $workspace = Workspace::factory()->create();
        $alert = ActivityAlert::factory()->create(['workspace_id' => $workspace->id]);

        $this->assertEquals($workspace->id, $alert->getWorkspaceId());
    }

    /**
     * Test: Cascada de eliminación (workspace)
     */
    public function test_deleting_workspace_deletes_alerts(): void
    {
        $workspace = Workspace::factory()->create();
        ActivityAlert::factory(3)->create(['workspace_id' => $workspace->id]);

        $this->assertDatabaseCount('activity_alerts', 3);

        $workspace->delete();

        $this->assertDatabaseCount('activity_alerts', 0);
    }

    /**
     * Test: Cascada de eliminación (activity log)
     */
    public function test_deleting_log_deletes_alert(): void
    {
        $log = ActivityLog::factory()->create();
        ActivityAlert::factory()->create(['log_id' => $log->id]);

        $this->assertDatabaseCount('activity_alerts', 1);

        $log->delete();

        $this->assertDatabaseCount('activity_alerts', 0);
    }
}
