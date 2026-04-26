<?php

declare(strict_types=1);

namespace Domains\Community\Tests\Feature\Http;

use Domains\Community\Models\Follower;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriptions_page_requires_authentication(): void
    {
        $response = $this->get('/subscriptions');

        $response->assertRedirect(route('login'));
    }

    public function test_subscriptions_page_renders_the_inertia_component(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/subscriptions');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('community::Subscriptions', false));
    }

    public function test_subscriptions_endpoint_returns_newsletters_from_followed_workspaces(): void
    {
        Carbon::setTestNow('2026-04-26 12:00:00');

        /** @var User $viewer */
        $viewer = User::factory()->create(['name' => 'Reader User']);
        $followedWorkspaceA = Workspace::factory()->create(['name' => 'Workspace A', 'slug' => 'workspace-a']);
        $followedWorkspaceB = Workspace::factory()->create(['name' => 'Workspace B', 'slug' => 'workspace-b']);
        $ignoredWorkspace = Workspace::factory()->create(['name' => 'Workspace Ignored', 'slug' => 'workspace-ignored']);

        Follower::factory()->create([
            'follower_id' => $viewer->id,
            'followed_workspace_id' => $followedWorkspaceA->id,
        ]);

        Follower::factory()->create([
            'follower_id' => $viewer->id,
            'followed_workspace_id' => $followedWorkspaceB->id,
        ]);

        $newsletterA1 = Post::factory()
            ->newsletter()
            ->published()
            ->create([
                'workspace_id' => $followedWorkspaceA->id,
                'author_id' => $viewer->id,
                'title' => 'Newsletter A1',
                'published_at' => now()->subHour(),
                'excerpt' => '',
                'content' => [
                    'blocks' => [
                        [
                            'type' => 'paragraph',
                            'data' => [
                                'text' => 'Primer bloque de la newsletter A1 con suficiente texto para preview.',
                            ],
                        ],
                        [
                            'type' => 'paragraph',
                            'data' => [
                                'text' => 'Segundo bloque para validar el cuerpo abierto del modal.',
                            ],
                        ],
                    ],
                ],
            ]);

        $media = Media::query()->create([
            'workspace_id' => $followedWorkspaceA->id,
            'path' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1200&q=80',
            'disk' => 'local',
            'mime_type' => 'image/jpeg',
            'size_kb' => 512,
        ]);

        $newsletterA1->media()->syncWithoutDetaching([$media->id]);

        Post::factory()
            ->newsletter()
            ->published()
            ->create([
                'workspace_id' => $followedWorkspaceB->id,
                'author_id' => $viewer->id,
                'title' => 'Newsletter B1',
                'published_at' => now()->subHours(2),
                'excerpt' => 'Resumen de B1',
            ]);

        Post::factory()
            ->newsletter()
            ->published()
            ->create([
                'workspace_id' => $followedWorkspaceA->id,
                'author_id' => $viewer->id,
                'title' => 'Newsletter A2',
                'published_at' => now()->subDays(2),
                'excerpt' => '',
                'content' => [
                    'blocks' => [
                        [
                            'type' => 'paragraph',
                            'data' => [
                                'text' => 'Segundo bloque anterior para la newsletter A2.',
                            ],
                        ],
                    ],
                ],
            ]);

        Post::factory()
            ->newsletter()
            ->published()
            ->create([
                'workspace_id' => $ignoredWorkspace->id,
                'author_id' => $viewer->id,
                'title' => 'Newsletter ignored',
                'published_at' => now()->subMinutes(15),
            ]);

        Post::factory()
            ->newsletter()
            ->draft()
            ->create([
                'workspace_id' => $followedWorkspaceA->id,
                'author_id' => $viewer->id,
                'title' => 'Draft newsletter',
            ]);

        $response = $this->actingAs($viewer)->getJson('/community/subscriptions');

        $response->assertOk();
        $response->assertJsonCount(3, 'data.subscriptions');
        $response->assertJsonPath('data.subscriptions.0.newsletter.title', 'Newsletter A1');
        $response->assertJsonPath('data.subscriptions.0.owner.name', 'Reader User');
        $response->assertJsonPath('data.subscriptions.0.workspace.slug', 'workspace-a');
        $response->assertJsonPath('data.subscriptions.0.cover_image_url', 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1200&q=80');
        $response->assertJsonPath('data.subscriptions.0.newsletter.body_paragraphs.0', 'Primer bloque de la newsletter A1 con suficiente texto para preview.');
        $response->assertJsonPath('data.subscriptions.1.newsletter.title', 'Newsletter B1');
        $response->assertJsonPath('data.subscriptions.2.newsletter.title', 'Newsletter A2');
        $response->assertJsonPath('data.subscriptions.0.preview_text', 'Primer bloque de la newsletter A1 con suficiente texto para preview.');
        $response->assertJsonPath('data.subscriptions.1.preview_text', 'Resumen de B1');
        $response->assertJsonMissingPath('data.subscriptions.3');

        Carbon::setTestNow();
    }

    public function test_subscriptions_endpoint_returns_empty_payload_when_no_workspaces_are_followed(): void
    {
        /** @var User $viewer */
        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)->getJson('/community/subscriptions');

        $response->assertOk();
        $response->assertJsonPath('data.subscriptions', []);
    }
}
