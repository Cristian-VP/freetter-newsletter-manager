<?php

namespace Domains\Publishing\Database\Factories;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'path' => $this->faker->filePath(),
            'disk' => $this->faker->randomElement(['local', 's3']),
            'mime_type' => $this->faker->randomElement([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ]),
            'size_kb' => $this->faker->numberBetween(10, 10240),
        ];
    }
}
