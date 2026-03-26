<?php

namespace Domains\Activity\Http\Controllers;

use Domains\Activity\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 20), 1), 50);

        $query = ActivityLog::query()->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->value());
        }

        $logs = $query->limit($limit)->get();

        return response()->json([
            'data' => $logs,
        ]);
    }
}
