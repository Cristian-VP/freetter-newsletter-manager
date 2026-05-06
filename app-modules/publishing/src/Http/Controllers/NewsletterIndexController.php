<?php

namespace Domains\Publishing\Http\Controllers;

use Domains\Identity\Models\Membership;
use Domains\Publishing\Http\Requests\IndexNewslettersRequest;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class NewsletterIndexController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 30;

    public function index(IndexNewslettersRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceIds = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->pluck('workspace_id');

        if ($workspaceIds->isEmpty()) {
            return response()->json([
                'data' => [
                    'items' => [],
                    'filters' => [
                        'workspace_id' => null,
                        'status' => 'all',
                        'q' => null,
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => self::DEFAULT_PER_PAGE,
                        'total' => 0,
                        'last_page' => 1,
                    ],
                    'counts_by_status' => [
                        'all' => 0,
                        'draft' => 0,
                        'scheduled' => 0,
                        'published' => 0,
                    ],
                ],
            ]);
        }

        $selectedWorkspaceId = $request->string('workspace_id')->value();
        $selectedWorkspaceId = $selectedWorkspaceId !== ''
            ? $selectedWorkspaceId
            : (string) $workspaceIds->first();

        abort_unless($workspaceIds->contains($selectedWorkspaceId), 403);

        $status = $request->string('status')->value();
        if ($status === '') {
            $status = 'all';
        }

        $searchTerm = trim($request->string('q')->value());
        $searchTerm = $searchTerm !== '' ? $searchTerm : null;

        $perPage = (int) $request->integer('per_page', self::DEFAULT_PER_PAGE);
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        $query = Post::query()
            ->ofType('newsletter')
            ->where('workspace_id', $selectedWorkspaceId)
            ->with(['workspace:id,name,slug'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($searchTerm !== null) {
            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($searchTerm).'%'])
                    ->orWhereRaw('LOWER(excerpt) LIKE ?', ['%'.mb_strtolower($searchTerm).'%']);
            });
        }

        $this->applyStatusFilter($query, $status);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $items = collect($paginator->items())
            ->map(function (Post $post): array {
                return [
                    'id' => $post->id,
                    'workspace_id' => $post->workspace_id,
                    'workspace_slug' => $post->workspace?->slug,
                    'title' => $post->title,
                    'status' => $post->status,
                    'excerpt' => $post->excerpt,
                    'preview_text' => $post->getExcerpt(240),
                    'created_at' => $post->created_at?->toIso8601String(),
                    'published_at' => $post->published_at?->toIso8601String(),
                    'updated_at' => $post->updated_at?->toIso8601String(),
                    'builder_url' => route('newsletters.publishing', ['post' => $post->id]),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'items' => $items,
                'filters' => [
                    'workspace_id' => $selectedWorkspaceId,
                    'status' => $status,
                    'q' => $searchTerm,
                ],
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'counts_by_status' => $this->buildStatusCounts($selectedWorkspaceId, $searchTerm),
            ],
        ]);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'draft') {
            $query->draft();

            return;
        }

        if ($status === 'scheduled') {
            $query->scheduled();

            return;
        }

        if ($status === 'published') {
            $query->published();
        }
    }

    /**
     * @return array{all: int, draft: int, scheduled: int, published: int}
     */
    private function buildStatusCounts(string $workspaceId, ?string $searchTerm): array
    {
        $baseQuery = Post::query()
            ->ofType('newsletter')
            ->where('workspace_id', $workspaceId);

        if ($searchTerm !== null) {
            $baseQuery->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($searchTerm).'%'])
                    ->orWhereRaw('LOWER(excerpt) LIKE ?', ['%'.mb_strtolower($searchTerm).'%']);
            });
        }

        return [
            'all' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->draft()->count(),
            'scheduled' => (clone $baseQuery)->scheduled()->count(),
            'published' => (clone $baseQuery)->published()->count(),
        ];
    }
}
