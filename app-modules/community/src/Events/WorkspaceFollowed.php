<?php

namespace Domains\Community\Events;

use Domains\Community\Models\Follower;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkspaceFollowed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Follower $follower,
        public array $context = [],
    ) {}
}
