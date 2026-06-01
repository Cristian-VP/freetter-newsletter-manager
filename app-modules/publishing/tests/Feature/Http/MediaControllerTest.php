<?php

namespace Domains\Publishing\Tests\Feature\Http;

use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_valid_image(): void
    {
        Storage::fake('public');

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->create();

        $file = UploadedFile::fake()->create('test-image.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->postJson('/publishing/media', [
            'file' => $file,
            'workspace_id' => $workspace->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('url', fn (?string $url) => ! empty($url));

        $mediaRecord = \Domains\Publishing\Models\Media::query()
            ->where('workspace_id', $workspace->id)
            ->where('disk', 'public')
            ->first();

        $this->assertNotNull($mediaRecord);
        Storage::disk('public')->assertExists($mediaRecord->path);
    }

    public function test_authenticated_user_can_upload_without_workspace_id(): void
    {
        Storage::fake('public');

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->create();

        $file = UploadedFile::fake()->create('test-image.png', 100, 'image/png');

        $response = $this->actingAs($user)->postJson('/publishing/media', [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonPath('url', fn (?string $url) => ! empty($url));

        $allMedia = \Domains\Publishing\Models\Media::query()->get();
        $this->assertCount(1, $allMedia, 'Expected exactly 1 media record');
        $this->assertSame($workspace->id, $allMedia->first()->workspace_id, 'Workspace ID mismatch');
        $this->assertSame('public', $allMedia->first()->disk, 'Disk mismatch');

        $mediaRecord = $allMedia->first();
        Storage::disk('public')->assertExists($mediaRecord->path);
    }

    public function test_upload_rejects_non_image_file(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->postJson('/publishing/media', [
            'file' => $file,
            'workspace_id' => $workspace->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_oversized_image(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($workspace)
            ->create();

        $file = UploadedFile::fake()->create('huge-image.jpg', 6000, 'image/jpeg');

        $response = $this->actingAs($user)->postJson('/publishing/media', [
            'file' => $file,
            'workspace_id' => $workspace->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_unauthenticated_user(): void
    {
        $workspace = Workspace::factory()->create();
        $file = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg');

        $response = $this->postJson('/publishing/media', [
            'file' => $file,
            'workspace_id' => $workspace->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_upload_rejects_non_member(): void
    {
        $workspace = Workspace::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Membership::factory()
            ->forUser($user)
            ->forWorkspace($otherWorkspace)
            ->create();

        $file = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg');

        $response = $this->actingAs($user)->postJson('/publishing/media', [
            'file' => $file,
            'workspace_id' => $workspace->id,
        ]);

        $response->assertForbidden();
    }
}
