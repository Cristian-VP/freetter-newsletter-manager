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

class NewsletterPublishingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_publishing_page_renders_selected_post(): void
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
            'title' => 'Newsletter de publicación',
            'excerpt' => 'Resumen para publicar',
        ]);

        $response = $this->actingAs($user)->get('/newsletters/publishing?post='.$post->id);

        $response->assertOk();
        $response->assertInertia(function (AssertableInertia $page) use ($workspace, $post): void {
            $page->component('publishing::NewsletterPublishing', false)
                ->where('workspace_id', $workspace->id)
                ->has('post')
                ->where('post.id', $post->id)
                ->where('post.title', 'Newsletter de publicación')
                ->where('post.workspace_id', $post->workspace_id)
                ->where('post.status', 'draft');
        });
    }
}
