<?php

namespace Domains\Audience\Tests\Feature\Events;

use Domains\Audience\Events\ImportFailed;
use Domains\Audience\Events\SubscriberCreated;
use Domains\Audience\Models\ImportJob;
use Domains\Audience\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudienceActivityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_created_event_is_logged_in_activity(): void
    {
        $subscriber = Subscriber::factory()->create();

        event(new SubscriberCreated($subscriber, ['source' => 'integration_test']));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'subscriber.created',
            'entity_type' => 'subscriber',
            'entity_id' => $subscriber->id,
        ]);
    }

    public function test_import_failed_event_is_logged_in_activity(): void
    {
        $importJob = ImportJob::factory()->failed()->create();

        event(new ImportFailed($importJob, ['source' => 'integration_test']));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'audience.import.failed',
            'entity_type' => 'audience_import_job',
            'entity_id' => $importJob->id,
        ]);
    }
}
