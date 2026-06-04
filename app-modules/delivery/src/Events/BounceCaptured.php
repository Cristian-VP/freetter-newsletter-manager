<?php

namespace Domains\Delivery\Events;

use Domains\Delivery\Models\Bounce;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BounceCaptured
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Bounce $bounce) {}
}
