<?php

namespace Domains\Activity\Tests\Feature;

use Domains\Activity\Models\ActivityLog;
use Domains\Activity\Models\ActivityStream;
use Domains\Identity\Models\Workspace;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityStreamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear un ActivityStream
     */
    public function test_can_create_activity_stream(): void
    {
        $workspace = Workspace::factory()->create();
        $log = ActivityLog::factory()->create();

        $stream = ActivityStream::factory()->create([
            'workspace_id' => $workspace->id,
            'log_id' => $log->id,
            'event_type' => 'post.published',
            'visibility' => 'public',
        ]);

        $this->assertDatabaseHas('activity_streams', [
            'id' => $stream->id,
            'workspace_id' => $workspace->id,
            'log_id' => $log->id,
            'visibility' => 'public',
        ]);
    }

    /**
     * Test: Helper isPublic()
     */
    public function test_is_public_helper(): void
    {
        $publicStream = ActivityStream::factory()->create(['visibility' => 'public']);
        $adminStream = ActivityStream::factory()->create(['visibility' => 'admin']);

        $this->assertTrue($publicStream->isPublic());
        $this->assertFalse($publicStream->isAdminOnly());

        $this->assertFalse($adminStream->isPublic());
        $this->assertTrue($adminStream->isAdminOnly());
    }

    /**
     * Test: Helper getWorkspaceId()
     */
    public function test_get_workspace_id_helper(): void
    {
        $workspace = Workspace::factory()->create();
        $stream = ActivityStream::factory()->create(['workspace_id' => $workspace->id]);

        $this->assertEquals($workspace->id, $stream->getWorkspaceId());
    }

    /**
     * Test: Scope forWorkspace()
     */
    public function test_scope_for_workspace(): void
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        ActivityStream::factory(3)->create(['workspace_id' => $workspace1->id]);
        ActivityStream::factory(2)->create(['workspace_id' => $workspace2->id]);

        $streams = ActivityStream::forWorkspace($workspace1->id)->get();

        $this->assertCount(3, $streams);
        $this->assertTrue($streams->every(fn($s) => $s->workspace_id === $workspace1->id));
    }

    /**
     * Test: Scope public()
     */
    public function test_scope_public(): void
    {
        ActivityStream::factory(4)->create(['visibility' => 'public']);
        ActivityStream::factory(2)->create(['visibility' => 'admin']);

        $publicStreams = ActivityStream::public()->get();

        $this->assertCount(4, $publicStreams);
        $this->assertTrue($publicStreams->every(fn($s) => $s->visibility === 'public'));
    }

    /**
     * Test: Cascada de eliminación (workspace)
     */
    public function test_deleting_workspace_deletes_streams(): void
    {
        $workspace = Workspace::factory()->create();
        ActivityStream::factory(3)->create(['workspace_id' => $workspace->id]);

        $this->assertDatabaseCount('activity_streams', 3);

        $workspace->delete();

        $this->assertDatabaseCount('activity_streams', 0);
    }

    /**
     * Test: Cascada de eliminación (activity log)
     */
    public function test_deleting_log_deletes_stream(): void
    {
        $log = ActivityLog::factory()->create();
        ActivityStream::factory()->create(['log_id' => $log->id]);

        $this->assertDatabaseCount('activity_streams', 1);

        $log->delete();

        $this->assertDatabaseCount('activity_streams', 0);
    }
}
