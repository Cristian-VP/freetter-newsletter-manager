<?php

namespace Domains\Audience\Database\Factories;

use Domains\Audience\Models\ImportJob;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportJobFactory extends Factory
{
    protected $model = ImportJob::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'created_by_user_id' => User::factory(),
            'status' => 'pending',
            'file_path' => 'audience-imports/sample.csv',
            'stats' => [
                'rows_total' => 0,
                'rows_imported' => 0,
                'rows_duplicated' => 0,
                'rows_invalid' => 0,
            ],
            'error_log' => [],
            'expires_at' => now()->addDays(30),
            'completed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => 'processing',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }
}
