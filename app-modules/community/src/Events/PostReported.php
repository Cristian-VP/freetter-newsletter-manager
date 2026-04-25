<?php

namespace Domains\Community\Events;

use Domains\Community\Models\PostReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostReported
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PostReport $report,
        public array $context = [],
    ) {}
}
