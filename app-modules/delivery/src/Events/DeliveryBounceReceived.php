<?php

namespace Domains\Delivery\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryBounceReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $workspaceId,
        public string $email,
        public string $bounceType,
        public ?string $messageId = null,
        public array $context = [],
    ) {}
}
