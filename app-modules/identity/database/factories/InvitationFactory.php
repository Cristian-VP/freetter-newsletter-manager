<?php

namespace Domains\Identity\Database\Factories;

use Domains\Identity\Models\Invitation;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Domains\Identity\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => $this->faker->randomElement(['admin', 'editor', 'viewer']),
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(7), // Válida por 7 días
            'accepted_by_user_id' => null,
            'accepted_at' => null,
        ];
    }

    /**
     * Invitación con workspace específico
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state([
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Invitación para un email específico
     */
    public function forEmail(string $email): static
    {
        return $this->state([
            'email' => $email,
        ]);
    }

    /**
     * Invitación con rol admin
     */
    public function admin(): static
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }

    /**
     * Invitación con rol editor
     */
    public function editor(): static
    {
        return $this->state([
            'role' => 'editor',
        ]);
    }

    /**
     * Invitación con rol viewer
     */
    public function viewer(): static
    {
        return $this->state([
            'role' => 'viewer',
        ]);
    }

    /**
     * Invitación sin rol owner (owner no se puede invitar, solo crear Membership directo)
     *
     * NOTA: No hay método owner() porque:
     * - Las invitaciones son para agregar miembros a un workspace existente
     * - El owner DEBE existir siempre (creado con withOwner() en WorkspaceFactory)
     * - Una invitación NO puede ser para promover a owner
     */

    /**
     * Invitación pendiente (válida y no aceptada)
     *
     * Estado: expires_at > now() AND accepted_by_user_id IS NULL
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => now()->addDays(7),
                'accepted_by_user_id' => null,
                'accepted_at' => null,
            ];
        });
    }

    /**
     * Invitación expirada (no aceptada y vencida)
     *
     * Estado: expires_at <= now() AND accepted_by_user_id IS NULL
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => now()->subDay(), // Hace 1 día
                'accepted_by_user_id' => null,
                'accepted_at' => null,
            ];
        });
    }

    /**
     * Invitación aceptada (aceptada por un usuario)
     *
     * Estado: accepted_by_user_id IS NOT NULL AND accepted_at IS NOT NULL
     *
     * NOTA: Cuando se acepta una invitación, se crea automáticamente un Membership
     * Esta factory solo simula el estado de la invitación, no crea el Membership
     */
    public function accepted(?User $user = null): static
    {
        if (!$user) {
            $user = User::factory()->create();
        }

        return $this->state(function (array $attributes) use ($user) {
            return [
                'accepted_by_user_id' => $user->id,
                'accepted_at' => now()->subHours(1), // Aceptada hace 1 hora
                'expires_at' => now()->addDays(7), // Sigue siendo válida (pero ya aceptada)
            ];
        });
    }

    /**
     * Invitación que expira en X días
     */
    public function expiresIn(int $days): static
    {
        return $this->state([
            'expires_at' => now()->addDays($days),
        ]);
    }

    /**
     * Invitación con token específico
     *
     * Útil para tests que necesitan un token conocido
     */
    public function withToken(string $token): static
    {
        return $this->state([
            'token' => $token,
        ]);
    }
}
