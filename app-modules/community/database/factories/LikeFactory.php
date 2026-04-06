<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\Like;
use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Like>
 */
class LikeFactory extends Factory
{
    protected $model = Like::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
        ];
    }
}
