<?php

namespace Domains\Publishing\Tests\Feature\Models;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_can_be_created_with_factory(): void
    {
        $workspace = $this->createWorkspace();
        $tag = Tag::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $this->assertDatabaseHas('publishing_tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_tag_belongs_to_workspace(): void
    {
        $workspace = $this->createWorkspace();
        $tag = Tag::factory()->create([
            'workspace_id' => $workspace->id,
        ]);

        $this->assertTrue($tag->workspace->is($workspace));
    }

    private function createWorkspace(): Workspace
    {
        return Workspace::factory()
            ->forSlug('ws-'.Str::lower(Str::random(12)))
            ->create();
    }
}
