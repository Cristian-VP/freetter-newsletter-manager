<?php

namespace Domains\Community\Events;

use Domains\Community\Models\Like;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Like $like,
        public array $context = [],
    ) {}
}
