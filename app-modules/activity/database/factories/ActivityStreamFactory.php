<?php

namespace Domains\Activity\Database\Factories;

use Domains\Activity\Models\ActivityStream;
use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityStreamFactory extends Factory
{
    protected $model = ActivityStream::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'log_id' => ActivityLog::factory(),
            'event_type' => $this->faker->randomElement([
                'post.published',
                'post.created',
                'post.deleted',
                'workspace.created',
                'workspace.deleted',
                'user.invited',
                'membership.created',
                'membership.removed',
            ]),
            'visibility' => $this->faker->randomElement(['public', 'admin']),
        ];
    }

    public function public(): self
    {
        return $this->state(['visibility' => 'public']);
    }

    public function adminOnly(): self
    {
        return $this->state(['visibility' => 'admin']);
    }
}
