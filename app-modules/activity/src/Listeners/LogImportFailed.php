<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Audience\Events\ImportFailed;

class LogImportFailed
{
    public function handle(ImportFailed $event): void
    {
        ActivityLog::query()->create([
            'user_id' => $event->importJob->created_by_user_id,
            'action' => 'audience.import.failed',
            'entity_type' => 'audience_import_job',
            'entity_id' => $event->importJob->id,
            'metadata' => [
                'workspace_id' => $event->importJob->workspace_id,
                'error_log' => $event->importJob->error_log,
                'context' => $event->context,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
