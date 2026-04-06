<?php

namespace Domains\Delivery\Database\Factories;

use Domains\Delivery\Models\Bounce;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class BounceFactory extends Factory
{
    protected $model = Bounce::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'campaign_id' => null,
            'email' => strtolower(fake()->safeEmail()),
            'bounce_type' => 'hard',
            'code' => '550',
            'reason' => fake()->sentence(),
        ];
    }

    public function hard(): static
    {
        return $this->state(fn (): array => [
            'bounce_type' => 'hard',
        ]);
    }

    public function soft(): static
    {
        return $this->state(fn (): array => [
            'bounce_type' => 'soft',
            'code' => '450',
        ]);
    }

    public function complaint(): static
    {
        return $this->state(fn (): array => [
            'bounce_type' => 'complaint',
            'code' => 'complaint',
        ]);
    }
}
