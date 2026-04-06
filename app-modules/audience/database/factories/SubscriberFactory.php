<?php

namespace Domains\Audience\Database\Factories;

use Domains\Audience\Models\Subscriber;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'email' => strtolower(fake()->safeEmail()),
            'name' => fake()->name(),
            'status' => 'active',
            'consent_given_at' => now(),
            'consent_ip' => fake()->ipv4(),
            'unsubscribe_token' => (string) Str::uuid(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'unsubscribed',
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn (): array => [
            'status' => 'bounced',
        ]);
    }
}
