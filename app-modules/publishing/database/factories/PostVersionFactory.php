<?php

namespace Domains\Publishing\Database\Factories;

use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostVersion>
 */
class PostVersionFactory extends Factory
{
    protected $model = PostVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => [
                        'text' => $this->faker->paragraph(),
                    ],
                ]],
            ],
            'version_number' => $this->faker->numberBetween(1, 10),
            'created_at' => now(),
        ];
    }
}
