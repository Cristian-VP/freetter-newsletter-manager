<?php

namespace Domains\Publishing\Events;

use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostPublished
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public Post $post,
        public ?string $publishedByUserId = null,
        public array $context = [],
    ) {}
}
