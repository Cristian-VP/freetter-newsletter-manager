<?php

namespace Tests\Feature;

use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishingActivityEventsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_post_triggers_activity_log(): void
    {
        $post = Post::factory()->draft()->create();
        $publisher = User::factory()->create();

        $this->postJson('/publishing/posts/'.$post->id.'/publish', [
            'published_by_user_id' => $publisher->id,
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $publisher->id,
            'action' => 'post.published',
            'entity_type' => 'post',
            'entity_id' => $post->id,
        ]);
    }
}
