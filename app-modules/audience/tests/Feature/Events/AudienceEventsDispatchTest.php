<?php

namespace Domains\Audience\Tests\Feature\Events;

use Domains\Audience\Events\ImportCompleted;
use Domains\Audience\Events\ImportFailed;
use Domains\Audience\Events\SubscriberBounced;
use Domains\Audience\Events\SubscriberCreated;
use Domains\Audience\Events\SubscriberUnsubscribed;
use Domains\Audience\Models\ImportJob;
use Domains\Audience\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AudienceEventsDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_audience_emits_expected_events(): void
    {
        Event::fake([
            SubscriberCreated::class,
            SubscriberUnsubscribed::class,
            SubscriberBounced::class,
            ImportCompleted::class,
            ImportFailed::class,
        ]);

        $subscriber = Subscriber::factory()->create();
        $importJob = ImportJob::factory()->create();

        event(new SubscriberCreated($subscriber, ['source' => 'test']));
        event(new SubscriberUnsubscribed($subscriber, ['source' => 'test']));
        event(new SubscriberBounced($subscriber, 'hard', ['source' => 'test']));
        event(new ImportCompleted($importJob, ['source' => 'test']));
        event(new ImportFailed($importJob, ['source' => 'test']));

        Event::assertDispatched(SubscriberCreated::class);
        Event::assertDispatched(SubscriberUnsubscribed::class);
        Event::assertDispatched(SubscriberBounced::class);
        Event::assertDispatched(ImportCompleted::class);
        Event::assertDispatched(ImportFailed::class);
    }
}
