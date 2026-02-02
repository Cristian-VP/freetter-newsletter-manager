<?php

namespace Domains\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Domains\Identity\Models\Membership;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Domains\Identity\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{

    /**generic path or null
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(),
            'branding_config' => [
                'logo_url' => $this->faker->imageUrl(100, 100, 'business', true, 'Logo'),
                'primary_color' => $this->faker->hexColor(),
                'secondary_color' => $this->faker->hexColor(),
            ],
            'donation_config' => [
                'default_amounts' => $this->faker->randomFloat(2, 5, 500),
                'currency' => $this->faker->currencyCode()
            ],
        ];
    }

    /**
     * Workspace with custom branding configuration
     */
    public function withCustomBranding(
        ?string $logoUrl = null,
        ?string $primaryColor = null,
        ?string $secondaryColor = null
    ): static{
        return $this->state(function (array $attributes) use ($logoUrl, $primaryColor, $secondaryColor) {
            return [
                'branding_config' => [
                    'logo_url' => $logoUrl ?? $attributes['branding_config']['logo_url'],
                    'primary_color' => $primaryColor ?? $attributes['branding_config']['primary_color'],
                    'secondary_color' => $secondaryColor ?? $attributes['branding_config']['secondary_color'],
                ],
            ];
        });
    }

    /**
     * Workspace with donation configuration
     */
    public function withCustomDonationConfig(
        ?float $defaultAmount = null,
        ?string $currency = null
    ): static{
        return $this->state(function (array $attributes) use ($defaultAmount, $currency) {
            return [
                'donation_config' => [
                    'default_amount' => $defaultAmount ?? $attributes['donation_config']['default_amount'],
                    'currency' => $currency ?? $attributes['donation_config']['currency'],
                ],
            ];
        });
    }

    public function withoutDonationConfig(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'donation_config' => null,
            ];
        });
    }

    /**
     * Workspace with specific slug
     */
    public function forSlug(string $slug): static
    {
        return $this->state(function (array $attributes) use ($slug) {
            return [
                'slug' => $slug,
            ];
        });
    }

    public function withName(string $name): static
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name,
            ];
        });
    }

    public function minimal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'branding_config' => [
                    // Minimal branding config
                    'logo_url' => null,
                    'primary_color' => '#000000',
                ],
                'donation_config' => [
                    // Minimal donation config
                    'default_amount' => 10.00,
                    'currency' => 'USD',
                ],
            ];
        });
    }

     /**
     * Workspace with owner Membership
     *
     * NOTE: This method creates a Membership (relationship)
     * because a Workspace without an owner is invalid in business logic.
     * We only use state for attributes of the Workspace itself.
     *
     * @param Membership|null $ownerMembership User to assign as owner (creates a new one if null)
     * @return static
     */
    public function withOwner(?Membership $ownerMembership = null): static
    {
       if ($ownerMembership) {
            return $this->has(
                Membership::factory()
                    ->state([
                        'user_id' => $ownerMembership->user_id,
                        'role' => 'owner',
                        'joined_at' => $ownerMembership->joined_at ?? now(),
                    ]),
                'memberships'
            );
        }

        return $this->has(
            Membership::factory()->owner(),
            'memberships'
        );
    }


    /**
     * Workspace con miembros adicionales (flexible)
     *
     * Permite crear miembros con roles específicos sin imponer estructura rígida.
     *
     * IMPORTANTE: Se asume que el owner ya fue creado con withOwner()
     *
     * @param array $membershipsData Array de miembros a crear
     *                               Formato: [
     *                                   ['role' => 'admin', 'quantity' => 2],
     *                                   ['role' => 'editor', 'quantity' => 1],
     *                                   ['role' => 'viewer', 'quantity' => 5],
     *                               ]
     * @return static
     *
     * Uso:
     *   // Owner + 2 admins + 1 editor
     *   Workspace::factory()
     *     ->withOwner()
     *     ->withMembers([
     *         ['role' => 'admin', 'quantity' => 2],
     *         ['role' => 'editor', 'quantity' => 1],
     *     ])
     *     ->create();
     */
    public function withMembers(array $membershipsData): static {
        return $this->afterCreating(function ($workspace) use ($membershipsData) {
            foreach ($membershipsData as $member) {
                $role = $member['role'] ?? null;
                $quantityMembers = $member['quantity'] ?? 1;

                if (!$role || !in_array($role, ['owner', 'admin', 'editor', 'viewer'])) {
                    throw new \InvalidArgumentException(
                        "Role '{$role}' is invalid. Allowed roles: owner, admin, editor, viewer"
                    );
                }

                if($role === 'owner') {
                     throw new \InvalidArgumentException(
                        "Cannot create multiple owners. Use withOwner() instead."
                    );
                }

                Membership::factory()
                    ->{$role}() // Llama al método dinámico del rol (->admin(), ->editor(), etc)
                    ->count($quantityMembers)
                    ->forWorkspace($workspace)
                    ->create();
            }
        });
    }

    /**
     * Workspace con equipo completo (helper conveniente)
     *
     * Crea un workspace con:
     * - 1 Owner
     * - N Admins (opcional)
     * - N Editors (opcional)
     * - N Viewers (opcional)
     *
     * @param int $adminCount Número de admins (0 por defecto)
     * @param int $editorCount Número de editores (0 por defecto)
     * @param int $viewerCount Número de viewers (0 por defecto)
     * @return static
     *
     * Uso:
     *   // Owner + 2 admins + 3 editores
     *   Workspace::factory()
     *     ->withOwner()
     *     ->withFullTeam(adminCount: 2, editorCount: 3)
     *     ->create();
     *
     *   // Solo owner
     *   Workspace::factory()
     *     ->withOwner()
     *     ->withFullTeam()
     *     ->create();
     */
    public function withFullTeam(int $adminCount = 0, int $editorCount = 0, int $viewerCount = 0): static
    {
        $membershipsData = [];

        if ($adminCount > 0) {
            $membershipsData[] = ['role' => 'admin', 'quantity' => $adminCount];
        }

        if ($editorCount > 0) {
            $membershipsData[] = ['role' => 'editor', 'quantity' => $editorCount];
        }

        if ($viewerCount > 0) {
            $membershipsData[] = ['role' => 'viewer', 'quantity' => $viewerCount];
        }

        return $this->withMembers($membershipsData);
    }

}
