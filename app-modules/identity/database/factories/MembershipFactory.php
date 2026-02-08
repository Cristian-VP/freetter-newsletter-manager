<?php

namespace Domains\Identity\Database\Factories;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Domains\Identity\Models\Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),      // Crea User automáticamente
            'workspace_id' => Workspace::factory(),  // Crea Workspace automáticamente
            'role' => $this->faker->randomElement(['admin', 'owner','editor', 'viewer','writer']),
            'joined_at' => now(),
        ];
    }

    /**
     * Membership con rol owner
     */
    public function owner(): static
    {
        return $this->state([
            'role' => 'owner',
        ]);
    }

    /**
     * Membership con rol admin
     */
    public function admin(): static
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }

    /**
     * Membership con rol editor
     */
    public function editor(): static
    {
        return $this->state([
            'role' => 'editor',
        ]);
    }

    /**
     * Membership con rol viewer
     */
    public function viewer(): static
    {
        return $this->state([
            'role' => 'viewer',
        ]);
    }

    /**
     * Membership con rol writer
     */
    public function writer(): static
        {
        return $this->state([
            'role' => 'writer',
        ]);
    }

    /**
     * Membership para un usuario específico
     */
    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    /**
     * Membership para un workspace específico
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state([
            'workspace_id' => $workspace->id,
        ]);
    }
}
