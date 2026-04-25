<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\PostReported;
use Domains\Community\Http\Requests\StoreReportRequest;
use Domains\Community\Models\PostReport;
use Domains\Publishing\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $post = Post::query()->findOrFail($request->string('post_id')->value());

        $report = PostReport::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'category' => $request->string('category')->value(),
            'reason' => $request->string('reason')->value(),
            'status' => 'pending',
        ]);

        event(new PostReported(
            report: $report,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $report->id,
                'user_id' => $report->user_id,
                'post_id' => $report->post_id,
                'status' => $report->status,
            ],
        ], 201);
    }
}
