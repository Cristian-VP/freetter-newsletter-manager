<?php

namespace Domains\Audience\Jobs;

use Domains\Audience\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredImportJobsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        ImportJob::query()
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($jobs): void {
                foreach ($jobs as $job) {
                    if (Storage::disk('local')->exists($job->file_path)) {
                        Storage::disk('local')->delete($job->file_path);
                    }

                    $job->delete();
                }
            }, 'id');
    }
}
