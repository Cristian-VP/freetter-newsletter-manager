<?php

namespace Domains\Delivery\Database\Factories;

use Domains\Delivery\Models\Campaign;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'post_id' => Post::factory(),
            'status' => 'queued',
            'stats' => [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'opened' => 0,
            ],
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(fn (): array => [
            'status' => 'queued',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    public function sending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'sending',
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => 'sent',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'stats' => [
                'total' => 20,
                'sent' => 20,
                'failed' => 0,
                'opened' => 0,
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'stats' => [
                'total' => 20,
                'sent' => 12,
                'failed' => 8,
                'opened' => 0,
            ],
        ]);
    }
}
