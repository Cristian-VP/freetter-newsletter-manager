<?php

namespace Domains\Publishing\Tests\Feature\Models;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_version_can_be_created_with_factory(): void
    {
        $workspace = $this->createWorkspace();
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $version = PostVersion::factory()->create([
            'post_id' => $post->id,
        ]);

        $this->assertDatabaseHas('publishing_post_versions', [
            'id' => $version->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_post_version_belongs_to_post(): void
    {
        $workspace = $this->createWorkspace();
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $version = PostVersion::factory()->create([
            'post_id' => $post->id,
        ]);

        $this->assertTrue($version->post->is($post));
    }

    private function createWorkspace(): Workspace
    {
        return Workspace::factory()
            ->forSlug('ws-'.Str::lower(Str::random(12)))
            ->create();
    }
}
