<?php

declare(strict_types=1);

namespace Domains\Publishing\Tests\Feature\Http;

use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NewsletterCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_builder_page_requires_authentication(): void
    {
        $this->withoutVite();

        $response = $this->get('/newsletters/create');

        $response->assertRedirect(route('login'));
    }

    public function test_newsletter_builder_page_renders_workspace_and_existing_post(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $post = Post::factory()->newsletter()->draft()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $user->id,
            'title' => 'Newsletter editorial',
            'excerpt' => 'Resumen editorial',
        ]);

        $response = $this->actingAs($user)->get('/newsletters/create?post='.$post->id);

        $response->assertOk();
        $response->assertInertia(function (AssertableInertia $page) use ($workspace, $post): void {
            $page->component('publishing::NewsletterCreate', false)
                ->where('workspace_id', $workspace->id)
                ->has('post')
                ->where('post.id', $post->id)
                ->where('post.title', 'Newsletter editorial')
                ->where('post.workspace_id', $post->workspace_id)
                ->where('post.status', 'draft');
        });
    }
}
