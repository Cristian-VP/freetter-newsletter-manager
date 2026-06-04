<?php

namespace Domains\Delivery\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResendBatchService
{
    protected string $endpoint = 'https://api.resend.com/emails/batch';

    /**
     * Send a batch of emails using Resend Batch Emails endpoint (chunked to 100 messages).
     *
     * @param  array  $subscribers  Array of subscriber arrays with at least 'email' and optional 'name' keys
     * @return array Results per chunk with keys: success(bool), status, body, sent_count, failed_count
     *
     * @throws \RuntimeException If API key is not configured
     */
    public function sendBatch(string $from, string $subject, string $htmlContent, array $subscribers): array
    {
        $results = [];

        $apiKey = config('services.resend.key');
        if (empty($apiKey)) {
            throw new \RuntimeException('Resend API key not configured (services.resend.key)');
        }

        $chunks = array_chunk($subscribers, 100);

        foreach ($chunks as $i => $chunk) {
            $messages = array_map(function ($sub) use ($from, $subject, $htmlContent) {
                return [
                    'from' => $from,
                    'to' => $sub['email'],
                    'subject' => $subject,
                    'html' => $htmlContent,
                ];
            }, $chunk);

            try {
                $response = Http::withToken($apiKey)
                    ->post($this->endpoint, $messages);

                if ($response->successful()) {
                    $body = $response->json();

                    $sentCount = 0;
                    $failedCount = 0;

                    if (isset($body['results']) && is_array($body['results'])) {
                        foreach ($body['results'] as $r) {
                            $status = $r['status'] ?? null;
                            if ($status === 'sent' || $status === 'delivered' || $status === 'queued') {
                                $sentCount++;
                            } else {
                                $failedCount++;
                            }
                        }
                    } else {
                        // Unknown body shape — assume all messages succeeded
                        $sentCount = count($messages);
                        $failedCount = 0;
                    }

                    $results[] = [
                        'chunk' => $i,
                        'success' => true,
                        'status' => $response->status(),
                        'body' => $body,
                        'sent_count' => $sentCount,
                        'failed_count' => $failedCount,
                    ];
                } else {
                    Log::warning('Resend batch failed', ['status' => $response->status(), 'body' => $response->body()]);
                    $results[] = [
                        'chunk' => $i,
                        'success' => false,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'sent_count' => 0,
                        'failed_count' => count($messages),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Resend batch exception', ['chunk' => $i, 'message' => $e->getMessage()]);
                $results[] = [
                    'chunk' => $i,
                    'success' => false,
                    'exception' => $e->getMessage(),
                    'sent_count' => 0,
                    'failed_count' => count($messages),
                ];
            }
        }

        return $results;
    }
}
