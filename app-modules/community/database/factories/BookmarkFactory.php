<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\Bookmark;
use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
        ];
    }
}
