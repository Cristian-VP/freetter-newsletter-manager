<?php

namespace Domains\Activity\Listeners;

use Domains\Activity\Models\ActivityLog;
use Domains\Delivery\Events\BounceCaptured;
use Domains\Delivery\Events\CampaignCompleted;
use Domains\Delivery\Events\CampaignCreated;
use Domains\Delivery\Events\CampaignSendingStarted;

class LogDeliveryActivity
{
    public function handle(object $event): void
    {
        if ($event instanceof CampaignCreated) {
            ActivityLog::query()->create([
                'user_id' => null,
                'action' => 'delivery.campaign.created',
                'entity_type' => 'delivery_campaign',
                'entity_id' => $event->campaign->id,
                'metadata' => [
                    'workspace_id' => $event->campaign->workspace_id,
                    'post_id' => $event->campaign->post_id,
                    'subscriber_count' => $event->subscriberCount,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof CampaignSendingStarted) {
            ActivityLog::query()->create([
                'user_id' => null,
                'action' => 'delivery.campaign.sending_started',
                'entity_type' => 'delivery_campaign',
                'entity_id' => $event->campaign->id,
                'metadata' => [
                    'workspace_id' => $event->campaign->workspace_id,
                    'post_id' => $event->campaign->post_id,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof CampaignCompleted) {
            ActivityLog::query()->create([
                'user_id' => null,
                'action' => 'delivery.campaign.completed',
                'entity_type' => 'delivery_campaign',
                'entity_id' => $event->campaign->id,
                'metadata' => [
                    'workspace_id' => $event->campaign->workspace_id,
                    'post_id' => $event->campaign->post_id,
                    'total_sent' => $event->totalSent,
                    'total_failed' => $event->totalFailed,
                    'stats' => $event->stats,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return;
        }

        if ($event instanceof BounceCaptured) {
            ActivityLog::query()->create([
                'user_id' => null,
                'action' => 'delivery.bounce.captured',
                'entity_type' => 'delivery_bounce',
                'entity_id' => $event->bounce->id,
                'metadata' => [
                    'workspace_id' => $event->bounce->workspace_id,
                    'campaign_id' => $event->bounce->campaign_id,
                    'email' => $event->bounce->email,
                    'bounce_type' => $event->bounce->bounce_type,
                    'code' => $event->bounce->code,
                    'reason' => $event->bounce->reason,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
