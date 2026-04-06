<?php

namespace Domains\Audience\Jobs;

use Domains\Audience\Events\ImportCompleted;
use Domains\Audience\Events\ImportFailed;
use Domains\Audience\Events\SubscriberCreated;
use Domains\Audience\Models\ImportJob;
use Domains\Audience\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;

class ProcessSubscriberImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $importJobId) {}

    public function handle(): void
    {
        $importJob = ImportJob::query()->find($this->importJobId);

        if (! $importJob) {
            return;
        }

        $importJob->update(['status' => 'processing']);

        try {
            $stats = [
                'rows_total' => 0,
                'rows_imported' => 0,
                'rows_duplicated' => 0,
                'rows_invalid' => 0,
            ];
            $errors = [];

            $filePath = Storage::disk('local')->path($importJob->file_path);
            $file = new SplFileObject($filePath);
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

            $header = null;
            $rowNumber = 0;

            foreach ($file as $row) {
                if (! is_array($row) || $row === [null]) {
                    continue;
                }

                $rowNumber++;

                if ($rowNumber === 1 && $this->looksLikeHeader($row)) {
                    $header = array_map(
                        static fn (?string $value): string => strtolower(trim((string) $value)),
                        $row
                    );

                    continue;
                }

                $stats['rows_total']++;
                $parsed = $this->parseRow($row, $header);

                if (! $parsed['email']) {
                    $stats['rows_invalid']++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => 'missing_email',
                    ];

                    continue;
                }

                $email = strtolower(trim($parsed['email']));

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $stats['rows_invalid']++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'email' => $email,
                        'reason' => 'invalid_email',
                    ];

                    continue;
                }

                try {
                    $subscriber = Subscriber::query()->create([
                        'workspace_id' => $importJob->workspace_id,
                        'email' => $email,
                        'name' => $parsed['name'] ? trim($parsed['name']) : null,
                        'status' => 'active',
                        'consent_given_at' => now(),
                        'consent_ip' => 'import_csv',
                        'unsubscribe_token' => (string) Str::uuid(),
                    ]);

                    $stats['rows_imported']++;

                    event(new SubscriberCreated(
                        subscriber: $subscriber,
                        context: [
                            'source' => 'csv_import',
                            'import_job_id' => $importJob->id,
                        ],
                    ));
                } catch (QueryException $exception) {
                    if ($this->isUniqueConstraintViolation($exception)) {
                        $stats['rows_duplicated']++;
                        $errors[] = [
                            'row' => $rowNumber,
                            'email' => $email,
                            'reason' => 'duplicate_subscriber',
                        ];

                        continue;
                    }

                    throw $exception;
                }
            }

            $importJob->update([
                'status' => 'completed',
                'stats' => $stats,
                'error_log' => $errors,
                'completed_at' => now(),
            ]);

            event(new ImportCompleted(
                importJob: $importJob->fresh(),
                context: [
                    'stats' => $stats,
                ],
            ));
        } catch (Throwable $exception) {
            $errorLog = $importJob->error_log ?? [];
            $errorLog[] = [
                'reason' => 'import_failed',
                'message' => $exception->getMessage(),
            ];

            $importJob->update([
                'status' => 'failed',
                'error_log' => $errorLog,
                'completed_at' => now(),
            ]);

            event(new ImportFailed(
                importJob: $importJob->fresh(),
                context: [
                    'message' => $exception->getMessage(),
                ],
            ));

            throw $exception;
        }
    }

    private function looksLikeHeader(array $row): bool
    {
        $values = array_map(
            static fn (?string $value): string => strtolower(trim((string) $value)),
            $row
        );

        return in_array('email', $values, true);
    }

    /**
     * @return array{email: ?string, name: ?string}
     */
    private function parseRow(array $row, ?array $header): array
    {
        if ($header !== null) {
            $mapped = [];
            foreach ($header as $index => $column) {
                $mapped[$column] = $row[$index] ?? null;
            }

            return [
                'email' => isset($mapped['email']) ? (string) $mapped['email'] : null,
                'name' => isset($mapped['name']) ? (string) $mapped['name'] : null,
            ];
        }

        return [
            'email' => isset($row[0]) ? (string) $row[0] : null,
            'name' => isset($row[1]) ? (string) $row[1] : null,
        ];
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23505', '23000'], true);
    }
}
