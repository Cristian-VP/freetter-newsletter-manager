<?php

namespace Domains\Audience\Console\Commands;

use Domains\Audience\Jobs\CleanupExpiredImportJobsJob;
use Illuminate\Console\Command;

class CleanupExpiredImportJobsCommand extends Command
{
    protected $signature = 'audience:cleanup-import-jobs';

    protected $description = 'Queue cleanup for expired Audience import jobs';

    public function handle(): int
    {
        CleanupExpiredImportJobsJob::dispatch();

        $this->info('Audience import jobs cleanup has been queued.');

        return self::SUCCESS;
    }
}
