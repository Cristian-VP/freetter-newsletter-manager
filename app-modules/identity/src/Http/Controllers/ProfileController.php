<?php

declare(strict_types=1);

namespace Domains\Identity\Http\Controllers;

use Domains\Community\Models\Bookmark;
use Domains\Community\Models\Follower;
use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Publishing\Models\Post;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $userId = (string) $user->id;

        $workspaceId = Membership::query()
            ->where('user_id', $userId)
            ->orderBy('joined_at')
            ->value('workspace_id');

        // Counts
        $publishedCount = $workspaceId
            ? Post::query()
                ->where('workspace_id', $workspaceId)
                ->published()
                ->count()
            : 0;

        $followersCount = $workspaceId
            ? Follower::query()
                ->where('followed_workspace_id', $workspaceId)
                ->count()
            : 0;

        $followingCount = Follower::query()
            ->where('follower_id', $userId)
            ->count();

        $bookmarksCount = Bookmark::query()
            ->where('user_id', $userId)
            ->count();

        return Inertia::render('identity::Profile', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'handle' => $user->handle,
                'bio' => $user->bio,
                'avatar_url' => $this->resolveAvatarUrl($user->avatar_path ?? null),
                'workspace_id' => $workspaceId,
                'stats' => [
                    'published_count' => $publishedCount,
                    'followers_count' => $followersCount,
                    'following_count' => $followingCount,
                    'bookmarks_count' => $bookmarksCount,
                ],
            ],
        ]);
    }

    /**
     * API endpoint: published newsletters for the current user's workspace.
     */
    public function newsletters(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceId = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->value('workspace_id');

        if (! $workspaceId) {
            return response()->json(['data' => ['newsletters' => []]]);
        }

        $posts = Post::query()
            ->where('workspace_id', $workspaceId)
            ->where('type', 'newsletter')
            ->published()
            ->with(['workspace:id,name,slug'])
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'slug', 'published_at', 'workspace_id']);

        $items = $posts->map(fn (Post $post): array => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'published_at' => $post->published_at?->toIso8601String(),
            'view_url' => $post->url(),
        ])->values()->all();

        return response()->json(['data' => ['newsletters' => $items]]);
    }

    /**
     * API endpoint: full content of a single newsletter owned by the current user.
     */
    public function showNewsletter(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceId = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->value('workspace_id');

        abort_unless($workspaceId, 403);

        $post = Post::query()
            ->where('workspace_id', $workspaceId)
            ->where('type', 'newsletter')
            ->with(['author:id,name,avatar_path', 'workspace:id,name,slug'])
            ->findOrFail($id);

        // Extract body paragraphs from Tiptap/EditorJS JSON content
        $paragraphs = $this->extractParagraphs($post->content ?? []);

        // Resolve cover image from first media block or media relation
        $coverImageUrl = $this->resolveCoverImage($post);

        // Resolve author avatar
        $authorAvatarUrl = $post->author
            ? $this->resolveAvatarUrl($post->author->avatar_path ?? null)
            : null;

        return response()->json([
            'data' => [
                'newsletter' => [
                    'id'              => $post->id,
                    'title'           => $post->title,
                    'slug'            => $post->slug,
                    'published_at'    => $post->published_at?->toIso8601String(),
                    'cover_image_url' => $coverImageUrl,
                    'body_paragraphs' => $paragraphs,
                    'owner'           => [
                        'id'         => $post->author?->id,
                        'name'       => $post->author?->name,
                        'avatar_url' => $authorAvatarUrl,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Extract readable text paragraphs from the stored JSON content (Tiptap/EditorJS).
     *
     * @param  array<mixed>  $content
     * @return string[]
     */
    private function extractParagraphs(array $content): array
    {
        $paragraphs = [];

        // Tiptap format: { type: 'doc', content: [...nodes] }
        $nodes = $content['content'] ?? $content['blocks'] ?? [];

        if (! is_array($nodes)) {
            return [];
        }

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            // Tiptap paragraph node
            if (($node['type'] ?? '') === 'paragraph') {
                $text = '';
                foreach ($node['content'] ?? [] as $inline) {
                    if (is_array($inline) && isset($inline['text'])) {
                        $text .= $inline['text'];
                    }
                }
                $text = trim(strip_tags($text));
                if ($text !== '') {
                    $paragraphs[] = $text;
                }
                continue;
            }

            // Tiptap heading node
            if (($node['type'] ?? '') === 'heading') {
                $text = '';
                foreach ($node['content'] ?? [] as $inline) {
                    if (is_array($inline) && isset($inline['text'])) {
                        $text .= $inline['text'];
                    }
                }
                $text = trim(strip_tags($text));
                if ($text !== '') {
                    $paragraphs[] = $text;
                }
                continue;
            }

            // EditorJS paragraph block
            if (($node['type'] ?? '') === 'paragraph' && isset($node['data']['text'])) {
                $text = trim(strip_tags((string) $node['data']['text']));
                if ($text !== '') {
                    $paragraphs[] = $text;
                }
                continue;
            }

            // EditorJS header block
            if (($node['type'] ?? '') === 'header' && isset($node['data']['text'])) {
                $text = trim(strip_tags((string) $node['data']['text']));
                if ($text !== '') {
                    $paragraphs[] = $text;
                }
            }
        }

        return $paragraphs;
    }

    /**
     * Try to resolve a cover image URL from the post content or media relation.
     */
    private function resolveCoverImage(Post $post): ?string
    {
        $content = $post->content ?? [];
        $nodes = $content['content'] ?? $content['blocks'] ?? [];

        if (is_array($nodes)) {
            foreach ($nodes as $node) {
                if (! is_array($node)) {
                    continue;
                }

                // Tiptap image node
                if (($node['type'] ?? '') === 'image') {
                    $src = $node['attrs']['src'] ?? null;
                    if (is_string($src) && $src !== '') {
                        return $src;
                    }
                }

                // EditorJS image block
                if (($node['type'] ?? '') === 'image' && isset($node['data']['file']['url'])) {
                    $url = $node['data']['file']['url'];
                    if (is_string($url) && $url !== '') {
                        return $url;
                    }
                }
            }
        }

        return null;
    }

    /**
     * API endpoint: bookmarked posts for the current user.
     */
    public function bookmarks(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $bookmarks = Bookmark::query()
            ->where('user_id', (string) $user->id)
            ->with([
                'post:id,title,excerpt,content,author_id,workspace_id',
                'post.author:id,name,avatar_path',
            ])
            ->orderByDesc('created_at')
            ->get();

        $items = $bookmarks->map(function (Bookmark $bookmark): array {
            $post = $bookmark->post;

            if (! $post) {
                return null;
            }

            $excerpt = $post->excerpt ?? $post->getExcerpt(180);

            return [
                'bookmark_id' => $bookmark->id,
                'post_id' => $post->id,
                'title' => $post->title,
                'preview_text' => $excerpt,
                'author' => [
                    'id' => $post->author?->id,
                    'name' => $post->author?->name,
                    'avatar_url' => $this->resolveAvatarUrl($post->author?->avatar_path),
                ],
            ];
        })->filter()->values()->all();

        return response()->json(['data' => ['bookmarks' => $items]]);
    }

    public function showPublic(Request $request, string $handle): Response
    {
        $handle = ltrim($handle, '@');

        $user = User::query()
            ->where('handle', '@' . $handle)
            ->firstOrFail();

        $workspaceId = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->value('workspace_id');

        return Inertia::render('identity::PublicProfile', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'handle' => $user->handle,
                'bio' => $user->bio,
                'avatar_url' => $this->resolveAvatarUrl($user->avatar_path ?? null),
                'workspace_id' => $workspaceId,
            ],
        ]);
    }

    public function publicNewsletters(Request $request, string $handle): JsonResponse
    {
        $handle = ltrim($handle, '@');

        $user = User::query()
            ->where('handle', '@' . $handle)
            ->firstOrFail();

        $workspaceId = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->value('workspace_id');

        if (! $workspaceId) {
            return response()->json(['data' => ['newsletters' => []]]);
        }

        $posts = Post::query()
            ->where('workspace_id', $workspaceId)
            ->where('type', 'newsletter')
            ->published()
            ->with(['workspace:id,name,slug'])
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'slug', 'published_at', 'workspace_id']);

        $items = $posts->map(fn (Post $post): array => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'published_at' => $post->published_at?->toIso8601String(),
            'view_url' => $post->url(),
        ])->values()->all();

        return response()->json(['data' => ['newsletters' => $items]]);
    }

    public function showPublicNewsletter(Request $request, string $handle, string $id): JsonResponse
    {
        $handle = ltrim($handle, '@');

        $user = User::query()
            ->where('handle', '@' . $handle)
            ->firstOrFail();

        $workspaceId = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->value('workspace_id');

        abort_unless($workspaceId, 404);

        $post = Post::query()
            ->where('workspace_id', $workspaceId)
            ->where('type', 'newsletter')
            ->with(['author:id,name,avatar_path', 'workspace:id,name,slug'])
            ->findOrFail($id);

        $paragraphs = $this->extractParagraphs($post->content ?? []);
        $coverImageUrl = $this->resolveCoverImage($post);
        $authorAvatarUrl = $post->author
            ? $this->resolveAvatarUrl($post->author->avatar_path ?? null)
            : null;

        return response()->json([
            'data' => [
                'newsletter' => [
                    'id'              => $post->id,
                    'title'           => $post->title,
                    'slug'            => $post->slug,
                    'published_at'    => $post->published_at?->toIso8601String(),
                    'cover_image_url' => $coverImageUrl,
                    'body_paragraphs' => $paragraphs,
                    'owner'           => [
                        'id'         => $post->author?->id,
                        'name'       => $post->author?->name,
                        'avatar_url' => $authorAvatarUrl,
                    ],
                ],
            ],
        ]);
    }

    public function edit(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        return Inertia::render('identity::ProfileEdit', [
            'profile' => [
                'name' => $user->name,
                'handle' => $user->handle,
                'bio' => $user->bio,
                'avatar_url' => $this->resolveAvatarUrl($user->avatar_path ?? null),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'unique:identity_users,handle,'.$user->id],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:2048'], // max 2MB
        ]);

        $user->name = $validated['name'];
        $user->handle = $validated['handle'] ?? null;
        $user->bio = $validated['bio'] ?? null;

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_path = $path;
        }

        $user->save();

        Inertia::clearHistory();

        return redirect()->route('profile');
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
