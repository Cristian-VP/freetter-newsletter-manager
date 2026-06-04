<?php

namespace Domains\Community\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostUnbookmarked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $userId,
        public string $postId,
        public array $context = [],
    ) {}
}
