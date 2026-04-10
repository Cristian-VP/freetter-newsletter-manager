<?php

namespace Domains\Audience\Tests\Feature\Http;

use Domains\Audience\Jobs\ProcessSubscriberImportJob;
use Domains\Audience\Models\ImportJob;
use Domains\Audience\Models\Subscriber;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportSubscribersTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_import_job_and_queue_processing(): void
    {
        Storage::fake('local');
        Queue::fake();

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent('import.csv', "email,name\nuser1@example.com,User One\n");

        $response = $this->actingAs($user)->postJson('/audience/workspaces/'.$workspace->id.'/imports', [
            'created_by_user_id' => $user->id,
            'file' => $file,
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('audience_import_jobs', [
            'workspace_id' => $workspace->id,
            'created_by_user_id' => $user->id,
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessSubscriberImportJob::class);
    }

    public function test_guest_cannot_create_import_job(): void
    {
        Storage::fake('local');

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('import.csv', "email,name\nuser1@example.com,User One\n");

        $response = $this->postJson('/audience/workspaces/'.$workspace->id.'/imports', [
            'created_by_user_id' => $user->id,
            'file' => $file,
        ]);

        $response->assertUnauthorized();
    }

    public function test_import_csv_processes_valid_rows_and_errors(): void
    {
        Storage::fake('local');
        Queue::fake();

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        Subscriber::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'dupe@example.com',
        ]);

        $csv = implode("\n", [
            'email,name',
            'valid@example.com,Valid User',
            'invalid-email,Invalid User',
            'dupe@example.com,Duplicate User',
        ]);

        $file = UploadedFile::fake()->createWithContent('mixed.csv', $csv);

        $response = $this->actingAs($user)->postJson('/audience/workspaces/'.$workspace->id.'/imports', [
            'created_by_user_id' => $user->id,
            'file' => $file,
        ]);

        $response->assertStatus(202);

        $jobId = $response->json('data.id');

        (new ProcessSubscriberImportJob($jobId))->handle();

        /** @var ImportJob $job */
        $job = ImportJob::query()->findOrFail($jobId);

        $this->assertSame('completed', $job->status);
        $this->assertSame(3, $job->stats['rows_total']);
        $this->assertSame(1, $job->stats['rows_imported']);
        $this->assertSame(1, $job->stats['rows_invalid']);
        $this->assertSame(1, $job->stats['rows_duplicated']);

        $this->assertDatabaseHas('audience_subscribers', [
            'workspace_id' => $workspace->id,
            'email' => 'valid@example.com',
            'status' => 'active',
        ]);
    }
}
