<?php

namespace Domains\Publishing\Database\Factories;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'workspace_id' => Workspace::factory(),
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(100, 999),
            'type' => 'note',
            'status' => 'draft',
            'content' => [
                'blocks' => [[
                    'type' => 'paragraph',
                    'data' => [
                        'text' => $this->faker->paragraph(),
                    ],
                ]],
            ],
            'excerpt' => $this->faker->sentence(),
            'published_at' => null,
            'carbon_score' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'published_at' => null,
            ];
        });
    }

    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'published',
                'published_at' => now()->subMinute(),
            ];
        });
    }

    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
                'published_at' => now()->addDay(),
            ];
        });
    }

    public function newsletter(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'newsletter',
            ];
        });
    }

    public function note(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'note',
            ];
        });
    }
}
