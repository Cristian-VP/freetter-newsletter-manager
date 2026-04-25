<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\BlockedUser;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockedUserFactory extends Factory
{
    protected $model = BlockedUser::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'blocked_user_id' => User::factory(),
        ];
    }
}
