<?php

namespace Domains\Delivery\Tests\Unit;

use Domains\Delivery\Services\ResendBatchService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResendBatchServiceTest extends TestCase
{
    public function test_send_batch_chunks_and_posts_to_resend()
    {
        Http::fake(function ($request) {
            return Http::response(['id' => 'fake-broadcast-id'], 200);
        });

        $service = new ResendBatchService;

        // Generate 250 fake subscribers to force chunking (100,100,50)
        $subscribers = [];
        for ($i = 1; $i <= 250; $i++) {
            $subscribers[] = [
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
            ];
        }

        $results = $service->sendBatch('Acme <onboarding@freetter.dev>', 'Subject', '<p>Hi</p>', $subscribers);

        // Expect 3 chunks
        $this->assertCount(3, $results);

        foreach ($results as $r) {
            $this->assertTrue($r['success']);
            $this->assertEquals(200, $r['status']);
            $this->assertIsArray($r['body']);
        }

        Http::assertSentCount(3);

        // Verify payload sizes sent per request (raw array body)
        Http::assertSent(function ($request) {
            $payload = $request->data();

            return is_array($payload) && count($payload) === 100;
        });

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return is_array($payload) && count($payload) === 50;
        });
    }

    public function test_api_key_not_configured_throws_exception()
    {
        config(['services.resend.key' => null]);

        $service = new ResendBatchService;
        $subscribers = [['email' => 'test@example.com']];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resend API key not configured');

        $service->sendBatch('from@example.com', 'Subject', '<p>Hi</p>', $subscribers);
    }

    public function test_http_exception_includes_sent_and_failed_counts()
    {
        Http::fake(function ($request) {
            throw new \Exception('Connection timeout');
        });

        $service = new ResendBatchService;
        $subscribers = [
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
        ];

        $results = $service->sendBatch('from@example.com', 'Subject', '<p>Hi</p>', $subscribers);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['sent_count']);
        $this->assertSame(2, $result['failed_count']);
    }

    public function test_response_without_results_assumes_all_success()
    {
        Http::fake(function ($request) {
            return Http::response(['id' => 'broadcast-123'], 200);
        });

        $service = new ResendBatchService;
        $subscribers = [
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
        ];

        $results = $service->sendBatch('from@example.com', 'Subject', '<p>Hi</p>', $subscribers);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertTrue($result['success']);
        // When no 'results' key, all messages are assumed successful
        $this->assertSame(2, $result['sent_count']);
        $this->assertSame(0, $result['failed_count']);
    }

    public function test_batch_with_partial_failures()
    {
        Http::fake(function ($request) {
            $payload = $request->data();
            $results = [];
            foreach ($payload as $emailMsg) {
                $to = $emailMsg['to'] ?? '';
                if (str_contains($to, 'invalid')) {
                    $results[] = ['to' => $to, 'status' => 'failed'];
                } else {
                    $results[] = ['to' => $to, 'status' => 'sent'];
                }
            }

            return Http::response(['results' => $results], 200);
        });

        $service = new ResendBatchService;
        $subscribers = [
            ['email' => 'valid1@example.com'],
            ['email' => 'invalid@example.com'],
            ['email' => 'valid2@example.com'],
        ];

        $results = $service->sendBatch('from@example.com', 'Subject', '<p>Hi</p>', $subscribers);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['sent_count']);
        $this->assertSame(1, $result['failed_count']);
    }
}
