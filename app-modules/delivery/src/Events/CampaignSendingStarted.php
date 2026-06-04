<?php

namespace Domains\Delivery\Events;

use Domains\Delivery\Models\Campaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignSendingStarted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Campaign $campaign) {}
}
