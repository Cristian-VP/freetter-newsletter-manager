<?php

namespace Domains\Delivery\Listeners;

use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Events\CampaignCreated;
use Domains\Delivery\Jobs\SendCampaignJob;
use Domains\Delivery\Models\Campaign;
use Domains\Publishing\Events\PostPublished;
use Illuminate\Support\Facades\DB;

class CreateDeliveryCampaignOnPublish
{
    public function handle(PostPublished $event): void
    {
        if ($event->post->type !== 'newsletter') {
            return;
        }

        DB::transaction(function () use ($event): void {
            $campaign = Campaign::query()->firstOrCreate(
                [
                    'workspace_id' => $event->post->workspace_id,
                    'post_id' => $event->post->id,
                ],
                [
                    'status' => 'queued',
                    'stats' => [
                        'total' => 0,
                        'sent' => 0,
                        'failed' => 0,
                        'opened' => 0,
                    ],
                ]
            );

            if (! $campaign->wasRecentlyCreated) {
                return;
            }

            $subscriberCount = Subscriber::query()
                ->where('workspace_id', $event->post->workspace_id)
                ->where('status', 'active')
                ->count();

            event(new CampaignCreated(
                campaign: $campaign,
                subscriberCount: $subscriberCount,
            ));

            SendCampaignJob::dispatch($campaign->id);
        });
    }
}
