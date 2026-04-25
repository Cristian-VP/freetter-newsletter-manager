<?php

namespace Domains\Community\Events;

use Domains\Community\Models\Repost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostReposted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Repost $repost,
        public array $context = [],
    ) {}
}
