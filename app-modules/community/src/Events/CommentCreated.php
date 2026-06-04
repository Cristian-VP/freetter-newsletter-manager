<?php

namespace Domains\Community\Events;

use Domains\Community\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Comment $comment,
        public array $context = [],
    ) {}
}
