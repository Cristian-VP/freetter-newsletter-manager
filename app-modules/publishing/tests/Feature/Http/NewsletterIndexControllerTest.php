<?php

declare(strict_types=1);

namespace Domains\Publishing\Tests\Feature\Http;

use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NewsletterIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_newsletters_index_endpoint(): void
    {
        $response = $this->getJson('/publishing/newsletters');

        $response->assertUnauthorized();
    }

    public function test_endpoint_returns_paginated_newsletters_filtered_by_status(): void
    {
        Carbon::setTestNow('2026-04-26 10:00:00');

        /** @var User $user */
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Draft newsletter',
            'excerpt' => 'Resumen draft',
        ]);

        Post::factory()->newsletter()->scheduled()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Scheduled newsletter',
        ]);

        Post::factory()->newsletter()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Published newsletter',
        ]);

        $response = $this->actingAs($user)->getJson('/publishing/newsletters?workspace_id='.$workspace->id.'&status=draft');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.items');
        $response->assertJsonPath('data.items.0.title', 'Draft newsletter');
        $response->assertJsonPath('data.items.0.workspace_slug', $workspace->slug);
        $response->assertJsonPath('data.items.0.builder_url', route('newsletters.create', ['post' => $response->json('data.items.0.id')]));
        $response->assertJsonPath('data.items.0.preview_text', 'Resumen draft');
        $response->assertJsonPath('data.filters.status', 'draft');
        $response->assertJsonPath('data.counts_by_status.all', 3);
        $response->assertJsonPath('data.counts_by_status.draft', 1);
        $response->assertJsonPath('data.counts_by_status.scheduled', 1);
        $response->assertJsonPath('data.counts_by_status.published', 1);

        Carbon::setTestNow();
    }

    public function test_endpoint_can_filter_scheduled_and_published_newsletters(): void
    {
        Carbon::setTestNow('2026-04-26 10:00:00');

        /** @var User $user */
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        Post::factory()->newsletter()->scheduled()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Scheduled newsletter',
        ]);

        Post::factory()->newsletter()->published()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Published newsletter',
        ]);

        $scheduledResponse = $this->actingAs($user)->getJson('/publishing/newsletters?workspace_id='.$workspace->id.'&status=scheduled');

        $scheduledResponse->assertOk();
        $scheduledResponse->assertJsonCount(1, 'data.items');
        $scheduledResponse->assertJsonPath('data.items.0.title', 'Scheduled newsletter');

        $publishedResponse = $this->actingAs($user)->getJson('/publishing/newsletters?workspace_id='.$workspace->id.'&status=published');

        $publishedResponse->assertOk();
        $publishedResponse->assertJsonCount(1, 'data.items');
        $publishedResponse->assertJsonPath('data.items.0.title', 'Published newsletter');

        $allResponse = $this->actingAs($user)->getJson('/publishing/newsletters?workspace_id='.$workspace->id.'&status=all');

        $allResponse->assertOk();
        $allResponse->assertJsonCount(2, 'data.items');

        Carbon::setTestNow();
    }

    public function test_endpoint_applies_search_and_pagination(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        Post::factory()->count(3)->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Boletin semanal',
            'excerpt' => 'Contenido editorial',
        ]);

        Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Actualizacion interna',
            'excerpt' => 'No coincide con filtro',
        ]);

        $response = $this->actingAs($user)->getJson('/publishing/newsletters?workspace_id='.$workspace->id.'&status=all&q=Boletin&per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data.items');
        $response->assertJsonPath('data.filters.q', 'Boletin');
        $response->assertJsonPath('data.meta.per_page', 2);
        $response->assertJsonPath('data.meta.total', 3);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonPath('data.counts_by_status.all', 3);
        $response->assertJsonPath('data.items.0.preview_text', 'Contenido editorial');
    }

    public function test_endpoint_returns_forbidden_when_workspace_is_not_accessible(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $allowedWorkspace = Workspace::factory()->create();
        $forbiddenWorkspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($allowedWorkspace)
            ->writer()
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/publishing/newsletters?workspace_id='.$forbiddenWorkspace->id);

        $response->assertForbidden();
    }

    public function test_endpoint_validates_query_parameters(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/publishing/newsletters?status=queued&per_page=99&page=0');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status', 'per_page', 'page']);
    }

    public function test_newsletters_preview_page_requires_authentication(): void
    {
        $this->withoutVite();

        $response = $this->get('/newsletters/preview');

        $response->assertRedirect(route('login'));
    }

    public function test_newsletters_preview_page_renders_inertia_component(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/newsletters/preview');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('publishing::NewsletterPreview', false));
    }
}
