<?php

namespace Domains\Audience\Events;

use Domains\Audience\Models\Subscriber;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriberBounced
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Subscriber $subscriber,
        public string $bounceType,
        public array $context = [],
    ) {}
}
