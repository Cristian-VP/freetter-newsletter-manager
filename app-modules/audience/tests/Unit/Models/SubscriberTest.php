<?php

namespace Domains\Audience\Tests\Unit\Models;

use Domains\Audience\Models\Subscriber;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_scopes_filter_by_workspace_and_status(): void
    {
        $workspaceA = Workspace::factory()->create();
        $workspaceB = Workspace::factory()->create();

        Subscriber::factory()->active()->count(2)->create(['workspace_id' => $workspaceA->id]);
        Subscriber::factory()->unsubscribed()->create(['workspace_id' => $workspaceA->id]);
        Subscriber::factory()->active()->create(['workspace_id' => $workspaceB->id]);

        $activeInWorkspaceA = Subscriber::query()
            ->forWorkspace($workspaceA->id)
            ->withStatus('active')
            ->count();

        $this->assertSame(2, $activeInWorkspaceA);
    }

    public function test_mark_helpers_change_state_once(): void
    {
        $subscriber = Subscriber::factory()->active()->create();

        $this->assertTrue($subscriber->markUnsubscribed());
        $this->assertFalse($subscriber->fresh()->markUnsubscribed());

        $this->assertTrue($subscriber->fresh()->markBounced());
        $this->assertFalse($subscriber->fresh()->markBounced());
    }
}
