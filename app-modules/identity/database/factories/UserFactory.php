<?php

namespace Domains\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Domains\Identity\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'avatar_path' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * User without email verified (Magic Link its required)
     */

    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * User Avatar (custom path or generated)
     */
    public function withAvatar(?string $path = null): static
    {
        return $this->state(function (array $attributes) use ($path) {
            return [
                'avatar_path' => $path ?? 'avatars/' . Str::uuid() . '.jpg',
            ];
        });
    }

    public function withoutAvatar(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'avatar_path' => null,
            ];
        });
    }

}
