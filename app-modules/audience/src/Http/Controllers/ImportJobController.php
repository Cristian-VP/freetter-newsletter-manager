<?php

namespace Domains\Audience\Http\Controllers;

use Domains\Audience\Http\Requests\StoreImportJobRequest;
use Domains\Audience\Jobs\ProcessSubscriberImportJob;
use Domains\Audience\Models\ImportJob;
use Domains\Identity\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ImportJobController extends Controller
{
    public function store(StoreImportJobRequest $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->findOrFail($workspace);

        $filePath = $request->file('file')->store('audience-imports', 'local');

        $job = ImportJob::query()->create([
            'workspace_id' => $workspaceModel->id,
            'created_by_user_id' => $request->string('created_by_user_id')->value(),
            'status' => 'pending',
            'file_path' => $filePath,
            'stats' => [
                'rows_total' => 0,
                'rows_imported' => 0,
                'rows_duplicated' => 0,
                'rows_invalid' => 0,
            ],
            'error_log' => [],
            'expires_at' => now()->addDays(30),
        ]);

        ProcessSubscriberImportJob::dispatch($job->id);

        return response()->json([
            'data' => [
                'id' => $job->id,
                'status' => $job->status,
                'expires_at' => $job->expires_at,
            ],
        ], 202);
    }
}
