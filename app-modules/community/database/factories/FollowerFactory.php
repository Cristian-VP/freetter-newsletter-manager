<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\Follower;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follower>
 */
class FollowerFactory extends Factory
{
    protected $model = Follower::class;

    public function definition(): array
    {
        return [
            'follower_id' => User::factory(),
            'followed_workspace_id' => Workspace::factory(),
        ];
    }
}
