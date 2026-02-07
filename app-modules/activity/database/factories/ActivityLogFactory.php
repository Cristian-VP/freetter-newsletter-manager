<?php

namespace Domains\Activity\Database\Factories;

use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define el estado por defecto de un ActivityLog
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'workspace.created',
                'workspace.deleted',
                'workspace.updated',
                'post.published',
                'post.deleted',
                'post.updated',
                'user.invited',
                'user.removed',
                'permission.changed',
                'subscriber.added',
                'subscriber.removed',
            ]),
            'entity_type' => $this->faker->randomElement([
                'workspace',
                'post',
                'user',
                'campaign',
                'subscriber',
            ]),
            'entity_id' => $this->faker->uuid(),
            'metadata' => [
                'previous_value' => $this->faker->word(),
                'new_value' => $this->faker->word(),
                'reason' => $this->faker->sentence(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Variante: Log de acción del sistema (sin usuario)
     */
    public function systemAction(): self
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Variante: Log de publicación de post
     */
    public function postPublished(): self
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'post.published',
            'entity_type' => 'post',
            'metadata' => ['status' => 'published'],
        ]);
    }

    /**
     * Variante: Log de eliminación crítica
     */
    public function deletion(): self
    {
        return $this->state(fn(array $attributes) => [
            'action' => 'workspace.deleted',
            'entity_type' => 'workspace',
            'metadata' => ['backup_created' => true],
        ]);
    }

    /**
     * Variante: Log antiguo (para tests de limpieza)
     */
    public function old(int $yearsAgo = 10): self
    {
        return $this->state(fn(array $attributes) => [
            'created_at' => now()->subYears($yearsAgo),
        ]);
    }

    /**
     * Variante: Log de hoy
     */
    public function today(): self
    {
        return $this->state(fn(array $attributes) => [
            'created_at' => now()->startOfDay()->addHours(
                $this->faker->numberBetween(0, 23)
            ),
        ]);
    }

    /**
     * Variante: Múltiples logs para una entidad
     */
    public function forEntity(string $entityType, string $entityId): self
    {
        return $this->state(fn(array $attributes) => [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }
}
