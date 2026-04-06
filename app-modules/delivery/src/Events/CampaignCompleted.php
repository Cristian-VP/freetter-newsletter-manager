<?php

namespace Domains\Delivery\Events;

use Domains\Delivery\Models\Campaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignCompleted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, int>  $stats
     */
    public function __construct(
        public Campaign $campaign,
        public int $totalSent,
        public int $totalFailed,
        public array $stats,
    ) {}
}
