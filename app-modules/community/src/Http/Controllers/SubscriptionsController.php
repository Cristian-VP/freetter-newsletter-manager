<?php

namespace Domains\Community\Http\Controllers;

use Domains\Community\Models\Follower;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubscriptionsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceIds = Follower::query()
            ->where('follower_id', (string) $user->id)
            ->pluck('followed_workspace_id');

        if ($workspaceIds->isEmpty()) {
            return response()->json([
                'data' => [
                    'subscriptions' => [],
                ],
            ]);
        }

        $posts = Post::query()
            ->published()
            ->ofType('newsletter')
            ->whereIn('workspace_id', $workspaceIds)
            ->with([
                'workspace:id,name,slug',
                'author:id,name,avatar_path',
                'media:id,path',
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $subscriptions = $posts->map(function (Post $post): array {
            $media = $post->media->first();

            return [
                'id' => $post->id,
                'newsletter' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'body_paragraphs' => $this->buildBodyParagraphs($post),
                ],
                'workspace' => [
                    'id' => $post->workspace?->id,
                    'name' => $post->workspace?->name,
                    'slug' => $post->workspace?->slug,
                ],
                'owner' => [
                    'id' => $post->author?->id,
                    'name' => $post->author?->name,
                    'avatar_url' => $this->resolveAvatarUrl($post->author?->avatar_path),
                ],
                'published_at' => $post->published_at?->toIso8601String(),
                'preview_text' => $this->buildPreviewText($post),
                'cover_image_url' => $media instanceof Media ? $this->resolveAssetUrl($media->path) : null,
                'image_urls' => $post->media
                    ->map(fn (Media $media): ?string => $this->resolveAssetUrl($media->path))
                    ->filter()
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        return response()->json([
            'data' => [
                'subscriptions' => $subscriptions,
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function buildBodyParagraphs(Post $post): array
    {
        $paragraphs = $this->extractContentTexts($post);

        if ($paragraphs !== []) {
            return array_slice($paragraphs, 0, 4);
        }

        $excerpt = trim((string) $post->excerpt);
        if ($excerpt !== '') {
            return [$excerpt];
        }

        $fallback = trim($post->getExcerpt(500));

        return $fallback !== '' ? [$fallback] : [];
    }

    private function buildPreviewText(Post $post): string
    {
        $excerpt = trim((string) $post->excerpt);
        if ($excerpt !== '') {
            return $excerpt;
        }

        $paragraphs = $this->extractContentTexts($post);
        if ($paragraphs === []) {
            return '';
        }

        $normalized = implode(' ', array_slice($paragraphs, 0, 1));

        return Str::limit(preg_replace('/\s+/', ' ', $normalized) ?? $normalized, 180, '...');
    }

    /**
     * @return array<int, string>
     */
    private function extractContentTexts(Post $post): array
    {
        $blocks = $post->content['blocks'] ?? [];
        if (! is_array($blocks) || $blocks === []) {
            return [];
        }

        $texts = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $data = $block['data'] ?? [];
            if (! is_array($data)) {
                continue;
            }

            $parts = [];

            foreach (['text', 'html', 'quote'] as $key) {
                if (isset($data[$key]) && is_string($data[$key])) {
                    $parts[] = $data[$key];
                }
            }

            if (($block['type'] ?? '') === 'list') {
                $items = $data['items'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (is_string($item)) {
                            $parts[] = $item;
                        }
                    }
                }
            }

            $text = trim(implode(' ', array_filter(array_map(static fn (string $value): string => trim(strip_tags($value)), $parts))));
            if ($text === '') {
                continue;
            }

            $normalized = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $texts[] = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        }

        return $texts;
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

    private function resolveAssetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk('public');

        return $filesystem->url($path);
    }
}
