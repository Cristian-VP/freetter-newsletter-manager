<?php

namespace Domains\Community\Events;

use Domains\Community\Models\Bookmark;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostBookmarked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Bookmark $bookmark,
        public array $context = [],
    ) {}
}
