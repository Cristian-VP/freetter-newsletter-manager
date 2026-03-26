<?php

namespace Domains\Publishing\Tests\Feature\Http;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_post_draft_via_http(): void
    {
        $workspace = Workspace::factory()->create();
        $author = User::factory()->create();

        $response = $this->postJson('/publishing/workspaces/'.$workspace->id.'/posts', [
            'author_id' => $author->id,
            'title' => 'First Editorial Draft',
            'type' => 'newsletter',
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => ['text' => 'Draft body'],
                ]],
            ],
            'excerpt' => 'Short summary',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('publishing_posts', [
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'status' => 'draft',
        ]);
    }

    public function test_can_publish_post_and_create_version_via_http(): void
    {
        $post = Post::factory()->draft()->create();
        $publisher = User::factory()->create();

        $response = $this->postJson('/publishing/posts/'.$post->id.'/publish', [
            'published_by_user_id' => $publisher->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('publishing_posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('publishing_post_versions', [
            'post_id' => $post->id,
            'version_number' => 1,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.published',
            'entity_type' => 'post',
            'entity_id' => $post->id,
        ]);
    }
}
