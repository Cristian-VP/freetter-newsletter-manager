<?php

namespace Domains\Community\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkspaceUnfollowed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $followerId,
        public string $workspaceId,
        public array $context = [],
    ) {}
}
