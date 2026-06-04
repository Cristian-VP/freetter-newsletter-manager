<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\Repost;
use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class RepostFactory extends Factory
{
    protected $model = Repost::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
        ];
    }
}
