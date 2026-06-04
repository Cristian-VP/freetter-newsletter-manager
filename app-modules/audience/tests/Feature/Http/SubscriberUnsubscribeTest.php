<?php

namespace Domains\Audience\Tests\Feature\Http;

use Domains\Audience\Events\SubscriberUnsubscribed;
use Domains\Audience\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SubscriberUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_unsubscribe_by_token(): void
    {
        Event::fake([SubscriberUnsubscribed::class]);

        $subscriber = Subscriber::factory()->active()->create();

        $response = $this->getJson('/audience/unsubscribe/'.$subscriber->unsubscribe_token);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'unsubscribed');

        $this->assertDatabaseHas('audience_subscribers', [
            'id' => $subscriber->id,
            'status' => 'unsubscribed',
        ]);

        Event::assertDispatched(SubscriberUnsubscribed::class);
    }
}
