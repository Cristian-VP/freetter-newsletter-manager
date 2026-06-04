<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Events\CommentCreated;
use Domains\Community\Http\Requests\ListCommentsRequest;
use Domains\Community\Http\Requests\StoreCommentRequest;
use Domains\Community\Models\Comment;
use Domains\Publishing\Models\Post;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function index(ListCommentsRequest $request): JsonResponse
    {
        $post = Post::query()->findOrFail($request->string('post_id')->value());

        $topLevelComments = Comment::query()
            ->visible()
            ->forPost((string) $post->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,avatar_path',
                'replies' => function ($query): void {
                    $query->visible()
                        ->with('user:id,name,avatar_path')
                        ->orderBy('created_at');
                },
            ])
            ->withCount([
                'replies as replies_count' => function ($query): void {
                    $query->visible();
                },
            ])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => [
                'comments' => $topLevelComments->map(function (Comment $comment): array {
                    return [
                        'id' => (string) $comment->id,
                        'post_id' => (string) $comment->post_id,
                        'parent_id' => $comment->parent_id,
                        'content' => (string) $comment->content,
                        'created_at' => $comment->created_at?->toIso8601String(),
                        'created_relative' => $comment->created_at ? $this->relativeTimeInSpanish($comment->created_at) : 'ahora',
                        'author' => [
                            'id' => $comment->user?->id,
                            'name' => $comment->user?->name,
                            'avatar_url' => $this->resolveAvatarUrl($comment->user?->avatar_path),
                        ],
                        'replies_count' => (int) $comment->replies_count,
                        'replies' => $comment->replies->map(function (Comment $reply): array {
                            return [
                                'id' => (string) $reply->id,
                                'post_id' => (string) $reply->post_id,
                                'parent_id' => $reply->parent_id,
                                'content' => (string) $reply->content,
                                'created_at' => $reply->created_at?->toIso8601String(),
                                'created_relative' => $reply->created_at ? $this->relativeTimeInSpanish($reply->created_at) : 'ahora',
                                'author' => [
                                    'id' => $reply->user?->id,
                                    'name' => $reply->user?->name,
                                    'avatar_url' => $this->resolveAvatarUrl($reply->user?->avatar_path),
                                ],
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $post = Post::query()->findOrFail($request->string('post_id')->value());

        $parentId = $request->filled('parent_id') ? $request->string('parent_id')->value() : null;

        if ($parentId) {
            $parent = Comment::query()->findOrFail($parentId);

            if ($parent->post_id !== $post->id) {
                return response()->json([
                    'message' => 'Parent comment does not belong to this post.',
                ], 422);
            }
        }

        $comment = Comment::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'workspace_id' => $post->workspace_id,
            'parent_id' => $parentId,
            'content' => $request->string('content')->value(),
            'is_hidden' => false,
        ]);

        event(new CommentCreated(
            comment: $comment,
            context: [
                'source' => 'community.http',
            ],
        ));

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'workspace_id' => $comment->workspace_id,
                'parent_id' => $comment->parent_id,
                'content' => $comment->content,
            ],
        ], 201);
    }

    private function relativeTimeInSpanish(Carbon $createdAt): string
    {
        $now = now();

        if ($createdAt->diffInSeconds($now) < 45) {
            return 'ahora';
        }

        $minutes = max(1, (int) floor($createdAt->diffInMinutes($now)));
        if ($minutes < 60) {
            return $minutes === 1 ? '1 minuto' : $minutes.' minutos';
        }

        $hours = max(1, (int) floor($createdAt->diffInHours($now)));
        if ($hours < 24) {
            return $hours === 1 ? '1 hora' : $hours.' horas';
        }

        $days = max(1, (int) floor($createdAt->diffInDays($now)));
        if ($days < 7) {
            return $days === 1 ? '1 dia' : $days.' dias';
        }

        $weeks = max(1, (int) floor($days / 7));
        if ($weeks < 5) {
            return $weeks === 1 ? '1 semana' : $weeks.' semanas';
        }

        $months = max(1, (int) floor($createdAt->diffInMonths($now)));
        if ($months < 12) {
            return $months === 1 ? '1 mes' : $months.' meses';
        }

        $years = max(1, (int) floor($createdAt->diffInYears($now)));

        return $years === 1 ? '1 ano' : $years.' anos';
    }

    private function resolveAvatarUrl(?string $avatarPath): ?string
    {
        if ($avatarPath === null || $avatarPath === '') {
            return null;
        }

        if (str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')) {
            return $avatarPath;
        }

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk('public');

        return $filesystem->url($avatarPath);
    }
}
