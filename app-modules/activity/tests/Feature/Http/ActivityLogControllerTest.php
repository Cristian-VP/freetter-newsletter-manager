<?php

namespace Domains\Activity\Tests\Feature\Http;

use Domains\Activity\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_activity_logs_with_limit_and_action_filter(): void
    {
        ActivityLog::factory()->create(['action' => 'post.published']);
        ActivityLog::factory()->create(['action' => 'workspace.created']);

        $response = $this->getJson('/activity/logs?limit=1&action=post.published');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.action', 'post.published');
    }
}
