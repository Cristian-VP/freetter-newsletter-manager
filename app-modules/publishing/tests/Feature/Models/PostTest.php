<?php

namespace Domains\Publishing\Tests\Feature\Models;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_can_be_created_with_factory(): void
    {
        $workspace = $this->createWorkspace();
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $this->assertDatabaseHas('publishing_posts', [
            'id' => $post->id,
        ]);
    }

    public function test_draft_scope_returns_only_draft_posts(): void
    {
        $workspace = $this->createWorkspace();

        $draftPost = Post::factory()->draft()->create([
            'workspace_id' => $workspace->id,
        ]);

        Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
        ]);

        $draftIds = Post::query()->draft()->pluck('id');

        $this->assertTrue($draftIds->contains($draftPost->id));
        $this->assertSame(1, $draftIds->count());
    }

    public function test_published_scope_returns_only_published_posts(): void
    {
        $workspace = $this->createWorkspace();

        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $workspace->id,
        ]);

        Post::factory()->draft()->create([
            'workspace_id' => $workspace->id,
        ]);

        $publishedIds = Post::query()->published()->pluck('id');

        $this->assertTrue($publishedIds->contains($publishedPost->id));
        $this->assertSame(1, $publishedIds->count());
    }

    public function test_post_belongs_to_workspace(): void
    {
        $workspace = $this->createWorkspace();
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $this->assertTrue($post->workspace->is($workspace));
    }

    public function test_post_belongs_to_author(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'author_id' => $author->id,
        ]);

        $this->assertTrue($post->author->is($author));
    }

    public function test_post_can_attach_tags(): void
    {
        $workspace = $this->createWorkspace();
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
        ]);
        $tag = Tag::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $post->tags()->attach($tag->id);

        $this->assertTrue($post->tags()->whereKey($tag->id)->exists());
        $this->assertDatabaseHas('publishing_post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_post_can_attach_media(): void
    {
        $workspace = $this->createWorkspace();
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
        ]);
        $media = Media::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $post->media()->attach($media->id);

        $this->assertTrue($post->media()->whereKey($media->id)->exists());
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
