<?php

namespace Domains\Community\Events;

use Domains\Community\Models\MutedUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMuted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MutedUser $mute,
        public array $context = [],
    ) {}
}
