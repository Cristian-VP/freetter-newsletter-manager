<?php

namespace Domains\Publishing\Tests\Feature\Models;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_can_be_created_with_factory(): void
    {
        $workspace = $this->createWorkspace();

        $media = Media::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $this->assertDatabaseHas('publishing_media', [
            'id' => $media->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    public function test_media_belongs_to_workspace(): void
    {
        $workspace = $this->createWorkspace();

        $media = Media::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $this->assertTrue($media->workspace->is($workspace));
    }

    public function test_media_can_attach_to_post(): void
    {
        $workspace = $this->createWorkspace();
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
        ]);
        $media = Media::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $post->media()->attach($media->id);

        $this->assertTrue($media->posts()->whereKey($post->id)->exists());
        $this->assertDatabaseHas('publishing_post_media', [
            'post_id' => $post->id,
            'media_id' => $media->id,
        ]);
    }

    private function createWorkspace(): Workspace
    {
        return Workspace::factory()
            ->forSlug('ws-'.Str::lower(Str::random(12)))
            ->create();
    }
}
