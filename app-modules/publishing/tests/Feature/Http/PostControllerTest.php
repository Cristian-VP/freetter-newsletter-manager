<?php

namespace Domains\Publishing\Tests\Feature\Http;

use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        return $user;
    }

    public function test_can_create_post_draft_via_http(): void
    {
        $workspace = Workspace::factory()->create();
        $author = $this->createUser();

        Membership::factory()
            ->forUser($author)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $response = $this->actingAs($author)->postJson('/publishing/workspaces/'.$workspace->id.'/posts', [
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

    public function test_can_update_existing_post_draft_via_http(): void
    {
        $workspace = Workspace::factory()->create();
        $author = $this->createUser();

        Membership::factory()
            ->forUser($author)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'Original title',
            'excerpt' => 'Original excerpt',
        ]);

        $response = $this->actingAs($author)->postJson('/publishing/workspaces/'.$workspace->id.'/posts', [
            'post_id' => $post->id,
            'title' => 'Updated newsletter title',
            'type' => 'newsletter',
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => ['text' => 'Updated body'],
                ]],
            ],
            'excerpt' => 'Updated excerpt',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', $post->id);
        $response->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('publishing_posts', [
            'id' => $post->id,
            'title' => 'Updated newsletter title',
            'excerpt' => 'Updated excerpt',
            'status' => 'draft',
        ]);
    }

    public function test_guest_cannot_create_post_draft_via_http(): void
    {
        $workspace = Workspace::factory()->create();
        $author = $this->createUser();

        $response = $this->postJson('/publishing/workspaces/'.$workspace->id.'/posts', [
            'author_id' => $author->id,
            'title' => 'Unauthorized Draft',
            'type' => 'newsletter',
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => ['text' => 'Draft body'],
                ]],
            ],
        ]);

        $response->assertUnauthorized();
    }

    public function test_can_publish_post_and_create_version_via_http(): void
    {
        $publisher = $this->createUser();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($publisher)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $publisher->id,
        ]);

        $response = $this->actingAs($publisher)->postJson('/publishing/posts/'.$post->id.'/publish', [
            'published_by_user_id' => $publisher->id,
            'audience' => 'subscribers',
            'delivery_channels' => ['web', 'email'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('publishing_posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);

        $this->assertNotNull($post->fresh()->published_at);

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

    public function test_can_publish_web_only_without_creating_delivery_campaign(): void
    {
        $publisher = $this->createUser();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($publisher)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $publisher->id,
        ]);

        $response = $this->actingAs($publisher)->postJson('/publishing/posts/'.$post->id.'/publish', [
            'published_by_user_id' => $publisher->id,
            'audience' => 'all',
            'delivery_channels' => ['web'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('publishing_posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseMissing('delivery_campaigns', [
            'post_id' => $post->id,
        ]);
    }

    public function test_can_schedule_post_and_delete_it_via_http(): void
    {
        Carbon::setTestNow('2026-04-26 10:00:00');

        $author = $this->createUser();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($author)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
        ]);

        $scheduleResponse = $this->actingAs($author)->postJson('/publishing/posts/'.$post->id.'/schedule', [
            'title' => 'Scheduled newsletter',
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => ['text' => 'Scheduled body'],
                ]],
            ],
            'excerpt' => 'Scheduled excerpt',
            'published_at' => now()->addDay()->toIso8601String(),
        ]);

        $scheduleResponse->assertOk();
        $scheduleResponse->assertJsonPath('data.status', 'scheduled');

        $this->assertDatabaseHas('publishing_posts', [
            'id' => $post->id,
            'status' => 'scheduled',
        ]);

        $deleteResponse = $this->actingAs($author)->deleteJson('/publishing/posts/'.$post->id);

        $deleteResponse->assertOk();
        $deleteResponse->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('publishing_posts', [
            'id' => $post->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_can_create_and_publish_note_with_multiple_images_via_http(): void
    {
        Storage::fake('local');

        $workspace = Workspace::factory()->create();
        $author = $this->createUser();

        Membership::factory()
            ->forUser($author)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $response = $this->actingAs($author)->post('/publishing/workspaces/'.$workspace->id.'/posts', [
            'title' => 'Nueva Note desde Home',
            'type' => 'note',
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => ['text' => 'Contenido de la nota'],
                ]],
            ],
            'excerpt' => 'Contenido de la nota',
            'publish_now' => true,
            'media' => [
                UploadedFile::fake()->image('photo-1.jpg', 1080, 1350),
                UploadedFile::fake()->image('photo-2.png', 1080, 1080),
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'published');

        $postId = (string) $response->json('data.id');

        $this->assertDatabaseHas('publishing_posts', [
            'id' => $postId,
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'type' => 'note',
            'status' => 'published',
        ]);

        $createdPost = Post::query()->findOrFail($postId);
        $this->assertNotNull($createdPost->published_at);

        $this->assertDatabaseHas('publishing_post_versions', [
            'post_id' => $postId,
            'version_number' => 1,
        ]);

        $this->assertDatabaseCount('publishing_media', 2);
        $this->assertDatabaseCount('publishing_post_media', 2);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.published',
            'entity_type' => 'post',
            'entity_id' => $postId,
        ]);
    }

    public function test_cannot_create_note_with_more_than_eight_images(): void
    {
        $workspace = Workspace::factory()->create();
        $author = $this->createUser();

        Membership::factory()
            ->forUser($author)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $media = [];
        for ($index = 0; $index < 9; $index++) {
            $media[] = UploadedFile::fake()->image('photo-'.$index.'.jpg', 1080, 1080);
        }

        $response = $this->actingAs($author)->post('/publishing/workspaces/'.$workspace->id.'/posts', [
            'title' => 'Too many images',
            'type' => 'note',
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => ['text' => 'Contenido'],
                ]],
            ],
            'publish_now' => true,
            'media' => $media,
        ]);

        $response->assertSessionHasErrors(['media']);
        $this->assertDatabaseCount('publishing_posts', 0);
    }

    public function test_publish_endpoint_can_transition_to_preview_page_flow(): void
    {
        $this->withoutVite();

        $publisher = $this->createUser();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($publisher)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $publisher->id,
        ]);

        $publishResponse = $this->actingAs($publisher)->postJson('/publishing/posts/'.$post->id.'/publish', [
            'published_by_user_id' => $publisher->id,
        ]);

        $publishResponse->assertOk();
        $publishResponse->assertJsonPath('data.status', 'published');

        $previewResponse = $this->actingAs($publisher)->get('/newsletters/preview');

        $previewResponse->assertOk();
        $previewResponse->assertInertia(fn (AssertableInertia $page) => $page->component('publishing::NewsletterPreview', false));
    }
}
