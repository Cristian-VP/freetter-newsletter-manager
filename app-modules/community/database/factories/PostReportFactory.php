<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\PostReport;
use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostReportFactory extends Factory
{
    protected $model = PostReport::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'category' => 'spam',
            'reason' => $this->faker->sentence(),
            'status' => 'pending',
        ];
    }
}
