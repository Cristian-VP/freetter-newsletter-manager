<?php

namespace Domains\Delivery\Jobs;

use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Events\CampaignCompleted;
use Domains\Delivery\Events\CampaignSendingStarted;
use Domains\Delivery\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public string $campaignId) {}

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(): void
    {
        $campaign = Campaign::query()->find($this->campaignId);

        if (! $campaign || $campaign->status !== 'queued') {
            return;
        }

        $campaign->startSending();
        event(new CampaignSendingStarted($campaign->fresh()));

        $stats = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'opened' => 0,
        ];

        try {
            Subscriber::query()
                ->where('workspace_id', $campaign->workspace_id)
                ->where('status', 'active')
                ->orderBy('email')
                ->cursor()
                ->each(function (Subscriber $subscriber) use (&$stats, $campaign): void {
                    $stats['total']++;

                    if ($this->sendToSubscriber($campaign, $subscriber)) {
                        $stats['sent']++;

                        return;
                    }

                    $stats['failed']++;
                });

            $campaign->complete($stats);

            event(new CampaignCompleted(
                campaign: $campaign->fresh(),
                totalSent: (int) $stats['sent'],
                totalFailed: (int) $stats['failed'],
                stats: $stats,
            ));
        } catch (Throwable $exception) {
            $stats['failed'] = max((int) $stats['failed'], 1);
            $campaign->complete($stats);

            throw $exception;
        }
    }

    private function sendToSubscriber(Campaign $campaign, Subscriber $subscriber): bool
    {
        $shouldFail = str_contains($subscriber->email, 'fail+');

        Log::info('delivery.campaign.send_attempt', [
            'campaign_id' => $campaign->id,
            'workspace_id' => $campaign->workspace_id,
            'post_id' => $campaign->post_id,
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'provider' => 'mock',
            'result' => $shouldFail ? 'failed' : 'sent',
        ]);

        return ! $shouldFail;
    }
}
