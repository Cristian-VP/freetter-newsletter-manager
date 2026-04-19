<?php

declare(strict_types=1);

namespace Domains\Publishing\Http\Controllers;

use Carbon\CarbonInterface;
use Domains\Publishing\Models\Post;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeFeedController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $posts = Post::query()
            ->published()
            ->where('author_id', '!=', (string) $user->id)
            ->with(['author:id,name,avatar_path', 'media'])
            ->orderByDesc('published_at')
            ->limit(30)
            ->get();

        $postIds = $posts->pluck('id')->values();

        $likeCounts = DB::table('community_likes')
            ->selectRaw('post_id, COUNT(*) as total')
            ->whereIn('post_id', $postIds)
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $likedPostIds = DB::table('community_likes')
            ->where('user_id', (string) $user->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip();

        $feedPosts = $posts->map(function (Post $post) use ($likeCounts, $likedPostIds): array {
            $contentText = $post->getExcerpt(500);
            $publishedAt = $post->published_at;

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
                    'title' => $post->title,
                    'excerpt' => $contentText,
                ],
                'media' => $post->media->map(function ($media): array {
                    return [
                        'id' => $media->id,
                        'url' => $this->resolveMediaUrl((string) $media->disk, (string) $media->path),
                        'mime_type' => $media->mime_type,
                    ];
                })->values()->all(),
                'metrics' => [
                    'likes_count' => (int) ($likeCounts[$post->id] ?? 0),
                    'liked_by_me' => isset($likedPostIds[$post->id]),
                ],
            ];
        })->values();

        return Inertia::render('publishing::Home', [
            'posts' => $feedPosts,
        ]);
    }

    private function resolveMediaUrl(string $disk, string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);

        return $filesystem->url($path);
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
