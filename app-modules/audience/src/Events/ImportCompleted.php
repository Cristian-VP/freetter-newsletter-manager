<?php

namespace Domains\Audience\Events;

use Domains\Audience\Models\ImportJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ImportJob $importJob,
        public array $context = [],
    ) {}
}
