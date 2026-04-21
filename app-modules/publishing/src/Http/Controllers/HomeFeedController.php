<?php

declare(strict_types=1);

namespace Domains\Publishing\Http\Controllers;

use Carbon\CarbonInterface;
use Domains\Identity\Models\Membership;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeFeedController extends Controller
{
    private const FEED_PAGE_SIZE = 30;

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceId = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->value('workspace_id');

        $payload = $this->buildFeedPayload($user->id, null, self::FEED_PAGE_SIZE);

        return Inertia::render('publishing::Home', [
            'posts' => $payload['posts'],
            'workspace_id' => $workspaceId,
            'feed' => [
                'has_more' => $payload['has_more'],
                'next_cursor' => $payload['next_cursor'],
            ],
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $cursor = $request->query('cursor');
        $decodedCursor = is_string($cursor) && $cursor !== '' ? $this->decodeCursor($cursor) : null;
        $limit = (int) $request->integer('limit', self::FEED_PAGE_SIZE);
        $limit = max(1, min($limit, self::FEED_PAGE_SIZE));

        $payload = $this->buildFeedPayload($user->id, $decodedCursor, $limit);

        return response()->json([
            'data' => [
                'posts' => $payload['posts'],
                'has_more' => $payload['has_more'],
                'next_cursor' => $payload['next_cursor'],
            ],
        ]);
    }

    /**
     * @param  array{published_at: string, id: string}|null  $cursor
     * @return array{posts: array<int, array<string, mixed>>, has_more: bool, next_cursor: ?string}
     */
    private function buildFeedPayload(string $userId, ?array $cursor, int $limit): array
    {
        $postsQuery = Post::query()
            ->published()
            ->with(['author:id,name,avatar_path', 'media'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($cursor !== null) {
            $postsQuery->where(function ($query) use ($cursor): void {
                $query->where('published_at', '<', $cursor['published_at'])
                    ->orWhere(function ($query) use ($cursor): void {
                        $query->where('published_at', '=', $cursor['published_at'])
                            ->where('id', '<', $cursor['id']);
                    });
            });
        }

        $posts = $postsQuery->limit($limit + 1)->get();
        $hasMore = $posts->count() > $limit;

        /** @var Collection<int, Post> $postsForPage */
        $postsForPage = $hasMore
            ? $posts->take($limit)->values()
            : $posts->values();

        $postIds = $postsForPage->pluck('id')->values();

        $likeCounts = DB::table('community_likes')
            ->selectRaw('post_id, COUNT(*) as total')
            ->whereIn('post_id', $postIds)
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $likedPostIds = DB::table('community_likes')
            ->where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip();

        $feedPosts = $postsForPage->map(function (Post $post) use ($likeCounts, $likedPostIds): array {
            $contentText = $post->getExcerpt(500);
            $publishedAt = $post->published_at;
            $contentBlocks = $post->content['blocks'] ?? [];

            if (! is_array($contentBlocks)) {
                $contentBlocks = [];
            }

            return [
                'id' => $post->id,
                'author' => [
                    'id' => $post->author?->id,
                    'name' => $post->author?->name,
                    'avatar_url' => $this->resolveAvatarUrl($post->author?->avatar_path),
                ],
                'published_at' => $publishedAt?->toIso8601String(),
                'published_relative' => $publishedAt?->diffForHumans(now(), [
                    'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
                    'short' => true,
                    'parts' => 1,
                ]),
                'content' => [
                    'blocks' => $contentBlocks,
                    'plain_text' => $contentText,
                ],
                'media' => $post->media->map(function ($media): array {
                    return [
                        'id' => $media->id,
                        'url' => $this->resolveMediaUrl($media),
                        'mime_type' => $media->mime_type,
                    ];
                })->values()->all(),
                'metrics' => [
                    'likes_count' => (int) ($likeCounts[$post->id] ?? 0),
                    'liked_by_me' => isset($likedPostIds[$post->id]),
                ],
            ];
        })->values();

        $nextCursor = null;

        if ($hasMore && $postsForPage->isNotEmpty()) {
            $lastPost = $postsForPage->last();
            if ($lastPost instanceof Post && $lastPost->published_at !== null) {
                $nextCursor = $this->encodeCursor((string) $lastPost->published_at->toDateTimeString(), (string) $lastPost->id);
            }
        }

        return [
            'posts' => $feedPosts,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
        ];
    }

    private function resolveMediaUrl(Media $media): string
    {
        $path = (string) $media->path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if ($media->disk === 'public') {
            /** @var FilesystemAdapter $filesystem */
            $filesystem = Storage::disk('public');

            return $filesystem->url($path);
        }

        return route('publishing.media.show', ['media' => $media->id]);
    }

    private function encodeCursor(string $publishedAt, string $id): string
    {
        $encoded = base64_encode($publishedAt.'|'.$id);

        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }

    /**
     * @return array{published_at: string, id: string}|null
     */
    private function decodeCursor(string $cursor): ?array
    {
        $normalized = strtr($cursor, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if (! is_string($decoded)) {
            return null;
        }

        $parts = explode('|', $decoded, 2);
        if (count($parts) !== 2) {
            return null;
        }

        if ($parts[0] === '' || $parts[1] === '') {
            return null;
        }

        try {
            $publishedAt = Carbon::parse($parts[0])->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }

        return [
            'published_at' => $publishedAt,
            'id' => $parts[1],
        ];
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
