<?php

namespace Domains\Community\Events;

use Domains\Community\Models\BlockedUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserBlocked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public BlockedUser $block,
        public array $context = [],
    ) {}
}
