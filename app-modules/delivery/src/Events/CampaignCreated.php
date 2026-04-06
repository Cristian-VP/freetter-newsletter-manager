<?php

namespace Domains\Delivery\Events;

use Domains\Delivery\Models\Campaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public int $subscriberCount,
    ) {}
}
