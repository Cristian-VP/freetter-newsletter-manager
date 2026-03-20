<?php

namespace Domains\Activity\Database\Factories;

use Domains\Activity\Models\ActivityAlert;
use Domains\Activity\Models\ActivityLog;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityAlertFactory extends Factory
{
    protected $model = ActivityAlert::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'log_id' => ActivityLog::factory(),
            'alert_type' => $this->faker->randomElement([
                'hard_delete',
                'permission_escalation',
                'rate_limit_exceeded',
                'suspicious_activity',
                'bulk_operation',
            ]),
            'severity' => $this->faker->randomElement(['info', 'warning', 'critical']),
            'resolved_at' => null,
        ];
    }

    public function critical(): self
    {
        return $this->state(['severity' => 'critical']);
    }

    public function warning(): self
    {
        return $this->state(['severity' => 'warning']);
    }

    public function info(): self
    {
        return $this->state(['severity' => 'info']);
    }

    public function resolved(): self
    {
        return $this->state(['resolved_at' => now()]);
    }

    public function unresolved(): self
    {
        return $this->state(['resolved_at' => null]);
    }
}
