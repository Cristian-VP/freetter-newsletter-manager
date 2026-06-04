<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\MutedUser;
use Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MutedUserFactory extends Factory
{
    protected $model = MutedUser::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'muted_user_id' => User::factory(),
        ];
    }
}
