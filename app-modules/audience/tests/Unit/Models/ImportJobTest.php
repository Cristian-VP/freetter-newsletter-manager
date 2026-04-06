<?php

namespace Domains\Audience\Tests\Unit\Models;

use Domains\Audience\Models\ImportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_increment_stats_and_accumulate_errors(): void
    {
        $job = ImportJob::factory()->create([
            'stats' => [
                'rows_total' => 0,
                'rows_imported' => 0,
                'rows_duplicated' => 0,
                'rows_invalid' => 0,
            ],
            'error_log' => [],
        ]);

        $job->incrementStat('rows_total', 3)
            ->incrementStat('rows_invalid')
            ->addError(['row' => 2, 'reason' => 'invalid_email'])
            ->save();

        $job->refresh();

        $this->assertSame(3, $job->stats['rows_total']);
        $this->assertSame(1, $job->stats['rows_invalid']);
        $this->assertCount(1, $job->error_log);
        $this->assertSame('invalid_email', $job->error_log[0]['reason']);
    }
}
