<?php

declare(strict_types=1);

namespace Domains\Publishing\Http\Controllers;

use Domains\Community\Models\BlockedUser;
use Domains\Community\Models\MutedUser;
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
        $mutedAuthorIds = MutedUser::query()
            ->where('user_id', $userId)
            ->pluck('muted_user_id')
            ->values();

        $blockedAuthorIds = BlockedUser::query()
            ->where('user_id', $userId)
            ->pluck('blocked_user_id')
            ->values();

        $postsQuery = Post::query()
            ->published()
            ->with(['author:id,name,avatar_path', 'media'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($mutedAuthorIds->isNotEmpty()) {
            $postsQuery->whereNotIn('author_id', $mutedAuthorIds);
        }

        if ($blockedAuthorIds->isNotEmpty()) {
            $postsQuery->whereNotIn('author_id', $blockedAuthorIds);
        }

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

        $repostCounts = DB::table('community_reposts')
            ->selectRaw('post_id, COUNT(*) as total')
            ->whereIn('post_id', $postIds)
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $repostedPostIds = DB::table('community_reposts')
            ->where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip();

        $bookmarkedPostIds = DB::table('community_bookmarks')
            ->where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip();

        $workspaceIds = $postsForPage->pluck('workspace_id')->filter()->values();

        $followedWorkspaceIds = DB::table('community_followers')
            ->where('follower_id', $userId)
            ->whereIn('followed_workspace_id', $workspaceIds)
            ->pluck('followed_workspace_id')
            ->flip();

        $feedPosts = $postsForPage->map(function (Post $post) use ($repostCounts, $repostedPostIds, $bookmarkedPostIds, $followedWorkspaceIds): array {
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
                'workspace_id' => $post->workspace_id,
                'published_at' => $publishedAt?->toIso8601String(),
                'published_relative' => $publishedAt ? $this->relativeTimeInSpanish($publishedAt) : 'ahora',
                'post_url' => route('home').'#post-'.$post->id,
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
                    'reposts_count' => (int) ($repostCounts[$post->id] ?? 0),
                    'reposted_by_me' => isset($repostedPostIds[$post->id]),
                    'bookmarked_by_me' => isset($bookmarkedPostIds[$post->id]),
                    'subscribed_to_workspace' => isset($followedWorkspaceIds[$post->workspace_id]),
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

    private function relativeTimeInSpanish(Carbon $publishedAt): string
    {
        $now = now();

        if ($publishedAt->diffInSeconds($now) < 45) {
            return 'ahora';
        }

        $minutes = max(1, (int) floor($publishedAt->diffInMinutes($now)));
        if ($minutes < 60) {
            return $minutes === 1 ? '1 minuto' : $minutes.' minutos';
        }

        $hours = max(1, (int) floor($publishedAt->diffInHours($now)));
        if ($hours < 24) {
            return $hours === 1 ? '1 hora' : $hours.' horas';
        }

        $days = max(1, (int) floor($publishedAt->diffInDays($now)));
        if ($days < 7) {
            return $days === 1 ? '1 dia' : $days.' dias';
        }

        $weeks = max(1, (int) floor($days / 7));
        if ($weeks < 5) {
            return $weeks === 1 ? '1 semana' : $weeks.' semanas';
        }

        $months = max(1, (int) floor($publishedAt->diffInMonths($now)));
        if ($months < 12) {
            return $months === 1 ? '1 mes' : $months.' meses';
        }

        $years = max(1, (int) floor($publishedAt->diffInYears($now)));

        return $years === 1 ? '1 ano' : $years.' anos';
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
