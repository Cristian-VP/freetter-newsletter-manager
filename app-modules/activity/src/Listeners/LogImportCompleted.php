<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Audience\Events\ImportCompleted;

class LogImportCompleted
{
    public function handle(ImportCompleted $event): void
    {
        ActivityLog::query()->create([
            'user_id' => $event->importJob->created_by_user_id,
            'action' => 'audience.import.completed',
            'entity_type' => 'audience_import_job',
            'entity_id' => $event->importJob->id,
            'metadata' => [
                'workspace_id' => $event->importJob->workspace_id,
                'stats' => $event->importJob->stats,
                'context' => $event->context,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
