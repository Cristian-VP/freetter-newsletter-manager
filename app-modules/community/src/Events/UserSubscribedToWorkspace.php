<?php

namespace Domains\Community\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSubscribedToWorkspace
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $workspaceId,
        public string $userId,
        public array $context = [],
    ) {}
}
