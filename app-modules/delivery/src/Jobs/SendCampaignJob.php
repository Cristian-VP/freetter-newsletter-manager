<?php

namespace Domains\Delivery\Jobs;

use Domains\Audience\Models\Subscriber;
use Domains\Delivery\Events\CampaignCompleted;
use Domains\Delivery\Events\CampaignSendingStarted;
use Domains\Delivery\Models\Campaign;
use Domains\Delivery\Notifications\NewsletterPublishedNotification;
use Domains\Delivery\Services\ResendBatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
        $campaign = Campaign::query()->with(['post.workspace'])->find($this->campaignId);

        if (! $campaign || $campaign->status !== 'queued') {
            return;
        }

        $campaign->startSending();
        event(new CampaignSendingStarted($campaign->fresh()));

        $stats = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            // 'opened' is reserved for tracking via webhooks; not populated in batch send flow
        ];

        try {
            $subscribers = Subscriber::query()
                ->where('workspace_id', $campaign->workspace_id)
                ->where('status', 'active')
                ->orderBy('email')
                ->get();

            // Extract subject and html using existing notification renderer
            $notification = new NewsletterPublishedNotification($campaign->post);
            $subject = ($campaign->post->workspace?->name ?? 'Freetter').': '.$campaign->post->title;
            $html = $notification->renderNewsletterHtml();

            $from = $campaign->post->workspace?->sending_email ?? config('mail.from.address');

            $service = app(ResendBatchService::class);

            $chunks = $subscribers->chunk(100);

            foreach ($chunks as $chunkIndex => $chunk) {
                $batchSubscribers = $chunk->map(fn (Subscriber $s) => ['email' => $s->email, 'name' => $s->name ?? null])->all();
                $batchSize = count($batchSubscribers);

                Log::info('delivery.campaign.batch_sending_started', [
                    'campaign_id' => $campaign->id,
                    'workspace_id' => $campaign->workspace_id,
                    'post_id' => $campaign->post_id,
                    'chunk_index' => $chunkIndex,
                    'batch_size' => $batchSize,
                ]);

                $stats['total'] += $batchSize;

                $results = $service->sendBatch($from, $subject, $html, $batchSubscribers);

                // ResendBatchService returns array of results per chunk; since we send one chunk per call, inspect first result
                $result = $results[0] ?? null;

                if (is_array($result)) {
                    $sentCount = (int) ($result['sent_count'] ?? ($result['success'] ? $batchSize : 0));
                    $failedCount = (int) ($result['failed_count'] ?? ($batchSize - $sentCount));

                    $stats['sent'] += $sentCount;
                    $stats['failed'] += $failedCount;

                    Log::info('delivery.campaign.batch_sent', [
                        'campaign_id' => $campaign->id,
                        'workspace_id' => $campaign->workspace_id,
                        'chunk_index' => $chunkIndex,
                        'sent' => $sentCount,
                        'failed' => $failedCount,
                        'status' => $result['status'] ?? 'unknown',
                    ]);
                } else {
                    // Unknown result shape: mark whole chunk as failed
                    $stats['failed'] += $batchSize;
                    Log::warning('delivery.campaign.batch_failed', [
                        'campaign_id' => $campaign->id,
                        'workspace_id' => $campaign->workspace_id,
                        'chunk_index' => $chunkIndex,
                        'batch_size' => $batchSize,
                        'error' => 'No result from service',
                    ]);
                }
            }

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
}
